<?php

namespace App\Enums;

enum StudentDocumentType: string
{
    case AadhaarFront = 'Aadhaar Front';
    case AadhaarBack = 'Aadhaar Back';
    case EducationCertificate = 'Education Certificate';
    case SchoolLeavingCertificate = 'School Leaving Certificate';
    case CasteCertificate = 'Caste Certificate';
    case IncomeCertificate = 'Income Certificate';
    case DomicileCertificate = 'Domicile Certificate';
    case BankPassbook = 'Bank Passbook';
    case PanCard = 'PAN Card';
    case PassportPhoto = 'Passport Photo';
    case RationCard = 'Ration Card';
    case DisabilityCertificate = 'Disability Certificate';
    case Other = 'Other';

    public function label(): string
    {
        return $this->value;
    }

    public function isMandatory(): bool
    {
        return in_array($this, [
            self::AadhaarFront,
            self::AadhaarBack,
            self::EducationCertificate,
            self::BankPassbook,
            self::PassportPhoto,
        ], true);
    }

    /**
     * @return list<self>
     */
    public static function mandatory(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type) => $type->isMandatory()));
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
