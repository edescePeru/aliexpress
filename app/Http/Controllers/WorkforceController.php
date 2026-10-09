<?php

namespace App\Http\Controllers;

use App\UnitMeasure;
use App\Workforce;
use Illuminate\Http\Request;

class WorkforceController extends Controller
{
    /**
     * Vista principal.
     */
    public function index()
    {
        $unitMeasures =
            UnitMeasure::query()
                ->orderBy(
                    'name',
                    'asc'
                )
                ->get();

        return view(
            'workforce.index',
            compact(
                'unitMeasures'
            )
        );
    }


    /**
     * Listado AJAX.
     */
    public function getData(
        Request $request
    ) {
        $perPage = 10;

        $search =
            trim(
                (string)
                $request->input(
                    'search',
                    ''
                )
            );

        $query =
            Workforce::query()
                ->with([
                    'unitMeasure'
                ])
                ->orderBy(
                    'description',
                    'asc'
                );

        if ($search !== '') {

            $query->where(
                function ($q) use (
                    $search
                ) {

                    $q->where(
                        'description',
                        'like',
                        '%' .
                        $search .
                        '%'
                    );

                    $q->orWhereHas(
                        'unitMeasure',
                        function ($unitQuery) use (
                            $search
                        ) {

                            $unitQuery->where(
                                'name',
                                'like',
                                '%' .
                                $search .
                                '%'
                            );
                        }
                    );
                }
            );
        }

        $workforces =
            $query->paginate(
                $perPage
            );

        $html =
            view(
                'workforce.partials.table',
                compact(
                    'workforces'
                )
            )->render();

        return response()->json([
            'html' =>
                $html,

            'current_page' =>
                $workforces
                    ->currentPage(),

            'last_page' =>
                $workforces
                    ->lastPage(),

            'total' =>
                $workforces
                    ->total(),
        ]);
    }


    /**
     * Crear servicio.
     */
    public function store(
        Request $request
    ) {
        $validated =
            $request->validate([
                'description' =>
                    'required|string|max:255',

                'unit_measure_id' =>
                    'required|integer|exists:unit_measures,id',

                'unit_price' =>
                    'required|numeric|min:0',

                'billable' =>
                    'required|boolean',
            ]);

        $workforce =
            Workforce::create([
                'description' =>
                    mb_strtoupper(
                        trim(
                            $validated[
                            'description'
                            ]
                        )
                    ),

                'unit_measure_id' =>
                    $validated[
                    'unit_measure_id'
                    ],

                'unit_price' =>
                    $validated[
                    'unit_price'
                    ],

                'billable' =>
                    $validated[
                    'billable'
                    ],
            ]);

        return response()->json([
            'message' =>
                'Servicio creado correctamente.',

            'workforce' =>
                $workforce,
        ]);
    }


    /**
     * Obtener un registro para editar.
     *
     * BelongsToTenant evita leer registros
     * pertenecientes a otro Tenant.
     */
    public function edit(
        $id
    ) {
        $workforce =
            Workforce::query()
                ->findOrFail(
                    $id
                );

        return response()->json([
            'workforce' => [
                'id' =>
                    $workforce->id,

                'description' =>
                    $workforce->description,

                'unit_measure_id' =>
                    $workforce
                        ->unit_measure_id,

                'unit_price' =>
                    (float)
                    $workforce
                        ->unit_price,

                'billable' =>
                    (bool)
                    $workforce
                        ->billable,
            ],
        ]);
    }


    /**
     * Actualizar.
     */
    public function update(
        Request $request,
        $id
    ) {
        $workforce =
            Workforce::query()
                ->findOrFail(
                    $id
                );

        $validated =
            $request->validate([
                'description' =>
                    'required|string|max:255',

                'unit_measure_id' =>
                    'required|integer|exists:unit_measures,id',

                'unit_price' =>
                    'required|numeric|min:0',

                'billable' =>
                    'required|boolean',
            ]);

        $workforce->update([
            'description' =>
                mb_strtoupper(
                    trim(
                        $validated[
                        'description'
                        ]
                    )
                ),

            'unit_measure_id' =>
                $validated[
                'unit_measure_id'
                ],

            'unit_price' =>
                $validated[
                'unit_price'
                ],

            'billable' =>
                $validated[
                'billable'
                ],
        ]);

        return response()->json([
            'message' =>
                'Servicio actualizado correctamente.',
        ]);
    }


    /**
     * Eliminar.
     */
    public function destroy(
        $id
    ) {
        $workforce =
            Workforce::query()
                ->findOrFail(
                    $id
                );

        $workforce->delete();

        return response()->json([
            'message' =>
                'Servicio eliminado correctamente.',
        ]);
    }
}
