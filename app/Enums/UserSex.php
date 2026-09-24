<?php

namespace App\Enums;

enum UserSex: string
{
    case Male = 'male';
    case Female = 'female';
    case Mix = 'mix';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
