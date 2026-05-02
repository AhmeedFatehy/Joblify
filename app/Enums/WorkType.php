<?php

namespace App\Enums;

enum WorkType: string
{
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
