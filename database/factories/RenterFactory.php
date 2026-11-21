<?php

namespace Database\Factories;

use App\Enums\RenterStatus;
use App\Models\Renter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Renter>
 */
class RenterFactory extends Factory
{
    /**
     * Define the model's default state (penyewa sudah disetujui).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_user' => User::factory(),
            'phone_number' => fake()->numerify('08##########'),
            'status' => RenterStatus::Approved,
            'nik' => fake()->unique()->numerify('################'),
        ];
    }

    /** Penyewa yang belum diverifikasi Admin. */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => RenterStatus::Pending]);
    }
}
