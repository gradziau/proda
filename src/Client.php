<?php

namespace GradziAu\Proda;

use Carbon\Carbon;
use GradziAu\Proda\Exceptions\AccessTokenDeviceErrorException;
use GradziAu\Proda\Exceptions\AccessTokenMappingErrorException;
use GradziAu\Proda\Exceptions\DeviceInInvalidStateException;
use GradziAu\Proda\Exceptions\DeviceNotFoundException;
use GradziAu\Proda\Exceptions\InvalidActivationCodeException;
use GradziAu\Proda\Exceptions\JwkInvalidAlgorithmException;
use GradziAu\Proda\Exceptions\JwkInvalidKeyUseException;
use GradziAu\Proda\Exceptions\JwkKeyInHistoryException;
use GradziAu\Proda\Exceptions\JwkParseException;
use GradziAu\Proda\Exceptions\OrganisationNotActiveException;
use GradziAu\Proda\Exceptions\OrganisationNotFoundException;
use GradziAu\Proda\Exceptions\ProdaAccessTokenException;
use GradziAu\Proda\Exceptions\ProdaDeviceActivationException;
use GradziAu\Proda\Exceptions\ProdaInputValidationErrorException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Str;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;

class Client
{

    const API_VERSION = 'v1';

    const JWK_GRANT_TYPE = 'urn:ietf:params:oauth:grant-type:jwt_bearer';

    const JSON_WEB_TOKEN_EXPIRY_TIME_IN_SECONDS = 3600;

    public $deviceName;

    public $publicKeyModulus;

    public $clientId;

    public $organisationId;

    public $oneTimeActivationCode;

    public $accessToken;

    public $jsonWebKey;

    public $privateKey;

    public $algorithm;

    protected $deviceActivationExceptions = [
        'DE.2' => OrganisationNotFoundException::class,
        'DE.4' => DeviceNotFoundException::class,
        'DE.5' => DeviceInInvalidStateException::class,
        'DE.7' => InvalidActivationCodeException::class,
        'DE.9' => OrganisationNotActiveException::class,
        'JWK.1' => JwkParseException::class,
        'JWK.2' => JwkInvalidAlgorithmException::class,
        'JWK.8' => JwkInvalidKeyUseException::class,
        'JWK.9' => JwkKeyInHistoryException::class,
        '111' => ProdaInputValidationErrorException::class,
    ];

    protected $accessTokenExceptions = [
        'mapping_error' => AccessTokenMappingErrorException::class,
        'device_error' => AccessTokenDeviceErrorException::class,
    ];

    public function forDeviceName($deviceName)
    {
        $this->deviceName = $deviceName;
        return $this;
    }

    public function usingPublicKeyModulus($publicKeyModulus)
    {
        $this->publicKeyModulus = $publicKeyModulus;
        return $this;
    }

    public function withClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function withOrganisationId($organisationId)
    {
        $this->organisationId = $organisationId;
        return $this;
    }

    public function withOneTimeActivationCode($oneTimeActivationCode)
    {
        $this->oneTimeActivationCode = $oneTimeActivationCode;
        return $this;
    }

    public function usingAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function usingJsonWebKey($jsonWebKey)
    {
        $this->jsonWebKey = $jsonWebKey;
        return $this;
    }

