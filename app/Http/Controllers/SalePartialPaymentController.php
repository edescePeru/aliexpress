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
                ->where('tenant_id',$sale->tenant_id)->where('company_id',$sale->company_id)->where('branch_id',$sale->branch_id)
                ->when($sale->internal_reversal_status !== 'reversed', function($q){$q->where('state',1);})
                ->with([
                    'cashMovement.cashRegister.cashBox',
                    'cashMovement.cashBoxSubtype'
                ])
                ->orderBy('payment_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $balance = app(\App\Services\Sales\PartialCreditSettlement::class)->snapshot($sale);
            if (!empty($balance['reconciliation_required'])) { throw new \DomainException('La devolución histórica requiere conciliación.'); }
            $totalVenta = $balance['net_cents'] / 100;
            $totalAbonado = $balance['retained_cents'] / 100;

            $pagosResponse = $pagos->map(function ($pago) use ($sale) {
                $cashBoxLabel = '-';

                if (
                    $pago->cashMovement &&
                    (int)$pago->cashMovement->tenant_id === (int)$sale->tenant_id &&
                    (int)$pago->cashMovement->company_id === (int)$sale->company_id &&
                    (int)$pago->cashMovement->branch_id === (int)$sale->branch_id &&
                    (int)$pago->cashMovement->sale_id === (int)$sale->id &&
                    $pago->cashMovement->cashRegister &&
                    (int)$pago->cashMovement->cashRegister->tenant_id === (int)$sale->tenant_id &&
                    (int)$pago->cashMovement->cashRegister->company_id === (int)$sale->company_id &&
                    (int)$pago->cashMovement->cashRegister->branch_id === (int)$sale->branch_id &&
                    $pago->cashMovement->cashRegister->cashBox &&
                    (int)$pago->cashMovement->cashRegister->cashBox->tenant_id === (int)$sale->tenant_id &&
                    (int)$pago->cashMovement->cashRegister->cashBox->company_id === (int)$sale->company_id
                ) {
                    $cashBox = $pago->cashMovement->cashRegister->cashBox;
                    $cashBoxLabel = $cashBox->name;

                    if ($pago->cashMovement->cashBoxSubtype && (int)$pago->cashMovement->cashBoxSubtype->tenant_id === (int)$sale->tenant_id && (int)$pago->cashMovement->cashBoxSubtype->company_id === (int)$sale->company_id) {
                        $cashBoxLabel .= ' - ' . $pago->cashMovement->cashBoxSubtype->name;
                    }
                }

                return [
                    'id' => $pago->id,
                    'payment_date' => optional($pago->payment_date)->format('d/m/Y'),
                    'amount' => number_format($pago->amount, 2, '.', ''),
                    'cash_box_label' => $cashBoxLabel . ((int)$pago->state===0?' · revertido':''),
                    'active' => (int)$pago->state===1,
                ];
            });

            return response()->json([
                'total_venta' => number_format($totalVenta, 2, '.', ''),
                'total_abonado' => number_format($totalAbonado, 2, '.', ''),
                'saldo_pendiente' => number_format($totalVenta - $totalAbonado, 2, '.', ''),
                'pagos' => $pagosResponse,
                'operation_reversed' => !empty($balance['operation_reversed']),
                'debt_cancelled' => number_format(($balance['debt_cancelled_cents']??0)/100,2,'.',''),
                'refunded' => number_format($balance['refunded_cents']/100,2,'.',''),
                'pending_cancelled' => number_format(($balance['pending_cancelled_cents']??0)/100,2,'.',''),
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

            app(\App\Services\Sales\SaleListAccess::class)->sale($request->sale_id);
            $sale = Sale::where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())
                ->where('branch_id', \App\Support\TenantContext::branchId())->lockForUpdate()->findOrFail($request->sale_id);
            $settlement = app(\App\Services\Sales\PartialCreditSettlement::class);
            if ($settlement->pending($sale)->exists()) { throw new \DomainException('Resuelva primero la nota de crédito pendiente.'); }
            $balance = $settlement->snapshot($sale);
            if (!empty($balance['reconciliation_required'])) { throw new \DomainException('La devolución histórica requiere conciliación.'); }
            if ($settlement::cents($request->amount) > $balance['debt_cents']) { throw new \DomainException('El abono supera la deuda pendiente después de las devoluciones.'); }

            if ($sale->pagos_parciales_venta !== 's') {
                throw new \Exception('Esta venta no está marcada como venta con pagos parciales.');
            }

            $amount = (float) $request->amount;

            $cashBoxId = $request->input('cash_box_id');
            $subtypeId = $request->input('cash_box_subtype_id');

            $cashRegister = CashRegister::where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())->where('branch_id', \App\Support\TenantContext::branchId())->where('cash_box_id', $cashBoxId)
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

                $subtype = app(\App\Services\Sales\SaleListAccess::class)->subtypes()->where(function ($q) use ($cashBoxId) { $q->whereNull('cash_box_id')->orWhere('cash_box_id', $cashBoxId); })
                    ->where('is_active', 1)
                    ->where('id', (int)$subtypeId)
                    ->lockForUpdate()->first();

                if (!$subtype) {
                    throw new \Exception('El subtipo seleccionado no es válido o está inactivo.');
                }

                $regularize = $subtype->is_deferred ? 0 : 1;
            } else {
                $subtypeId = null;
            }

            $movement = CashMovement::create([
                'cash_box_id' => $cashBoxId,
                'tenant_id' => \App\Support\TenantContext::tenantId(),
                'company_id' => \App\Support\TenantContext::companyId(),
                'branch_id' => \App\Support\TenantContext::branchId(),
                'user_id' => Auth::id(),
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
                'tenant_id' => \App\Support\TenantContext::tenantId(),
                'company_id' => \App\Support\TenantContext::companyId(),
                'branch_id' => \App\Support\TenantContext::branchId(),
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

            $sale = Sale::where('company_id', \App\Support\TenantContext::companyId())
                ->where('branch_id', \App\Support\TenantContext::branchId())
                ->lockForUpdate()->findOrFail($payment->sale_id);
            $payment = SalePartialPayment::where('state', 1)->where('sale_id', $sale->id)
                ->where('company_id', $sale->company_id)->where('branch_id', $sale->branch_id)
                ->lockForUpdate()->findOrFail($id);

            if ($sale->creditNotes()->where(function ($q) {
                $q->whereIn('status', ['pending', 'accepted'])->orWhere('internal_reversal_status', 'reversed');
            })->exists()) { throw new \DomainException('No se puede eliminar un cobro de una venta con notas de crédito aplicadas o pendientes. Requiere conciliación para conservar la deuda y las devoluciones.'); }
            $movement = CashMovement::where('company_id', $sale->company_id)->where('branch_id', $sale->branch_id)
                ->lockForUpdate()->find($payment->cash_movement_id);

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

            if ((int) $movement->regularize === 0) {
                $movement->cancelPendingDeferred('partial_payment_reversal');
            } elseif ($movement->regularize && ($isDeferred || $movement->amount_regularize !== null || $movement->balance_effect === false)) {
                $refunds = app(\App\Services\Sales\DeferredSaleRefund::class);
                $planned = $refunds->plan($sale, (int) $movement->id);
                if (!array_key_exists($movement->id, $planned)) {
                    throw new \RuntimeException('El cobro regularizado requiere conciliación.');
                }
                $refunds->post($sale, $planned);
            } else {
                app(\App\Services\CashRegularizationGuard::class)->movement($movement);
                $amountToReverse = (float) ($movement->amount_regularize ?? $movement->amount ?? 0);

                CashMovement::create([
                    'cash_box_id' => $movement->cash_box_id,
                    'tenant_id' => \App\Support\TenantContext::tenantId(),
                    'company_id' => \App\Support\TenantContext::companyId(),
                    'branch_id' => \App\Support\TenantContext::branchId(),
                    'user_id' => Auth::id(),
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
        $data = $request->validate([
            'sale_id' => 'required|integer|min:1', 'tipo_comprobante' => 'required|in:boleta,factura',
            'dni' => 'nullable|required_if:tipo_comprobante,boleta|regex:/^\d{8}$/D',
            'name' => 'nullable|required_if:tipo_comprobante,boleta|string|max:250',
            'email_boleta' => 'nullable|email|max:250',
            'ruc' => 'nullable|required_if:tipo_comprobante,factura|regex:/^\d{11}$/D',
            'razon_social' => 'nullable|required_if:tipo_comprobante,factura|string|max:250',
            'direccion_fiscal' => 'nullable|required_if:tipo_comprobante,factura|string|max:500',
            'email_factura' => 'nullable|email|max:250',
        ]);
        $access = app(\App\Services\Sales\SaleListAccess::class);
        $sale = $access->sale($data['sale_id']);
        $access->permission('manageDocuments_puntoVenta');
        $access->permission($data['tipo_comprobante'] === 'factura' ? 'generarFactura_puntoVenta' : 'generarBoleta_puntoVenta');
        try {
            $invoice = app(\App\Services\Sales\ExistingSaleInvoice::class);
            $outcome = app(\App\Services\SaleEmissionService::class)->emit($sale,
                function ($locked, $connection) use ($invoice) { return $invoice->payload($locked, $connection); },
                function ($locked) use ($invoice, $data) { $invoice->prepare($locked, $data); });
            $sale->refresh();
            $state = $outcome['emission_status'];
            if ($state !== 'confirmed') {
                return response()->json(['message' => implode(' ', $outcome['warnings']), 'sale_id' => $sale->id,
                    'emission_status' => $state, 'requires_review' => in_array($state, ['processing', 'unknown'], true)],
                    $state === 'failed' ? 422 : 202);
            }
            $warning = '';
            if (is_array($outcome['result'])) {
                try {
                    $files = $this->persistNubefactFilesAndUpdateSale($sale, $outcome['result']);
                    if (!empty($files['errores'])) { $warning = ' Algunos archivos están pendientes de recuperación.'; }
                } catch (\Throwable $e) {
                    $warning = ' El comprobante está registrado, pero falta recuperar sus archivos.';
                    \Illuminate\Support\Facades\Log::warning('Existing sale invoice files pending', ['sale_id' => $sale->id, 'exception' => get_class($e)]);
                }
            }
            $sale->refresh();
            return response()->json(['message' => (is_array($outcome['result']) ? 'Comprobante registrado en Nubefact. Estado SUNAT: ' . $sale->sunat_status . '.' : 'La venta ya tiene un comprobante registrado; no se realizó otro envío.') . $warning,
                'sale_id' => $sale->id, 'emission_status' => 'confirmed', 'sunat_status' => $sale->sunat_status], 200);
        } catch (\Illuminate\Validation\ValidationException $e) { throw $e; }
        catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Existing sale invoice requires review', ['sale_id' => $sale->id, 'exception' => get_class($e)]);
            return response()->json(['message' => 'No se completó la emisión. Revise el estado de la venta antes de intentar otro envío.'], 422);
        }
    }

    public function invoiceEmissionStatus($sale)
    {
        $record = app(\App\Services\Sales\SaleListAccess::class)->sale($sale);
        app(\App\Services\Sales\SaleListAccess::class)->permission('manageDocuments_puntoVenta');
        $attempt = $record->emissionAttempts()->where('tenant_id', $record->tenant_id)->where('company_id', $record->company_id)
            ->where('branch_id', $record->branch_id)->latest('id')->first();
        $recoverable = $attempt && $attempt->status === 'persistence_pending'
            && in_array($record->emission_status, ['processing', 'unknown'], true);
        return response()->json(['sale_id' => $record->id, 'emission_status' => $record->emission_status,
            'attempt_id' => $attempt ? $attempt->id : null, 'can_recover_locally' => (bool) $recoverable,
            'reference' => $record->emission_key,
            'message' => $recoverable ? 'Hay una respuesta guardada. Se pueden recuperar sus datos sin volver a emitir.'
                : 'La emisión requiere revisar la evidencia y contrastarla con Nubefact. No reenvíe ni cambie la referencia para crear otro comprobante.']);
    }

    public function recoverInvoiceEmission(Request $request, $sale)
    {
        $data = $request->validate(['attempt_id' => 'required|integer|min:1']);
        $record = app(\App\Services\Sales\SaleListAccess::class)->sale($sale);
        app(\App\Services\Sales\SaleListAccess::class)->permission('manageDocuments_puntoVenta');
        return response()->json(app(\App\Services\SaleEmissionRecoveryService::class)->recover($record->id, $data['attempt_id'], true));
    }

    public function updateDispatchStatus(Request $request)
    {
        try {
            $request->validate([
                'sale_id' => 'required|exists:sales,id',
                'dispatch_status' => 'required|in:pendiente,despachado',
            ]);

            app(\App\Services\Sales\SaleListAccess::class)->sale($request->sale_id);
            DB::beginTransaction();
            $sale = Sale::where('tenant_id', \App\Support\TenantContext::tenantId())->where('company_id', \App\Support\TenantContext::companyId())
                ->where('branch_id', \App\Support\TenantContext::branchId())->lockForUpdate()->findOrFail($request->sale_id);
            $sale->update([
                'dispatch_status' => $request->dispatch_status,
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Estado de despacho actualizado correctamente.',
                'dispatch_status' => $sale->dispatch_status,
            ]);

        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) { DB::rollBack(); }
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
