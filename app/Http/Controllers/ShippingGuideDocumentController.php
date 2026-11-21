<?php
namespace App\Http\Controllers;

use App\ShippingGuide;
use App\Services\ShippingGuideDocument;
use App\Support\TenantContext;

class ShippingGuideDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:print_referralGuide|download_referralGuide');
        $this->middleware(function ($request, $next) {
            $tenant = TenantContext::tenantIdOrNull(); $company = TenantContext::companyIdOrNull(); $branch = TenantContext::branchIdOrNull();
            abort_unless($tenant && $company && $branch
                && \App\Company::where('tenant_id', $tenant)->whereKey($company)->exists()
                && \App\Branch::where('company_id', $company)->whereKey($branch)->exists(), 403);
            return $next($request);
        });
    }

    public function download($guide, $type)
    {
        abort_unless(in_array($type, ShippingGuideDocument::TYPES, true), 404);
        $user = auth()->user();
        abort_unless($user->can('download_referralGuide') || ($type === 'pdf' && $user->can('print_referralGuide')), 403);
        $record = ShippingGuide::forCurrentLocal()->where('tipo_de_comprobante', 7)->findOrFail($guide);
        $file = ShippingGuideDocument::resolve($record, $type);
        if ($file['mode'] === 'link') {
            return redirect()->away($file['url'], 302, ['Cache-Control' => 'private, no-store', 'Referrer-Policy' => 'no-referrer']);
        }
        return response($file['bytes'], 200, [
            'Content-Type' => 'application/zip', 'Content-Length' => (string) strlen($file['bytes']),
            'Content-Disposition' => 'attachment; filename="'.$file['name'].'"',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
