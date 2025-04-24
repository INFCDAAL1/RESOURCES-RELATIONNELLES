<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['pending', 'accepted', 'declined'];
        
        return [
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'resource_id' => Resource::factory(),
            'status' => $this->faker->randomElement($statuses),
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Configure the invitation to be pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Configure the invitation to be accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
        ]);
    }

    /**
     * Configure the invitation to be declined.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
        ]);
    }
}