<?php

namespace App\Exceptions;

use RuntimeException;

class OtpDeliveryUnavailable extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('سرویس ارسال کد ورود در حال حاضر در دسترس نیست.');
    }
}
