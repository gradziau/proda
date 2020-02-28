<?php

namespace GradziAu\Proda;

class AccessToken
{

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var integer
     */
    protected $expiresIn;

    /**
     * @var string
     */
    protected $keyExpiry;

    /**
     * @var string
     */
    protected $deviceExpiry;

    static public function fromAccessTokenRequest($tokenData): AccessToken
    {
        $token = (new static);
        $token->accessToken = $tokenData['access_token'];
        $token->expiresIn = $tokenData['expires_in'];
        $token->keyExpiry = $tokenData['key_expiry'];
        $token->deviceExpiry = $tokenData['device_expiry'];
        return $token;
    }

    /**
     * @param $propertyName
     * @return mixed
     */
    public function __get($propertyName)
    {
        return $this->$propertyName;
    }

}