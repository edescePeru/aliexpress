<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteTypeScrapRequest;
use App\Http\Requests\StoreTypeScrapRequest;
use App\Http\Requests\UpdateTypeScrapRequest;
use App\Typescrap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TypescrapController extends Controller
{
    public function index()
    {
        $typescraps =
            Typescrap::all();

        $user =
            Auth::user();

        $permissions =
            $user
                ->getPermissionsViaRoles()
                ->pluck('name')
                ->toArray();

        return view(
            'typeScrap.index',
            compact(
                'typescraps',
                'permissions'
            )
        );
    }


    public function store(
        StoreTypeScrapRequest $request
    ) {
        $validated =
            $request->validated();

        DB::beginTransaction();

        try {

            $typeScrap =
                Typescrap::create([
                    'name' =>
                        $validated['name'],

                    'width' =>
                        $validated['width'],

                    'length' =>
                        $validated['length'],
                ]);

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo registrar el tipo de retacería.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Tipo de retacería guardado con éxito.',
        ], 200);
    }


    public function update(
        UpdateTypeScrapRequest $request
    ) {
        $validated =
            $request->validated();

        $typeScrap =
            Typescrap::findOrFail(
                $validated[
                'typeScrap_id'
                ]
            );

        DB::beginTransaction();

        try {

            $typeScrap->name =
                $validated['name'];

            $typeScrap->width =
                $validated['width'];

            $typeScrap->length =
                $validated['length'];

            $typeScrap->save();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo modificar el tipo de retacería.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Tipo de retacería modificado con éxito.',

            'url' =>
                route(
                    'typescrap.index'
                ),
        ], 200);
    }


    public function destroy(
        DeleteTypeScrapRequest $request
    ) {
        $validated =
            $request->validated();

        $typeScrap =
            Typescrap::findOrFail(
                $validated[
                'typeScrap_id'
                ]
            );

        /*
         * Evitamos eliminar un catálogo
         * que todavía esté asociado a materiales.
         */
        if (
        $typeScrap->materials()
            ->exists()
        ) {
            return response()->json([
                'message' =>
                    'No se puede eliminar el tipo de retacería porque está siendo utilizado por uno o más materiales.',
            ], 422);
        }

        DB::beginTransaction();

        try {

            $typeScrap->delete();

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudo eliminar el tipo de retacería.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Tipo de retacería eliminado con éxito.',
        ], 200);
    }


    public function create()
    {
        return view(
            'typeScrap.create'
        );
    }


    public function edit($id)
    {
        $typeScrap =
            Typescrap::findOrFail(
                $id
            );

        return view(
            'typeScrap.edit',
            compact(
                'typeScrap'
            )
        );
    }


    public function getTypeScraps()
    {
        $typescraps =
            Typescrap::query()
                ->select(
                    'id',
                    'name',
                    'length',
                    'width'
                )
                ->orderBy(
                    'name',
                    'asc'
                )
                ->get();

        return datatables(
            $typescraps
        )->toJson();
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

        $typescraps =
            Typescrap::query()
                ->whereIn(
                    'id',
                    $ids
                )
                ->get();

        /*
         * No hacemos eliminación parcial.
         */
        foreach (
            $typescraps as $typeScrap
        ) {

            if (
            $typeScrap->materials()
                ->exists()
            ) {
                return response()->json([
                    'message' =>
                        'No se pueden eliminar los tipos seleccionados porque "' .
                        $typeScrap->name .
                        '" está siendo utilizado por uno o más materiales.',
                ], 422);
            }
        }

        DB::beginTransaction();

        try {

            foreach (
                $typescraps as $typeScrap
            ) {
                $typeScrap->delete();
            }

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'message' =>
                    'No se pudieron eliminar los tipos de retacería.',
            ], 422);
        }

        return response()->json([
            'message' =>
                'Tipos de retacería eliminados correctamente.',
        ]);
    }
}