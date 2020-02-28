<?php

namespace GradziAu\Proda\Exceptions;

abstract class ProdaException extends \Exception
{

    public $responseData = [];
    public $errorCode = 'N/A';
    public $message = 'N/A';
    public $extendedMessage = 'N/A';

    public function __construct($responseData)
    {
        parent::__construct();

        $this->responseData = $responseData;
        if ($this->hasValidError()) {
            $this->populateFromError();
        }
    }

    protected abstract function hasValidError(): bool;

    protected abstract function populateFromError();
}