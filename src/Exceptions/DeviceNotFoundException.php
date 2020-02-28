<?php

namespace GradziAu\Proda\Exceptions;

class DeviceNotFoundException extends ProdaDeviceActivationException
{
    public function __construct($responseData)
    {
        parent::__construct($responseData);
        $this->extendedMessage = "The device name provided in the service request is not associated with the identified organisation.";
    }
}