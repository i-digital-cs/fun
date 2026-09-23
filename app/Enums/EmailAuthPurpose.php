<?php

namespace App\Enums;

enum EmailAuthPurpose: string
{
    case EmailVerify = 'email_verify';
    case PasswordReset = 'password_reset';
}
