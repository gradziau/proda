<?php

namespace GradziAu\Proda\Exceptions;

class JwkInvalidKeyUseException extends ProdaDeviceActivationException
{
    public function __construct($responseData)
    {
        parent::__construct($responseData);
        $this->extendedMessage = "The key 'use' value must be provided and set to 'sig'.";
    }
}