<?php

namespace App\Enums;

enum StudentVisitRecordStatus: string
{
    case VisitPlanned = 'Visit Planned';
    case CentreVisited = 'Centre Visited';
    case VisitConfirmed = 'Visit Confirmed';
    case VisitCancelled = 'Visit Cancelled';
    case NoShow = 'No Show';

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
