<?php

namespace GradziAu\Proda\Exceptions;

class OrganisationNotFoundException extends ProdaDeviceActivationException
{
    public function __construct($responseData)
    {
        parent::__construct($responseData);
        $this->extendedMessage = "The organisation's identifier provided in the request is not associated with an active organisation.";
    }
}