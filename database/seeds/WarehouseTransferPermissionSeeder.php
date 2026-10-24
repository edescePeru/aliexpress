<?php
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Permission, Role};

class WarehouseTransferPermissionSeeder extends Seeder
{
    public function run()
    {
        app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $names = ['list_warehouseTransfer', 'create_warehouseTransfer', 'prepare_warehouseTransfer',
            'dispatch_warehouseTransfer', 'receive_warehouseTransfer', 'cancel_warehouseTransfer', 'manage_all_warehouseTransfer'];
        foreach ($names as $name) { Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['description' => 'Traslados entre almacenes: '.$name]); }
        foreach (Role::query()->where('guard_name', 'web')->whereIn('name', ['owner', 'admin'])->get() as $role) { $role->givePermissionTo($names); }
        $ids = Permission::query()->where('guard_name', 'web')->whereIn('name', $names)->pluck('id')->all();
        foreach (App\RoleTemplate::query()->whereIn('code', ['owner', 'admin'])->get() as $template) { $template->permissions()->syncWithoutDetaching($ids); }
        app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
