<?php

namespace Tests;

use Carbon\Carbon;
use GradziAu\Proda\Client;
use GradziAu\Proda\Exceptions\InvalidActivationCodeException;
use GradziAu\Proda\Exceptions\ProdaAccessTokenException;
use GradziAu\Proda\Exceptions\ProdaDeviceActivationException;
use GradziAu\Proda\ProdaServiceProvider;
use Illuminate\Support\Str;
use Zttp\Zttp;
use Orchestra\Testbench;
use GradziAu\Proda\AccessToken;
use GradziAu\Proda\Device;
use GradziAu\Proda\Tests\BaseTest;

class ProdaTest extends BaseTest
{

    /**
     * @var string The base host/port for the test server
     */
    public static $baseHost;

    /**
     * @var Client
     */
    protected $client;

    protected $organisationId;
    protected $clientId;

    public static function setUpBeforeClass(): void
    {
        static::$baseHost = 'http://localhost:' . getenv('PRODA_TEST_SERVER_PORT');
        ProdaServer::start(static::$baseHost);
    }

    protected function getPackageProviders($app)
    {
        return [
            ProdaServiceProvider::class,
        ];
    }

    protected function setup(): void
    {
        parent::setUp();
        $this->app->config->set('proda.organisation_id', '1234567');
        $this->app->config->set('proda.client_id', 'abcdefg1234567');
    }

    /**
     * @test
     * @throws ProdaDeviceActivationException
     */
    public function it_can_activate_a_device()
    {
        $name = Str::random();
        $device = Device::create([
            'name' => $name,
            'one_time_activation_code' => Zttp::get(static::$baseHost . '/otac/' . $name)->body()
        ]);
        $device->activate();

        $this->assertTrue($device->isActive());
    }

    /**
     * @test
     * @throws ProdaDeviceActivationException
     */
    public function it_raises_an_exception_when_activating_an_expired_device()
    {
        $name = Str::random();
        $device = Device::create([
            'name' => $name,
            'one_time_activation_code' => Zttp::get(static::$baseHost . '/expiredotac/' . $name)->body()
        ]);

        $this->expectException(InvalidActivationCodeException::class);
        $device->activate();
    }

    /**
     * @test
     * @throws ProdaDeviceActivationException
     */
    public function it_raises_an_exception_with_invalid_activation_code()
    {
        $device = Device::create([
            'name' => Str::random(),
            'one_time_activation_code' => Str::random()
        ]);

        Zttp::get(static::$baseHost . '/otac/' . $device->name)->body();

        $this->expectException(InvalidActivationCodeException::class);
        $device->activate();
    }

    /**
     * @test
     * @throws ProdaDeviceActivationException
     * @throws ProdaAccessTokenException
     */
    public function it_retrieves_an_access_token()
    {
        $name = Str::random();
        $device = Device::create([
            'name' => $name,
            'one_time_activation_code' => Zttp::get(static::$baseHost . '/otac/' . $name)->body()
        ]);
        $device->activate();

        $accessToken = $device->getAccessToken();
        $this->assertIsString($accessToken);
    }

    /**
     * @test
     * @throws ProdaDeviceActivationException
     * @throws ProdaAccessTokenException
     */
    public function it_refreshes_a_device_key()
    {
        $name = Str::random();
        $device = Device::create([
            'name' => $name,
            'one_time_activation_code' => Zttp::get(static::$baseHost . '/otac/' . $name)->body()
        ]);
        $device->activate();

        $device->key_expiry = null;

        $device->refresh();

        $this->assertNotNull($device->key_expiry);
    }

    /** @test */
    public function it_caches_an_access_token()
    {
        $name = Str::random();
        $device = Device::create([
            'name' => $name,
            'one_time_activation_code' => Zttp::get(static::$baseHost . '/otac/' . $name)->body()
        ]);
        $device->activate();

        $accessToken = $device->getAccessToken();
        $cachedAccessToken = $device->getAccessToken();

        $this->assertEquals($accessToken, $cachedAccessToken);
    }

    /** @test */
    public function it_retrieves_a_new_token_when_cache_has_expired()
    {
        $name = Str::random();
        $device = Device::create([
            'name' => $name,
            'one_time_activation_code' => Zttp::get(static::$baseHost . '/otac/' . $name)->body()
        ]);
        $device->activate();

        $this->app->config->set('proda.access_token_expiry_seconds', '0.001');
        $accessToken = $device->getAccessToken();
        sleep(0.005);
        $newAccessToken = $device->getAccessToken();

        $this->assertNotEquals($accessToken, $newAccessToken);
    }

}

class ProdaServer
{
    static function start($host)
    {
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
    }
}
