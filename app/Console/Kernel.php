<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('inbound-emails:process')->everyFiveMinutes();

        // LawFirm: Robô Agendador de notificações WhatsApp para prazos jurídicos
        $schedule->command('lawfirm:prazo-notifications')->dailyAt('07:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        // Carrega comandos do package LawFirm
        $this->load(base_path('packages/SuiteZap/LawFirm/src/Whatsapp/Commands'));

        require base_path('routes/console.php');
    }
}
