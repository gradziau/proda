<?php

namespace GradziAu\Proda;

class SslKey
{

    const ALGORITHM = 'rs256';

    const KEY_PAIR_CONFIG = [
        "digest_alg" => 'sha256',
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];

    protected $privateKey;

    protected $publicKeyModulus;

    protected $keyIdForJsonWebKey;

    static public function new()
    {
        return (new static)->generateNewKey();
    }

    static public function fromPrivateKey($privateKey)
    {
        return (new static)->setPrivateKey($privateKey);
    }

    /**
     * See https://www.php.net/manual/en/function.openssl-pkey-new.php
     */
    public function generateNewKey()
    {
        $keyResource = openssl_pkey_new(static::KEY_PAIR_CONFIG);
        openssl_pkey_export($keyResource, $privateKey);
        $this->setPrivateKey($privateKey);
        return $this;
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
        $this->initialisePublicKeyModulus();
        return $this;
    }

    protected function initialisePublicKeyModulus()
    {
        $publicKey = openssl_pkey_get_details(openssl_pkey_get_private($this->privateKey));
        $this->publicKeyModulus = base64_encode($publicKey['rsa']['n']);
        return $this;
    }

    public function getPublicKeyModulus()
    {
        return $this->publicKeyModulus;
    }

    public function setKeyIdForJsonWebKey($keyId)
    {
        $this->keyIdForJsonWebKey = $keyId;
        return $this;
    }

    /**
     * Generates a JSON Web Key: https://tools.ietf.org/html/rfc7517
     *
     * @return array
     */
    public function getJsonWebKeyWithKeyId($keyId)
    {
        $jsonWebKey = $this->getJsonWebKey();
        $jsonWebKey['kid'] = $keyId;

        return $jsonWebKey;
    }

    public function getJsonWebKey()
    {
        return [
            'alg' => strtoupper(static::ALGORITHM),
            'e' => 'AQAB',
            'n' => $this->publicKeyModulus,
            'kty' => 'RSA',
            'use' => 'sig',
        ];
    }

}