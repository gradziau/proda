<?php

namespace GradziAu\Proda\Exceptions;

class ProdaInputValidationErrorException extends ProdaDeviceActivationException
{
    public function __construct($responseData)
    {
        parent::__construct($responseData);
        $this->extendedMessage = "There was an input validation error.";
    }
}