<?php
namespace App\Services\Sales;
use App\{MaterialPresentation,StockItem};
use App\PriceList;
use Illuminate\Validation\ValidationException;

class PosLineCalculator
{
    public function fail(string $message): void {throw ValidationException::withMessages(['items'=>$message]);}
    public function decimal($value,bool $positive=false): string
    {
        if(!is_scalar($value)||!preg_match('/^\d{1,12}(?:\.\d{1,4})?$/D',(string)$value))$this->fail('Cantidad o precio inválido; admite hasta cuatro decimales.');
        $value=bcadd((string)$value,'0',4);
        if($positive&&bccomp($value,'0',4)<=0)$this->fail('La cantidad debe ser mayor que cero.');
        return $value;
    }
    public function cents(string $value): string {return bcadd($value,'0.005',2);}
    public function tax($tax): array
    {
        if(!$tax||!$tax->is_active)$this->fail('El producto debe tener un impuesto activo configurado.');
        $rate=$this->decimal($tax->tax);
        if(bccomp($rate,'100',4)>0)$this->fail('Porcentaje de impuesto inválido.');
        $code=strtoupper(trim((string)$tax->code));$name=strtolower((string)$tax->name);
        if(bccomp($rate,'0',4)>0){
            if(in_array($code,['20','30'],true))$this->fail('La afectación de impuesto no coincide con su porcentaje.');
            return ['kind'=>'gravada','rate'=>$rate,'provider'=>1];
        }
        if($code==='30'||strpos($code,'INAF')!==false||strpos($name,'inafect')!==false)return ['kind'=>'inafecta','rate'=>$rate,'provider'=>9];
        if($code==='20'||strpos($code,'EXO')!==false||strpos($name,'exoner')!==false)return ['kind'=>'exonerada','rate'=>$rate,'provider'=>8];
        $this->fail('Debe identificar si el impuesto de tasa cero es exonerado o inafecto.');
    }
    public function line(array $row,StockItem $item,PriceList $list): array
    {
        if(!isset($row['materialId'])||(string)$row['materialId']!==(string)$item->material_id)$this->fail('El StockItem no corresponde al producto padre.');
        $snapshot=app(PosSalePriceReference::class)->snapshot($item,$list);
        $quantity=$this->decimal($row['productQuantity']??null,true);
        $presentation=null;$presentationLabel=null;$units=$quantity;$perPack=null;$packs=null;$price=$this->decimal($snapshot['catalog_unit_price']);
        if(!empty($row['presentationId'])){
            $id=$row['presentationId'];
            if(!is_scalar($id)||!preg_match('/^[1-9][0-9]*$/D',(string)$id))$this->fail('Presentación inválida.');
            $offering=app(BranchPresentationService::class)->forSale($item->material_id,$id);
            $presentation=$offering['definition'];$presentationLabel=$offering['label'];
            if(bccomp($quantity,bcadd($quantity,'0',0),4)!==0)$this->fail('Los paquetes deben ser enteros.');
            $perPack=$this->decimal($presentation->quantity,true);$packs=$quantity;
            if(bccomp($perPack,bcadd($perPack,'0',0),4)!==0)$this->fail('Unidades de paquete inválidas.');
            $units=bcmul($quantity,$perPack,4);$price=$this->decimal($offering['price']);
        }
        if((int)$item->material->tipo_venta_id!==2&&bccomp($units,bcadd($units,'0',0),4)!==0)$this->fail('Este producto requiere unidades enteras.');
        if(array_key_exists('unitsEquivalent',$row)&&bccomp($this->decimal($row['unitsEquivalent']),$units,4)!==0)$this->fail('Las unidades equivalentes no coinciden con la presentación.');
        if(array_key_exists('productDiscount',$row)&&bccomp($this->decimal($row['productDiscount']),'0',4)!==0)$this->fail('Los descuentos están deshabilitados.');
        foreach(['productPrice','priceEffective']as$field){
            if(array_key_exists($field,$row)&&bccomp($this->decimal($row[$field]),$price,4)!==0)$this->fail('El precio cambió o no coincide con el configurado. Actualice el producto del carrito.');
        }
        $tax=$this->tax($item->material->typeTax);
        if(array_key_exists('productTax',$row)&&bccomp($this->decimal($row['productTax']),$tax['rate'],4)!==0)$this->fail('El impuesto cambió desde que agregó el producto. Actualice el carrito.');
        $gross=$this->cents(bcmul($price,$quantity,8));
        if(bccomp($gross,'999999999999.99',2)>0)$this->fail('El importe de la línea supera el límite permitido.');
        $factor=bcadd('1',bcdiv($tax['rate'],'100',8),8);
        $net=$tax['kind']==='gravada'?$this->cents(bcdiv($gross,$factor,10)):$gross;
        $taxAmount=bcsub($gross,$net,2);
        return compact('row','item','snapshot','quantity','presentation','presentationLabel','units','perPack','packs','price','tax','gross','net','taxAmount','factor');
    }
    public function totals(array $lines): array
    {
        $sum=['gravada'=>'0.00','exonerada'=>'0.00','inafecta'=>'0.00','igv'=>'0.00','total'=>'0.00'];
        foreach($lines as$line){
            $kind=$line['tax']['kind'];$sum[$kind]=bcadd($sum[$kind],$line['net'],2);
            $sum['igv']=bcadd($sum['igv'],$line['taxAmount'],2);$sum['total']=bcadd($sum['total'],$line['gross'],2);
        }
        if(bccomp($sum['total'],'0',2)<=0)$this->fail('La venta debe tener un importe total mayor que cero.');
        if(bccomp($sum['total'],'999999999999.99',2)>0)$this->fail('El importe total supera el límite permitido.');
        return $sum;
    }
}
