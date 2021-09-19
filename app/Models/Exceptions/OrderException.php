<?php

namespace App\Models\Exceptions;
use App\Models\Traits\Serialize;

/**
 * Class OrderException
 */
class OrderException extends \Exception
{
    use Serialize;

    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }
}
