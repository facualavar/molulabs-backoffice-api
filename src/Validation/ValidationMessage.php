<?php

namespace App\Validation;

final class ValidationMessage
{
    public const string NAME_BLANK = 'El nombre no puede estar vacío.';
    public const string LASTNAME_BLANK = 'El apellido no puede estar vacío.';
    public const string EMAIL_BLANK = 'El email no puede estar vacío.';
    public const string EMAIL_INVALID = 'El email no es válido.';
    public const string DOCUMENT_NUMBER_BLANK = 'El número de documento no puede estar vacío.';
    public const string PHONE_BLANK = 'El teléfono no puede estar vacío.';
    public const string PASSWORD_BLANK = 'La contraseña no puede estar vacía.';
    public const string CURRENT_PASSWORD_BLANK = 'La contraseña actual no puede estar vacía.';
    public const string NEW_PASSWORD_BLANK = 'La nueva contraseña no puede estar vacía.';

    public const string NAME_TOO_LONG = 'El nombre no puede tener más de {{ limit }} caracteres.';
    public const string LASTNAME_TOO_LONG = 'El apellido no puede tener más de {{ limit }} caracteres.';
    public const string EMAIL_TOO_LONG = 'El email no puede tener más de {{ limit }} caracteres.';
    public const string PHONE_TOO_LONG = 'El teléfono no puede tener más de {{ limit }} caracteres.';
}
