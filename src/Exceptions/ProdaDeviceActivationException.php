<?php

namespace GradziAu\Proda\Exceptions;

/**
 * Class ProdaDeviceActivationException
 * @package GradziAu\Proda\Exceptions
 *
 * Error example:
 *
 * {
 *  "errors" : [ {
 *      "code" : "JWK.9",
 *      "message" : "Key In History."
 *   } ],
 *   "reference" : "N/A",
 *   "status" : "404",
 *   "url" : "PUT /piaweb/api/b2b/v1/devices/test-device /jwk"
 * }
 */
class ProdaDeviceActivationException extends ProdaException
{

    protected function hasValidError(): bool
    {
        return ((is_array($this->responseData)) &&
            array_key_exists('errors', $this->responseData) &&
            count($this->responseData['errors']) > 0);
    }

    protected function populateFromError()
    {
        $this->errorCode = $this->responseData['errors']['code'];
        $this->message = $this->responseData['errors']['message'];
    }

}