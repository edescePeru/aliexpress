<?php
namespace App\Services\Sales;

use App\Sale;
use App\Services\NubefactCompanyConnection;
use App\Support\TenantContext;
use Illuminate\Validation\ValidationException;

class ExistingSaleInvoice
{
    public function canOffer(Sale $sale): bool
    {
        return !(bool) $sale->free_sale && !(bool) $sale->state_annulled
            && $sale->internal_reversal_status !== 'reversed'
            && !in_array($sale->annulment_status, ['pending', 'waiting_sunat_process', 'accepted'], true)
            && in_array($sale->emission_status, ['not_requested', 'failed'], true)
            && !$sale->serie_sunat && !$sale->numero;
    }

    public function flags(Sale $sale): array
    {
        $attempts = $sale->emissionAttempts()->where('tenant_id', $sale->tenant_id)->where('company_id', $sale->company_id)
            ->where('branch_id', $sale->branch_id)->get(['status', 'failure_stage']);
        $unresolved = $attempts->contains(function ($a) use ($sale) { return in_array($a->status, ['processing', 'unknown', 'persistence_pending'], true)
            || ($a->status === 'confirmed' && ($sale->emission_status !== 'confirmed' || !$sale->serie_sunat || !$sale->numero)); });
        $localFailed = $attempts->contains(function ($a) { return $a->status === 'failed' && $a->failure_stage === 'validation'; });
        $legacyError = $sale->sunat_status === 'Error' && !$localFailed;
        return ['can_generate_invoice' => $this->canOffer($sale) && !$unresolved && !$legacyError && !$sale->creditNotes()->exists(),
            'invoice_emission_review' => $unresolved || $sale->requiresEmissionReview() || $legacyError,
            'invoice_emission_status' => $sale->emission_status,
            'invoice_has_identity' => (bool) ($sale->serie_sunat && $sale->numero),
            'invoice_original_amount' => number_format((float) $sale->importe_total, 2, '.', ''),
            'invoice_currency' => $sale->currency];
    }

    /** Called under the emission claim lock. Only changes document/customer metadata. */
    public function prepare(Sale $sale, array $data): void
    {
        $access = app(SaleListAccess::class);
        $access->sale($sale->id);
        $access->permission('manageDocuments_puntoVenta');
        $access->permission($data['tipo_comprobante'] === 'factura' ? 'generarFactura_puntoVenta' : 'generarBoleta_puntoVenta');
        if ((int) $sale->tenant_id !== TenantContext::tenantId() || (int) $sale->company_id !== TenantContext::companyId()
            || (int) $sale->branch_id !== TenantContext::branchId() || !$this->canOffer($sale)) {
            $this->invalid('La venta ya tiene emisión, anulación o un resultado pendiente de revisar.');
        }
        if ($sale->creditNotes()->exists()) { $this->invalid('Esta venta tiene notas de crédito. Revise sus comprobantes antes de emitir.'); }
        if ($sale->emissionAttempts()->whereIn('status', ['processing', 'unknown', 'persistence_pending', 'confirmed'])->exists()) {
            $this->invalid('Hay un intento sin conciliar. Revise la emisión; no vuelva a enviarla.');
        }
        // An old generic Error is not proof that the provider never created a document.
        if ($sale->sunat_status === 'Error' && !$sale->emissionAttempts()->where('status', 'failed')->where('failure_stage', 'validation')->exists()) {
            $this->invalid('El error histórico no confirma que el documento no exista. Requiere conciliación antes de emitir.');
        }
        $invoice = $data['tipo_comprobante'] === 'factura';
        $sale->type_document = $invoice ? '01' : '03';
        $sale->tipo_documento_cliente = $invoice ? '6' : '1';
        $sale->numero_documento_cliente = $invoice ? $data['ruc'] : $data['dni'];
        $sale->nombre_cliente = trim($invoice ? $data['razon_social'] : $data['name']);
        $sale->direccion_cliente = $invoice ? trim($data['direccion_fiscal']) : null;
        $sale->email_cliente = $invoice ? ($data['email_factura'] ?? null) : ($data['email_boleta'] ?? null);
        $sale->sunat_status = null;
        $sale->sunat_message = null;
    }

