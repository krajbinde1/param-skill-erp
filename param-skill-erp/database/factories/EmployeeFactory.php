<?php

namespace Database\Factories;

use App\Enums\EmployeeRole;
use App\Enums\EmployeeStatus;
use App\Models\Centre;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'centre_id' => Centre::factory(),
            'user_id' => null,
            'employee_code' => 'EMP'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'father_husband_name' => fake()->optional()->name(),
            'mobile' => '9'.fake()->numerify('#########'),
            'alternate_mobile' => null,
            'email' => fake()->optional()->safeEmail(),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'date_of_birth' => fake()->optional()->date(),
            'pan_number' => null,
            'address' => fake()->optional()->address(),
            'village' => fake()->optional()->city(),
            'taluka' => fake()->optional()->city(),
            'district' => fake()->randomElement(['Pune', 'Nagpur', 'Nashik']),
            'state' => 'Maharashtra',
            'pincode' => fake()->numerify('######'),
            'employee_role' => fake()->randomElement(EmployeeRole::cases()),
            'joining_date' => now()->subMonth()->toDateString(),
            'salary' => fake()->optional()->randomFloat(2, 10000, 40000),
            'status' => EmployeeStatus::Active,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => EmployeeStatus::Inactive]);
    }
}
