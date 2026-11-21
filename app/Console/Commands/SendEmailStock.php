<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;

class SendEmailStock extends Command
{
    protected $signature = 'stocks:send';
    protected $description = 'Comando legacy de alertas retirado; no genera archivos ni envía correo.';
    public function handle()
    {
        $this->info('FOLLOW-01: alertas antiguas retiradas. No se consultaron materiales ni se envió correo.');
        return 0;
    }
}
