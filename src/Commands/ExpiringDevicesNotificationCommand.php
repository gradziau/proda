<?php

namespace GradziAu\Proda\Commands;

use GradziAu\Proda\Notifications\ExpiringDevicesNotification;
use GradziAu\Proda\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class ExpiringDevicesNotificationCommand extends Command
{
    protected $signature = 'proda:notify-expiring-devices';

    protected $description = 'Send a notification that lists all expiring devices';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $devices = Device::expiring()->get();
        if ($devices->count()) {
            Notification::route('mail', config('proda.expiring_devices_notification_email'))
                ->notify(new ExpiringDevicesNotification($devices));
        }
    }

}