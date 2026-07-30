<?php

namespace App\Exception;

/**
 * Iznimke koje ApiExceptionListener pretvara u JSON odgovor.
 * Svaka sama zna svoj HTTP status.
 */
interface ApiExceptionInterface extends \Throwable
{
    public function getStatusCode(): int;
}