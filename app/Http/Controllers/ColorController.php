<?php

namespace App\Http\Controllers;

use App\Color;
use App\Http\Requests\StoreColorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ColorController extends Controller
{
    public function store(StoreColorRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $color = Color::create([
                'name' => $request->get('name'),
                'code' => $request->get('code'),
                'short_name' => $request->get('short_name'),
            ]);

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
        return response()->json([
            'success' => true,
            'message' => 'Color guardado con éxito.',
            'data' => [
                'id' => $color->id,
                'description' => $color->name
            ]
        ], 200);
    }
}
