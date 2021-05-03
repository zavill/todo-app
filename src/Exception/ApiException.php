<?php


namespace App\Exception;


use Throwable;

class ApiException extends \Exception
{

    public function __construct($message = "", $code = 404, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}