<?php

namespace GradziAu\Proda\Tests;

use GradziAu\Proda\Device;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BaseTestWithServer extends BaseTest
{

    public static $baseServerHost;

    public static function setUpBeforeClass(): void
    {
        static::$baseServerHost = 'http://localhost:' . getenv('PRODA_TEST_SERVER_PORT');
        ProdaServer::start(static::$baseServerHost);
    }

    public function newActivatedDevice()
    {
        $name = Str::random();
        $device = factory(Device::class)->create(['name' => $name, 'one_time_activation_code' => $this->getTestActivationCode($name)]);
        $device->activate();
        return $device;
    }

    public function getTestActivationCode($deviceName)
    {
        return Http::get(static::$baseServerHost . '/otac/' . $deviceName)->body();
    }

}

class ProdaServer
{

    private static $isRunning = false;

    static function start($host)
    {
        if (static::$isRunning)
            return;

        /**
         * Start the Proda Test Server
         */
        $cmd = 'php -S ' . Str::after($host, '//') . ' -t ' . __DIR__ . '/server/public > /dev/null 2>&1 & echo $!';
        $pid = exec($cmd);

        /**
         * Loop until the server is up and running
         */
        $url = $host . '/get';
        while (@file_get_contents($url) === false) {
            usleep(1000);
        }

        /**
         * Shut down the server once PHPUnit has finished execution
         */
        register_shutdown_function(function () use ($pid) {
            exec('kill ' . $pid);
        });

        static::$isRunning = true;
    }
}


