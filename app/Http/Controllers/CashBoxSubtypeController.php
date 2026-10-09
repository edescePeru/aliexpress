<?php

namespace App\Http\Controllers;

use App\CashBox;
use App\CashBoxSubtype;
use Illuminate\Http\Request;
use App\Support\TenantContext;

class CashBoxSubtypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware(
            'permission:list_cashBoxSubtype'
        )->only([
            'index',
            'listar',
        ]);

        $this->middleware(
            'permission:create_cashBoxSubtype'
        )->only([
            'store',
        ]);

        $this->middleware(
            'permission:edit_cashBoxSubtype'
        )->only([
            'update',
        ]);

        $this->middleware(
            'permission:disable_cashBoxSubtype'
        )->only([
            'toggle',
        ]);
    }

    public function index()
    {
        $companyId =
            TenantContext::companyId();

        $permissions = [
            'can_create' =>
                auth()
                    ->user()
                    ->can(
                        'create_cashBoxSubtype'
                    ),

            'can_edit' =>
                auth()
                    ->user()
                    ->can(
                        'edit_cashBoxSubtype'
                    ),

            'can_toggle' =>
                auth()
                    ->user()
                    ->can(
                        'disable_cashBoxSubtype'
                    ),
        ];

        /*
         * Solo CashBoxes de la Company actual
         * que realmente pueden utilizar subtipos.
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
                    'uses_subtypes',
                    1
                )
                ->orderBy(
                    'position'
                )
                ->orderBy(
                    'name'
                )
                ->get();

        return view(
            'cashBoxSubtype.index',
            compact(
                'permissions',
                'cashBoxes'
            )
        );
    }

    /**
     * GET /list?q=&cash_box_id=&page=
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

        $cashBoxId =
            $request->get(
                'cash_box_id',
                ''
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

        $query =
            CashBoxSubtype::query()
                ->with([
                    'cashBox',
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

        /*
         * Alcance.
         */
        if (
            $cashBoxId !== '' &&
            $cashBoxId !== null
        ) {

            if (
                $cashBoxId === 'global'
            ) {

                $query->whereNull(
                    'cash_box_id'
                );

            } else {

                $query->where(
                    'cash_box_id',
                    $cashBoxId
                );
            }
        }

        /*
         * Búsqueda.
         */
        if ($q !== '') {

            $query->where(
                function ($w) use ($q) {

                    $w
                        ->where(
                            'code',
                            'like',
                            "%{$q}%"
                        )
                        ->orWhere(
                            'name',
                            'like',
                            "%{$q}%"
                        )
                        ->orWhereHas(
                            'cashBox',
                            function ($cashBoxQuery) use ($q) {

                                $cashBoxQuery->where(
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
                    function ($item) {

                        return [
                            'id' =>
                                $item->id,

                            'cash_box_id' =>
                                $item->cash_box_id,

                            'cash_box_name' =>
                                $item->cashBox
                                    ? $item
                                    ->cashBox
                                    ->name
                                    : null,

                            'scope_label' =>
                                $item->cashBox
                                    ? $item
                                    ->cashBox
                                    ->name
                                    : 'Toda la empresa',

                            'code' =>
                                $item->code,

                            'name' =>
                                $item->name,

                            'position' =>
                                (int)
                                $item
                                    ->position,

                            'is_active' =>
                                (bool)
                                $item
                                    ->is_active,

                            'is_deferred' =>
                                (bool)
                                $item
                                    ->is_deferred,

                            'requires_commission' =>
                                (bool)
                                $item
                                    ->requires_commission,
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

    public function store(Request $request)
    {
        $tenantId =
            TenantContext::tenantId();

        $companyId =
            TenantContext::companyId();

        $data =
            $this->validateSubtype(
                $request
            );

        /*
         * Evitar conflicto global/específico
         * dentro de la misma Company.
         */
        $conflictMsg =
            $this->conflictRuleMessage(
                $data['code'],
                $data['cash_box_id'],
                null,
                $companyId
            );

        if ($conflictMsg) {

            return response()->json([
                'message' =>
                    $conflictMsg,
            ], 422);
        }

        /*
         * Evitar duplicado mismo alcance.
         */
        if (
        $this->existsSameScopeDuplicate(
            $data['code'],
            $data['cash_box_id'],
            null,
            $companyId
        )
        ) {

            return response()->json([
                'message' =>
                    'Ya existe un subtipo con ese código en ese alcance.',
            ], 422);
        }

        $data['tenant_id'] =
            $tenantId;

        $data['company_id'] =
            $companyId;

        $data['is_active'] =
            true;

        $sub =
            CashBoxSubtype::create(
                $data
            );

        return response()->json([
            'message' =>
                'Subtipo creado correctamente.',

            'data' =>
                $sub,
        ], 201);
    }

    public function update(Request $request)
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

        $sub =
            CashBoxSubtype::query()
                ->where(
                    'id',
                    $id
                )
                ->where(
                    'company_id',
                    $companyId
                )
                ->firstOrFail();

        $data =
            $this->validateSubtype(
                $request
            );

        $conflictMsg =
            $this->conflictRuleMessage(
                $data['code'],
                $data['cash_box_id'],
                $sub->id,
                $companyId
            );

        if ($conflictMsg) {

            return response()->json([
                'message' =>
                    $conflictMsg,
            ], 422);
        }

        if (
        $this->existsSameScopeDuplicate(
            $data['code'],
            $data['cash_box_id'],
            $sub->id,
            $companyId
        )
        ) {

            return response()->json([
                'message' =>
                    'Ya existe un subtipo con ese código en ese alcance.',
            ], 422);
        }

        $sub->update(
            $data
        );

        return response()->json([
            'message' =>
                'Subtipo actualizado correctamente.',

            'data' =>
                $sub
                    ->fresh([
                        'cashBox',
                    ]),
        ]);
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

        $sub =
            CashBoxSubtype::query()
                ->where(
                    'id',
                    $id
                )
                ->where(
                    'company_id',
                    $companyId
                )
                ->firstOrFail();

        $sub->is_active =
            !$sub->is_active;

        $sub->save();

        return response()->json([
            'message' =>
                $sub->is_active
                    ? 'Subtipo activado correctamente.'
                    : 'Subtipo desactivado correctamente.',

            'data' =>
                $sub,
        ]);
    }

    private function validateSubtype(
        Request $request
    ): array {

        $companyId =
            TenantContext::companyId();

        $data =
            $request->validate([
                'cash_box_id' =>
                    'nullable',

                'code' =>
                    'required|string|max:50',

                'name' =>
                    'required|string|max:120',

                'position' =>
                    'nullable|integer|min:0',

                'is_deferred' =>
                    'nullable',

                'requires_commission' =>
                    'nullable',
            ]);

        /*
         * ============================================================
         * ALCANCE
         * ============================================================
         */

        if (
            isset(
                $data['cash_box_id']
            )
            &&
            (
                $data['cash_box_id'] === 'global'
                ||
                $data['cash_box_id'] === ''
            )
        ) {

            $data['cash_box_id'] =
                null;
        }

        if (
        !is_null(
            $data['cash_box_id']
        )
        ) {

            $cashBox =
                CashBox::query()
                    ->where(
                        'id',
                        $data['cash_box_id']
                    )
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'is_active',
                        1
                    )
                    ->where(
                        'uses_subtypes',
                        1
                    )
                    ->first();

            if (!$cashBox) {

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cash_box_id' => [
                        'La caja seleccionada no pertenece a la empresa actual, está inactiva o no permite subtipos.'
                    ],
                ]);
            }

            $data['cash_box_id'] =
                $cashBox->id;
        }

        /*
         * ============================================================
         * NORMALIZACIÓN DEL CÓDIGO
         * ============================================================
         */

        $data['code'] =
            strtolower(
                trim(
                    $data['code']
                )
            );

        $data['code'] =
            preg_replace(
                '/\s+/',
                '_',
                $data['code']
            );

        /*
         * Opcionalmente eliminamos caracteres problemáticos.
         */
        $data['code'] =
            preg_replace(
                '/[^a-z0-9_\-]/',
                '',
                $data['code']
            );

        if (
            $data['code'] === ''
        ) {

            throw \Illuminate\Validation\ValidationException::withMessages([
                'code' => [
                    'El código no es válido.'
                ],
            ]);
        }

        /*
         * ============================================================
         * NOMBRE / POSICIÓN
         * ============================================================
         */

        $data['name'] =
            trim(
                $data['name']
            );

        $data['position'] =
            $data['position']
            ?? 0;

        /*
         * ============================================================
         * FLAGS
         * ============================================================
         */

        $data['is_deferred'] =
            $request->boolean(
                'is_deferred'
            );

        $data['requires_commission'] =
            $request->boolean(
                'requires_commission'
            );

        /*
         * Si no es diferido,
         * no puede requerir comisión.
         */
        if (
        !$data['is_deferred']
        ) {

            $data['requires_commission'] =
                false;
        }

        return $data;
    }

    /**
     * Evita ambigüedad:
     * - Si GLOBAL: no puede existir en ninguna caja (cualquier cash_box_id != null)
     * - Si CAJA: no puede existir GLOBAL (cash_box_id null)
     */
    private function conflictRuleMessage(
        string $code,
        $cashBoxId,
        $ignoreId = null,
        $companyId = null
    ): ?string {

        $companyId =
            $companyId
            ?? TenantContext::companyId();

        /*
         * GLOBAL:
         *
         * No puede existir el mismo code
         * como específico en esta Company.
         */
        if (
        is_null(
            $cashBoxId
        )
        ) {

            $query =
                CashBoxSubtype::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'code',
                        $code
                    )
                    ->whereNotNull(
                        'cash_box_id'
                    );

            if ($ignoreId) {

                $query->where(
                    'id',
                    '!=',
                    $ignoreId
                );
            }

            if (
            $query->exists()
            ) {

                return
                    "No se puede crear como global: el código '{$code}' ya existe como subtipo específico de una caja en esta empresa.";
            }

        } else {

            /*
             * ESPECÍFICO:
             *
             * No puede existir el mismo code
             * global en esta Company.
             */

            $query =
                CashBoxSubtype::query()
                    ->where(
                        'company_id',
                        $companyId
                    )
                    ->where(
                        'code',
                        $code
                    )
                    ->whereNull(
                        'cash_box_id'
                    );

            if ($ignoreId) {

                $query->where(
                    'id',
                    '!=',
                    $ignoreId
                );
            }

            if (
            $query->exists()
            ) {

                return
                    "No se puede crear para esta caja: el código '{$code}' ya existe como subtipo global en esta empresa.";
            }
        }

        return null;
    }

    /**
     * Duplicado dentro del mismo alcance:
     * - global vs global
     * - caja X vs caja X
     */
    private function existsSameScopeDuplicate(
        string $code,
        $cashBoxId,
        $ignoreId = null,
        $companyId = null
    ): bool {

        $companyId =
            $companyId
            ?? TenantContext::companyId();

        $query =
            CashBoxSubtype::query()
                ->where(
                    'company_id',
                    $companyId
                )
                ->where(
                    'code',
                    $code
                );

        if (
        is_null(
            $cashBoxId
        )
        ) {

            $query->whereNull(
                'cash_box_id'
            );

        } else {

            $query->where(
                'cash_box_id',
                $cashBoxId
            );
        }

        if ($ignoreId) {

            $query->where(
                'id',
                '!=',
                $ignoreId
            );
        }

        return
            $query->exists();
    }
}
