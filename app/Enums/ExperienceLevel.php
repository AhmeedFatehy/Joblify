<?php

namespace App\Enums;

enum ExperienceLevel: string
{
    case ENTRY = 'entry';
    case MID = 'mid';
    case SENIOR = 'senior';
    case LEAD = 'lead';

    public function label(): string
    {
        return match ($this) {
            self::ENTRY => 'Entry Level',
            self::MID => 'Mid Level',
            self::SENIOR => 'Senior Level',
            self::LEAD => 'Lead / Principal',
        };
    }
}
