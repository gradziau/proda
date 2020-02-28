<?php

namespace GradziAu\Proda\Exceptions;

/**
 * Class ProdaAccessTokenException
 * @package GradziAu\Proda\Exceptions
 *
 * Error example:
 *
 * {
 *  "error" : "mapping_error"
 *  "error_description" : "Token was not valid",
 * }
 *
 */
class ProdaAccessTokenException extends ProdaException
{

    protected function hasValidError(): bool
    {
        return (is_array($this->responseData) &&
            array_key_exists('error', $this->responseData) &&
            array_key_exists('error_description', $this->responseData));
    }

    protected function populateFromError()
    {
        $this->errorCode = $this->responseData['error'];
        $this->message = $this->responseData['error_description'];
    }


}