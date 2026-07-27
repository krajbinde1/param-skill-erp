<?php

namespace App\Enums;

enum StudentVerificationStatus: string
{
    case Pending = 'Pending';
    case UnderVerification = 'Under Verification';
    case Verified = 'Verified';
    case Rejected = 'Rejected';
    case CorrectionRequired = 'Correction Required';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending, self::UnderVerification => 'warning',
            self::Verified => 'success',
            self::Rejected, self::CorrectionRequired => 'danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
