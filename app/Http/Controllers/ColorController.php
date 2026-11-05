<?php

namespace App\Http\Controllers;

use App\Color;
use App\Http\Requests\DeleteColorRequest;
use App\Http\Requests\DeleteMultipleColorsRequest;
use App\Http\Requests\StoreColorRequest;
use App\Http\Requests\UpdateColorRequest;
use App\Services\Materials\DeleteColors;
use App\Variant;
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


    public function destroy(DeleteColorRequest $request)
    {
        $validated = $request->validated();

        try {
            app(DeleteColors::class)->delete(
                [$validated['color_id']],
                'color_id'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'No se pudo eliminar el color.',
            ], 422);
        }

        return response()->json([
            'message' => 'Color eliminado con éxito.',
        ], 200);
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


    public function deleteMultiple(DeleteMultipleColorsRequest $request)
    {
        $validated = $request->validated();

        try {
            $count = app(DeleteColors::class)
                ->delete($validated['ids']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'No se pudieron eliminar los colores.',
            ], 422);
        }

        return response()->json([
            'message' => 'Colores eliminados correctamente.',
            'deleted_count' => $count,
        ], 200);
    }

}