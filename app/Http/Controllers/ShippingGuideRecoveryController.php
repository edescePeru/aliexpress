<?php
namespace App\Http\Controllers;

use App\Services\ShippingGuideRecovery;
use App\Services\ShippingGuideDiagnostic;
use App\ShippingGuide;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class ShippingGuideRecoveryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list_referralGuide');
        $this->middleware('permission:create_referralGuide');
        $this->middleware('permission:edit_referralGuide');
        $this->middleware(function ($request, $next) {
            $tenant = TenantContext::tenantIdOrNull(); $company = TenantContext::companyIdOrNull(); $branch = TenantContext::branchIdOrNull();
            abort_unless($tenant && $company && $branch, 403, 'Selecciona empresa y local para operar guías.');
            abort_unless(\App\Company::where('tenant_id', $tenant)->whereKey($company)->exists()
                && \App\Branch::where('company_id', $company)->whereKey($branch)->exists(), 403, 'El contexto de empresa y local no es válido.');
            return $next($request);
        });
    }

    public function recover(Request $request, $guide, ShippingGuideRecovery $recovery)
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'],
            'confirm_account' => ['accepted'], 'confirm_send' => ['sometimes', 'accepted'],
        ]);
        $guide = ShippingGuide::forCurrentLocal()->where('tipo_de_comprobante', 7)->findOrFail($guide);
        try {
            $allowGeneration = in_array($request->input('confirm_send'), [true, 1, '1'], true);
            $result = $recovery->run((int) $guide->id, $data['version'], (int) $request->user()->id, $allowGeneration);
            return response()->json($result, $result['ok'] ? 200 : 502);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $error) {
            throw $error;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $error) {
            throw $error;
        } catch (\Throwable $error) {
            $reference = ShippingGuideDiagnostic::record($error, 'generate', $guide);
            return response()->json([
                'message' => 'No se pudo completar la recuperación. Conserve la guía y revise el diagnóstico antes de otra acción.'
                    . ($reference ? ' Referencia de diagnóstico: '.$reference : ''),
                'diagnostic_reference' => $reference,
            ], 502);
        }
    }
}
