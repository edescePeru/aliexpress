<?php

namespace App\Http\Controllers;

use App\CreditPay;
use App\SupplierCredit;
use App\Support\TenantContext;
use App\Services\Purchases\SupplierCreditAttachments;

class SupplierCreditAttachmentController extends Controller
{
    private $files;

    public function __construct(SupplierCreditAttachments $files)
    {
        $this->files = $files;
        $this->middleware('permission:control_credit|edit_credit|pay_credit|nopay_credit');
        $this->middleware(function ($request, $next) {
            $tenant = TenantContext::tenantIdOrNull(); $company = TenantContext::companyIdOrNull();
            abort_unless($tenant && $company && \App\Company::where('tenant_id', $tenant)->whereKey($company)->exists(), 403);
            return $next($request);
        });
    }

    public function paid($credit)
    {
        $record = app(\App\Services\Purchases\SupplierCreditDocumentAccess::class)->paid($credit);
        return $this->stream('paid', $record->image_paid);
    }

    public function payment($payment)
    {
        $record = app(\App\Services\Purchases\SupplierCreditDocumentAccess::class)->payment($payment);
        return $this->stream('pays', $record->image);
    }

    private function stream($kind, $filename)
    {
        $file = $this->files->resolve($kind, $filename);
        $response = response()->file($file['path'], [
            'Content-Type' => $file['mime'], 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setContentDisposition('inline', 'comprobante.'.$file['extension']);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');
        return $response;
    }
}
