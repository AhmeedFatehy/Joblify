<?php

namespace App\Enums;

use App\Traits\HasEnumValues;

enum WorkType: string
{
    use HasEnumValues;
    case REMOTE = 'remote';
    case ONSITE = 'onsite';
    case HYBRID = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::REMOTE => 'Remote',
            self::ONSITE => 'On-site',
            self::HYBRID => 'Hybrid',
        };
    }
}
