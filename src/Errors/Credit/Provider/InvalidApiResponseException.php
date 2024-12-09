<?php

namespace App\Errors\Credit\Provider;

use Exception;
class InvalidApiResponseException extends Exception
{
    public function __construct($message = "Invalid API response", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}