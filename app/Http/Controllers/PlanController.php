<?php

namespace App\Http\Controllers;

use App\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index()
    {
        return view('plan.index');
    }

    public function data(Request $request)
    {
        $perPage = (int) $request->get(
            'per_page',
            10
        );

        if (!in_array(
            $perPage,
            [10, 20, 50]
        )) {
            $perPage = 10;
        }

        $search = trim(
            (string) $request->get('search')
        );

        $query = Plan::query()
            ->withCount('tenants')
            ->orderBy('id', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use (
                $search
            ) {
                $q->where(
                    'name',
                    'like',
                    '%' . $search . '%'
                )
                    ->orWhere(
                        'code',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }

        $plans = $query->paginate($perPage);

        return response()->json($plans);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                'unique:plans,code',
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'max_active_users' => [
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        DB::transaction(function () use (
            $validated
        ) {
            Plan::create([
                'code' => strtolower(
                    $validated['code']
                ),

                'name' =>
                    $validated['name'],

                'max_active_users' =>
                    $validated[
                    'max_active_users'
                    ],

                'description' =>
                    $validated[
                    'description'
                    ] ?? null,

                'is_active' => true,
            ]);
        });

        return response()->json([
            'message' =>
                'Plan registrado correctamente.',
        ]);
    }

    public function update(Request $request,$id){
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',

                Rule::unique(
                    'plans',
                    'code'
                )->ignore($plan->id),
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'max_active_users' => [
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        DB::transaction(function () use (
            $plan,
            $validated
        ) {
            $plan->update([
                'code' => strtolower(
                    $validated['code']
                ),

                'name' =>
                    $validated['name'],

                'max_active_users' =>
                    $validated[
                    'max_active_users'
                    ],

                'description' =>
                    $validated[
                    'description'
                    ] ?? null,
            ]);
        });

        return response()->json([
            'message' =>
                'Plan actualizado correctamente.',
        ]);
    }

    public function toggleStatus($id)
    {
        $plan = Plan::findOrFail($id);

        if (
            $plan->is_active &&
            $plan->tenants()
                ->where('is_active', true)
                ->exists()
        ) {
            return response()->json([
                'message' =>
                    'No se puede inhabilitar el plan porque tiene tenants activos asociados.',
            ], 422);
        }

        $plan->is_active =
            !$plan->is_active;

        $plan->save();

        return response()->json([
            'message' =>
                $plan->is_active
                    ? 'Plan habilitado correctamente.'
                    : 'Plan inhabilitado correctamente.',
        ]);
    }
}
