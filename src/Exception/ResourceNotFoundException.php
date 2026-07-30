<?php

namespace App\Exception;

class ResourceNotFoundException extends \RuntimeException implements ApiExceptionInterface
{
    public function __construct(string $message = 'Not found')
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return 404;
    }
}