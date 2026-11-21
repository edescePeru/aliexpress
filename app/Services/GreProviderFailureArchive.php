<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Crypt;

class GreProviderFailureArchive
{
    public static function capture(NubefactGreHttpException $error, string $reference, string $operation, $guide): bool
    {
        try {
            if (!$guide || $error->providerDetail() === null || !Schema::hasTable('shipping_guide_provider_failures')) { return false; }
            DB::table('shipping_guide_provider_failures')->insert([
                'reference' => $reference, 'shipping_guide_id' => $guide->id,
                'tenant_id' => $guide->tenant_id, 'company_id' => $guide->company_id, 'branch_id' => $guide->branch_id,
                'operation' => $operation, 'http_status' => $error->diagnostic()['http_status'],
                'provider_code' => $error->diagnostic()['provider_code'],
                'detail_encrypted' => Crypt::encryptString($error->providerDetail()), 'created_at' => now(),
            ]);
            return true;
        } catch (\Throwable $archiveError) { return false; }
    }
}
