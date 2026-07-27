<?php

namespace App\Enums;

enum StudentStatusType: string
{
    case Admission = 'admission';
    case Verification = 'verification';
    case CentreVisit = 'centre_visit';

    public function label(): string
    {
        return match ($this) {
            self::Admission => 'Admission',
            self::Verification => 'Verification',
            self::CentreVisit => 'Centre Visit',
        };
    }
}
