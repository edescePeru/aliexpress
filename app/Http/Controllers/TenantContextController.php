<?php

namespace App\Http\Controllers;

use App\Branch;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenantContextController extends Controller
{
    public function branches(Request $request)
    {
        $request->validate([
            'company_id' => [
                'required',
                'integer',
            ],
        ]);

        $user = Auth::user();
        $companyId = (int) $request->input('company_id');

        $company = $user->companies()
            ->where('companies.id', $companyId)
            ->where('companies.tenant_id', $user->tenant_id)
            ->where('companies.is_active', true)
            ->wherePivot('is_active', true)
            ->first();

        if (!$company) {
            abort(
                403,
                'No tiene acceso a la empresa seleccionada.'
            );
        }

        $branches = $user->branches()
            ->where('branches.company_id', $company->id)
            ->where('branches.is_active', true)
            ->wherePivot('is_active', true)
            ->orderByDesc('branch_user.is_default')
            ->orderByDesc('branches.is_main')
            ->orderBy('branches.name')
            ->get([
                'branches.id',
                'branches.name',
            ]);

        return response()->json([
            'success' => true,
            'branches' => $branches,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_id' => [
                'required',
                'integer',
            ],

            'branch_id' => [
                'required',
                'integer',
            ],
        ]);

        $user = Auth::user();

        if ($user->isPlatformAdmin()) {
            abort(
                403,
                'El administrador de plataforma no puede cambiar este contexto.'
            );
        }

        $companyId = (int) $request->input('company_id');
        $branchId = (int) $request->input('branch_id');

        $company = $user->companies()
            ->where('companies.id', $companyId)
            ->where('companies.tenant_id', $user->tenant_id)
            ->where('companies.is_active', true)
            ->wherePivot('is_active', true)
            ->first();

        if (!$company) {
            throw ValidationException::withMessages([
                'company_id' =>
                    'No tiene acceso a la empresa seleccionada.',
            ]);
        }

        $branch = $user->branches()
            ->where('branches.id', $branchId)
            ->where('branches.company_id', $company->id)
            ->where('branches.is_active', true)
            ->wherePivot('is_active', true)
            ->first();

        if (!$branch) {
            throw ValidationException::withMessages([
                'branch_id' =>
                    'No tiene acceso al local seleccionado.',
            ]);
        }

        DB::transaction(function () use (
            $user,
            $company,
            $branch
        ) {
            DB::table('company_user')
                ->where('user_id', $user->id)
                ->update([
                    'is_default' => false,
                ]);

            DB::table('company_user')
                ->where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->update([
                    'is_default' => true,
                ]);

            DB::table('branch_user')
                ->where('user_id', $user->id)
                ->update([
                    'is_default' => false,
                ]);

            DB::table('branch_user')
                ->where('user_id', $user->id)
                ->where('branch_id', $branch->id)
                ->update([
                    'is_default' => true,
                ]);
        });

        session([
            'multitenancy.tenant_id' => $user->tenant_id,
            'multitenancy.company_id' => $company->id,
            'multitenancy.branch_id' => $branch->id,
        ]);

        /*
         * Regenerar el ID de sesión después de cambiar
         * el contexto operativo.
         */
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'message' =>
                'El contexto de trabajo fue actualizado correctamente.',

            'context' => [
                'tenant_id' => $user->tenant_id,
                'company_id' => $company->id,
                'branch_id' => $branch->id,
            ],
        ]);
    }
}
