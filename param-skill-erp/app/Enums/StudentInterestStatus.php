<?php

namespace App\Enums;

enum StudentInterestStatus: string
{
    case Interested = 'Interested';
    case NotInterested = 'Not Interested';
    case CallBack = 'Call Back';
    case CentreVisitPlanned = 'Centre Visit Planned';
    case AdmissionPending = 'Admission Pending';
    case OnHold = 'On Hold';
    case Unreachable = 'Unreachable';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
