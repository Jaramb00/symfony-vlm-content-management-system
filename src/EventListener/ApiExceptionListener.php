<?php

namespace App\EventListener;

use App\Exception\ApiExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Jedno mjesto koje SVE naše API iznimke pretvara u JSON odgovor.
 * Kontroleri zbog ovoga ne trebaju try/catch — samo pozovu servis.
 */
#[AsEventListener]
final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof ApiExceptionInterface) {
            return; // nije naša — Symfony rješava po defaultu
        }

        $event->setResponse(new JsonResponse(
            ['error' => $exception->getMessage()],
            $exception->getStatusCode()
        ));
    }
}