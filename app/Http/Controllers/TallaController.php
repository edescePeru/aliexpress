<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteTallaRequest;
use App\Http\Requests\StoreTallaRequest;
use App\Http\Requests\UpdateTallaRequest;
use App\Talla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TallaController extends Controller
{
    public function index()
    {
        $user =
            Auth::user();

        $permissions =
            $user
                ->getPermissionsViaRoles()
                ->pluck('name')
                ->toArray();

        return view(
            'talla.index',
            compact(
                'permissions'
            )
        );
    }


    public function create()
    {
        return view(
            'talla.create'
        );
    }


    public function store(
        StoreTallaRequest $request
    ) {
        $validated =
            $request->validated();

        DB::beginTransaction();

        try {

            $talla = Talla::create([
                'name' =>
                    $validated['name'],

                'description' =>
                    $validated['description']
                    ?? null,

                'short_name' =>
                    $validated['short_name']
                    ?? null,
            ]);

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudo registrar la talla.',
            ], 422);
        }

        return response()->json([
            'success' => true,

            'message' =>
                'Talla guardada con éxito.',

            'data' => [
                'id' =>
                    $talla->id,

                'description' =>
                    $talla->name,

                'short_name' =>
                    $talla->short_name,
            ],
        ], 200);
    }


    public function edit($id)
    {
        $talla =
            Talla::findOrFail(
                $id
            );

        return view(
            'talla.edit',
            compact(
                'talla'
            )
        );
    }


    public function update(
        UpdateTallaRequest $request
    ) {
        $validated =
            $request->validated();

        $talla =
            Talla::findOrFail(
                $validated['talla_id']
            );

        DB::beginTransaction();

        try {

            $talla->name =
                $validated['name'];

            $talla->description =
                $validated['description']
                ?? null;

            $talla->short_name =
                $validated['short_name']
                ?? null;

            $talla->save();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo modificar la talla.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Talla modificada con éxito.',

            'url' =>
                route('talla.index'),
        ]);
    }


    public function destroy(
        DeleteTallaRequest $request
    ) {
        $validated =
            $request->validated();

        $talla =
            Talla::findOrFail(
                $validated['talla_id']
            );

        /*
         * Igual que Color:
         * una Talla utilizada por Variant
         * no debe desaparecer.
         */
        if (
        $talla->variants()
            ->exists()
        ) {
            return response()->json([
                'message' =>
                    'No se puede eliminar la talla porque está siendo utilizada por una o más variantes.',
            ], 422);
        }

        DB::beginTransaction();

        try {

            $talla->delete();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo eliminar la talla.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Talla eliminada con éxito.',
        ]);
    }


    public function getTallas(Request $request)
    {
        $perPage =
            (int) $request->get(
                'per_page',
                10
            );

        if (
        !in_array(
            $perPage,
            [10, 25, 50],
            true
        )
        ) {
            $perPage = 10;
        }

        $search =
            trim(
                $request->get(
                    'search',
                    ''
                )
            );

        $query =
            Talla::query()
                ->select(
                    'id',
                    'name',
                    'description',
                    'short_name'
                );

        if ($search !== '') {

            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'name',
                        'like',
                        '%' . $search . '%'
                    )
                        ->orWhere(
                            'short_name',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'description',
                            'like',
                            '%' . $search . '%'
                        );

                }
            );
        }

        $tallas =
            $query
                ->orderBy(
                    'name',
                    'asc'
                )
                ->paginate(
                    $perPage
                );

        return response()->json(
            $tallas
        );
    }


    public function deleteMultiple(
        Request $request
    ) {
        $ids =
            $request->input(
                'ids'
            );

        if (
            !$ids ||
            !is_array($ids)
        ) {
            return response()->json([
                'message' =>
                    'Datos inválidos.',
            ], 400);
        }

        $tallas =
            Talla::query()
                ->whereIn(
                    'id',
                    $ids
                )
                ->get();

        /*
         * Evitamos eliminación parcial.
         */
        foreach (
            $tallas as $talla
        ) {

            if (
            $talla->variants()
                ->exists()
            ) {
                return response()->json([
                    'message' =>
                        'No se pueden eliminar las tallas seleccionadas porque "' .
                        $talla->name .
                        '" está siendo utilizada por una o más variantes.',
                ], 422);
            }
        }

        DB::beginTransaction();

        try {

            foreach (
                $tallas as $talla
            ) {
                $talla->delete();
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudieron eliminar las tallas.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Tallas eliminadas correctamente.',
        ]);
    }
}