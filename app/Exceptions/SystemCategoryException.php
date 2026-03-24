<?php

namespace App\Exceptions;

use RuntimeException;

class SystemCategoryException extends RuntimeException
{
    public function __construct(string $message = 'System categories cannot be modified or deleted.')
    {
        parent::__construct($message);
    }
}
