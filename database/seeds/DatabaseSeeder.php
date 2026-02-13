<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        /*$this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);

        $this->call(CategorySeeder::class);
        $this->call(SubcategorySeeder::class);
        $this->call(MaterialTypeSeeder::class);
        $this->call(SubtypeSeeder::class);
        $this->call(QualitySeeder::class);
        $this->call(WarrantSeeder::class);
        $this->call(UnitMeasureSeeder::class);
        $this->call(TypescrapSeeder::class);

        $this->call(CustomerSeeder::class);
        $this->call(ContactNameSeeder::class);

        $this->call(BrandSeeder::class);
        $this->call(ExamplerSeeder::class);
        $this->call(MaterialSeeder::class);

        $this->call(AreaSeeder::class);
        $this->call(WarehouseSeeder::class);
        $this->call(ShelfSeeder::class);
        $this->call(LevelSeeder::class);
        $this->call(ContainerSeeder::class);
        $this->call(PositionSeeder::class);
        $this->call(LocationSeeder::class);

        $this->call(ConsumableSeeder::class);
        $this->call(InvoicePurchaseSeeder::class);
        $this->call(WorkforceSeeder::class);*/
        /*
        |--------------------------------------------------------------------------
        | PASO 1: Permisos + Rol Admin (FUENTE ÚNICA)
        |--------------------------------------------------------------------------
        */
        Artisan::call('permissions:sync');

        /*
        |--------------------------------------------------------------------------
        | PASO 2: Usuario Administrador
        |--------------------------------------------------------------------------
        */
        $this->call(RoleSeeder::class);
        $this->call(AdminUserSeeder::class);

        /*
        |--------------------------------------------------------------------------
        | PASO 3: DATA BASE DEL SISTEMA
        |--------------------------------------------------------------------------
        */
        Artisan::call('data-generals:sync');
        $this->call(PorcentagesQoteSeeder::class);
        $this->call(PercentageWorkerSeeder::class);
        $this->call(TipoVentasSeeder::class);
        $this->call(DataFirstPositionAlmacenSeeder::class);
        $this->call(TipoPagoSeeder::class);
        $this->call(WorkforceSeeder::class);
        $this->call(BankSeeder::class);

        // 👇 DIMENSIÓN TIEMPO
        Artisan::call('dimension:populate-date');

        $this->call(TransferReasonsSeeder::class);
        $this->call(WeightUnitsSeeder::class);
        $this->call(IdentityDocumentTypesSeeder::class);
        $this->call(SunatShippingIndicatorsSeeder::class);
    }
}