    public function payload(Sale $sale, NubefactCompanyConnection $connection): array
    {
        $sale->loadMissing('details');
        if (!in_array($sale->currency, ['PEN', 'USD'], true) || $sale->details->isEmpty()) { $this->invalid('Revise moneda y detalles guardados en la venta.'); }
        if ($this->cents($sale->total_descuentos) !== 0) { $this->invalid('El descuento histórico requiere una distribución fiscal verificada antes de emitir.'); }
        $gross = $tax = 0; $bases = [1 => 0, 8 => 0, 9 => 0];
        foreach ($sale->details as $detail) {
            if ((int) $detail->tenant_id !== (int) $sale->tenant_id || (int) $detail->company_id !== (int) $sale->company_id
                || (int) $detail->sale_id !== (int) $sale->id) { $this->invalid('Los detalles no pertenecen a esta venta y empresa.'); }
            $qty = $detail->packs !== null ? $detail->packs : $detail->quantity;
            $this->positive($qty);
            if ($detail->packs !== null && ((float) $detail->units_per_pack <= 0
                || bccomp(bcmul((string) $qty, (string) $detail->units_per_pack, 4), (string) $detail->quantity, 4) !== 0)) {
                $this->invalid('La presentación guardada no coincide con las unidades vendidas.');
            }
            $code = (int) $detail->tax_provider_code;
            if (!in_array($code, [1, 8, 9], true) || $detail->tax_amount === null) {
                $this->invalid('El detalle no conserva su identidad tributaria. Se debe revisar el histórico, sin usar el impuesto actual del catálogo.');
            }
            $amount = $this->cents($detail->total); $lineTax = $this->cents($detail->tax_amount);
            if ($amount < 0 || $lineTax < 0 || $lineTax > $amount || $this->cents($detail->discount) !== 0
                || $this->cents(bcmul((string) $detail->price, (string) $qty, 10)) !== $amount) {
                $this->invalid('Los precios, cantidades o descuentos guardados no coinciden con el importe de la línea.');
            }
            if (($code !== 1 && $lineTax !== 0) || ($code === 1 && ((float) ($detail->tax_rate_snapshot ?? $detail->percentage_tax) <= 0
                || abs((int) round(($amount - $lineTax) * (float) ($detail->tax_rate_snapshot ?? $detail->percentage_tax) / 100) - $lineTax) > 1))) {
                $this->invalid('La afectación tributaria guardada no coincide con el impuesto de la línea.');
            }
            $gross += $amount; $tax += $lineTax; $bases[$code] += $amount - $lineTax;
        }
        if ($gross <= 0 || $gross !== $this->cents($sale->importe_total) || $tax !== $this->cents($sale->igv)
            || $bases[1] !== $this->cents($sale->op_gravada) || $bases[8] !== $this->cents($sale->op_exonerada)
            || $bases[9] !== $this->cents($sale->op_inafecta)) {
            $this->invalid('Los totales guardados de la venta no coinciden con sus detalles. No se recalculó ni se envió el comprobante.');
        }
        $payload = app(PosEmissionPayload::class)->build($sale, $connection);
        // This button already emitted using today's date. Preserve the original sale date separately.
        $payload['fecha_de_emision'] = now()->format('d-m-Y');
        return $payload;
    }

    private function cents($value): int
    {
        if ($value === null) { return 0; }
        if (!is_scalar($value) || !is_numeric($value) || !is_finite((float) $value)) { $this->invalid('Importe guardado inválido.'); }
        return (int) round((float) $value * 100, 0, PHP_ROUND_HALF_UP);
    }
    private function positive($value): void
    {
        if (!is_scalar($value) || !preg_match('/^\d{1,12}(?:\.\d{1,10})?$/D', (string) $value) || bccomp((string) $value, '0', 4) <= 0) {
            $this->invalid('La cantidad guardada no es válida.');
        }
    }
    private function invalid(string $message): void { throw ValidationException::withMessages(['sale_id' => $message]); }
}
