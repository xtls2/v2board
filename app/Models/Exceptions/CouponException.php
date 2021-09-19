<?php

namespace App\Models\Exceptions;
use App\Models\Traits\Serialize;

/**
 * Class CouponException
 */
class CouponException extends \Exception
{
    use Serialize;

    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }
}
