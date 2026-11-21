<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class ReadGreProviderDiagnostic extends Command
{
    protected $signature = 'gre:diagnostic {reference} {--tenant=} {--company=} {--branch=} {--show}';
    protected $description = 'Consulta local y de solo lectura de un diagnóstico GRE archivado; no llama al proveedor.';

    public function handle()
    {
        $ref = (string) $this->argument('reference');
        if (!preg_match('/^[a-f0-9]{24}$/D', $ref)) { $this->error('Referencia inválida.'); return 1; }
        foreach (['tenant','company','branch'] as $key) {
            if (!ctype_digit((string) $this->option($key)) || (int) $this->option($key) < 1) { $this->error('Indique contexto completo.'); return 1; }
        }
        try {
            $row = DB::table('shipping_guide_provider_failures')->where('reference',$ref)
                ->where('tenant_id',(int)$this->option('tenant'))->where('company_id',(int)$this->option('company'))
                ->where('branch_id',(int)$this->option('branch'))->first();
            if (!$row) { $this->error('No hay detalle archivado para esa referencia y contexto.'); return 1; }
            $this->line('Guía '.$row->shipping_guide_id.' / '.$row->operation.' / HTTP '.$row->http_status.' / código '.$row->provider_code);
            if ($this->option('show')) {
                $text = Crypt::decryptString($row->detail_encrypted);
                // Salida de consola sin emails, URLs, identificadores largos ni controles ANSI.
                $text = preg_replace('~https?://[^\s"<>]+~i','[URL omitida]',$text);
                $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i','[email omitido]',$text);
                $text = preg_replace('/[0-9]{8,}/','[identificador omitido]',$text);
                $text = preg_replace('/[\x00-\x1f\x7f]/',' ',$text);
                $this->line($text);
            } else { $this->info('Detalle disponible. --show muestra el mensaje saneado en esta consola.'); }
            return 0;
        } catch (\Throwable $error) { $this->error('No se pudo leer el detalle. Revise instalación y cifrado; no genere otra clave.'); return 1; }
    }
}
