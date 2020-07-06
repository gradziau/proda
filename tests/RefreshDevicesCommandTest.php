<?php

namespace GradziAu\Proda\Tests;

use Carbon\Carbon;
use GradziAu\Proda\Device;
use Illuminate\Support\Str;

class RefreshDevicesCommandTest extends BaseTestWithServer
{

    /** @test */
    public function it_refreshes_devices_with_expiring_keys()
    {
        $this->withoutExceptionHandling();
        $deviceName = Str::random();
        $device = factory(Device::class)->create(['name' => $deviceName, 'one_time_activation_code' => $this->getTestActivationCode($deviceName)]);
        $device->activate()
            ->update(['key_expiry' => (string)Carbon::now()]);
        $keyExpiry = $device->key_expiry;

        $this->artisan('proda:refresh-devices');
        $device->refresh();

        $this->assertNotEquals($keyExpiry, $device->key_expiry);
    }

}
