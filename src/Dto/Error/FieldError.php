<?php

namespace App\Dto\Error;

final class FieldError
{
    public function __construct(
        public readonly string $field,
        public readonly string $message,
    ) {}
}
