<?php

namespace App\Enum;

enum DocumentType: string
{
    case DNI = 'DNI';
    case CUIT = 'CUIT';
    case CUIL = 'CUIL';
    case PASSPORT = 'PASAPORTE';
}
