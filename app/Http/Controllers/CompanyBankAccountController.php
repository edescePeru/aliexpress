<?php

namespace App\Http\Controllers;

use App\Bank;
use App\CompanyBankAccount;
use App\Http\Requests\StoreCompanyBankAccountRequest;
use App\Http\Requests\UpdateCompanyBankAccountRequest;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CompanyBankAccountController extends Controller
{
    public function index()
    {
        $accounts = CompanyBankAccount::query()
            ->with('bank')
            ->where(
                'company_id',
                TenantContext::companyId()
            )
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return view(
            'companyBankAccount.index',
            compact('accounts')
        );
    }

    public function create()
    {
        $banks = Bank::query()
            ->orderBy('name')
            ->get();

        return view(
            'companyBankAccount.create',
            compact('banks')
        );
    }

    public function store(
        StoreCompanyBankAccountRequest $request
    ) {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $companyId = TenantContext::companyId();

            $hasAccounts =
                CompanyBankAccount::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where('is_active', true)
                    ->exists();

            $isDefault =
                !$hasAccounts ||
                !empty($validated['is_default']);

            if ($isDefault) {
                CompanyBankAccount::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->update([
                        'is_default' => false,
                    ]);
            }

            $position =
                ((int) CompanyBankAccount::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->max('position')) + 1;

            CompanyBankAccount::create([
                'company_id' =>
                    $companyId,

                'bank_id' =>
                    $validated['bank_id'],

                'title' =>
                    $validated['title'],

                'account_number' =>
                    $validated['account_number'],

                'cci' =>
                    $validated['cci'] ?? null,

                'currency' =>
                    $validated['currency'],

                'account_holder' =>
                    $validated['account_holder']
                    ?? null,

                'position' =>
                    $position,

                'is_default' =>
                    $isDefault,

                'is_active' =>
                    true,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Cuenta bancaria creada correctamente.',
            'url' => route('companyBankAccount.index'),
        ], 200);
    }

    public function edit($id)
    {
        $account = $this->findAccount($id);

        $banks = Bank::query()
            ->orderBy('name')
            ->get();

        return view(
            'companyBankAccount.edit',
            compact(
                'account',
                'banks'
            )
        );
    }

    public function update(
        UpdateCompanyBankAccountRequest $request,
        $id
    ) {
        $validated = $request->validated();

        $account = $this->findAccount($id);

        DB::transaction(
            function () use (
                $account,
                $validated
            ) {
                $isDefault =
                    !empty(
                    $validated['is_default']
                    );

                if ($isDefault) {
                    CompanyBankAccount::query()
                        ->where(
                            'company_id',
                            TenantContext::companyId()
                        )
                        ->where(
                            'id',
                            '<>',
                            $account->id
                        )
                        ->update([
                            'is_default' => false,
                        ]);
                }

                $account->update([
                    'bank_id' =>
                        $validated['bank_id'],

                    'title' =>
                        $validated['title'],

                    'account_number' =>
                        $validated['account_number'],

                    'cci' =>
                        $validated['cci'] ?? null,

                    'currency' =>
                        $validated['currency'],

                    'account_holder' =>
                        $validated['account_holder']
                        ?? null,

                    /*
                     * Si ya era default y el checkbox
                     * no vino marcado, no la quitamos
                     * automáticamente.
                     */
                    'is_default' =>
                        $account->is_default
                            ? true
                            : $isDefault,
                ]);
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Cuenta bancaria actualizada correctamente.',
            'url' => route('companyBankAccount.index'),
        ], 200);
    }

    public function toggleStatus($id)
    {
        $account = $this->findAccount($id);

        $wasActive = $account->is_active;

        DB::transaction(function () use ($account, $wasActive) {
            if ($wasActive) {
                $wasDefault = $account->is_default;

                $account->update([
                    'is_active' => false,
                    'is_default' => false,
                ]);

                if ($wasDefault) {
                    $nextAccount = CompanyBankAccount::query()
                        ->where(
                            'company_id',
                            TenantContext::companyId()
                        )
                        ->where('is_active', true)
                        ->orderBy('position')
                        ->orderBy('id')
                        ->first();

                    if ($nextAccount) {
                        $nextAccount->update([
                            'is_default' => true,
                        ]);
                    }
                }
            } else {
                $hasActive = CompanyBankAccount::query()
                    ->where(
                        'company_id',
                        TenantContext::companyId()
                    )
                    ->where('is_active', true)
                    ->exists();

                $account->update([
                    'is_active' => true,
                    'is_default' => !$hasActive,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => $wasActive
                ? 'Cuenta bancaria desactivada correctamente.'
                : 'Cuenta bancaria activada correctamente.',
        ]);
    }

    private function findAccount($id)
    {
        return CompanyBankAccount::query()
            ->where(
                'company_id',
                TenantContext::companyId()
            )
            ->where('id', $id)
            ->firstOrFail();
    }
}