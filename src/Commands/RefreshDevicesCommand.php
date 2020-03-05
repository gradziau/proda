<?php

namespace GradziAu\Proda\Commands;

use GradziAu\Proda\Device;
use Illuminate\Console\Command;

class RefreshDevicesCommand extends Command
{
    protected $signature = 'proda:refresh-devices';

    protected $description = 'Refresh PRODA devices with expiring keys';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $devices = Device::withExpiringKeys()->get();
        foreach ($devices as $device)
        {
            $this->outputDeviceRefreshBeforeMessage($device);
            $device->refreshKey();
            $this->outputDeviceRefreshAfterMessage($device);
        }
    }

    protected function outputDeviceRefreshBeforeMessage(Device $device)
    {
        $this->line($this->getOutputDeviceRefreshMessage('Refreshing', $device));
    }

    protected function outputDeviceRefreshAfterMessage(Device $device)
    {
        $this->comment($this->getOutputDeviceRefreshMessage('Refreshed', $device));
    }

    protected function getOutputDeviceRefreshMessage($verb, Device $device)
    {
        return sprintf('$s device: %s (Organisation: %s, Client: %s)',
            $verb, $device->name, $device->organisation_id, $device->client_id);
    }


}