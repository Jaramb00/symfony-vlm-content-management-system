<?php

namespace App\Exception;

class ValidationException extends \RuntimeException implements ApiExceptionInterface
{
    public function getStatusCode(): int
    {
        return 400;
    }
}