<?php

namespace App\Enums;

enum StudentDocumentStatus: string
{
    case NotUploaded = 'Not Uploaded';
    case Uploaded = 'Uploaded';
    case UnderVerification = 'Under Verification';
    case Verified = 'Verified';
    case Rejected = 'Rejected';
    case ReuploadRequired = 'Re-upload Required';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::NotUploaded => 'gray',
            self::Uploaded, self::UnderVerification => 'warning',
            self::Verified => 'success',
            self::Rejected, self::ReuploadRequired => 'danger',
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
