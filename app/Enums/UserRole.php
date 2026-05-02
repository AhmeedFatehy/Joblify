<?php

namespace App\Enums;

enum UserRole: string
{
    case CANDIDATE = 'candidate';
    case EMPLOYER = 'employer';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::CANDIDATE => 'Candidate',
            self::EMPLOYER => 'Employer',
            self::ADMIN => 'Admin',
        };
    }

    public function isCandidate(): bool
    {
        return $this === self::CANDIDATE;
    }

    public function isEmployer(): bool
    {
        return $this === self::EMPLOYER;
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
