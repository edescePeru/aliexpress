<?php
namespace App\Services\Sales;

use App\{Material,MaterialPresentation,MaterialPresentationBranch};
use App\Services\Materials\{MaterialInventoryScope,MaterialCompanyCatalog};
use App\Services\SettingService;
use App\Support\TenantContext as Context;
use Illuminate\Support\Facades\{DB,Auth};
use Illuminate\Validation\ValidationException;

class BranchPresentationService
{
    private function fail($message): void
    {
        throw ValidationException::withMessages(['presentation'=>$message]);
    }

    public function manage($request): void
    {
        app(MaterialInventoryScope::class)->snapshot($request);
        abort_unless(Auth::user()->can('managePresentations_material'),403);
    }

    public function material($id, bool $lock=false)
    {
        $query=app(MaterialCompanyCatalog::class)->apply(Material::query())->where('materials.id',$id);
        if($lock)$query->lockForUpdate();
        return $query->firstOrFail();
    }

    public function definition($id, bool $lock=false)
    {
        $query=MaterialPresentation::query()->where('tenant_id',Context::tenantId())->where('id',$id);
        if($lock)$query->lockForUpdate();
        $definition=$query->firstOrFail();
        $this->material($definition->material_id);
        return $definition;
    }

    public function currency(): string
    {
        $currency=(string)app(SettingService::class)->get('finance.base_currency');
        if(!in_array($currency,['PEN','USD'],true))$this->fail('Configure la moneda de venta de la empresa.');
        return $currency;
    }

    public function configurations()
    {
        return MaterialPresentationBranch::query()->where('tenant_id',Context::tenantId())
            ->where('company_id',Context::companyId())->where('branch_id',Context::branchId());
    }

    private function validQuantity($value): bool
    {
        return is_scalar($value)&&preg_match('/^[1-9][0-9]{0,8}$/D',(string)$value)===1;
    }

