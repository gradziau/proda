<?php

namespace GradziAu\Proda;

use Carbon\Carbon;
use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @method static Device create(array $array)
 */
class Device extends Model
{
    use HasFactory;

    const PRODA_DEVICE_ACTIVE = '[ACTIVE]';
    const PRODA_DEVICE_INACTIVE = '[INACTIVE]';

    const DAYS_BEFORE_KEY_EXPIRY_SCOPE = 2;
    const DAYS_BEFORE_DEVICE_EXPIRY_SCOPE = 7;

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

    protected static function newFactory()
    {
        return DeviceFactory::new();
    }

    public function scopeWithExpiringKeys($query)
    {
        $query->where('key_status', static::PRODA_DEVICE_ACTIVE)
            ->where('key_expiry', '<=', Carbon::now()->addDays(static::DAYS_BEFORE_KEY_EXPIRY_SCOPE));
    }

    public function scopeExpiring($query)
    {
        $query->where('key_status', static::PRODA_DEVICE_ACTIVE)
            ->where('device_expiry', '<=', Carbon::now()->addDays(static::DAYS_BEFORE_DEVICE_EXPIRY_SCOPE));
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
        return $this->sendActivateDeviceRequest()->store();
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

    public function refreshKey(): Device
    {
        $accessToken = $this->getAccessToken();

        return $this->generateNewSslKeyValues()
            ->sendDeviceRefreshRequest($accessToken)
            ->store();
    }

    public function getAccessToken()
    {
        return $this->getAccessTokenFromCache();
    }

    protected function getAccessTokenFromCache()
    {
        $cacheKey = $this->getAccessTokenCacheKey();
        $expirySeconds = $this->getAccessTokenExpiryInSeconds();
        return Cache::remember($cacheKey, $expirySeconds, function () {
            return $this->getAccessTokenFromClient();
        });
    }

    protected function getAccessTokenCacheKey()
    {
        return sprintf('.%s.%s.%s', $this->organisation_id, $this->client_id, $this->name);
    }

    protected function getAccessTokenExpiryInSeconds()
    {
        return config('proda.access_token_expiry_seconds');
    }

    protected function getAccessTokenFromClient()
    {
        $requestData = app('proda')->usingPrivateKey($this->private_key)
            ->forDeviceName($this->name)
            ->withClientId($this->client_id)
            ->withOrganisationId($this->organisation_id)
            ->withAlgorithm($this->json_algorithm)
            ->getAccessToken();
        return AccessToken::fromAccessTokenRequest($requestData)->accessToken;
    }

    public function generateNewSslKeyValues()
    {
        $sslKey = SslKey::new();
        $this->private_key = $sslKey->getPrivateKey();
        $this->public_key_modulus = $sslKey->getPublicKeyModulus();
        $this->json_web_key = $sslKey->getJsonWebKeyWithKeyId($this->name);
        $this->json_algorithm = strtoupper($sslKey::ALGORITHM);

        return $this;
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

    protected function store(): Device
    {
        $this->save();
        return $this;
    }

    protected function fromProdaResponse(array $responseData): Device
    {
        $this->name = $responseData['deviceName'];
        $this->organisation_id = $responseData['orgId'];
        $this->status = $responseData['deviceStatus'];
        $this->key_status = $responseData['keyStatus'];
        $this->key_expiry = $responseData['keyExpiry'];
        $this->device_expiry = $responseData['deviceExpiry'];
        return $this;
    }

    public function isActive(): bool
    {
        return ($this->status == static::PRODA_DEVICE_ACTIVE);
    }
}
