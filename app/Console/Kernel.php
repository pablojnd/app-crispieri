<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // ...existing code...

        $schedule->command('dashboard:send-report')
            ->dailyAt('23:00')
            ->emailOutputOnFailure(config('mail.dashboard_report.to'));
    }
}
