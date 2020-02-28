<?php

namespace GradziAu\Proda\Tests;

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

        $device1 = Device::create(['name' => 'test', 'one_time_activation_code' => '1234']);
        $device2 = Device::create([
            'name' => 'test2',
            'organisation_id' => 'testorg',
            'client_id' => 'testclient',
            'one_time_activation_code' => '1234',
        ]);

        $this->assertEquals('1234567', $device1->organisation_id);
        $this->assertEquals('abcdefg1234567', $device1->client_id);

        $this->assertEquals('testorg', $device2->organisation_id);
        $this->assertEquals('testclient', $device2->client_id);

        $this->assertCount(2, Device::all());
    }

}
