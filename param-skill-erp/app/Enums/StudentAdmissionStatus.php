<?php

namespace App\Enums;

enum StudentAdmissionStatus: string
{
    case Draft = 'Draft';
    case Submitted = 'Submitted';
    case DocumentVerificationPending = 'Document Verification Pending';
    case DocumentsVerified = 'Documents Verified';
    case CentreVisitPending = 'Centre Visit Pending';
    case CentreVisited = 'Centre Visited';
    case Interested = 'Interested';
    case AdmissionPending = 'Admission Pending';
    case AdmissionConfirmed = 'Admission Confirmed';
    case JoinedCentre = 'Joined Centre';
    case Rejected = 'Rejected';
    case NotInterested = 'Not Interested';
    case OnHold = 'On Hold';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted, self::DocumentVerificationPending, self::CentreVisitPending, self::AdmissionPending => 'warning',
            self::DocumentsVerified, self::CentreVisited, self::Interested => 'info',
            self::AdmissionConfirmed, self::JoinedCentre => 'success',
            self::Rejected, self::NotInterested => 'danger',
            self::OnHold => 'gray',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::AdmissionConfirmed,
            self::JoinedCentre,
            self::Rejected,
            self::NotInterested,
        ], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
