<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteMultipleTypeScrapsRequest;
use App\Http\Requests\DeleteTypeScrapRequest;
use App\Http\Requests\StoreTypeScrapRequest;
use App\Http\Requests\UpdateTypeScrapRequest;
use App\Material;
use App\Services\Materials\DeleteTypeScraps;
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

            'data' => [
                'id' => $typeScrap->id,
                'name' => $typeScrap->name,
                'width' => $typeScrap->width,
                'length' => $typeScrap->length,
            ],
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


    public function destroy(DeleteTypeScrapRequest $request)
    {
        $validated = $request->validated();

        try {
            app(DeleteTypeScraps::class)->delete(
                [$validated['typeScrap_id']],
                'typeScrap_id'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'No se pudo eliminar el tipo de retacería.',
            ], 422);
        }

        return response()->json([
            'message' => 'Tipo de retacería eliminado con éxito.',
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


    public function deleteMultiple(DeleteMultipleTypeScrapsRequest $request)
    {
        $validated = $request->validated();

        try {
            $count = app(DeleteTypeScraps::class)
                ->delete($validated['ids']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'No se pudieron eliminar los tipos de retacería.',
            ], 422);
        }

        return response()->json([
            'message' => 'Tipos de retacería eliminados correctamente.',
            'deleted_count' => $count,
        ], 200);
    }

}