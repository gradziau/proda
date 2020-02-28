<?php

namespace GradziAu\Proda\Exceptions;

class DeviceInInvalidStateException extends ProdaDeviceActivationException
{
    public function __construct($responseData)
    {
        parent::__construct($responseData);
        $this->extendedMessage = "Only devices that are in the 'Inactive' state and are associated with an active 'activation code' can be activated.";
    }
}