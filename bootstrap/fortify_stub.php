<?php

namespace Laravel\Fortify;

/**
 * Minimal Fortify Features stub used by tests when Fortify is not installed.
 * Tests check for class existence and call these helpers; returning false
 * from `enabled()` causes Fortify-dependent tests to be skipped.
 */
class Features
{
    public static function enabled(string $feature): bool
    {
        return false;
    }

    public static function twoFactorAuthentication($options = [])
    {
        return 'two-factor-authentication';
    }

    public static function emailVerification()
    {
        return 'email-verification';
    }

    public static function resetPasswords()
    {
        return 'reset-passwords';
    }

    public static function registration()
    {
        return 'registration';
    }
}
