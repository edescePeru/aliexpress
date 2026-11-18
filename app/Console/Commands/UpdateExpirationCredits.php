<?php

namespace App\Console\Commands;

use App\Notification;
use App\NotificationUser;
use App\SupplierCredit;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class UpdateExpirationCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credits:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando que actualizará los días para expirar de los creditos';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $previous = session()->get('multitenancy');
        try {
            $contexts = \Illuminate\Support\Facades\DB::table('supplier_credits')
                ->select('tenant_id', 'company_id')->distinct()->get();
            foreach ($contexts as $context) {
                $company = \App\Company::where('tenant_id', $context->tenant_id)->findOrFail($context->company_id);
                session()->put('multitenancy', ['tenant_id' => (int) $company->tenant_id, 'company_id' => (int) $company->id]);
                $this->updateCompanyCredits();
            }
        } finally {
            if ($previous === null) { session()->forget('multitenancy'); }
            else { session()->put('multitenancy', $previous); }
        }
        return 0;
    }

    private function updateCompanyCredits()
    {
        // TODO: Aqui va la logica de la actualización.
        /*
        'date_issue',
        'date_expiration',
        'days_to_expiration',
        'state_credit',
        $dias_to_expire = $fecha_expiration->diffInDays(Carbon::now('America/Lima'));
        */
        /**
         * TODO: Primero actualizar los days_to_expiration
         * $dias_to_expire = date_expiration->diffInDays(Carbon::now('America/Lima'));
         * Hallamos los nuevos dias para expiracion y actualizamos
         * TODO: Luego actualizar el state_credit
         * Si el state_credit es diferente a paid_out o expired -> procedemos sino no
         * Si days_to_expiration es menor que 4 y mayor 0 días entonces vamos a
         * crear una notificación
         * que le llegue al admin, principal, finance para que checke los creditos
         * que estan a punto de vencerse.
         * Luego por siacaso modificamos el state_credit a by_expire
         * Si days_to_expiration es igual a 0 entonces modificar el state_credit a
         * expired y crear una notificacion indicando que un crédito ya expiró
         *
        */

        $warning = app(\App\Services\SettingService::class)->get('supplier_credit.expiration_warning_days');
        $ids = SupplierCredit::query()->pluck('id');
        $crear_notif_credits_by_expire = false;
        $crear_notif_credits_expired = false;
        foreach ($ids as $id) {
            $transition = \Illuminate\Support\Facades\DB::transaction(function () use ($id, $warning) {
                $credit = SupplierCredit::lockForUpdate()->find($id);
                if (!$credit) { return null; }
                $value = \App\Services\Purchases\SupplierCreditExpiration::evaluate($credit, $warning);
                if (!$value['eligible']) { return null; }
                $before = $credit->state_credit;
                $credit->days_to_expiration = $value['days'];
                $credit->state_credit = $value['state'];
                if ($credit->isDirty(['days_to_expiration', 'state_credit'])) { $credit->save(); }
                return $before === $value['state'] ? null : $value['state'];
            });
            if ($transition === 'by_expire') { $crear_notif_credits_by_expire = true; }
            if ($transition === 'expired') { $crear_notif_credits_expired = true; }
        }

        if ( $crear_notif_credits_by_expire == true )
        {
            // Crear notificacion
            $notification = Notification::create([
                'content' => 'Hay créditos por vencer, click en Ir para ver los créditos.',
                'reason_for_creation' => 'update_credit',
                'user_id' => null,
                'url_go' => route('index.credit.supplier')
            ]);

            // Roles adecuados para recibir esta notificación admin, logistica
            $users = User::role(['admin', 'principal', 'finance'])
                ->where('tenant_id', \App\Support\TenantContext::tenantId())
                ->whereHas('companies', function ($query) {
                    $query->where('companies.id', \App\Support\TenantContext::companyId())->where('company_user.is_active', true);
                })->get();
            foreach ( $users as $user )
            {
                foreach ( $user->roles as $role )
                {
                    NotificationUser::create([
                        'notification_id' => $notification->id,
                        'role_id' => $role->id,
                        'user_id' => $user->id,
                        'read' => false,
                        'date_read' => null,
                        'date_delete' => null
                    ]);
                }
            }
        }

        if ( $crear_notif_credits_expired == true )
        {
            // Crear notificacion
            $notification = Notification::create([
                'content' => 'Hay créditos vencidos, click en Ir para ver los créditos.',
                'reason_for_creation' => 'expired_credit',
                'user_id' => null,
                'url_go' => route('index.credit.supplier')
            ]);

            // Roles adecuados para recibir esta notificación admin, logistica
            $users = User::role(['admin', 'principal', 'finance'])
                ->where('tenant_id', \App\Support\TenantContext::tenantId())
                ->whereHas('companies', function ($query) {
                    $query->where('companies.id', \App\Support\TenantContext::companyId())->where('company_user.is_active', true);
                })->get();
            foreach ( $users as $user )
            {
                foreach ( $user->roles as $role )
                {
                    NotificationUser::create([
                        'notification_id' => $notification->id,
                        'role_id' => $role->id,
                        'user_id' => $user->id,
                        'read' => false,
                        'date_read' => null,
                        'date_delete' => null
                    ]);
                }
            }
        }


    }
}
