<?php

namespace App\Exception;

interface HasInvalidFieldInterface
{
    public function getField(): string;
}
