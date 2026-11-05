<?php

namespace App\Http\Controllers;

use App\MaterialDetailSetting;
use App\Services\Materials\MaterialParameters;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MaterialDetailSettingController extends Controller
{
    public function index(MaterialParameters $policy)
    {
        $setting=$policy->current();$sections=config('material_details.sections');
        $enabled=$policy->enabled($setting);
        $configurationMissing=$enabled===null;
        if($enabled===null){
            $raw=$setting&&is_array($setting->enabled_sections)?$setting->enabled_sections:[];
            $raw=array_values(array_filter($raw,function($key)use($sections){return is_string($key)&&array_key_exists($key,$sections);}));
            $enabled=$policy->normalize(array_merge(['unit_measure'],$raw));
        }
        $materialParameterVersion=$policy->version($setting);
        return view('materialDetailSetting.index',compact('setting','sections','enabled','configurationMissing','materialParameterVersion'));
    }

    public function store(Request $request,MaterialParameters $policy)
    {
        $data=$request->validate([
            'enabled_sections'=>['required','array','min:1'],
            'enabled_sections.*'=>['required','string','distinct',Rule::in(array_keys(config('material_details.sections')))],
        ]);
        $enabled=$policy->normalize($data['enabled_sections']);
        DB::transaction(function()use($request,$policy,$enabled){
            $setting=$policy->current(true);$policy->snapshot($request,$setting);
            MaterialDetailSetting::updateOrCreate([
                'tenant_id'=>TenantContext::tenantId(),'company_id'=>TenantContext::companyId(),
            ],['enabled_sections'=>$enabled]);
        });
        return redirect()->back()->with('success','Configuración de materiales de esta empresa guardada correctamente.');
    }

}