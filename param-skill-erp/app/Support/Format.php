<?php

namespace App\Support;

use Carbon\CarbonInterface;
use NumberFormatter;

class Format
{
    public static function date(?CarbonInterface $value): ?string
    {
        return $value?->timezone(config('app.timezone'))->format(config('erp.date_format'));
    }

    public static function time(?CarbonInterface $value): ?string
    {
        return $value?->timezone(config('app.timezone'))->format(config('erp.time_format'));
    }

    public static function datetime(?CarbonInterface $value): ?string
    {
        return $value?->timezone(config('app.timezone'))->format(config('erp.datetime_format'));
    }

    public static function currency(float|int|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return config('erp.currency_symbol').'0.00';
        }

        if (extension_loaded('intl')) {
            $formatter = new NumberFormatter(config('erp.currency_locale'), NumberFormatter::CURRENCY);

            return $formatter->formatCurrency((float) $amount, config('erp.currency')) ?: self::fallbackCurrency((float) $amount);
        }

        return self::fallbackCurrency((float) $amount);
    }

    protected static function fallbackCurrency(float $amount): string
    {
        return config('erp.currency_symbol').number_format($amount, 2, '.', ',');
    }
}
