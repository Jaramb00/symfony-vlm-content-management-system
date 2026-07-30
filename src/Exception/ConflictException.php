<?php

namespace App\Exception;

/**
 * Za sukobe sa postojećim stanjem (npr. email već zauzet).
 * 409 je semantički ispravniji od 400 — klijent je poslao valjan
 * zahtjev, ali se sudara s postojećim resursom.
 */
class ConflictException extends \RuntimeException implements ApiExceptionInterface
{
    public function getStatusCode(): int
    {
        return 409;
    }
}