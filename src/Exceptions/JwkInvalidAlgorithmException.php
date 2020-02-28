<?php

namespace GradziAu\Proda\Exceptions;

class JwkInvalidAlgorithmException extends ProdaDeviceActivationException
{
    public function __construct($responseData)
    {
        parent::__construct($responseData);
        $this->extendedMessage = "The key provided must be an RSA public key with the following properties: . "
            "Key Size: 2048 (minimum size); " .
            "Key Use: Signature; " .
            "Algorithm RS256 (RS384 and RS512 are also accepted); " .
            "Key Id: Is the name of the device with which the key is associated.";
    }
}