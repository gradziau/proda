<?php

namespace GradziAu\Proda\Exceptions;

class OrganisationNotActiveException extends ProdaDeviceActivationException
{
    public function __construct($responseData)
    {
        parent::__construct($responseData);
        $this->extendedMessage = "The device must be associated with an active organisation.";
    }
}