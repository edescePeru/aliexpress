<?php
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\PaymentDeadline;
use App\Support\TenantContext;
class PaymentDeadlineSeeder extends Seeder
{
    public function run()
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $names = ['enable_paymentDeadline', 'list_paymentDeadline', 'create_paymentDeadline', 'update_paymentDeadline', 'destroy_paymentDeadline'];
        foreach ($names as $name) { Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['description' => 'Administrar plazos de pago: '.$name]); }
        foreach (Role::query()->where('guard_name', 'web')->whereIn('name', ['owner', 'admin'])->get() as $role) { $role->givePermissionTo($names); }
        $permissionIds = Permission::query()->where('guard_name', 'web')->whereIn('name', $names)->pluck('id')->all();
        foreach (\App\RoleTemplate::query()->whereIn('code', ['owner', 'admin'])->get() as $template) {
            $template->permissions()->syncWithoutDetaching($permissionIds);
        }
        app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        if (!TenantContext::tenantIdOrNull() || !TenantContext::companyIdOrNull()) { return; }
        PaymentDeadline::firstOrCreate(['description' => 'AL CONTADO EN EFECTIVO', 'type' => 'purchases'], ['days' => 0, 'credit' => false, 'is_active' => true]);
        PaymentDeadline::firstOrCreate(['description' => 'TRANSFERENCIA BANCARIA', 'type' => 'purchases'], ['days' => 0, 'credit' => false, 'is_active' => true]);
        PaymentDeadline::firstOrCreate(['description' => 'FACTURA 7 DIAS', 'type' => 'purchases'], ['days' => 7, 'credit' => true, 'is_active' => true]);
        PaymentDeadline::firstOrCreate(['description' => 'FACTURA 15 DIAS', 'type' => 'purchases'], ['days' => 15, 'credit' => true, 'is_active' => true]);
        PaymentDeadline::firstOrCreate(['description' => 'FACTURA 30 DIAS', 'type' => 'purchases'], ['days' => 30, 'credit' => true, 'is_active' => true]);
        PaymentDeadline::firstOrCreate(['description' => 'FACTURA 45 DIAS', 'type' => 'purchases'], ['days' => 45, 'credit' => true, 'is_active' => true]);
        PaymentDeadline::firstOrCreate(['description' => 'LETRA 30 DIAS', 'type' => 'purchases'], ['days' => 30, 'credit' => true, 'is_active' => true]);
        PaymentDeadline::firstOrCreate(['description' => 'AL CONTADO', 'type' => 'quotes'], ['days' => 0, 'credit' => false, 'is_active' => true]);
        PaymentDeadline::firstOrCreate(['description' => 'FACTORING A 45 DÍAS', 'type' => 'quotes'], ['days' => 45, 'credit' => false, 'is_active' => true]);
        PaymentDeadline::firstOrCreate(['description' => 'FACTURA A 30 DÍAS', 'type' => 'quotes'], ['days' => 30, 'credit' => false, 'is_active' => true]);
        PaymentDeadline::firstOrCreate(['description' => '50% DE ADELANTO 25% AL AVANCE - 25% (FACTORING A 45 DÍAS)', 'type' => 'quotes'], ['days' => 45, 'credit' => false, 'is_active' => true]);
    }
}
