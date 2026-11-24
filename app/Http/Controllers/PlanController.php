<?php
namespace App\Http\Controllers;

use App\Plan;
use App\Services\PlatformAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function __construct() { $this->middleware(['auth', 'platform.admin']); }

    public function index() { return view('plan.index'); }

    public function data(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        if (!in_array($perPage, [10, 20, 50], true)) { $perPage = 10; }
        $search = trim((string) $request->get('search'));
        $query = Plan::query()->withCount('tenants')->orderByDesc('id');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%');
            });
        }
        return response()->json($query->paginate($perPage));
    }

    private function validatePlan(Request $request, $id = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('plans', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:100'],
            'max_active_users' => ['required', 'integer', 'min:1', 'max:10000'],
            'max_active_companies' => ['required', 'integer', 'min:1', 'max:10000'],
            'max_active_branches' => ['required', 'integer', 'min:1', 'max:10000'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function values(Plan $plan): array
    {
        return $plan->only(['code', 'name', 'max_active_users', 'max_active_companies',
            'max_active_branches', 'description', 'is_active']);
    }

    public function store(Request $request, PlatformAuditService $auditService)
    {
        $validated = $this->validatePlan($request);
        $validated['code'] = strtolower($validated['code']);
        DB::transaction(function () use ($validated, $auditService) {
            $plan = Plan::create($validated + ['is_active' => true]);
            $auditService->log('plan.created', $plan, ['new' => ['id' => $plan->id] + $this->values($plan)]);
        });
        return response()->json(['message' => 'Plan registrado correctamente.']);
    }

    public function update(Request $request, $id, PlatformAuditService $auditService)
    {
        Plan::findOrFail($id);
        $validated = $this->validatePlan($request, $id);
        $validated['code'] = strtolower($validated['code']);
        DB::transaction(function () use ($id, $validated, $auditService) {
            // Quota consumers hold a shared lock on this row until commit.
            $plan = Plan::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $old = $this->values($plan);
            $plan->fill($validated)->save();
            $new = $this->values($plan);
            if ($old !== $new) { $auditService->log('plan.updated', $plan, ['old' => $old, 'new' => $new]); }
        });
        return response()->json(['message' => 'Plan actualizado correctamente. Los registros existentes se conservan; los nuevos cupos se aplican a las altas y reactivaciones.']);
    }

    public function toggleStatus($id, PlatformAuditService $auditService)
    {
        $active = DB::transaction(function () use ($id, $auditService) {
            $plan = Plan::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($plan->is_active && $plan->tenants()->where('is_active', true)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'plan' => 'No se puede inhabilitar el plan porque tiene tenants activos asociados.'
                ]);
            }
            $old = (bool) $plan->is_active;
            $plan->is_active = !$old;
            $plan->save();
            $auditService->log('plan.status_changed', $plan,
                ['old' => ['is_active' => $old], 'new' => ['is_active' => (bool) $plan->is_active]]);
            return (bool) $plan->is_active;
        });
        return response()->json(['message' => $active ? 'Plan habilitado correctamente.' : 'Plan inhabilitado correctamente.']);
    }
}
