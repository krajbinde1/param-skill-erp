<?php

namespace App\Http\Resources;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Student */
class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_code' => $this->student_code,
            'client_uuid' => $this->client_uuid,
            'version' => $this->version,
            'centre_id' => $this->centre_id,
            'preferred_centre_id' => $this->preferred_centre_id,
            'mobilizer_id' => $this->mobilizer_id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'mobile' => $this->mobile,
            'parent_mobile' => $this->parent_mobile,
            'email' => $this->email,
            'masked_aadhaar' => $this->masked_aadhaar,
            'full_address' => $this->full_address,
            'village' => $this->village,
            'taluka' => $this->taluka,
            'district' => $this->district,
            'state' => $this->state,
            'pincode' => $this->pincode,
            'hostel_required' => $this->hostel_required,
            'preferred_course' => $this->preferred_course,
            'admission_status' => $this->admission_status?->value,
            'verification_status' => $this->verification_status?->value,
            'centre_visit_status' => $this->centre_visit_status?->value,
            'next_follow_up_date' => $this->next_follow_up_date?->format('Y-m-d'),
            'centre' => $this->whenLoaded('centre', fn () => [
                'id' => $this->centre?->id,
                'centre_code' => $this->centre?->centre_code,
                'centre_name' => $this->centre?->centre_name,
            ]),
            'preferred_centre' => $this->whenLoaded('preferredCentre', fn () => [
                'id' => $this->preferredCentre?->id,
                'centre_code' => $this->preferredCentre?->centre_code,
                'centre_name' => $this->preferredCentre?->centre_name,
            ]),
            'mobilizer' => $this->whenLoaded('mobilizer', fn () => [
                'id' => $this->mobilizer?->id,
                'employee_code' => $this->mobilizer?->employee_code,
                'full_name' => $this->mobilizer?->full_name,
            ]),
            'documents' => $this->whenLoaded('documents'),
            'follow_ups' => $this->whenLoaded('followUps'),
            'centre_visits' => $this->whenLoaded('centreVisits'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
