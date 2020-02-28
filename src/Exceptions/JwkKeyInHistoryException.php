<?php

namespace GradziAu\Proda\Exceptions;

class JwkKeyInHistoryException extends ProdaDeviceActivationException
{
    public function __construct($responseData)
    {
        parent::__construct($responseData);
        $this->extendedMessage = "The key provided in the request is a historical key that was assigned to the device.";
    }
}