    public function usingPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
        return $this;
    }

    public function withAlgorithm($algorithm)
    {
        $this->algorithm = $algorithm;
        return $this;
    }

    public function activateDevice()
    {
        $response = Http::withHeaders($this->getDeviceActivationHeaders())
            ->put($this->getActivateDeviceUrl(), $this->getDeviceActivationBody());

        return $this->handleDeviceActivationResponse($response);
    }

    public function refreshDevice()
    {
        $response = Http::withHeaders($this->getDeviceActivationHeaders())
            ->put($this->getRefreshDeviceUrl(), $this->jsonWebKey);

        return $this->handleDeviceActivationResponse($response);
    }

    protected function getDeviceActivationHeaders()
    {
        $headers = [
            'Accept-Encoding' => 'gzip,deflate',
            'Content-Type' => 'application/json',
            'dhs-auditId' => $this->organisationId,
            'dhs-auditIdType' => 'http://humanservices.gov.au/PRODA/org',
            'dhs-subjectId' => $this->deviceName,
            'dhs-subjectIdType' => 'http://humanservices.gov.au/PRODA/org',
            'dhs-productId' => $this->clientId,
            'dhs-messageId' => 'urn:uuid:' . (string)Str::uuid(),
            'dhs-correlationId' => 'urn:uuid:' . (string)Str::uuid(),
        ];

        if ($this->accessToken) {
            $headers['Authorization'] = 'Bearer ' . $this->accessToken;
        }

        return $headers;
    }

    protected function getActivateDeviceUrl()
    {
        return sprintf(config('proda.urls.activate_device'),
            static::API_VERSION, $this->deviceName);
    }

    protected function getRefreshDeviceUrl()
    {
        return sprintf(config('proda.urls.refresh_device_key'),
            static::API_VERSION, $this->organisationId, $this->deviceName);
    }

    protected function getDeviceActivationBody()
    {
        return [
            'orgId' => $this->organisationId,
            'otac' => $this->oneTimeActivationCode,
            'key' => $this->jsonWebKey,
        ];
    }

    protected function handleDeviceActivationResponse(HttpResponse $response)
    {
        $responseData = $response->json();

        if (!$response->ok()) {
            $this->handleDeviceActivationError($responseData);
        }

        return $responseData;
    }

    protected function handleDeviceActivationError(array $responseData)
    {
        if ($this->responseHasValidDeviceActivationError($responseData)) {
            $errorCode = $responseData['errors']['code'];
            throw new $this->deviceActivationExceptions[$errorCode]($responseData);
        }

        throw new ProdaDeviceActivationException($responseData);
    }

    protected function responseHasValidDeviceActivationError(array $responseData)
    {
        return (is_array($responseData) &&
            array_key_exists('errors', $responseData) &&
            (count($responseData['errors']) > 0) &&
            array_key_exists($responseData['errors']['code'], $this->deviceActivationExceptions));
    }

    public function getAccessToken()
    {
        $response = Http::asForm()
            ->post($this->getAuthorisationServiceRequestUrl(), $this->getAccessTokenPostParameters());

        return $this->handleAccessTokenResponse($response);
    }

    protected function getAuthorisationServiceRequestUrl()
    {
        return config('proda.urls.authorisation_service_request');
    }

    protected function getAccessTokenPostParameters()
    {
        return [
            'grant_type' => static::JWK_GRANT_TYPE,
            'assertion' => $this->getJsonWebToken(),
            'client_id' => $this->clientId,
        ];
    }

    protected function getJsonWebToken()
    {
        $now = new DateTimeImmutable();
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($this->privateKey));

        $token = $config->builder()
            ->issuedBy($this->organisationId)
            ->relatedTo($this->deviceName)
            ->permittedFor('https://proda.humanservices.gov.au')
            ->withClaim('token.aud', 'tcsi.test.audience.string')
            ->issuedAt($now)
            ->expiresAt($now->modify('+' . static::JSON_WEB_TOKEN_EXPIRY_TIME_IN_SECONDS . ' seconds'))
            ->withHeader('alg', $this->algorithm)
            ->withHeader('kid', $this->deviceName)
            ->getToken($config->signer(), $config->signingKey());

        return $token->toString();
    }

    protected function handleAccessTokenResponse(HttpResponse $response)
    {
        $responseData = $response->json();

        if (!$response->ok()) {
            $this->handleAccessTokenError($responseData);
        }

        return $responseData;
    }

    protected function handleAccessTokenError(array $responseData)
    {
        if ($this->responseHasValidAccessTokenError($responseData)) {
            $errorCode = $responseData['error'];
            throw new $this->accessTokenExceptions[$errorCode]($responseData);
        }

        throw new ProdaAccessTokenException($responseData);
    }

    protected function responseHasValidAccessTokenError(array $responseData)
    {
        return (is_array($responseData) &&
            array_key_exists('error', $responseData) &&
            array_key_exists($responseData['error'], $this->accessTokenExceptions));
    }

}
