<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_hp_email' => fake()->unique()->safeEmail(),
            'status_otp' => false,
        ];
    }

    /**
     * Indicate that the user's OTP has been verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_otp' => true,
        ]);
    }

    /**
     * Indicate that the user's OTP has not been verified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_otp' => false,
        ]);
    }
}