    private function price($value): string
    {
        if(!is_scalar($value)||!preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D',(string)$value))
            $this->fail('Ingrese un precio no negativo con hasta dos decimales.');
        return bcadd((string)$value,'0',2);
    }

    private function label($value): string
    {
        if(!is_string($value)||preg_match('//u',$value)!==1)
            $this->fail('Ingrese un nombre válido para la presentación.');
        $value=trim($value);
        if($value===''||mb_strlen($value,'UTF-8')>255||preg_match('/[\x00-\x1F\x7F]/u',$value))
            $this->fail('El nombre es obligatorio, admite hasta 255 caracteres y no acepta saltos de línea.');
        return $value;
    }

    private function effectiveLabel($definition): string
    {
        $label=trim((string)$definition->label);
        return $label!==''?$label:'Paquete de '.$definition->quantity;
    }

    public function dto($definition,$configuration): array
    {
        $currency=$this->currency();
        $valid=(bool)$definition->active&&$this->validQuantity($definition->getRawOriginal('quantity'));
        $configured=$configuration&&$configuration->currency===$currency;
        return [
            'id'=>$definition->id,'material_id'=>$definition->material_id,'quantity'=>$definition->quantity,
            'label'=>$this->effectiveLabel($definition),
            'price'=>$configured?$configuration->price:null,
            'active'=>$valid&&$configured&&(bool)$configuration->is_active,
            'definition_active'=>$valid,'configured'=>(bool)$configured,'currency'=>$currency,
        ];
    }

    public function listing($materialId): array
    {
        $material=$this->material($materialId);
        $rows=MaterialPresentation::query()->where('tenant_id',Context::tenantId())->where('material_id',$material->id)
            ->orderBy('quantity')->orderBy('id')->get();
        $config=$this->configurations()->whereIn('material_presentation_id',$rows->pluck('id'))->get()->keyBy('material_presentation_id');
        return [
            'material'=>['id'=>$material->id,'description'=>$material->full_name],
            'branch'=>['id'=>Context::branchId(),'name'=>DB::table('branches')->where('id',Context::branchId())->value('name')],
            'currency'=>$this->currency(),
            'presentations'=>$rows->map(function($p)use($config){return $this->dto($p,$config->get($p->id));})->values(),
        ];
    }

    private function persist($definition,$price,bool $active)
    {
        if(!$definition->active||!$this->validQuantity($definition->getRawOriginal('quantity')))
            $this->fail('La definición está inactiva o tiene una cantidad inválida. Cree otra presentación.');
        $config=$this->configurations()->where('material_presentation_id',$definition->id)->lockForUpdate()->first();
        if(!$config)$config=new MaterialPresentationBranch([
            'tenant_id'=>Context::tenantId(),'company_id'=>Context::companyId(),'branch_id'=>Context::branchId(),
            'material_presentation_id'=>$definition->id,
        ]);
        $config->price=$this->price($price);
        $config->currency=$this->currency();
        $config->is_active=$active;
        $config->save();
        return $this->dto($definition,$config);
    }

    public function create($materialId,array $data): array
    {
        return DB::transaction(function()use($materialId,$data){
            $material=$this->material($materialId,true); // Serializes simultaneous definition creation.
            if(!$this->validQuantity($data['quantity']??null))$this->fail('La cantidad debe ser un entero entre 1 y 999999999.');
            $price=$this->price($data['price']??null);
            $label=array_key_exists('label',$data)?$this->label($data['label']):'Paquete de '.$data['quantity'];
            $definitions=MaterialPresentation::query()->where('tenant_id',Context::tenantId())->where('material_id',$material->id)
                ->where('quantity',$data['quantity'])->orderBy('id')->lockForUpdate()->get();
            if($definitions->count()>1)$this->fail('Hay definiciones antiguas repetidas para esta cantidad. Configure una fila existente.');
            $definition=$definitions->first();
            if($definition&&array_key_exists('label',$data)&&$label!==$this->effectiveLabel($definition))
                $this->fail('Ya existe un paquete con esa cantidad y otro nombre. Use Editar en la fila existente para cambiar su nombre compartido.');
            if(!$definition)$definition=MaterialPresentation::create([
                'tenant_id'=>Context::tenantId(),'material_id'=>$material->id,
                'quantity'=>$data['quantity'],'label'=>$label,'active'=>true,
                'price'=>null, // Legacy shared price is never used for a new branch offering.
            ]);
            return $this->persist($definition,$price,true);
        },3);
    }

    public function update($id,array $data): array
    {
        return DB::transaction(function()use($id,$data){
            $definition=$this->definition($id,true);
            if(!isset($data['quantity'])||!$this->validQuantity($data['quantity'])
                ||(string)(int)$data['quantity']!==(string)(int)$definition->quantity)
                $this->fail('La cantidad de una presentación existente no se cambia. Cree otra y desactive esta en el local.');
            if(array_key_exists('label',$data)){
                $label=$this->label($data['label']);
                if(array_key_exists('label_original',$data)
                    &&(!is_string($data['label_original'])||$data['label_original']!==$this->effectiveLabel($definition)))
                    $this->fail('El nombre de la presentación cambió desde que abrió la fila. Recargue antes de guardar.');
                $definition->label=$label;
                $definition->save();
            }
            $config=$this->configurations()->where('material_presentation_id',$id)->lockForUpdate()->first();
            return $this->persist($definition,$data['price']??null,$config?(bool)$config->is_active:false);
        },3);
    }

    public function toggle($id): array
    {
        return DB::transaction(function()use($id){
            $definition=$this->definition($id,true);
            $config=$this->configurations()->where('material_presentation_id',$id)->lockForUpdate()->first();
            if(!$config||$config->currency!==$this->currency())$this->fail('Guarde primero el precio para este local y moneda.');
            return $this->persist($definition,$config->price,!$config->is_active);
        },3);
    }

    public function available($materialId)
    {
        app(MaterialInventoryScope::class)->context();
        $this->material($materialId);
        return collect($this->listing($materialId)['presentations'])->filter(function($row){return $row['active'];})->values();
    }

    // Caller owns the sale transaction. Definition and local offer remain locked until commit.
    public function forSale($materialId,$id)
    {
        $definition=MaterialPresentation::query()->where('tenant_id',Context::tenantId())->where('material_id',$materialId)
            ->where('id',$id)->lockForUpdate()->first();
        if(!$definition)$this->fail('La presentación no pertenece al producto o grupo empresarial.');
        $config=$this->configurations()->where('material_presentation_id',$id)->lockForUpdate()->first();
        if(!$config||!$this->dto($definition,$config)['active'])
            $this->fail('La presentación no está habilitada con precio y moneda vigentes para este local.');
        return ['definition'=>$definition,'price'=>$this->price($config->price),
            'label'=>$this->effectiveLabel($definition)];
    }
}
