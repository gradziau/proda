<?php

namespace Tests;

use Carbon\Carbon;
use GradziAu\Proda\Client;
use GradziAu\Proda\Exceptions\InvalidActivationCodeException;
use GradziAu\Proda\Exceptions\ProdaAccessTokenException;
use GradziAu\Proda\Exceptions\ProdaDeviceActivationException;
use GradziAu\Proda\ProdaServiceProvider;
use GradziAu\Proda\Tests\BaseTestWithServer;
use Illuminate\Support\Str;
use Zttp\Zttp;
use Orchestra\Testbench;
use GradziAu\Proda\AccessToken;
use GradziAu\Proda\Device;

class ProdaTest extends BaseTestWithServer
{

    protected $client;

    protected $organisationId;
    protected $clientId;

    protected function getPackageProviders($app)
    {
        return [
            ProdaServiceProvider::class,
        ];
    }

    public function setup(): void
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
        $this->assertTrue($this->newActivatedDevice()->isActive());
    }

    /**
     * @test
     */
    public function it_raises_an_exception_when_activating_an_expired_activation_code()
    {
        $name = Str::random();
        $device = Device::create([
            'name' => $name,
            'one_time_activation_code' => Zttp::get(static::$baseServerHost . '/expiredotac/' . $name)->body()
        ]);

        $this->expectException(InvalidActivationCodeException::class);
        $device->activate();
    }

    /**
     * @test
     */
    public function it_raises_an_exception_with_invalid_activation_code()
    {
        $device = Device::create([
            'name' => Str::random(),
            'one_time_activation_code' => Str::random()
        ]);

        Zttp::get(static::$baseServerHost . '/otac/' . $device->name)->body();

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
        $device = $this->newActivatedDevice();
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
        $device = $this->newActivatedDevice();
        $device->key_expiry = null;
        $device->refreshKey();

        $this->assertNotNull($device->key_expiry);
    }

    /** @test */
    public function it_caches_an_access_token()
    {
        $device = $this->newActivatedDevice();
        $accessToken = $device->getAccessToken();
        $cachedAccessToken = $device->getAccessToken();

        $this->assertEquals($accessToken, $cachedAccessToken);
    }

    /** @test */
    public function it_retrieves_a_new_token_when_cache_has_expired()
    {
        $device = $this->newActivatedDevice();

        $this->app->config->set('proda.access_token_expiry_seconds', '0.001');
        $accessToken = $device->getAccessToken();
        sleep(0.005);
        $newAccessToken = $device->getAccessToken();

        $this->assertNotEquals($accessToken, $newAccessToken);
    }

    /** @test */
    public function it_updates_expiry_for_a_device()
    {
        $this->assertNotNull($this->newActivatedDevice()->device_expiry);
    }


}
