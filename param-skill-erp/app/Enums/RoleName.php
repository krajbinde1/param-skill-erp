<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'Super Admin';
    case Admin = 'Admin';
    case CentreManager = 'Centre Manager';
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
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
