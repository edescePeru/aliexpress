<?php

namespace App\Http\Controllers;

use App\Color;
use App\Http\Requests\DeleteColorRequest;
use App\Http\Requests\StoreColorRequest;
use App\Http\Requests\UpdateColorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ColorController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $permissions = $user
            ->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        return view(
            'color.index',
            compact(
                'permissions'
            )
        );
    }


    public function create()
    {
        return view(
            'color.create'
        );
    }


    public function store(
        StoreColorRequest $request
    ) {
        $validated =
            $request->validated();

        DB::beginTransaction();

        try {

            $color = Color::create([
                'name' =>
                    $validated['name'],

                'code' =>
                    $validated['code']
                    ?? null,

                'short_name' =>
                    $validated['short_name'],
            ]);

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudo registrar el color.',
            ], 422);
        }

        return response()->json([
            'success' => true,

            'message' =>
                'Color guardado con éxito.',

            'data' => [
                'id' =>
                    $color->id,

                'description' =>
                    $color->name,

                'short_name' =>
                    $color->short_name,
            ],
        ], 200);
    }


    public function edit($id)
    {
        $color =
            Color::findOrFail(
                $id
            );

        return view(
            'color.edit',
            compact(
                'color'
            )
        );
    }


    public function update(
        UpdateColorRequest $request
    ) {
        $validated =
            $request->validated();

        $color =
            Color::findOrFail(
                $validated['color_id']
            );

        DB::beginTransaction();

        try {

            $color->name =
                $validated['name'];

            $color->code =
                $validated['code']
                ?? null;

            $color->short_name =
                $validated['short_name'];

            $color->save();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo modificar el color.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Color modificado con éxito.',

            'url' =>
                route('color.index'),
        ]);
    }


    public function destroy(
        DeleteColorRequest $request
    ) {
        $validated =
            $request->validated();

        $color =
            Color::findOrFail(
                $validated['color_id']
            );

        /*
         * No permitir eliminar colores
         * que estén siendo utilizados
         * por variantes.
         */
        if (
        $color->variants()
            ->exists()
        ) {
            return response()->json([
                'message' =>
                    'No se puede eliminar el color porque está siendo utilizado por una o más variantes.',
            ], 422);
        }

        DB::beginTransaction();

        try {

            $color->delete();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo eliminar el color.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Color eliminado con éxito.',
        ]);
    }


    public function getColors(Request $request)
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
            Color::query()
                ->select(
                    'id',
                    'name',
                    'code',
                    'short_name'
                );


        if ($search !== '') {

            $query->where(
                function ($q) use (
                    $search
                ) {

                    $q->where(
                        'name',
                        'like',
                        '%' .
                        $search .
                        '%'
                    )
                        ->orWhere(
                            'code',
                            'like',
                            '%' .
                            $search .
                            '%'
                        )
                        ->orWhere(
                            'short_name',
                            'like',
                            '%' .
                            $search .
                            '%'
                        );

                }
            );

        }


        $colors =
            $query
                ->orderBy(
                    'name',
                    'asc'
                )
                ->paginate(
                    $perPage
                );


        return response()->json(
            $colors
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

        $colors =
            Color::query()
                ->whereIn(
                    'id',
                    $ids
                )
                ->get();

        /*
         * Si cualquiera está siendo utilizado,
         * cancelamos toda la eliminación.
         */
        foreach (
            $colors as $color
        ) {

            if (
            $color->variants()
                ->exists()
            ) {

                return response()->json([
                    'message' =>
                        'No se pueden eliminar los colores seleccionados porque el color "' .
                        $color->name .
                        '" está siendo utilizado por una o más variantes.',
                ], 422);
            }
        }

        DB::beginTransaction();

        try {

            foreach (
                $colors as $color
            ) {
                $color->delete();
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudieron eliminar los colores.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Colores eliminados correctamente.',
        ]);
    }
}