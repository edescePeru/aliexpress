<?php
namespace App\Services\Sales;
use App\{Sale,User,Notification,NotificationUser};
use App\Support\TenantContext as Context;
use Illuminate\Support\Facades\Auth;
class PosSaleNotice
{
    public function send(Sale $sale): void
    {
        $users=User::query()->where('users.tenant_id',Context::tenantId())->where('users.enable',true)->where('users.id','<>',Auth::id())
            ->where(function($q){$q->where('users.is_platform_admin',false)->orWhereNull('users.is_platform_admin');})
            ->whereHas('companies',function($q){$q->where('companies.id',Context::companyId())->where('company_user.is_active',true);})
            ->whereHas('branches',function($q){$q->where('branches.id',Context::branchId())->where('branch_user.is_active',true);})
            ->with('roles')->get()->filter(function($user){return $user->can('list_puntoVenta')&&$user->can('viewBranch_puntoVenta');});
        if($users->isEmpty())return;
        $notification=Notification::create(['content'=>'Venta POS registrada por '.Auth::user()->name,'reason_for_creation'=>'create_sale',
            'user_id'=>Auth::id(),'url_go'=>route('puntoVenta.list')]);
        foreach($users as$user)foreach($user->roles as$role)NotificationUser::create([
            'notification_id'=>$notification->id,'role_id'=>$role->id,'user_id'=>$user->id,'read'=>false]);
    }
}
