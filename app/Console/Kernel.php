<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('fetch:ltp')
            ->everyMinute()
            ->timezone('Asia/Dhaka')
            ->withoutOverlapping(5)   // lock expires after 5 min in case of crash
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/fetch-ltp.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
