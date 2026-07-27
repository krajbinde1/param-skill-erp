<?php

namespace App\Enums;

enum EmployeeRole: string
{
    case Mobilizer = 'Mobilizer';
    case Trainer = 'Trainer';
    case Warden = 'Warden';
    case Watchman = 'Watchman';
    case Housekeeper = 'Housekeeper';
    case Accountant = 'Accountant';
    case DataEntryOperator = 'Data Entry Operator';
    case Cook = 'Cook';
    case Helper = 'Helper';
    case CentreCoordinator = 'Centre Coordinator';
    case OtherStaff = 'Other Staff';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->value])
            ->all();
    }

    public function toSpatieRole(): string
    {
        return $this->value;
    }
}
