<?php

namespace GradziAu\Proda\Exceptions;

class JwkParseException extends ProdaDeviceActivationException
{
    public function __construct($responseData)
    {
        parent::__construct($responseData);
        $this->extendedMessage = "The public key provided is not in the valid format.";
    }
}