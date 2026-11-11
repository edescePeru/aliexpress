<?php

namespace App\Console\Commands;

use App\Services\Sales\DailyProfitReport;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateResumenDiarioGanancia extends Command
{
    protected $signature = 'calculate:ganancia {--date= : Fecha de venta YYYY-MM-DD. Por defecto ayer en Lima}';
    protected $description = 'Recalcula la ganancia diaria por tenant, empresa y local';

    public function handle(DailyProfitReport $report)
    {
        $date = $this->option('date') ?? Carbon::yesterday('America/Lima')->toDateString();
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) {
            $this->error('Fecha inválida. Usa YYYY-MM-DD.');
            return 1;
        }
        [$year, $month, $day] = array_map('intval', explode('-', $date));
        if (!checkdate($month, $day, $year) || $date > Carbon::today('America/Lima')->toDateString()) {
            $this->error('La fecha debe existir y no puede ser futura.');
            return 1;
        }

        $count = 0;
        $failed = 0;
        foreach ($report->contexts($date) as $context) {
            try {
                $report->recalculate($context, $date);
                $count++;
            } catch (\Throwable $error) {
                report($error);
                $this->error("No se pudo calcular tenant {$context->tenant_id}, empresa {$context->company_id}, local {$context->branch_id}.");
                $failed++;
            }
        }

        $this->info("Fecha {$date}. Resúmenes procesados: {$count}. Fallos: {$failed}.");
        return $failed ? 1 : 0;
    }
}
