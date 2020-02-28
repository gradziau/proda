<?php

namespace GradziAu\Proda\Exceptions;

class InvalidActivationCodeException extends ProdaDeviceActivationException
{

    public function __construct($responseData)
    {
        parent::__construct($responseData);
        $this->extendedMessage = "The activation code provided in the request does not match the device's activation code.";
    }

}