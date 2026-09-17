<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeletePercentageWorkerRequest;
use App\Http\Requests\StorePercentageWorkerRequest;
use App\Http\Requests\UpdatePercentageWorkerRequest;
use App\PercentageWorker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PercentageWorkerController extends Controller
{
    public function index()
    {
        $porcentages = PercentageWorker::query()
            ->whereIn('name', [
                'assign_family',
                'essalud',
                'rmv',
            ])
            ->orderBy('id')
            ->get();

        return view('percentageWorker.index', compact('porcentages'));
    }


    public function store(StorePercentageWorkerRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $percentageWorker = PercentageWorker::create([
                'name' => $request->get('name'),
                'value' => $request->get('value'),
            ]);

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['message' => 'Porcentaje de recursos humanos guardado con éxito.'], 200);
    }


    public function update(
        UpdatePercentageWorkerRequest $request,
        $id
    ) {
        $percentageWorker = PercentageWorker::query()
            ->whereIn('name', [
                'assign_family',
                'essalud',
                'rmv',
            ])
            ->where('id', $id)
            ->firstOrFail();

        $percentageWorker->value = $request->get('value');
        $percentageWorker->save();

        return redirect()
            ->route('platformPercentageWorker.index')
            ->with(
                'success',
                'Parámetro laboral actualizado correctamente.'
            );
    }


    public function destroy(DeletePercentageWorkerRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $percentageWorker = PercentageWorker::find($request->get('percentage_id'));

            $percentageWorker->delete();

            DB::commit();

        } catch ( \Throwable $e ) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Porcentaje de recursos humanos eliminado con éxito.'], 200);
    }


    public function create()
    {
        return view('percentageWorker.create');
    }

    public function edit($id)
    {
        $percentageWorker = PercentageWorker::query()
            ->whereIn('name', [
                'assign_family',
                'essalud',
                'rmv',
            ])
            ->where('id', $id)
            ->firstOrFail();

        return view('percentageWorker.edit', compact('percentageWorker')
        );
    }


    public function getPercentageWorkers()
    {
        $porcentageQuotes = PercentageWorker::select('id', 'name', 'value') -> get();
        return datatables($porcentageQuotes)->toJson();
        //dd(datatables($customers)->toJson());
    }
}
