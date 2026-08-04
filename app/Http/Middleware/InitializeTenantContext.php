<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class InitializeTenantContext
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        /*
         * Este middleware solo debe operar sobre
         * usuarios autenticados.
         */
        if (!$user) {
            return $next($request);
        }

        /*
         * Los administradores de plataforma no pertenecen
         * operativamente a un tenant cliente.
         *
         * Todavía no implementaremos su panel.
         */
        if ($user->isPlatformAdmin()) {
            $this->clearTenantContext();

            return $next($request);
        }

        /*
         * Todo usuario normal debe pertenecer a un tenant.
         */
        if (!$user->tenant_id || !$user->tenant) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'El usuario no tiene un grupo empresarial asignado.',
                ]);
        }

        /*
         * El tenant debe encontrarse activo.
         */
        if (!$user->tenant->is_active) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'El grupo empresarial se encuentra deshabilitado.',
                ]);
        }

        /*
         * Si ya existe contexto en sesión, se valida
         * nuevamente en cada petición.
         */
        if ($this->hasCompleteContext()) {
            if (!$this->isCurrentContextValid($user)) {
                $this->clearTenantContext();

                abort(
                    403,
                    'El contexto empresarial seleccionado no es válido para este usuario.'
                );
            }

            return $next($request);
        }

        /*
         * Resolver empresa predeterminada autorizada.
         */
        $company = $user->companies()
            ->wherePivot('is_active', true)
            ->where('companies.tenant_id', $user->tenant_id)
            ->where('companies.is_active', true)
            ->orderByDesc('company_user.is_default')
            ->orderBy('companies.id')
            ->first();

        if (!$company) {
            abort(
                403,
                'El usuario no tiene una empresa activa asignada.'
            );
        }

        /*
         * Resolver local predeterminado autorizado y
         * perteneciente a la empresa seleccionada.
         */
        $branch = $user->branches()
            ->wherePivot('is_active', true)
            ->where('branches.company_id', $company->id)
            ->where('branches.is_active', true)
            ->orderByDesc('branch_user.is_default')
            ->orderByDesc('branches.is_main')
            ->orderBy('branches.id')
            ->first();

        if (!$branch) {
            abort(
                403,
                'El usuario no tiene un local activo asignado para esta empresa.'
            );
        }

        /*
         * Guardar el contexto operativo.
         *
         * Usamos nombres agrupados para no mezclar estas
         * variables con otras sesiones del sistema.
         */
        session([
            'multitenancy.tenant_id' => $user->tenant_id,
            'multitenancy.company_id' => $company->id,
            'multitenancy.branch_id' => $branch->id,
        ]);

        return $next($request);
    }

    private function hasCompleteContext()
    {
        return session()->has([
            'multitenancy.tenant_id',
            'multitenancy.company_id',
            'multitenancy.branch_id',
        ]);
    }

    private function isCurrentContextValid($user)
    {
        $tenantId = (int) session(
            'multitenancy.tenant_id'
        );

        $companyId = (int) session(
            'multitenancy.company_id'
        );

        $branchId = (int) session(
            'multitenancy.branch_id'
        );

        /*
         * El tenant de sesión debe coincidir exactamente
         * con el tenant del usuario.
         */
        if ($tenantId !== (int) $user->tenant_id) {
            return false;
        }

        /*
         * La empresa debe:
         * - pertenecer al tenant;
         * - estar activa;
         * - estar asignada al usuario;
         * - tener activa la asignación.
         */
        $hasCompanyAccess = $user->companies()
            ->where('companies.id', $companyId)
            ->where('companies.tenant_id', $tenantId)
            ->where('companies.is_active', true)
            ->wherePivot('is_active', true)
            ->exists();

        if (!$hasCompanyAccess) {
            return false;
        }

        /*
         * El local debe:
         * - pertenecer a la empresa activa;
         * - estar activo;
         * - estar asignado al usuario;
         * - tener activa la asignación.
         */
        return $user->branches()
            ->where('branches.id', $branchId)
            ->where('branches.company_id', $companyId)
            ->where('branches.is_active', true)
            ->wherePivot('is_active', true)
            ->exists();
    }

    private function clearTenantContext()
    {
        session()->forget([
            'multitenancy.tenant_id',
            'multitenancy.company_id',
            'multitenancy.branch_id',
        ]);
    }
}
