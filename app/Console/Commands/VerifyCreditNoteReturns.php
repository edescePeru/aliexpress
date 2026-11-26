<?php
namespace App\Console\Commands;

use App\Services\Inventory\CreditNoteReturnHistory;
use Illuminate\Console\Command;

class VerifyCreditNoteReturns extends Command
{
    protected $signature = 'inventory:credit-note-returns {--tenant=} {--company=} {--currency=} {--note=*} {--apply}';
    protected $description = 'Diagnostica notas concretas; --apply agrega únicamente evidencia histórica, sin alterar stock ni caja.';

    public function handle()
    {
        foreach (['tenant', 'company'] as $key) {
            $value = $this->option($key);
            if (!is_scalar($value) || !preg_match('/^[1-9][0-9]*$/D', (string) $value) || filter_var($value, FILTER_VALIDATE_INT) === false) {
                $this->error('Indique --tenant y --company explícitamente.'); return 1;
            }
        }
        $currency = strtoupper((string) $this->option('currency'));
        $notes = $this->option('note');
        if (!in_array($currency, ['PEN', 'USD'], true) || !$notes || count($notes) > 100) {
            $this->error('Confirme --currency=PEN|USD y entre 1 y 100 --note=ID concretos. Sin --apply solo se revisa.'); return 1;
        }
        foreach ($notes as $id) {
            if (!preg_match('/^[1-9][0-9]*$/D', (string) $id) || filter_var($id, FILTER_VALIDATE_INT) === false) { $this->error('ID de nota inválido.'); return 1; }
        }
        $notes = array_unique(array_map('intval', $notes)); sort($notes);
        $this->info($this->option('apply') ? 'Aplicación de evidencia histórica. No se modificarán cantidades físicas ni dinero.' : 'DIAGNÓSTICO SIN ESCRITURAS.');
        $failed = false;
        foreach ($notes as $id) {
            try {
                $result = app(CreditNoteReturnHistory::class)->inspect($id, (int) $this->option('tenant'),
                    (int) $this->option('company'), $currency, (bool) $this->option('apply'));
                $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } catch (\DomainException $e) { $failed = true; $this->error('NC #'.$id.': '.$e->getMessage()); }
        }
        return $failed ? 1 : 0;
    }
}
