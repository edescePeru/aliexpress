<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\{DB, Schema, Validator};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** R5: disposable SQLite only. Never connect deletion services to the shared DB. */
class MaterialCatalogDeletionContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'r5_memory', 'database.connections.r5_memory' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'session.driver' => 'array', 'cache.default' => 'array']);
        DB::setDefaultConnection('r5_memory');
        $this->assertSame(':memory:', DB::connection()->getConfig('database'));
        session(['multitenancy.tenant_id' => 1]);
        $tables = ['material_types', 'subtypes', 'categories', 'subcategories', 'examplers', 'brands',
            'typescraps', 'unit_measures', 'generos', 'tallas', 'colors', 'materials', 'variants',
            'items', 'stock_items', 'workforces', 'seasonal_promotions'];
        foreach ($tables as $table) {
            Schema::create($table, function ($t) {
                $t->increments('id');
                $t->integer('tenant_id')->nullable();
                foreach (['category', 'subcategory', 'material_type', 'subtype', 'brand', 'exampler',
                    'typescrap', 'unit_measure', 'genero', 'talla', 'color'] as $key) {
                    $t->integer($key.'_id')->nullable();
                }
                $t->timestamps();
                $t->softDeletes();
            });
        }
    }

    public function catalogs(): array
    {
        return [
            ['MaterialType', 'MaterialTypes', 'material_types', 'materials', 'material_type_id', true],
            ['Subtype', 'Subtypes', 'subtypes', 'materials', 'subtype_id', true],
            ['Category', 'Categories', 'categories', 'materials', 'category_id', true],
            ['Subcategory', 'Subcategories', 'subcategories', 'materials', 'subcategory_id', true],
            ['Exampler', 'Examplers', 'examplers', 'materials', 'exampler_id', false],
            ['Brand', 'Brands', 'brands', 'materials', 'brand_id', true],
            ['Typescrap', 'TypeScraps', 'typescraps', 'items', 'typescrap_id', true],
            ['UnitMeasure', 'UnitMeasures', 'unit_measures', 'stock_items', 'unit_measure_id', true],
            ['Genero', 'Generos', 'generos', 'materials', 'genero_id', true],
            ['Talla', 'Tallas', 'tallas', 'variants', 'talla_id', true],
            ['Color', 'Colors', 'colors', 'variants', 'color_id', false],
        ];
    }

    /** @dataProvider catalogs */
    public function test_request_and_atomic_reference_rejection($model, $plural, $table, $reference, $key, $soft)
    {
        DB::table($table)->insert([['id' => 1, 'tenant_id' => 1], ['id' => 2, 'tenant_id' => 1], ['id' => 3, 'tenant_id' => 2]]);
        $class = 'App\\Http\\Requests\\DeleteMultiple'.$plural.'Request';
        $request = new $class;
        foreach ([[], [999], [1, 999], [1, 1], [1, 3], ['invalid'], [0]] as $ids) {
            $this->assertTrue(Validator::make(['ids' => $ids], $request->rules())->fails());
        }
        $this->assertFalse(Validator::make(['ids' => [1, 2]], $request->rules())->fails());
        $service = app('App\\Services\\Materials\\Delete'.$plural);
        foreach ([[], [1, 999], [1, 1], [1, 3]] as $ids) {
            try { $service->delete($ids); $this->fail('Invalid batch accepted'); }
            catch (ValidationException $e) { $this->assertSame(422, $e->status); }
            $this->assertSame(3, DB::table($table)->whereNull('deleted_at')->count());
        }
        DB::table($reference)->insert(['id' => 10, 'tenant_id' => 2, $key => 2, 'deleted_at' => '2020-01-01 00:00:00']);
        try { $service->delete([1, 2]); $this->fail('Referenced selection accepted'); }
        catch (ValidationException $e) { $this->assertSame(422, $e->status); }
        $this->assertSame(3, DB::table($table)->whereNull('deleted_at')->count());
        DB::table($reference)->delete(); // Disposable fixture only.
        $this->assertSame(2, $service->delete([1, 2]));
        $this->assertSame(1, DB::table($table)->whereNull('deleted_at')->count());
        $this->assertSame($soft ? 3 : 1, DB::table($table)->count());
        $this->assertSame(0, DB::transactionLevel());
    }

    /** @dataProvider catalogs */
    public function test_failure_on_second_delete_rolls_back_first($model, $plural, $table)
    {
        DB::table($table)->insert([['id' => 1, 'tenant_id' => 1], ['id' => 2, 'tenant_id' => 1]]);
        $class = 'App\\'.$model;
        $class::deleting(function ($record) {
            if ((int) $record->id === 2) { throw new \RuntimeException('R5 isolated rollback'); }
        });
        try {
            app('App\\Services\\Materials\\Delete'.$plural)->delete([1, 2]);
            $this->fail('Expected fixture failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('R5 isolated rollback', $e->getMessage());
        } finally {
            // Remove only test listeners with the disposable application lifecycle.
            $class::flushEventListeners();
            $class::clearBootedModels();
        }
        $this->assertSame(2, DB::table($table)->whereNull('deleted_at')->count());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function hierarchies(): array
    {
        return [
            ['Categories', 'categories', 'subcategories', 'category_id', 'Subcategory'],
            ['Subcategories', 'subcategories', 'material_types', 'subcategory_id', 'MaterialType'],
            ['MaterialTypes', 'material_types', 'subtypes', 'material_type_id', 'Subtype'],
            ['Brands', 'brands', 'examplers', 'brand_id', 'Exampler'],
        ];
    }

    /** @dataProvider hierarchies */
    public function test_hierarchy_foreign_child_reference_and_failed_cascade($plural, $table, $child, $key, $childModel)
    {
        DB::table($table)->insert(['id' => 1, 'tenant_id' => 1]);
        DB::table($child)->insert(['id' => 2, 'tenant_id' => 2, $key => 1]);
        $service = app('App\\Services\\Materials\\Delete'.$plural);
        try { $service->delete([1]); $this->fail('Foreign child accepted'); }
        catch (ValidationException $e) { $this->assertSame(422, $e->status); }
        DB::table($child)->where('id', 2)->update(['tenant_id' => 1]);
        $childKey = ['subcategories' => 'subcategory_id', 'material_types' => 'material_type_id',
            'subtypes' => 'subtype_id', 'examplers' => 'exampler_id'][$child];
        DB::table('materials')->insert(['id' => 3, 'tenant_id' => 1, $childKey => 2, 'deleted_at' => '2020-01-01 00:00:00']);
        try { $service->delete([1]); $this->fail('Historical descendant reference accepted'); }
        catch (ValidationException $e) { $this->assertSame(422, $e->status); }
        DB::table('materials')->delete();
        $class = 'App\\'.$childModel;
        $class::deleting(function () { return false; });
        try { $service->delete([1]); $this->fail('Incomplete cascade accepted'); }
        catch (\RuntimeException $e) { $this->assertNotEmpty($e->getMessage()); }
        finally { $class::flushEventListeners(); $class::clearBootedModels(); }
        $this->assertSame(1, DB::table($table)->whereNull('deleted_at')->count());
        $this->assertSame(1, DB::table($child)->whereNull('deleted_at')->count());
        $this->assertSame(0, DB::transactionLevel());
    }
}
