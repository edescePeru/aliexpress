<?php
namespace App\Http\Controllers;

use App\SettingDefinition;
use App\Services\{SettingService,CompanySettingImageService};
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanySettingController extends Controller
{
    private function definitions()
    {
        return SettingDefinition::query()->where('scope','company')->where('editable_by_owner',true)
            ->where('is_active',true)->orderBy('module')->orderBy('label')->get();
    }

    public function index()
    {
        $images=app(CompanySettingImageService::class);
        $images->authorize('list_companySetting');
        $definitions=$this->definitions();
        $settings=app(SettingService::class);
        foreach($definitions as$definition){
            $definition->current_value=$settings->get($definition->key);
            $definition->is_image_upload=$images->supports($definition);
            $definition->image_preview_url=$definition->is_image_upload?$images->preview($definition->current_value):null;
        }
        $modules=$definitions->groupBy('module');
        return view('companySetting.index',compact('modules'));
    }

    public function update(Request $request)
    {
        $images=app(CompanySettingImageService::class);
        $images->authorize('edit_companySetting',$request);
        $values=$request->input('settings',[]);
        $uploads=$request->allFiles()['setting_images']??[];
        if(!is_array($values)||!is_array($uploads)||$request->input('setting_images')!==null)
            $images->fail('settings','Los valores o archivos enviados no son válidos.');
        // Symfony represents an untouched file input as null inside its FileBag.
        $uploads=array_filter($uploads,function($file){return $file!==null;});
        $definitions=$this->definitions();
        $byKey=$definitions->keyBy('key');$byId=$definitions->keyBy('id');
        foreach($values as$key=>$value){
            if($byKey->has($key)&&$images->supports($byKey->get($key)))
                $images->fail('settings.'.$key,'Use el selector de imagen para reemplazar el logotipo.');
        }
        foreach($uploads as$id=>$file){
            if(!preg_match('/^[1-9][0-9]*$/D',(string)$id)||!$byId->has($id)||!$images->supports($byId->get($id)))
                $images->fail('setting_images','Una imagen no corresponde a una configuración editable de esta empresa.');
            $images->validateUpload($file,'setting_images.'.$id);
        }
        $settings=app(SettingService::class);
        $created=[];$previous=[];
        try {
            DB::transaction(function()use($values,$uploads,$byKey,$byId,$settings,$images,&$created,&$previous){
                // Serialize updates for this company, including first overrides with nullable branch_id.
                $company=DB::table('companies')->where('id',TenantContext::companyId())
                    ->where('tenant_id',TenantContext::tenantId())->where('is_active',true)->lockForUpdate()->first();
                abort_unless($company,403);
                foreach($values as$key=>$value){
                    if(!$byKey->has($key))continue; // Preserve existing behavior for unknown/noneditable keys.
                    try {$settings->set($key,$value);}
                    catch(\RuntimeException $e){$images->fail('settings.'.$key,'Revise el valor de '.$byKey->get($key)->label.'.');}
                }
                foreach($uploads as$id=>$file){
                    $definition=$byId->get($id);
                    $previous[]=$settings->get($definition->key);
                    $path=$images->store($definition,$file);
                    $created[]=$path;
                    $settings->set($definition->key,$path);
                }
            });
        } catch(\Throwable $e) {
            foreach($created as$path){
                try {$images->discardIfUnreferenced($path);}catch(\Throwable $cleanup){report($cleanup);}
            }
            if($e instanceof ValidationException || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface)throw $e;
            report($e);
            return response()->json(['success'=>false,'message'=>'No se pudieron guardar las configuraciones. Intente nuevamente.'],422);
        }
        foreach($previous as$path){
            try {$images->discardIfUnreferenced($path);}catch(\Throwable $cleanup){report($cleanup);}
        }
        $previews=[];
        foreach($definitions as$definition){
            if($images->supports($definition))$previews[$definition->id]=['url'=>$images->preview($settings->get($definition->key))];
        }
        return response()->json(['success'=>true,'message'=>'Configuraciones actualizadas correctamente.','images'=>$previews]);
    }
}
