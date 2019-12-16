<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\EventbriteUpdate::class,
        Commands\TamburinoUpdate::class,
        Commands\ZanoxUpdate::class,
        Commands\NotificationsEventsStarting::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('eventbrite:update')->cron('0 0 * * *');
        $schedule->command('eventbrite:update')->cron('0 8 * * *');
        $schedule->command('eventbrite:update')->cron('0 16 * * *');

        $schedule->command('tamburino:update')->cron('10 0 * * *');
        $schedule->command('tamburino:update')->cron('10 8 * * *');
        $schedule->command('tamburino:update')->cron('10 16 * * *');

        $schedule->command('zanox:update')->cron('20 0 * * *');

        // Trigger generate sitemap
        $schedule->call(function () {
            file_get_contents('http://www.findeem.com/external/api/sitemap/generate');
        })->cron('0 1 * * *');
    }
}
