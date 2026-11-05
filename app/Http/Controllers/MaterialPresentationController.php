<?php
namespace App\Http\Controllers;

use App\{Material,MaterialPresentation};
use App\Services\Sales\BranchPresentationService;
use Illuminate\Http\Request;

class MaterialPresentationController extends Controller
{
    // Existing quotation clients consume this read URL. They do not need maintenance permission.
    public function catalog(Request $request,Material $material)
    {
        app(\App\Services\Materials\MaterialInventoryScope::class)->context();
        $user=\Illuminate\Support\Facades\Auth::user();
        abort_unless($user->can('managePresentations_material')||$user->can('create_quoteSale')
            ||$user->can('edit_quoteSale')||$user->can('create_puntoVenta')||$user->can('createV2_puntoVenta'),403);
        $service=app(BranchPresentationService::class);
        $service->material($material->id);
        return response()->json(['material'=>['id'=>$material->id,'description'=>$material->full_name],
            'presentations'=>$service->available($material->id)]);
    }
    public function index(Request $request,Material $material)
    {
        $service=app(BranchPresentationService::class);$service->manage($request);
        return response()->json($service->listing($material->id));
    }
    public function store(Request $request,Material $material)
    {
        $service=app(BranchPresentationService::class);$service->manage($request);
        $request->validate(['label'=>'required|string|max:255']);
        $row=$service->create($material->id,$request->only(['quantity','price','label']));
        return response()->json(['message'=>'Presentación habilitada con su precio en este local.','presentation'=>$row]);
    }
    public function update(Request $request,MaterialPresentation $presentation)
    {
        $service=app(BranchPresentationService::class);$service->manage($request);
        $request->validate(['label'=>'required|string|max:255','label_original'=>'required|string|max:255']);
        $row=$service->update($presentation->id,$request->only(['quantity','price','label','label_original']));
        return response()->json(['message'=>'Nombre compartido y precio del local guardados.','presentation'=>$row]);
    }
    public function toggle(Request $request,MaterialPresentation $presentation)
    {
        $service=app(BranchPresentationService::class);$service->manage($request);
        $row=$service->toggle($presentation->id);
        return response()->json(['message'=>$row['active']?'Presentación activada en este local.':'Presentación desactivada en este local.','presentation'=>$row]);
    }
    public function destroy(Request $request,MaterialPresentation $presentation)
    {
        $service=app(BranchPresentationService::class);$service->manage($request);
        $service->definition($presentation->id);
        throw \Illuminate\Validation\ValidationException::withMessages([
            'presentation'=>'Las presentaciones se conservan para el historial. Desactívela en el local.',
        ]);
    }
}
