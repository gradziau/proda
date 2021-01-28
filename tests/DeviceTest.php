<?php

namespace GradziAu\Proda\Tests;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Orchestra\Testbench;
use GradziAu\Proda\Device;
use GradziAu\Proda\Tests\BaseTest;

class DeviceTest extends BaseTest
{

    /** @test */
    public function it_stores_a_device_with_and_without_default_values()
    {
        $this->app->config->set('proda.organisation_id', '1234567');
        $this->app->config->set('proda.client_id', 'abcdefg1234567');

        $device1 = Device::factory()->create(['organisation_id' => null, 'client_id' => null]);
        $device2 = Device::factory()->create(['organisation_id' => 'testorg', 'client_id' => 'testclient']);

        $this->assertEquals('1234567', $device1->organisation_id);
        $this->assertEquals('abcdefg1234567', $device1->client_id);

        $this->assertEquals('testorg', $device2->organisation_id);
        $this->assertEquals('testclient', $device2->client_id);

        $this->assertCount(2, Device::all());
    }

    /** @test */
    public function it_only_retrieves_devices_with_expiring_keys()
    {
        Device::factory()->create(['key_expiry' => (string)Carbon::now()->addDays(8), 'key_status' => Device::PRODA_DEVICE_ACTIVE]);
        Device::factory()->create(['key_expiry' => (string)Carbon::now(), 'key_status' => Device::PRODA_DEVICE_INACTIVE]);
        Device::factory()->create(['key_expiry' => (string)Carbon::now(), 'key_status' => Device::PRODA_DEVICE_ACTIVE]);

        $this->assertCount(1, Device::withExpiringKeys()->get());
    }

    /** @test */
    public function it_retrieves_devices_that_are_expiring()
    {
        Device::factory()->create(['device_expiry' => Carbon::now()->addDays(50)]);
        Device::factory()->create(['device_expiry' => Carbon::now()->addDays(5)]);

        $this->assertCount(1, Device::expiring()->get());
    }

}
