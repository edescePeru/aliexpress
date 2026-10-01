<?php

namespace Tests\Feature;

use App\Material;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StoreWebProductDetailTest extends TestCase
{
    public function test_real_presentation_uses_only_supplied_data_and_no_business_queries()
    {
        $this->app->instance('env', 'local');
        config(['store-web.preview' => true]);
        $deny = function () { throw new \RuntimeException('Unexpected presentation DB query'); };
        DB::connection()->setPdo($deny)->setReadPdo($deny);
        $material = new Material();
        $material->setRawAttributes(['id' => 900001, 'full_name' => 'QA <Producto> & color', 'code' => 'CODE-QA', 'description' => 'INTERNAL PRIVATE DESCRIPTION']);
        $material->setRelation('brand', (object) ['name' => 'QA Brand']);
        $material->setRelation('presentations', collect());
        $data = ['material' => $material, 'storeWebFixture' => false, 'storeWebAuthenticatedDataBridge' => true,
            'stockAvailable' => 3, 'priceText' => 'Desde S/. 15.00', 'showPricesCatalogEmpresa' => true,
            'sizes' => collect([(object) ['name' => 'M']]), 'colors' => collect([(object) ['name' => 'Azul']]),
            'images' => collect(), 'whatsappEmpresa' => '+51 (900) 000-001', 'descriptionFooterEmpresa' => 'QA Footer',
            'socialNetworksEmpresa' => ['facebook' => 'javascript:alert(1)', 'instagram' => 'https://example.com/qa']];
        $snapshots = [];
        foreach (['zero', 'one', 'many', 'unavailable'] as $case) {
            $values = $data;
            if ($case === 'one') $values['images'] = collect([['image' => asset('images/material/no-image.png'), 'label' => 'No image']]);
            if ($case === 'many') $values['images'] = collect([
                ['image' => asset('store-web/img/product/details/product-1.jpg'), 'label' => 'First'],
                ['image' => asset('store-web/img/product/details/product-2.jpg'), 'label' => 'Second'],
                ['image' => asset('store-web/img/qa-missing.jpg'), 'label' => 'Missing QA image']]);
            if ($case === 'unavailable') {
                $values['stockAvailable'] = 0; $values['showPricesCatalogEmpresa'] = false; $values['whatsappEmpresa'] = null;
                $values['sizes'] = collect(); $values['colors'] = collect();
            }
            $html = view('shop.detailCatalog', $values)->render();
            $this->assertStringContainsString('QA &lt;Producto&gt; &amp; color', $html);
            $this->assertStringContainsString('QA Brand', $html);
            $this->assertStringContainsString('CODE-QA', $html);
            $this->assertStringContainsString('QA Footer', $html);
            foreach (['INTERNAL PRIVATE DESCRIPTION', 'Producto de ejemplo', 'Productos relacionados', '<del>', '>Oferta<', 'javascript:', '/images/material/no-image.png', 'project-catalog'] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $html);
            }
            $this->assertStringContainsString('no_image.png', $html);
            $this->assertStringContainsString('data-social="facebook"', $html);
            if ($case === 'unavailable') {
                $this->assertStringNotContainsString('Desde S/. 15.00', $html);
                $this->assertStringContainsString('No disponible', $html);
                $this->assertStringContainsString('disabled aria-disabled="true"', $html);
            } else {
                $this->assertStringContainsString('Desde S/. 15.00', $html);
                $this->assertStringContainsString('VARIANT RESOLUTION PENDING', $html);
            }
            $snapshots[$case] = ['html' => $html];
        }
        if (getenv('STORE_WEB_DETAIL_CASES_OUTPUT')) file_put_contents(getenv('STORE_WEB_DETAIL_CASES_OUTPUT'), json_encode($snapshots));
    }
}
