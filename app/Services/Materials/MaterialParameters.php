<?php
namespace App\Services\Materials;

use App\MaterialDetailSetting;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaterialParameters
{
    public const FIELDS = [
        'unit_measure'=>'unit_measure_id', 'brand'=>'brand_id', 'exampler'=>'exampler_id',
        'category'=>'category_id', 'subcategory'=>'subcategory_id', 'material_type'=>'material_type_id',
        'subtype'=>'subtype_id', 'typescrap'=>'typescrap_id', 'genero'=>'genero_id', 'perecible'=>'perecible',
    ];

    public function context(): void
    {
        $user=Auth::user();
        abort_unless($user && !$user->isPlatformAdmin(),403,
            'Esta operación requiere el contexto de un grupo empresarial y una empresa. El acceso de soporte de plataforma todavía no está habilitado.');
        $tenant=TenantContext::tenantIdOrNull();$company=TenantContext::companyIdOrNull();
        abort_unless($tenant && $company && (int)$user->tenant_id===(int)$tenant && (bool)$user->enable
            && DB::table('companies')->where('id',$company)->where('tenant_id',$tenant)->where('is_active',true)->exists()
            && DB::table('company_user')->where('company_id',$company)->where('user_id',$user->id)->where('is_active',true)->exists(),
            403,'No tiene un contexto de empresa válido para esta operación.');
    }

    public function current(bool $lock=false)
    {
        $this->context();
        if($lock){DB::table('companies')->where('id',TenantContext::companyId())->where('tenant_id',TenantContext::tenantId())->lockForUpdate()->first();}
        $query=MaterialDetailSetting::query()->where('tenant_id',TenantContext::tenantId())->where('company_id',TenantContext::companyId());
        if($lock){$query->lockForUpdate();}
        return $query->first();
    }

    public function normalize(array $sections): array
    {
        $known=array_keys(config('material_details.sections'));
        foreach($sections as$key){if(!is_string($key)||!in_array($key,$known,true)){
            throw ValidationException::withMessages(['enabled_sections'=>'La configuración contiene una sección inválida.']);
        }}
        if(!in_array('unit_measure',$sections,true)){
            throw ValidationException::withMessages(['enabled_sections'=>'La unidad de medida es obligatoria.']);
        }
        $dependencies=['exampler'=>['brand'],'subcategory'=>['category'],
            'material_type'=>['category','subcategory'],'subtype'=>['category','subcategory','material_type'],
            'talla'=>['color'],'color'=>['talla']];
        foreach($dependencies as$key=>$parents){if(in_array($key,$sections,true)){$sections=array_merge($sections,$parents);}}
        $sections=array_values(array_unique($sections));sort($sections);
        return $sections;
    }

    public function enabled($setting): ?array
    {
        if(!$setting||!is_array($setting->enabled_sections)){return null;}
        try{return $this->normalize($setting->enabled_sections);}catch(ValidationException$e){return null;}
    }

    public function version($setting): string
    {
        return hash('sha256',json_encode([TenantContext::tenantId(),TenantContext::companyId(),
            $setting?$setting->getKey():null,$setting?$setting->enabled_sections:null]));
    }

    public function snapshot($request,$setting): void
    {
        foreach(['material_context_tenant'=>TenantContext::tenantId(),'material_context_company'=>TenantContext::companyId(),
            'material_parameters_version'=>$this->version($setting)]as$field=>$expected){
            if(!is_scalar($request->input($field))||(string)$request->input($field)!==(string)$expected){
                throw ValidationException::withMessages(['context'=>'La empresa o los parámetros cambiaron desde que abrió el formulario. Recargue la página.']);
            }
        }
    }

    public function configured(): array
    {
        $enabled=$this->enabled($this->current());
        if($enabled===null){throw ValidationException::withMessages([
            'configuration'=>'Esta empresa todavía no tiene una configuración válida de materiales. Configure sus parámetros incluyendo la unidad de medida antes de continuar.',
        ]);}
        return $enabled;
    }

    public function stripDisabled($request,array $enabled): void
    {
        $source=$request->isJson()?$request->json():$request->request;
        foreach(array_merge(array_keys(self::FIELDS),['talla','color'])as$field){
            if(!in_array($field,$enabled,true)){$source->remove($field);$request->query->remove($field);}
        }
    }

    public function forWrite($request): array
    {
        // Caller holds a transaction. Serialize against configuration saves for this company.
        $setting=$this->current(true);$this->snapshot($request,$setting);$enabled=$this->enabled($setting);
        if($enabled===null){$this->configured();}
        $this->stripDisabled($request,$enabled);
        return $enabled;
    }

    public function apply($material,$request,array $enabled): void
    {
        foreach(self::FIELDS as$field=>$attribute){
            if(in_array($field,$enabled,true)&&array_key_exists($field,$request->all())){
                $material->$attribute=$request->input($field);
            }
        }
    }

    public function rules($request,array $rules,bool $edit): array
    {
        $enabled=$this->configured();
        foreach(array_merge(array_keys(self::FIELDS),['talla','color'])as$field){
            if(!in_array($field,$enabled,true)){unset($rules[$field],$rules[$field.'.*']);}
        }
        $unit=['required','integer',Rule::exists('unit_measures','id')
            ->where('tenant_id',TenantContext::tenantId())->whereNull('deleted_at')];
        if($edit&&!array_key_exists('unit_measure',$request->all())){array_unshift($unit,'sometimes');}
        $rules['unit_measure']=$unit;
        return $rules;
    }

    public function validateForm($validator,$request,$material=null): void
    {
        $enabled=$this->configured();
        if($material && !$material->unit_measure_id && !array_key_exists('unit_measure',$request->all())){
            $validator->errors()->add('unit_measure','Seleccione una unidad de medida para el producto.');
        }
        $rows=json_decode($request->input('variantes_json','[]'),true);
        if((string)$request->input('tipo_variantes')==='1'&&is_array($rows)
            &&(!in_array('talla',$enabled,true)||!in_array('color',$enabled,true))){
            foreach($rows as$row){if(is_array($row)&&(!$material||empty($row['variant_id']))){
                $validator->errors()->add('variantes_json','Habilite Talla y Color en los parámetros de esta empresa para crear combinaciones nuevas.');break;
            }}
        }
        if(!$material){return;}
        // Validate the effective hierarchy, including unchanged hidden/absent children.
        // Skip unchanged pairs so historical disabled catalogs do not block unrelated edits.
        foreach([['category','subcategory','subcategories','category_id'],
            ['subcategory','material_type','material_types','subcategory_id'],
            ['material_type','subtype','subtypes','material_type_id'],
            ['brand','exampler','examplers','brand_id']]as$pair){
            list($parent,$child,$table,$fk)=$pair;
            $parentAttr=self::FIELDS[$parent];$childAttr=self::FIELDS[$child];
            $parentId=in_array($parent,$enabled,true)&&array_key_exists($parent,$request->all())?$request->input($parent):$material->$parentAttr;
            $childId=in_array($child,$enabled,true)&&array_key_exists($child,$request->all())?$request->input($child):$material->$childAttr;
            if((string)$parentId===(string)$material->$parentAttr&&(string)$childId===(string)$material->$childAttr){continue;}
            if($childId && (!$parentId||!DB::table($table)->where('id',$childId)->where('tenant_id',TenantContext::tenantId())->where($fk,$parentId)->exists())){
                $validator->errors()->add($child,'El cambio es incompatible con la clasificación o modelo que conserva el producto. Revise también los niveles dependientes.');
            }
        }
    }
}
