<?php

namespace App\Support;

class SensitiveData
{
    public static function aadhaarHash(string $aadhaar): string
    {
        $digits = preg_replace('/\D+/', '', $aadhaar) ?? '';

        return hash_hmac('sha256', $digits, (string) config('app.key'));
    }

    public static function aadhaarLast4(string $aadhaar): string
    {
        $digits = preg_replace('/\D+/', '', $aadhaar) ?? '';

        return substr($digits, -4);
    }

    public static function maskAadhaar(?string $last4): string
    {
        if (blank($last4)) {
            return '—';
        }

        return 'XXXX-XXXX-'.$last4;
    }

    public static function maskPan(?string $pan): string
    {
        if (blank($pan) || strlen($pan) < 4) {
            return '—';
        }

        return str_repeat('X', max(strlen($pan) - 4, 0)).substr($pan, -4);
    }

    public static function maskAccountNumber(?string $accountNumber): string
    {
        if (blank($accountNumber)) {
            return '—';
        }

        $plain = (string) $accountNumber;
        $last4 = substr($plain, -4);

        return str_repeat('X', max(strlen($plain) - 4, 0)).$last4;
    }

    public static function normalizeIndianMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    public static function isValidIndianMobile(string $mobile): bool
    {
        return (bool) preg_match('/^[6-9]\d{9}$/', self::normalizeIndianMobile($mobile));
    }

    public static function isValidPan(string $pan): bool
    {
        return (bool) preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', strtoupper($pan));
    }

    public static function isValidAadhaar(string $aadhaar): bool
    {
        $digits = preg_replace('/\D+/', '', $aadhaar) ?? '';

        return (bool) preg_match('/^\d{12}$/', $digits);
    }
}
