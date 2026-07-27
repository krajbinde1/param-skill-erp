<?php

namespace Database\Factories;

use App\Enums\CentreStatus;
use App\Models\Centre;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Centre>
 */
class CentreFactory extends Factory
{
    protected $model = Centre::class;

    public function definition(): array
    {
        return [
            'centre_code' => 'PSC'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'centre_name' => fake()->company().' Skill Centre',
            'scheme_name' => fake()->optional()->words(3, true),
            'project_name' => fake()->optional()->words(2, true),
            'manager_name' => fake()->name(),
            'manager_mobile' => '9'.fake()->numerify('#########'),
            'manager_email' => fake()->optional()->safeEmail(),
            'address' => fake()->streetAddress(),
            'village_city' => fake()->city(),
            'taluka' => fake()->city(),
            'district' => fake()->randomElement(['Pune', 'Nagpur', 'Nashik', 'Aurangabad', 'Solapur']),
            'state' => 'Maharashtra',
            'pincode' => fake()->numerify('######'),
            'centre_capacity' => 100,
            'boys_capacity' => 50,
            'girls_capacity' => 50,
            'hostel_available' => fake()->boolean(),
            'latitude' => fake()->optional()->latitude(15, 22),
            'longitude' => fake()->optional()->longitude(72, 80),
            'opening_date' => now()->subMonths(3)->toDateString(),
            'agreement_start_date' => now()->subMonths(3)->toDateString(),
            'agreement_end_date' => now()->addYear()->toDateString(),
            'status' => CentreStatus::Active,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => CentreStatus::Inactive]);
    }
}
