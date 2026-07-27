<?php

namespace Database\Factories;

use App\Enums\StudentAdmissionStatus;
use App\Enums\StudentCentreVisitStatus;
use App\Enums\StudentVerificationStatus;
use App\Models\Centre;
use App\Models\Employee;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'student_code' => 'STD'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'centre_id' => Centre::factory(),
            'preferred_centre_id' => null,
            'mobilizer_id' => Employee::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'mobile' => '9'.fake()->numerify('#########'),
            'full_address' => fake()->address(),
            'village' => fake()->city(),
            'taluka' => fake()->city(),
            'district' => fake()->randomElement(['Pune', 'Nagpur', 'Nashik']),
            'state' => 'Maharashtra',
            'hostel_required' => false,
            'admission_status' => StudentAdmissionStatus::Draft,
            'verification_status' => StudentVerificationStatus::Pending,
            'centre_visit_status' => StudentCentreVisitStatus::NotPlanned,
            'version' => 1,
            'created_by' => User::factory(),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'admission_status' => StudentAdmissionStatus::DocumentVerificationPending,
            'submitted_at' => now(),
        ]);
    }
}
