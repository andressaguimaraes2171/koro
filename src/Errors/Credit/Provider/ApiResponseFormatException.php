<?php

namespace App\Errors\Credit\Provider;

use Exception;
class ApiResponseFormatException extends Exception
{
    public function __construct($message = "API response format error", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}