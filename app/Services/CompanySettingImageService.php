<?php
namespace App\Services;

use App\{SettingDefinition,SettingValue};
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;

class CompanySettingImageService
{
    public const MAX_BYTES = 2097152;
    public const MAX_DIMENSION = 4096;
    public const MAX_PIXELS = 8388608;

    public function authorize(string $permission,$request=null): void
    {
        $user=Auth::user();
        $tenant=TenantContext::tenantIdOrNull();
        $company=TenantContext::companyIdOrNull();
        abort_unless($user && !$user->isPlatformAdmin() && $user->enable
            && $tenant && $company && (int)$user->tenant_id===(int)$tenant
            && $user->can($permission)
            && DB::table('companies')->where('id',$company)->where('tenant_id',$tenant)->where('is_active',true)->exists()
            && DB::table('company_user')->where('company_id',$company)->where('user_id',$user->id)->where('is_active',true)->exists(),
            403,'No tiene autorización para configurar la empresa actual.');
        if($request){
            foreach(['tenant'=>$tenant,'company'=>$company]as$key=>$expected){
                $actual=$request->input('settings_context_'.$key);
                if(!is_scalar($actual)||(string)$actual!==(string)$expected){
                    $this->fail('context','Cambió la empresa desde que abrió esta pantalla. Recargue antes de guardar.');
                }
            }
        }
    }

    public function supports(SettingDefinition $definition): bool
    {
        return $definition->scope==='company' && $definition->value_type==='string' && is_array($definition->options_json)
            && ($definition->options_json['input']??null)==='image';
    }

    public function fail($field,$message): void
    {
        throw ValidationException::withMessages([$field=>$message]);
    }

    public function validateUpload($file,$field): void
    {
        if(!$file instanceof UploadedFile || !$file->isValid())
            $this->fail($field,'La imagen no se pudo subir. Seleccione el archivo nuevamente.');
        if($file->getSize()>self::MAX_BYTES || $file->getSize()<=0)
            $this->fail($field,'La imagen debe pesar como máximo 2 MB.');
        $info=@getimagesize($file->getRealPath());
        if(!$info || !in_array($info[2],[IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_WEBP],true))
            $this->fail($field,'Seleccione una imagen PNG, JPG o WebP válida.');
        if($info[0]<=0 || $info[1]<=0 || $info[0]>self::MAX_DIMENSION || $info[1]>self::MAX_DIMENSION
            || $info[0]*$info[1]>self::MAX_PIXELS)
            $this->fail($field,'La imagen excede el tamaño permitido: máximo 4096 píxeles por lado y 8 megapíxeles.');
    }

    private function base(): string { return public_path('images/logo'); }
    private function prefix(): string
    {
        return 'tenant-'.TenantContext::tenantId().'/company-'.TenantContext::companyId().'/';
    }
    private function contained($path,$base): bool
    {
        $path=str_replace('\\','/',$path);
        $base=rtrim(str_replace('\\','/',$base),'/').'/';
        return strpos($path,$base)===0;
    }

    public function store(SettingDefinition $definition,UploadedFile $file): string
    {
        $field='setting_images.'.$definition->id;
        $this->validateUpload($file,$field);
        if(!$this->supports($definition))$this->fail($field,'Esta configuración no admite imágenes.');
        $relative=$this->prefix().'setting-'.$definition->id.'-'.bin2hex(random_bytes(16)).'.png';
        $directory=$this->base().'/'.$this->prefix();
        if(!is_dir($directory)&&!@mkdir($directory,0755,true)&&!is_dir($directory))
            throw new \RuntimeException('No se pudo preparar la carpeta de logotipos.');
        if(!$this->contained(realpath($directory),realpath($this->base())))
            throw new \RuntimeException('La carpeta de logotipos no es válida.');
        $path=$this->base().'/'.$relative;
        $image=null;
        try {
            $image=Image::make($file->getRealPath())->orientate();
            $image->resize(1200,600,function($constraint){$constraint->aspectRatio();$constraint->upsize();});
            // PNG preserves transparency and matches the existing ticket/PDF image contract.
            $core=$image->getCore();
            if(is_resource($core)||$core instanceof \GdImage){
                imagealphablending($core,false);imagesavealpha($core,true);
                if(!imagepng($core,$path,9))throw new \RuntimeException('No se pudo codificar el logotipo.');
            }else{
                $image->encode('png')->save($path);
            }
            return $relative;
        } catch(\Throwable $e) {
            // This random filename belongs exclusively to this failed encoding.
            if(is_file($path))@unlink($path);
            $this->fail($field,'No se pudo procesar la imagen. Pruebe con otro PNG, JPG o WebP.');
        } finally {
            if($image)$image->destroy();
        }
    }

    public function preview($relative): ?string
    {
        if(!is_string($relative)||$relative===''||strpos($relative,'..')!==false
            ||!preg_match('~^[a-zA-Z0-9_./-]+$~D',$relative)||$relative[0]==='/')return null;
        $base=realpath($this->base());
        $path=realpath($this->base().'/'.$relative);
        if(!$base||!$path||!is_file($path)||!$this->contained($path,$base))return null;
        return asset('images/logo/'.$relative);
    }

    public function discardIfUnreferenced($relative): void
    {
        if(!is_string($relative)||strpos($relative,$this->prefix())!==0
            ||!preg_match('~^tenant-[1-9][0-9]*/company-[1-9][0-9]*/setting-[1-9][0-9]*-[a-f0-9]{32}\.png$~D',$relative))return;
        // Legacy/shared files and files belonging to other companies are never removed.
        if(SettingValue::withoutGlobalScopes()->where('value_text',$relative)->exists())return;
        if($this->preview($relative)!==null){
            if(!@unlink($this->base().'/'.$relative))throw new \RuntimeException('No se pudo retirar un logotipo sin referencias.');
        }
    }
}
