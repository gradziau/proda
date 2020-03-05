<?php

namespace GradziAu\Proda\Tests;

use GradziAu\Proda\Notifications\ExpiringDevicesNotification;
use Carbon\Carbon;
use GradziAu\Proda\Device;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Zttp\Zttp;
use Illuminate\Support\Facades\Notification;

class ExpiringDevicesNotificationCommandTest extends BaseTestWithServer
{

    /** @test */
    public function it_sends_a_notification_when_a_device_is_expiring()
    {
        $deviceName = Str::random();
        factory(Device::class)->create(['name' => $deviceName, 'device_expiry' => (string)Carbon::now()->addDays(5)]);
        $testEmailAddress = 'proda@example.com';
        config()->set('proda.expiring_devices_notification_email', $testEmailAddress);

        Notification::fake();

        $this->artisan('proda:notify-expiring-devices');

        Notification::assertSentTo(
            new AnonymousNotifiable,
            ExpiringDevicesNotification::class,
            function ($notification, $channels, $notifiable) use ($testEmailAddress, $deviceName) {
                return ($notifiable->routes['mail'] === $testEmailAddress) &&
                    (strpos($notification->toMail($notifiable)->render(), $deviceName) !== false);
            }
        );

        Notification::assertTimesSent(1, ExpiringDevicesNotification::class);
    }

}
