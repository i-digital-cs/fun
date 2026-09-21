<?php

namespace App\Enums;

enum IdentityDocumentType: string
{
    case IdFront = 'id_front';
    case IdBack = 'id_back';
    case Selfie = 'selfie';

    /**
     * @return list<self>
     */
    public static function required(): array
    {
        return [
            self::IdFront,
            self::IdBack,
            self::Selfie,
        ];
    }
}
