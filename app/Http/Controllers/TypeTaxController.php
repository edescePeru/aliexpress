<?php

namespace App\Http\Controllers;

use App\TypeTax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TypeTaxController extends Controller
{
    public function index()
    {
        $typeTaxes = TypeTax::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view(
            'typeTax.index',
            compact('typeTaxes')
        );
    }

    public function store(Request $request)
    {
        /*
         * El code es una clave técnica.
         */
        $request->merge([
            'code' => strtoupper(
                trim(
                    $request->get('code', '')
                )
            ),
        ]);

        $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_]+$/',
                'unique:type_taxes,code',
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'tax' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'is_default' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $isDefault =
            (bool) $request->is_default;

        $isActive =
            (bool) $request->is_active;

        /*
         * Una lista/default fiscal no puede ser inactiva.
         */
        if (
            $isDefault &&
            !$isActive
        ) {
            return response()->json([
                'message' =>
                    'El tipo de impuesto predeterminado debe estar activo.',
            ], 422);
        }

        $typeTax = DB::transaction(
            function () use (
                $request,
                $isDefault,
                $isActive
            ) {

                /*
                 * Si se convierte en default,
                 * quitamos el default anterior.
                 */
                if ($isDefault) {
                    TypeTax::query()
                        ->where(
                            'is_default',
                            true
                        )
                        ->update([
                            'is_default' => false,
                        ]);
                }

                return TypeTax::create([
                    'code' =>
                        $request->code,

                    'name' =>
                        trim(
                            $request->name
                        ),

                    'tax' =>
                        $request->tax,

                    'is_default' =>
                        $isDefault,

                    'is_active' =>
                        $isActive,
                ]);
            }
        );

        return response()->json([
            'message' =>
                'Tipo de impuesto creado correctamente.',

            'data' =>
                $typeTax,
        ], 201);
    }

    public function update(
        Request $request,
        $id
    ) {
        $typeTax = TypeTax::query()
            ->where('id', $id)
            ->firstOrFail();

        /*
         * code NO se modifica.
         *
         * Es una clave técnica estable.
         */
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'tax' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'is_default' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $isDefault =
            (bool) $request->is_default;

        $isActive =
            (bool) $request->is_active;

        /*
         * No permitir:
         *
         * default = sí
         * active = no
         */
        if (
            $isDefault &&
            !$isActive
        ) {
            return response()->json([
                'message' =>
                    'El tipo de impuesto predeterminado debe estar activo.',
            ], 422);
        }

        /*
         * Tampoco permitimos quitar/desactivar
         * directamente el default actual.
         *
         * Primero debe elegirse otro default.
         */
        if (
            $typeTax->is_default &&
            (
                !$isDefault ||
                !$isActive
            )
        ) {
            return response()->json([
                'message' =>
                    'No puede desactivar o quitar como predeterminado este impuesto. Seleccione primero otro impuesto como predeterminado.',
            ], 422);
        }

        DB::transaction(
            function () use (
                $request,
                $typeTax,
                $isDefault,
                $isActive
            ) {

                if ($isDefault) {
                    TypeTax::query()
                        ->where(
                            'id',
                            '<>',
                            $typeTax->id
                        )
                        ->where(
                            'is_default',
                            true
                        )
                        ->update([
                            'is_default' => false,
                        ]);
                }

                $typeTax->update([
                    'name' =>
                        trim(
                            $request->name
                        ),

                    'tax' =>
                        $request->tax,

                    'is_default' =>
                        $isDefault,

                    'is_active' =>
                        $isActive,
                ]);
            }
        );

        return response()->json([
            'message' =>
                'Tipo de impuesto actualizado correctamente.',
        ]);
    }
}