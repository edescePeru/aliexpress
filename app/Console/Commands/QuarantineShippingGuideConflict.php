<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ShippingGuideIdentityConflict;

class QuarantineShippingGuideConflict extends Command
{
    protected $signature = 'gre:quarantine-conflict {guide} {--tenant=} {--company=} {--branch=} {--series=} {--number=} {--customer-document=} {--reason=} {--apply}';
    protected $description = 'Comprueba o aísla una guía local vinculada a otro documento remoto; no realiza llamadas externas.';

    public function handle()
    {
        $identity = [
            'guide_id' => $this->argument('guide'), 'tenant_id' => $this->option('tenant'),
            'company_id' => $this->option('company'), 'branch_id' => $this->option('branch'),
            'serie' => $this->option('series'), 'numero' => $this->option('number'),
            'customer_doc_number' => $this->option('customer-document'),
        ];
        try {
            $guide = ShippingGuideIdentityConflict::inspect($identity);
            $this->line('Guía local '.$guide->id.' / '.$guide->serie.'-'.$guide->numero.' / contexto '.$guide->tenant_id.'-'.$guide->company_id.'-'.$guide->branch_id);
            if (!$this->option('apply')) {
                $this->info('Solo comprobación. Identidad local coincide; no se modificó nada.');
                return 0;
            }
            $changed = ShippingGuideIdentityConflict::quarantine($identity, (string) $this->option('reason'));
            $this->info($changed ? 'Conflicto registrado. Evidencia cifrada conservada y documentos/consulta bloqueados. Nubefact no fue modificado.'
                : 'El conflicto ya estaba registrado. No se duplicó ni reemplazó la evidencia.');
            return 0;
        } catch (\Throwable $error) {
            $this->error('No se aplicó el aislamiento. Revise la identidad, instalación, motivo y disponibilidad del cifrado. No genere otra clave.');
            return 1;
        }
    }
}
