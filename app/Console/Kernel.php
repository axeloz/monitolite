<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use App\Console\Commands\SyncCustomers;
use App\Console\Commands\RunMonitoring;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SyncCustomers::class,
        RunMonitoring::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /**
         * This is for my own needs
         * You may safely remove this scheduled task
         */
        if (env('CMS_ENABLE_SYNC') == true) {
            $schedule->command('monitolite:customers:sync')->hourly();
        }

        /**
         * This is the main monitoring task
         */
        $schedule->command('monitolite:monitoring:run')->everyMinute();
    }
}
