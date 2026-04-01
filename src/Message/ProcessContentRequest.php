<?php

namespace App\Message;

final class ProcessContentRequest
{
    public function __construct(
        public readonly int $contentRequestId
    ) {}
}