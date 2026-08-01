<?php

namespace App\Message;

final class ExportMessage
{
    public function __construct(
        public readonly int $exportJobId,
    ) {}
}
