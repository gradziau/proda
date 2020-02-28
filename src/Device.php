<?php

namespace GradziAu\Proda;

use GradziAu\Proda\Exceptions\ProdaAccessTokenException;
use GradziAu\Proda\Exceptions\ProdaDeviceActivationException;
use GradziAu\Proda\SslKey;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static Device create(array $array)
 */
class Device extends Model
{

    const PRODA_DEVICE_ACTIVE = '[ACTIVE]';
    const PRODA_DEVICE_INACTIVE = '[INACTIVE]';

    protected $guarded = [];

    protected $attributes = [
        'status' => self::PRODA_DEVICE_INACTIVE,
        'key_status' => self::PRODA_DEVICE_INACTIVE,
    ];

    protected $casts = [
        'json_web_key' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Device $device) {
            $device->setAdditionalDefaultValues();
        });
    }

    protected function setAdditionalDefaultValues()
    {
        if (!$this->organisation_id) {
            $this->organisation_id = config('proda.organisation_id');
        }

        if (!$this->client_id) {
            $this->client_id = config('proda.client_id');
        }

        if (!$this->private_key) {
            $this->generateNewSslKeyValues();
        }
    }

    public function activate(): Device
    {
        return $this->sendActivateDeviceRequest();
    }

    protected function sendActivateDeviceRequest(): Device
    {
        $deviceActivationResponse = app('proda')->forDeviceName($this->name)
            ->usingPublicKeyModulus($this->public_key_modulus)
            ->withClientId($this->client_id)
            ->withOrganisationId($this->organisation_id)
            ->withOneTimeActivationCode($this->one_time_activation_code)
            ->usingJsonWebKey($this->json_web_key)
            ->activateDevice();

        return $this->fromProdaResponse($deviceActivationResponse);
    }

    public function refresh(): Device
    {
        $accessToken = $this->getAccessToken();

        return $this->generateNewSslKeyValues()
            ->sendDeviceRefreshRequest($accessToken);
    }

    public function getAccessToken()
    {
        $requestData = app('proda')->usingPrivateKey($this->private_key)
            ->forDeviceName($this->name)
            ->withClientId($this->client_id)
            ->withOrganisationId($this->organisation_id)
            ->withAlgorithm($this->json_algorithm)
            ->getAccessToken();

        return AccessToken::fromAccessTokenRequest($requestData)->accessToken;
    }

    protected function sendDeviceRefreshRequest($accessToken): Device
    {
        $deviceRefreshResponse = app('proda')->forDeviceName($this->name)
            ->usingAccessToken($accessToken)
            ->withClientId($this->client_id)
            ->withOrganisationId($this->organisation_id)
            ->usingJsonWebKey($this->json_web_key)
            ->refreshDevice();

        return $this->fromProdaResponse($deviceRefreshResponse);
    }

    /**
     * @return $this
     */
    public function generateNewSslKeyValues()
    {
        $sslKey = SslKey::new();
        $this->private_key = $sslKey->getPrivateKey();
        $this->public_key_modulus = $sslKey->getPublicKeyModulus();
        $this->json_web_key = $sslKey->getJsonWebKeyWithKeyId($this->name);
        $this->json_algorithm = strtoupper($sslKey::ALGORITHM);

        return $this;
    }

    protected function fromProdaResponse(array $responseData): Device
    {
        $this->name = $responseData['deviceName'];
        $this->organisation_id = $responseData['orgId'];
        $this->status = $responseData['deviceStatus'];
        $this->key_status = $responseData['keyStatus'];
        $this->key_expiry = $responseData['keyExpiry'];
        return $this;
    }

    public function isActive(): bool
    {
        return ($this->status == static::PRODA_DEVICE_ACTIVE);
    }

}