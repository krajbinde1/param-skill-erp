<?php

namespace App\Enums;

enum StudentCentreVisitStatus: string
{
    case NotPlanned = 'Not Planned';
    case VisitPlanned = 'Visit Planned';
    case CentreVisited = 'Centre Visited';
    case VisitConfirmed = 'Visit Confirmed';
    case Cancelled = 'Cancelled';
    case NoShow = 'No Show';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::NotPlanned => 'gray',
            self::VisitPlanned => 'warning',
            self::CentreVisited => 'info',
            self::VisitConfirmed => 'success',
            self::Cancelled, self::NoShow => 'danger',
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
