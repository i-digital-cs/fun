<?php

namespace App\Enums\Marketing;

enum MarketingCredentialType: string
{
    case SystemUserToken = 'system_user_token';
    case OauthToken = 'oauth_token';
    case AppSecretRef = 'app_secret_ref';
}
