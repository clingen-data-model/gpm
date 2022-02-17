<?php

namespace App\Console;

use App\Actions\SendCoiReminders;
use Illuminate\Support\Facades\Log;
use App\Actions\SendInviteReminders;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\Dev\NotifyDeployed;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->call(function () {
            if (config('app.test_scheduler')) {
                Log::debug('scheduler is running.');
            }
        })->everyMinute();

        $schedule->job(new SendCoiReminders)
            ->weeklyOn(1, '6:00');
        $schedule->job(new SendInviteReminders)
            ->weeklyOn(1, '6:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
