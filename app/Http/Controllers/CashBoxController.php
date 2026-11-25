<?php

namespace App\Http\Controllers;

use App\CashBox;
use App\CashBoxSubtype;
use App\CashMovement;
use App\CashRegister;
use App\Support\TenantContext;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Branch;
use App\CompanyBankAccount;

class CashBoxController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        /*
         * ============================================================
         * PERMISOS DEL MÓDULO
         * ============================================================
         */

        $this->middleware(
            'permission:list_cashBox'
        )->only([
            'index',
            'listar',
        ]);

        $this->middleware(
            'permission:create_cashBox'
        )->only([
            'store',
        ]);

        $this->middleware(
            'permission:edit_cashBox'
        )->only([
            'update',
        ]);

        $this->middleware(
            'permission:disable_cashBox'
        )->only([
            'toggle',
        ]);

        /*
         * Los listados de movimientos conservan
         * sus permisos propios.
         */
        $this->middleware(
            'permission:listCashMovementMy_cashBox'
        )->only([
            'myIndex',
            'myList',
        ]);

        $this->middleware(
            'permission:listCashMovementAdmin_cashBox'
        )->only([
            'adminIndex',
            'adminList',
        ]);
    }

    /**
     * Vista principal (Blade con modal + JS)
     */
    public function index()
    {
        $companyId =
            TenantContext::companyId();

        $branches =
            Branch::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->orderBy(
                    'name',
                    'asc'
                )
                ->get();

        /*
         * Cuentas bancarias disponibles
         * únicamente de la Company actual.
         */
        $bankAccounts =
            CompanyBankAccount::query()
                ->with([
                    'bank',
                ])
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'is_active',
                    1
                )
                ->orderBy(
                    'position'
                )
                ->orderBy(
                    'title'
                )
                ->get();

        $permissions = [
            'can_create' =>
                auth()->user()->can(
                    'create_cashBox'
                ),

            'can_edit' =>
                auth()->user()->can(
                    'edit_cashBox'
                ),

            'can_toggle' =>
                auth()->user()->can(
                    'disable_cashBox'
                ),
        ];

        return view(
            'cashBox.index',
            compact(
                'permissions',
                'branches',
                'bankAccounts'
            )
        );
    }

    /**
     * Listado AJAX paginado + filtro
     * GET: ?q=texto&page=1
     */
    public function listar(Request $request)
    {
        $companyId =
            TenantContext::companyId();

        $q =
            trim(
                (string)
                $request->get(
                    'q',
                    ''
                )
            );

        $perPage =
            (int)
            $request->get(
                'per_page',
                10
            );

        if (
            $perPage <= 0 ||
            $perPage > 100
        ) {
            $perPage = 10;
        }

        /*
         * ============================================================
         * LISTADO ADMINISTRATIVO
         * ============================================================
         *
         * IMPORTANTE:
         *
         * Aquí NO filtramos por Branch activa.
         *
         * El mantenedor de CashBox administra TODAS las cajas
         * pertenecientes a la Company actual.
         */

        $query =
            CashBox::query()
                ->with([
                    'branch',
                    'bankAccount.bank',
                ])
                ->where(
                    'company_id',
                    $companyId
                )
                ->orderBy(
                    'position'
                )
                ->orderBy(
                    'id'
                );

        if ($q !== '') {

            $query->where(
                function ($w) use ($q) {

                    $w
                        ->where(
                            'name',
                            'like',
                            "%{$q}%"
                        )
                        ->orWhere(
                            'bank_name',
                            'like',
                            "%{$q}%"
                        )
                        ->orWhere(
                            'account_label',
                            'like',
                            "%{$q}%"
                        )
                        ->orWhere(
                            'account_number_mask',
                            'like',
                            "%{$q}%"
                        )
                        ->orWhere(
                            'currency',
                            'like',
                            "%{$q}%"
                        )
                        ->orWhereHas(
                            'branch',
                            function ($branchQuery) use ($q) {

                                $branchQuery->where(
                                    'name',
                                    'like',
                                    "%{$q}%"
                                );
                            }
                        );
                }
            );
        }

        $paginator =
            $query->paginate(
                $perPage
            );

        $items =
            collect(
                $paginator->items()
            )
                ->map(
                    function ($box) {

                        return [
                            'id' =>
                                $box->id,

                            'name' =>
                                $box->name,

                            'type' =>
                                $box->type,

                            'uses_subtypes' =>
                                (bool)
                                $box
                                    ->uses_subtypes,

                            'is_active' =>
                                (bool)
                                $box
                                    ->is_active,

                            'position' =>
                                (int)
                                $box
                                    ->position,

                            'account_label' =>
                                $box
                                    ->account_label,

                            'account_number_mask' =>
                                $box
                                    ->account_number_mask,

                            'branch_id' =>
                                $box
                                    ->branch_id,

                            /*
                             * NULL = toda la Company.
                             */
                            'branch_name' =>
                                $box->branch
                                    ? $box
                                    ->branch
                                    ->name
                                    : null,

                            'scope_label' =>
                                $box->branch
                                    ? $box
                                    ->branch
                                    ->name
                                    : 'Toda la empresa',

                            /*
                             * Lo enviamos por si luego quieres
                             * mostrarlo en administración.
                             */
                            'current_balance' =>
                                $box
                                    ->current_balance,

                            'company_bank_account_id' =>
                                $box->company_bank_account_id,

                            'bank_account_title' =>
                                $box->bankAccount
                                    ? $box->bankAccount->title
                                    : $box->account_label,

                            'bank_name' =>
                                $box->bankAccount &&
                                $box->bankAccount->bank
                                    ? $box->bankAccount->bank->name
                                    : $box->bank_name,

                            'account_number' =>
                                $box->bankAccount
                                    ? $box->bankAccount->account_number
                                    : $box->account_number_mask,

                            'cci' =>
                                $box->bankAccount
                                    ? $box->bankAccount->cci
                                    : null,

                            'account_holder' =>
                                $box->bankAccount
                                    ? $box->bankAccount->account_holder
                                    : null,

                            'currency' =>
                                $box->bankAccount
                                    ? $box->bankAccount->currency
                                    : $box->currency,
                        ];
                    }
                )
                ->values();

        return response()->json([
            'data' =>
                $items,

            'meta' => [
                'current_page' =>
                    $paginator
                        ->currentPage(),

                'last_page' =>
                    $paginator
                        ->lastPage(),

                'total' =>
                    $paginator
                        ->total(),

                'from' =>
                    $paginator
                        ->firstItem(),

                'to' =>
                    $paginator
                        ->lastItem(),

                'per_page' =>
                    $paginator
                        ->perPage(),
            ],
        ]);
    }

    /**
     * Crear caja (AJAX)
     */
    public function store(Request $request)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            app(\App\Services\CashConfigurationGuard::class)->lockContext();
            $tenantId =
                TenantContext::tenantId();

            $companyId =
                TenantContext::companyId();

            $data =
                $this->validateCashBox(
                    $request
                );

            /*
             * Nunca aceptamos Tenant/Company
             * enviados desde frontend.
             */

            $data['tenant_id'] =
                $tenantId;

            $data['company_id'] =
                $companyId;

            /*
             * Una CashBox nueva comienza sin saldo maestro.
             */
            $data['current_balance'] =
                0;

            $data['is_active'] =
                true;

            $box =
                CashBox::create(
                    $data
                );

            return response()->json([
                'message' =>
                    'Caja creada correctamente.',

                'data' =>
                    $box,
            ], 201);
        }, 3);
    }

    /**
     * Actualizar caja (AJAX)
     * Recibe id en body (por tu modal)
     */
    public function update(Request $request)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            $guard = app(\App\Services\CashConfigurationGuard::class);
            $guard->lockContext();
            $request->validate(['id' => ['required', 'integer', 'min:1']]);
            $box = $guard->scope(CashBox::query())->whereKey($request->input('id'))->lockForUpdate()->firstOrFail();
            $data = $this->validateCashBox($request, $box);
            $guard->assertCurrency($box, array_key_exists('currency', $data) ? $data['currency'] : $box->currency);
            $box->update($data);
            return response()->json(['message' => 'Caja actualizada correctamente.', 'data' => $box->fresh(['branch'])]);
        }, 3);
    }


    public function toggle(Request $request)
    {
        $companyId =
            TenantContext::companyId();

        $id =
            (int)
            $request->input(
                'id'
            );

        if ($id <= 0) {

            return response()->json([
                'message' =>
                    'ID requerido.',
            ], 422);
        }

        $box =
            CashBox::query()
                ->where(
                    'id',
                    $id
                )
                ->where(
                    'company_id',
                    $companyId
                )
                ->firstOrFail();

        /*
         * ============================================================
         * DESACTIVAR
         * ============================================================
         */

        if ($box->is_active) {

            $hasOpenRegisters =
                CashRegister::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'cash_box_id',
                        $box->id
                    )
                    ->where(
                        'status',
                        1
                    )
                    ->exists();

            if ($hasOpenRegisters) {

                return response()->json([
                    'message' =>
                        'No se puede desactivar la caja porque tiene sesiones abiertas.',
                ], 422);
            }
        }

        $box->is_active =
            !$box->is_active;

        $box->save();

        return response()->json([
            'message' =>
                $box->is_active
                    ? 'Caja activada correctamente.'
                    : 'Caja desactivada correctamente.',

            'data' =>
                $box,
        ]);
    }

    /**
     * Validación y normalización centralizada
     */
    private function validateCashBox(
        Request $request,
        CashBox $currentBox = null
    ) {
        $companyId =
            TenantContext::companyId();

        if ($request->input('type') === 'bank') {
            // La moneda bancaria procede exclusivamente de la cuenta vinculada.
            $request->request->remove('currency');
            $request->query->remove('currency');
        } elseif (is_string($request->input('currency'))) {
            $currency = strtoupper(trim($request->input('currency')));
            $request->merge(['currency' => $currency === '' ? null : $currency]);
        }

        $data =
            $request->validate([
                'name' =>
                    'required|string|max:120',

                'type' =>
                    'required|in:cash,bank',

                'branch_id' =>
                    'nullable|integer',

                'uses_subtypes' =>
                    'nullable',

                'position' =>
                    'nullable|integer|min:0',

                'company_bank_account_id' =>
                    'nullable|integer',

                'currency' =>
                    'nullable|string|in:PEN,USD',
            ]);

        /*
         * ============================================================
         * BRANCH
         * ============================================================
         *
         * branch_id NULL:
         * disponible para toda la Company.
         *
         * branch_id X:
         * debe pertenecer a la Company actual.
         */

        $branchId =
            !empty(
            $data['branch_id']
            )
                ? (int)
            $data['branch_id']
                : null;

        if ($branchId) {

            $branchExists =
                Branch::query()
                    ->where(
                        'id',
                        $branchId
                    )
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->exists();

            if (!$branchExists) {

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'branch_id' => [
                        'La sucursal seleccionada no pertenece a la empresa actual.'
                    ],
                ]);
            }
        }

        /*
         * ============================================================
         * CUENTA BANCARIA
         * ============================================================
        */

        $bankAccountId =
            !empty(
            $data['company_bank_account_id']
            )
                ? (int)
            $data['company_bank_account_id']
                : null;

        if (
            $data['type'] ===
            'bank'
        ) {

            if (!$bankAccountId) {

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'company_bank_account_id' => [
                        'Debe seleccionar una cuenta bancaria.'
                    ],
                ]);
            }

            /*
             * BelongsToTenant protege Tenant.
             *
             * Además validamos explícitamente Company
             * y que la cuenta esté activa.
             */
            $bankAccount =
                CompanyBankAccount::query()
                    ->where(
                        'id',
                        $bankAccountId
                    )
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'is_active',
                        1
                    )
                    ->first();

            if (!$bankAccount) {

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'company_bank_account_id' => [
                        'La cuenta bancaria seleccionada no pertenece a la empresa actual o está inactiva.'
                    ],
                ]);
            }

            $data['company_bank_account_id'] =
                $bankAccount->id;

            /*
             * Mientras currency siga existiendo en CashBox,
             * la mantenemos sincronizada.
             *
             * Pero la fuente de verdad para una caja bancaria
             * será CompanyBankAccount.
             */
            $accountCurrency = strtoupper(trim((string) $bankAccount->currency));
            if (!in_array($accountCurrency, ['PEN', 'USD'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'company_bank_account_id' => 'La cuenta bancaria debe tener moneda PEN o USD.',
                ]);
            }
            $data['currency'] = $accountCurrency;

        } else {

            /*
             * Efectivo nunca tiene cuenta bancaria.
             */
            $data['company_bank_account_id'] =
                null;
        }

        /*
         * ============================================================
         * PROTECCIÓN EN EDICIÓN
         * ============================================================
         *
         * No permitimos mover una CashBox a otra Branch
         * mientras tenga sesiones abiertas.
         */

        if (
            $currentBox &&
            (int) ($currentBox->branch_id ?: 0)
            !==
            (int) ($branchId ?: 0)
        ) {

            $hasOpenRegisters =
                CashRegister::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'cash_box_id',
                        $currentBox->id
                    )
                    ->where(
                        'status',
                        1
                    )
                    ->exists();

            if ($hasOpenRegisters) {

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'branch_id' => [
                        'No puede cambiar el alcance de la caja mientras existan sesiones abiertas.'
                    ],
                ]);
            }
        }

        $data['branch_id'] =
            $branchId;

        /*
         * ============================================================
         * BOOLEANOS
         * ============================================================
         */

        $data['uses_subtypes'] =
            $request->boolean(
                'uses_subtypes'
            );

        /*
         * ============================================================
         * DEFAULTS
         * ============================================================
         */

        $data['position'] =
            $data['position']
            ?? 0;

        /*
         * ============================================================
         * EFECTIVO
         * ============================================================
         */

        if (
            $data['type'] ===
            'cash'
        ) {

            $data['uses_subtypes'] =
                false;

            $data['bank_name'] =
                null;

            $data['account_label'] =
                null;

            $data['account_number_mask'] =
                null;
        }

        /*
         * ============================================================
         * BANCARIO
         * ============================================================
         */

        if (
            $data['type'] ===
            'bank'
        ) {

            $data['bank_name'] =
                null;

            $data['account_label'] =
                null;

            $data['account_number_mask'] =
                null;
        }

        if (
            $data['type'] ===
            'bank'
            &&
            empty(
            $data['currency']
            )
        ) {

            $data['currency'] =
                'PEN';
        }

        /*
         * ============================================================
         * LIMPIEZA
         * ============================================================
         */

        foreach (
            [
                'name',
                'bank_name',
                'account_label',
                'account_number_mask',
                'currency',
            ]
            as $key
        ) {

            if (
                isset(
                    $data[$key]
                )
                &&
                is_string(
                    $data[$key]
                )
            ) {

                $data[$key] =
                    trim(
                        $data[$key]
                    );

                if (
                    $data[$key] ===
                    ''
                ) {

                    $data[$key] =
                        null;
                }
            }
        }

        if (
        !empty(
        $data['currency']
        )
        ) {

            $data['currency'] =
                strtoupper(
                    $data['currency']
                );
        }

        return $data;
    }

    // =========================
    //  USER (mis movimientos)
    // =========================
    public function myIndex()
    {
        /*
         * =========================================================
         * CONTEXTO
         * =========================================================
         */

        $user =
            Auth::user();

        $userId =
            $user->id;

        $companyId =
            TenantContext::companyId();

        $branchId =
            TenantContext::branchId();

        /*
         * =========================================================
         * CASHBOX DISPONIBLES
         * =========================================================
         *
         * - Company actual
         * - activas
         * - company-wide
         *   O específicas de la Branch actual
         */

        $cashBoxes =
            CashBox::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'is_active',
                    1
                )
                ->where(
                    function ($query) use (
                        $branchId
                    ) {

                        $query
                            ->whereNull(
                                'branch_id'
                            )
                            ->orWhere(
                                'branch_id',
                                $branchId
                            );
                    }
                )
                ->orderBy(
                    'position'
                )
                ->orderBy(
                    'id'
                )
                ->get();

        /*
         * =========================================================
         * SESIONES ABIERTAS DEL USUARIO
         * =========================================================
         *
         * Esto controla qué botones aparecen verdes.
         */

        $openCashBoxIds =
            CashRegister::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'branch_id',
                    $branchId
                )
                ->where(
                    'user_id',
                    $userId
                )
                ->where(
                    'status',
                    1
                )
                ->whereNotNull(
                    'cash_box_id'
                )
                ->pluck(
                    'cash_box_id'
                )
                ->unique()
                ->values()
                ->toArray();

        /*
         * =========================================================
         * SUBTIPOS DE LA COMPANY
         * =========================================================
         *
         * El filtro del listado puede mostrar todos los subtipos
         * configurados en la Company.
         */

        $subtypes =
            CashBoxSubtype::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'is_active',
                    1
                )
                ->orderBy(
                    'position'
                )
                ->orderBy(
                    'name'
                )
                ->get();

        $permissions =
            $user
                ->getPermissionsViaRoles()
                ->pluck('name')
                ->toArray();

        return view(
            'cashBox.my_index',
            compact(
                'permissions',
                'cashBoxes',
                'subtypes',
                'openCashBoxIds'
            )
        );
    }

    public function myList(
        Request $request
    ) {
        return $this->buildListResponse(
            $request,
            Auth::id(),
            false
        );
    }

    // =========================
    //  ADMIN (todos)
    // =========================
    public function adminIndex()
    {
        /*
         * =========================================================
         * CONTEXTO
         * =========================================================
         */

        $user =
            Auth::user();

        $userId =
            $user->id;

        $companyId =
            TenantContext::companyId();

        $branchId =
            TenantContext::branchId();

        /*
         * =========================================================
         * CASHBOX DISPONIBLES EN EL CONTEXTO ACTUAL
         * =========================================================
         */

        $cashBoxes =
            CashBox::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'is_active',
                    1
                )
                ->where(
                    function ($query) use (
                        $branchId
                    ) {

                        $query
                            ->whereNull(
                                'branch_id'
                            )
                            ->orWhere(
                                'branch_id',
                                $branchId
                            );
                    }
                )
                ->orderBy(
                    'position'
                )
                ->orderBy(
                    'name'
                )
                ->get();

        /*
         * =========================================================
         * SESIONES ABIERTAS DEL USUARIO ACTUAL
         * =========================================================
         *
         * Los botones superiores siguen representando
         * "mis sesiones abiertas", incluso estando en vista Admin.
         */

        $openCashBoxIds =
            CashRegister::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'branch_id',
                    $branchId
                )
                ->where(
                    'user_id',
                    $userId
                )
                ->where(
                    'status',
                    1
                )
                ->whereNotNull(
                    'cash_box_id'
                )
                ->pluck(
                    'cash_box_id'
                )
                ->unique()
                ->values()
                ->toArray();

        /*
         * =========================================================
         * SUBTIPOS
         * =========================================================
         */

        $subtypes =
            CashBoxSubtype::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'is_active',
                    1
                )
                ->orderBy(
                    'position'
                )
                ->orderBy(
                    'name'
                )
                ->get();

        /*
         * =========================================================
         * USUARIOS QUE TIENEN MOVIMIENTOS EN ESTA COMPANY
         * =========================================================
         *
         * Esto evita mostrar usuarios de otra Company/Tenant
         * solamente porque existen en users.
         */

        $movementUserIds =
            CashMovement::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'branch_id',
                    $branchId
                )
                ->whereNotNull(
                    'user_id'
                )
                ->select(
                    'user_id'
                )
                ->distinct();

        $users =
            User::query()
                ->whereIn(
                    'id',
                    $movementUserIds
                )
                ->orderBy(
                    'name'
                )
                ->get();

        $permissions =
            $user
                ->getPermissionsViaRoles()
                ->pluck('name')
                ->toArray();

        return view(
            'cashBox.admin_index',
            compact(
                'openCashBoxIds',
                'permissions',
                'cashBoxes',
                'subtypes',
                'users'
            )
        );
    }

    public function adminList(
        Request $request
    ) {
        return $this->buildListResponse(
            $request,
            null,
            true
        );
    }

    public function summaryIndex(Request $request, $cashBox)
    {
        [$box, $branchId, $data] = $this->summaryFilters($request, $cashBox);
        $readOnly = true;
        $cashBoxes = collect([$box]);
        $users = collect();
        $subtypes = collect();
        $permissions = [];
        $sessionOptions = \App\CashRegister::query()
            ->where('tenant_id', TenantContext::tenantId())
            ->where('company_id', TenantContext::companyId())
            ->where('cash_box_id', $box->id)->whereNotNull('branch_id')
            ->when($branchId !== null, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })->with(['user:id,name', 'branch:id,name'])
            ->orderByDesc('id')->get(['id', 'user_id', 'branch_id', 'status', 'opening_time']);
        $summaryQuery = array_intersect_key($data, array_flip(['branch_id']));
        $summaryListUrl = route('financial.summary.movements.list', ['cashBox' => $box->id] + $summaryQuery);
        $summaryBackUrl = route('financial.summary', [
            'tab' => !empty($data['period']) || isset($data['date_from'], $data['date_to']) ? 'activity' : 'balances',
            'period' => $data['period'] ?? null,
            'branch_id' => $branchId, 'from' => $data['date_from'] ?? null, 'to' => $data['date_to'] ?? null,
        ]);

        return view('cashBox.admin_index', compact(
            'box', 'branchId', 'readOnly', 'cashBoxes', 'users', 'subtypes', 'permissions',
            'sessionOptions', 'summaryListUrl', 'summaryBackUrl', 'data'
        ));
    }

    public function summaryList(Request $request, $cashBox)
    {
        [$box, $branchId, $data] = $this->summaryFilters($request, $cashBox);
        $request->query->set('cash_box_id', (string) $box->id);

        return $this->buildListResponse($request, null, true, [
            'branch_id' => $branchId,
            'session_id' => $data['session_id'] ?? null,
        ]);
    }

    private function summaryFilters(Request $request, $cashBox): array
    {
        app(\App\Services\Materials\MaterialParameters::class)->context();
        $data = $request->validate([
            'period' => ['nullable', 'in:all'],
            'branch_id' => ['nullable', 'integer', 'min:1'],
            'session_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $branchId = isset($data['branch_id']) ? (int) $data['branch_id'] : null;
        $companyId = TenantContext::companyId();
        if ($branchId !== null) {
            abort_unless(\App\Branch::query()->where('company_id', $companyId)->whereKey($branchId)->exists(), 404);
        }
        $box = CashBox::query()->where('tenant_id', TenantContext::tenantId())->where('company_id', $companyId)
            ->when($branchId !== null, function ($query) use ($branchId) {
                $query->where(function ($scope) use ($branchId) {
                    $scope->whereNull('branch_id')->orWhere('branch_id', $branchId);
                });
            })->findOrFail($cashBox);

        return [$box, $branchId, $data];
    }

    // =========================
    //  Core: query + paginación
    // =========================
    private function buildListResponse(
        Request $request,
        $onlyUserId = null,
        $isAdmin = false,
        ?array $summary = null
    ) {
        /*
         * =========================================================
         * CONTEXTO
         * =========================================================
         */

        $companyId =
            TenantContext::companyId();

        $branchId = $summary === null ? TenantContext::branchId() : $summary['branch_id'];

        /*
         * =========================================================
         * FILTROS
         * =========================================================
         */

        $q =
            trim(
                (string)
                $request->get(
                    'q',
                    ''
                )
            );

        $cashBoxId =
            $request->get(
                'cash_box_id',
                ''
            );

        $subtypeId =
            $request->get(
                'subtype_id',
                ''
            );

        $type =
            $request->get(
                'type',
                ''
            );

        $dateFrom =
            $request->get(
                'date_from',
                ''
            );

        $dateTo =
            $request->get(
                'date_to',
                ''
            );

        $filterUserId =
            $isAdmin
                ? $request->get(
                'user_id',
                ''
            )
                : null;

        $perPage =
            (int)
            $request->get(
                'per_page',
                10
            );

        if (
            $perPage <= 0 ||
            $perPage > 100
        ) {
            $perPage = 10;
        }

        /*
         * =========================================================
         * QUERY BASE
         * =========================================================
         *
         * IMPORTANTE:
         *
         * CashMovement.cash_box_id y user_id son ahora
         * las relaciones financieras principales.
         *
         * CashRegister se deja como LEFT JOIN porque una
         * regularización futura puede tener:
         *
         * cash_register_id = NULL
         */

        $query =
            CashMovement::query()
                ->leftJoin(
                    'cash_registers',
                    'cash_registers.id',
                    '=',
                    'cash_movements.cash_register_id'
                )
                ->join(
                    'cash_boxes',
                    'cash_boxes.id',
                    '=',
                    'cash_movements.cash_box_id'
                )
                ->leftJoin(
                    'users',
                    'users.id',
                    '=',
                    'cash_movements.user_id'
                )
                ->leftJoin(
                    'cash_box_subtypes',
                    'cash_box_subtypes.id',
                    '=',
                    'cash_movements.cash_box_subtype_id'
                )
                ->leftJoin(
                    'branches',
                    'branches.id',
                    '=',
                    'cash_movements.branch_id'
                )
                ->where(
                    'cash_movements.company_id',
                    $companyId
                )
                ->where('cash_movements.tenant_id', TenantContext::tenantId())
                ->when($branchId !== null, function ($query) use ($branchId) {
                    $query->where('cash_movements.branch_id', $branchId);
                })
                ->when($summary !== null, function ($query) use ($summary, $companyId) {
                    $query->where('cash_boxes.tenant_id', TenantContext::tenantId())
                        ->where('cash_boxes.company_id', $companyId)
                        ->whereNotNull('cash_movements.branch_id');
                    if ($summary['session_id'] !== null) {
                        $query->where('cash_movements.cash_register_id', $summary['session_id']);
                    }
                })
                ->select([
                    /*
                     * Movimiento.
                     */
                    'cash_movements.id',
                    'cash_movements.balance_effect',
                    'cash_movements.deferred_cancelled_at',
                    'cash_movements.regularization_source_id',

                    'cash_movements.type',

                    'cash_movements.amount',

                    'cash_movements.description',

                    'cash_movements.sale_id',

                    'cash_movements.entry_id',

                    'cash_movements.created_at',

                    'cash_movements.observation',

                    'cash_movements.amount_regularize',

                    'cash_movements.commission',

                    'cash_movements.regularize',

                    'cash_movements.cash_movement_origin_id',

                    'cash_movements.cash_movement_regularize_id',

                    'cash_movements.cash_settlement_id',

                    /*
                     * Contexto moderno.
                     */
                    'cash_movements.cash_register_id',

                    'cash_movements.cash_box_id',

                    'cash_movements.user_id',

                    'cash_movements.branch_id',

                    /*
                     * CashBox.
                     */
                    'cash_boxes.name as cash_box_name',

                    'cash_boxes.type as cash_box_type',

                    /*
                     * Usuario.
                     */
                    'users.name as user_name',

                    /*
                     * Branch.
                     */
                    'branches.name as branch_name',

                    /*
                     * Subtipo.
                     */
                    'cash_box_subtypes.name as subtype_name',

                    'cash_box_subtypes.code as subtype_code',

                    'cash_box_subtypes.is_deferred as subtype_is_deferred',
                ])
                ->orderBy(
                    'cash_movements.created_at',
                    'desc'
                )
                ->orderBy(
                    'cash_movements.id',
                    'desc'
                );

        /*
         * =========================================================
         * MIS MOVIMIENTOS
         * =========================================================
         */

        if (
        !is_null(
            $onlyUserId
        )
        ) {

            $query->where(
                'cash_movements.user_id',
                $onlyUserId
            );
        }

        /*
         * =========================================================
         * ADMIN: USUARIO
         * =========================================================
         */

        if (
            $isAdmin &&
            $filterUserId !== '' &&
            $filterUserId !== null
        ) {

            $query->where(
                'cash_movements.user_id',
                $filterUserId
            );
        }

        /*
         * =========================================================
         * CASHBOX
         * =========================================================
         */

        if (
            $cashBoxId !== '' &&
            $cashBoxId !== null
        ) {

            $query->where(
                'cash_movements.cash_box_id',
                $cashBoxId
            );
        }

        /*
         * =========================================================
         * SUBTIPO
         * =========================================================
         */

        if (
            $subtypeId !== '' &&
            $subtypeId !== null
        ) {

            if (
                $subtypeId ===
                'none'
            ) {

                $query->whereNull(
                    'cash_movements.cash_box_subtype_id'
                );

            } else {

                $query->where(
                    'cash_movements.cash_box_subtype_id',
                    $subtypeId
                );
            }
        }

        /*
         * =========================================================
         * TIPO
         * =========================================================
         */

        if (
            $type !== '' &&
            $type !== null &&
            in_array(
                $type,
                [
                    'sale',
                    'expense',
                    'income',
                ],
                true
            )
        ) {

            $query->where(
                'cash_movements.type',
                $type
            );
        }

        /*
         * =========================================================
         * FECHAS
         * =========================================================
         */

        if ($dateFrom) {

            $query->whereDate(
                'cash_movements.created_at',
                '>=',
                $dateFrom
            );
        }

        if ($dateTo) {

            $query->whereDate(
                'cash_movements.created_at',
                '<=',
                $dateTo
            );
        }

        /*
         * =========================================================
         * BÚSQUEDA
         * =========================================================
         */

        if ($q !== '') {

            $query->where(
                function ($w) use ($q) {

                    $w
                        ->where(
                            'cash_movements.description',
                            'like',
                            "%{$q}%"
                        )
                        ->orWhere(
                            'cash_movements.observation',
                            'like',
                            "%{$q}%"
                        )
                        ->orWhere(
                            'cash_boxes.name',
                            'like',
                            "%{$q}%"
                        )
                        ->orWhere(
                            'users.name',
                            'like',
                            "%{$q}%"
                        )
                        ->orWhere(
                            'cash_box_subtypes.name',
                            'like',
                            "%{$q}%"
                        )
                        ->orWhere(
                            'cash_box_subtypes.code',
                            'like',
                            "%{$q}%"
                        )
                        ->orWhere(
                            'branches.name',
                            'like',
                            "%{$q}%"
                        );
                }
            );
        }

        /*
         * =========================================================
         * PAGINACIÓN
         * =========================================================
         */

        $paginator =
            $query->paginate(
                $perPage
            );

        /*
         * =========================================================
         * RESPONSE
         * =========================================================
         */

        $items =
            collect(
                $paginator->items()
            )
                ->map(
                    function ($row) {

                        return [
                            'id' =>
                                $row->id,

                            /*
                             * Tipo.
                             */
                            'type_raw' =>
                                $row->type,

                            'type' =>
                                $row->type,

                            'regularize' =>
                                (int)
                                $row
                                    ->regularize,
                            'balance_effect' => $row->balance_effect !== false,
                            'deferred_cancelled' => $row->deferred_cancelled_at !== null,
                            'regularization_source_id' => $row->regularization_source_id,

                            /*
                             * Montos.
                             */
                            'amount' =>
                                $row->amount,

                            'amount_regularize' =>
                                $row
                                    ->amount_regularize,

                            'commission' =>
                                $row
                                    ->commission,

                            /*
                             * Texto.
                             */
                            'description' =>
                                $row
                                    ->description,

                            'observation' =>
                                $row
                                    ->observation,

                            /*
                             * Referencias.
                             */
                            'sale_id' =>
                                $row->sale_id,

                            'entry_id' =>
                                $row->entry_id,

                            'cash_register_id' =>
                                $row
                                    ->cash_register_id,

                            'cash_settlement_id' =>
                                $row
                                    ->cash_settlement_id,

                            'origin_id' =>
                                $row
                                    ->cash_movement_origin_id,

                            'regularize_id' =>
                                $row
                                    ->cash_movement_regularize_id,

                            /*
                             * Fecha.
                             */
                            'created_at' =>
                                $row->created_at
                                    ? $row
                                    ->created_at
                                    ->format(
                                        'd/m/Y h:i A'
                                    )
                                    : '',

                            /*
                             * CashBox.
                             */
                            'cash_box_id' =>
                                $row
                                    ->cash_box_id,

                            'cash_box_name' =>
                                $row
                                    ->cash_box_name,

                            'cash_box_type' =>
                                $row
                                    ->cash_box_type,

                            'cash_box_label' =>
                                $row
                                    ->cash_box_name,

                            /*
                             * Branch.
                             *
                             * Todavía no tienes la columna en Blade,
                             * pero ya queda disponible.
                             */
                            'branch_id' =>
                                $row
                                    ->branch_id,

                            'branch_name' =>
                                $row
                                    ->branch_name,

                            /*
                             * Subtipo.
                             */
                            'subtype_name' =>
                                $row
                                    ->subtype_name,

                            'subtype_code' =>
                                $row
                                    ->subtype_code,

                            'subtype_is_deferred' =>
                                (bool)
                                $row
                                    ->subtype_is_deferred,

                            /*
                             * Usuario que realmente generó
                             * o registró el movimiento.
                             */
                            'user_name' =>
                                $row
                                    ->user_name,
                        ];
                    }
                )
                ->values();

        return response()->json([
            'data' =>
                $items,

            'meta' => [
                'current_page' =>
                    $paginator
                        ->currentPage(),

                'last_page' =>
                    $paginator
                        ->lastPage(),

                'total' =>
                    $paginator
                        ->total(),

                'from' =>
                    $paginator
                        ->firstItem(),

                'to' =>
                    $paginator
                        ->lastItem(),

                'per_page' =>
                    $paginator
                        ->perPage(),
            ],
        ]);
    }

}

