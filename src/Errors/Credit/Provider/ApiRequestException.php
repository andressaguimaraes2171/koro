<?php

namespace App\Errors\Credit\Provider;

use Exception;

class ApiRequestException extends Exception
{
    public function __construct($message = "API request error", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}