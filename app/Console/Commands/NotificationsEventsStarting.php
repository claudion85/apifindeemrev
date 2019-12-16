<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class NotificationsEventsStarting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:events:starting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify users for events starting tomorrow';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $events = \App\Event::where('start_date', \Carbon\Carbon::parse('tomorrow'))->get();
        $ids = $events->pluck(['_id'])->toArray();
        $eventUsers = \App\EventUser::whereIn('event_id', $ids)->where('type', 'going')->get();

        foreach ($eventUsers as $eu) {
            newNotification([
                'user' => $eu->user_id,
                'notification_type' => 'starts_tomorrow',
                'notification_entity' => 'events',
                'entity_id' => $eu->event_id,
            ]);
        }
    }
}
