<?php

namespace App\Console;

use App\Actions\SendCoiReminders;
use App\Actions\SendInviteReminders;
use App\Actions\SendSubmissionDigestNotifications;
use App\DataExchange\Actions\DxConsume;
use App\Modules\Group\Actions\SubmissionApprovalRemindersCreate;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

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
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        if (config('app.test_scheduler')) {
            $schedule->call(function () {
                Log::debug('scheduler is running.');
            })->everyMinute();
        }

        if (config('dx.consume')) {
            $schedule->command(DxConsume::class, array_values(config('dx.topics.incoming')))
                ->hourly()
                ->withoutOverlapping()
                ->runInBackground();
        }

        $schedule->job(new SendCoiReminders)
            ->weeklyOn(1, '6:00');
        $schedule->job(new SendInviteReminders)
            ->weeklyOn(1, '6:00');

        $schedule->job(new SubmissionApprovalRemindersCreate)
            ->dailyAt('6:10')
            ->days([1, 4])
            ->after(function () {
                app()->make(SendSubmissionDigestNotifications::class)->handle();
            });
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
