<?php

namespace App\Http\Controllers;

use App\Material;
use App\MaterialPresentation;
use Illuminate\Http\Request;

class MaterialPresentationController extends Controller
{
    public function index(Material $material)
    {
        $items = MaterialPresentation::where('material_id', $material->id)
            ->orderBy('quantity', 'asc')
            ->get(['id','material_id','quantity','price','active']);

        return response()->json([
            'material' => [
                'id' => $material->id,
                'description' => $material->full_name ?? null,
            ],
            'presentations' => $items,
        ]);
    }

    public function store(Request $request, Material $material)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'price'    => ['required', 'numeric', 'min:0'],
        ]);

        // (Opcional) evitar duplicados exactos por cantidad
        /*$exists = MaterialPresentation::where('material_id', $material->id)
            ->where('quantity', $data['quantity'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe una presentación con esa cantidad.'
            ], 422);
        }*/

        $item = MaterialPresentation::create([
            'material_id' => $material->id,
            'quantity' => $data['quantity'],
            'price' => $data['price'],
        ]);

        $item->refresh(); // ✅ vuelve a consultar la fila y trae defaults (active=1)

        return response()->json([
            'message' => 'Presentación creada.',
            'presentation' => $item->only(['id','material_id','quantity','price', 'active']),
        ]);
    }

    public function update(Request $request, MaterialPresentation $presentation)
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'price'    => ['required', 'numeric', 'min:0'],
        ]);

        // (Opcional) evitar duplicados por cantidad dentro del mismo material
        /*$dup = MaterialPresentation::where('material_id', $presentation->material_id)
            ->where('quantity', $data['quantity'])
            ->where('id', '!=', $presentation->id)
            ->exists();

        if ($dup) {
            return response()->json([
                'message' => 'Otra presentación ya tiene esa cantidad.'
            ], 422);
        }*/

        $presentation->update($data);

        return response()->json([
            'message' => 'Presentación actualizada.',
            'presentation' => $presentation->only(['id','material_id','quantity','price']),
        ]);
    }

    public function destroy(MaterialPresentation $presentation)
    {
        $presentation->delete();

        return response()->json([
            'message' => 'Presentación eliminada.'
        ]);
    }

    public function toggle(MaterialPresentation $presentation)
    {
        $presentation->active = !$presentation->active;
        $presentation->save();

        return response()->json([
            'message' => $presentation->active ? 'Presentación activada.' : 'Presentación desactivada.',
            'presentation' => $presentation->only(['id','active'])
        ]);
    }
}
