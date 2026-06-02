<?php

namespace App\Http\Controllers;

use App\CashBoxSubtype;
use App\CashMovement;
use App\CashRegister;
use App\Http\Controllers\Traits\NubefactTrait;
use App\Sale;
use App\SalePartialPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalePartialPaymentController extends Controller
{
    use NubefactTrait;

    public function getBySale($sale_id)
    {
        try {
            $sale = Sale::findOrFail($sale_id);

            if ($sale->pagos_parciales_venta !== 's') {
                return response()->json([
                    'message' => 'Esta venta no está marcada como venta con pagos parciales.'
                ], 422);
            }

            $pagos = SalePartialPayment::where('sale_id', $sale->id)
                ->where('state', 1)
                ->with([
                    'cashMovement.cashRegister.cashBox',
                    'cashMovement.cashBoxSubtype'
                ])
                ->orderBy('payment_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $totalVenta = (float) $sale->importe_total;
            $totalAbonado = (float) $pagos->sum('amount');

            $pagosResponse = $pagos->map(function ($pago) {
                $cashBoxLabel = '-';

                if (
                    $pago->cashMovement &&
                    $pago->cashMovement->cashRegister &&
                    $pago->cashMovement->cashRegister->cashBox
                ) {
                    $cashBox = $pago->cashMovement->cashRegister->cashBox;
                    $cashBoxLabel = $cashBox->name;

                    if ($pago->cashMovement->cashBoxSubtype) {
                        $cashBoxLabel .= ' - ' . $pago->cashMovement->cashBoxSubtype->name;
                    }
                }

                return [
                    'id' => $pago->id,
                    'payment_date' => optional($pago->payment_date)->format('d/m/Y'),
                    'amount' => number_format($pago->amount, 2, '.', ''),
                    'cash_box_label' => $cashBoxLabel,
                ];
            });

            return response()->json([
                'total_venta' => number_format($totalVenta, 2, '.', ''),
                'total_abonado' => number_format($totalAbonado, 2, '.', ''),
                'saldo_pendiente' => number_format($totalVenta - $totalAbonado, 2, '.', ''),
                'pagos' => $pagosResponse,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'sale_id' => 'required|exists:sales,id',
                'payment_date' => 'required|date',
                'amount' => 'required|numeric|min:0.01',
                'cash_box_id' => 'required|exists:cash_boxes,id',
                'cash_box_subtype_id' => 'nullable|exists:cash_box_subtypes,id',
            ]);

            $sale = Sale::findOrFail($request->sale_id);

            if ($sale->pagos_parciales_venta !== 's') {
                throw new \Exception('Esta venta no está marcada como venta con pagos parciales.');
            }

            $amount = (float) $request->amount;

            $cashBoxId = $request->input('cash_box_id');
            $subtypeId = $request->input('cash_box_subtype_id');

            $cashRegister = CashRegister::where('cash_box_id', $cashBoxId)
                ->where('user_id', Auth::id())
                ->where('status', 1)
                ->latest()
                ->first();

            if (!$cashRegister) {
                throw new \Exception('No hay sesión abierta para la caja seleccionada.');
            }

            $cashBox = $cashRegister->cashBox;

            if (!$cashBox) {
                throw new \Exception('La sesión seleccionada no tiene CashBox asociado.');
            }

            $regularize = 1;

            if ($cashBox->type === 'bank' && (int)$cashBox->uses_subtypes === 1) {
                if (!$subtypeId) {
                    throw new \Exception('Debe seleccionar el subtipo bancario (Yape/Plin/POS/Transfer).');
                }

                $subtype = CashBoxSubtype::whereNull('cash_box_id')
                    ->where('is_active', 1)
                    ->where('id', (int)$subtypeId)
                    ->first();

                if (!$subtype) {
                    throw new \Exception('El subtipo seleccionado no es válido o está inactivo.');
                }

                $regularize = $subtype->is_deferred ? 0 : 1;
            } else {
                $subtypeId = null;
            }

            $movement = CashMovement::create([
                'cash_register_id' => $cashRegister->id,
                'type' => 'sale',
                'amount' => $amount,
                'description' => 'Pago parcial de venta #' . $sale->id,
                'observation' => ($cashBox->type === 'bank') ? 'Pago parcial bancario' : 'Pago parcial efectivo',
                'regularize' => $regularize,
                'cash_box_subtype_id' => $subtypeId,
                'sale_id' => $sale->id,
            ]);

            if ($regularize == 1) {
                $cashRegister->current_balance += $amount;
                $cashRegister->total_sales += $amount;
                $cashRegister->save();
            }

            $payment = SalePartialPayment::create([
                'sale_id' => $sale->id,
                'cash_movement_id' => $movement->id,
                'user_id' => Auth::id(),
                'payment_date' => $request->payment_date,
                'amount' => $amount,
                'state' => 1,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pago parcial registrado correctamente.',
                'payment' => $payment,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $payment = SalePartialPayment::where('id', $id)
                ->where('state', 1)
                ->firstOrFail();

            $sale = Sale::findOrFail($payment->sale_id);

            $movement = CashMovement::find($payment->cash_movement_id);

            if (!$movement) {
                throw new \Exception('No se encontró el movimiento asociado al pago.');
            }

            $cashBoxSubType = $movement->cash_box_subtype_id
                ? CashBoxSubtype::find($movement->cash_box_subtype_id)
                : null;

            $cashBoxSubtypeIdToUse = $cashBoxSubType ? $cashBoxSubType->id : null;
            $isDeferred = $cashBoxSubType ? (int) $cashBoxSubType->is_deferred : 0;

            if ($movement->type !== 'sale') {
                throw new \Exception('El movimiento asociado no corresponde a una venta.');
            }

            if ($isDeferred == 1 && (int) $movement->regularize === 0) {
                // Si fue diferido y aún no regularizado, eliminamos el movimiento original
                $movement->delete();
            } else {
                $amountToReverse = (float) ($movement->amount_regularize ?? $movement->amount ?? 0);

                CashMovement::create([
                    'cash_register_id'    => $movement->cash_register_id,
                    'sale_id'             => $sale->id,
                    'type'                => 'expense',
                    'amount'              => $amountToReverse,
                    'description'         => 'Reversión de pago parcial de venta #' . $sale->id,
                    'regularize'          => $movement->regularize,
                    'cash_box_subtype_id' => $cashBoxSubtypeIdToUse,
                ]);

                $cashRegister = CashRegister::find($movement->cash_register_id);

                if ($cashRegister) {
                    $cashRegister->current_balance -= $amountToReverse;
                    $cashRegister->total_sales     -= $amountToReverse;
                    $cashRegister->total_expenses  += $amountToReverse;
                    $cashRegister->save();
                }
            }

            $payment->update([
                'state' => 0,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pago parcial eliminado correctamente.'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function generateInvoiceFromSale(Request $request)
    {
        try {
            $sale = Sale::with(['details.material', 'details.stockItem'])
                ->findOrFail($request->sale_id);

            if ($sale->pagos_parciales_venta !== 's') {
                return response()->json([
                    'message' => 'Esta opción solo aplica para ventas con pagos parciales.'
                ], 422);
            }

            if (in_array($sale->type_document, ['01', '03']) && $sale->sunat_status !== 'Error') {
                return response()->json([
                    'message' => 'Esta venta ya tiene un comprobante generado.'
                ], 422);
            }

            $tipoComprobante = $request->tipo_comprobante;

            if (!in_array($tipoComprobante, ['boleta', 'factura'])) {
                return response()->json([
                    'message' => 'Tipo de comprobante inválido.'
                ], 422);
            }

            if ($tipoComprobante === 'boleta') {
                $numeroDocumento = preg_replace('/\D/', '', $request->dni ?? '');

                if (!preg_match('/^\d{8}$/', $numeroDocumento)) {
                    return response()->json(['message' => 'Ingrese un DNI válido de 8 dígitos.'], 422);
                }

                if (!$request->name) {
                    return response()->json(['message' => 'Ingrese el nombre del cliente.'], 422);
                }

                $sale->update([
                    'type_document' => '03',
                    'tipo_documento_cliente' => '1',
                    'numero_documento_cliente' => $numeroDocumento,
                    'nombre_cliente' => $request->name,
                    'direccion_cliente' => null,
                    'email_cliente' => $request->email_boleta,
                    'sunat_status' => null,
                    'sunat_message' => null,
                ]);
            }

            if ($tipoComprobante === 'factura') {
                $numeroDocumento = preg_replace('/\D/', '', $request->ruc ?? '');

                if (!preg_match('/^\d{11}$/', $numeroDocumento)) {
                    return response()->json(['message' => 'Ingrese un RUC válido de 11 dígitos.'], 422);
                }

                if (!$request->razon_social || !$request->direccion_fiscal) {
                    return response()->json([
                        'message' => 'Ingrese razón social y dirección fiscal.'
                    ], 422);
                }

                $sale->update([
                    'type_document' => '01',
                    'tipo_documento_cliente' => '6',
                    'numero_documento_cliente' => $numeroDocumento,
                    'nombre_cliente' => $request->razon_social,
                    'direccion_cliente' => $request->direccion_fiscal,
                    'email_cliente' => $request->email_factura,
                    'sunat_status' => null,
                    'sunat_message' => null,
                ]);
            }

            $sale->refresh();
            $sale->loadMissing(['details.material', 'details.stockItem']);

            try {
                $nubefactResult = $this->generarComprobanteNubefactParaVenta($sale);
                $this->persistNubefactFilesAndUpdateSale($sale, $nubefactResult);
            } catch (\Throwable $e) {
                $sale->update([
                    'sunat_status' => 'Error',
                    'sunat_message' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Nubefact rechazó el comprobante: ' . $e->getMessage(),
                ], 422);
            }

            $sale->refresh();

            $urlPrint = route('puntoVenta.print', $sale->id);
            $printType = 'ticket';

            if (!empty($sale->pdf_path)) {
                $localPath = public_path('comprobantes/pdfs/' . $sale->pdf_path);

                if (file_exists($localPath)) {
                    $urlPrint = asset('comprobantes/pdfs/' . $sale->pdf_path);
                    $printType = 'sunat_pdf';
                }
            }

            return response()->json([
                'message' => 'Comprobante generado correctamente.',
                'sale_id' => $sale->id,
                'url_print' => $urlPrint,
                'print_type' => $printType,
                'sunat_status' => $sale->sunat_status,
                'sunat_message' => $sale->sunat_message,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateDispatchStatus(Request $request)
    {
        try {
            $request->validate([
                'sale_id' => 'required|exists:sales,id',
                'dispatch_status' => 'required|in:pendiente,despachado',
            ]);

            $sale = Sale::findOrFail($request->sale_id);

            $sale->update([
                'dispatch_status' => $request->dispatch_status,
            ]);

            return response()->json([
                'message' => 'Estado de despacho actualizado correctamente.',
                'dispatch_status' => $sale->dispatch_status,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
