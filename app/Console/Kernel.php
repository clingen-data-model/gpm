<?php

namespace App\Console;

use App\Actions\SendCoiReminders;
use Illuminate\Support\Facades\Log;
use App\Actions\SendInviteReminders;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\Dev\NotifyDeployed;
use App\DataExchange\Actions\DxConsume;
use App\Modules\Group\Actions\ApplicationSubmissionRemindChairs;
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

        $schedule->call(function () {
            if (!config('dx.consume')) {
                return;
            }
            $consumeDxMessages = app()->make(DxConsume::class);
            $consumeDxMessages->handle(array_values(config('dx.topics.incoming')));
        })->hourly();

        $schedule->job(new SendCoiReminders)
            ->weeklyOn(1, '6:00');
        $schedule->job(new SendInviteReminders)
            ->weeklyOn(1, '6:00');
        $schedule->job(new ApplicationSubmissionRemindChairs)
            ->weeklyOn(1, '6:10');
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
