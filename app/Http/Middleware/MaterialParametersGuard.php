<?php
namespace App\Http\Middleware;

use App\Services\Materials\MaterialParameters;

class MaterialParametersGuard
{
    public function handle($request,\Closure $next,string $mode='material')
    {
        $policy=app(MaterialParameters::class);$setting=$policy->current();
        if(!in_array($request->method(),['GET','HEAD'],true)){$policy->snapshot($request,$setting);}
        if($mode==='material'){
            $enabled=$policy->enabled($setting);
            if($enabled===null){
                if($request->isMethod('GET')){
                    return response()->view('materialDetailSetting.required',[],409);
                }
                $policy->configured();
            }
            if(!in_array($request->method(),['GET','HEAD'],true)){$policy->stripDisabled($request,$enabled);}
        }
        return $next($request);
    }
}
