<?php

namespace GradziAu\Proda\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpiringDevicesNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $devices;

    public function __construct($devices)
    {
        $this->devices = $devices;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mailMessage = new MailMessage;

        $mailMessage->subject('Expiring PRODA Devices')
            ->line('The following devices will be expiring soon:');

        foreach ($this->devices as $device) {
            $mailMessage->line('Device: ' . $device->name .
                ' (Organisation ID: ' . $device->organisation_id . '; Client ID: ' . $device->client_id . ') ' .
                'Expiring: ' . $device->device_expiry);
        }

        return $mailMessage;
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
