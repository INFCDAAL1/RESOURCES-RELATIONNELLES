<?php

namespace Database\Factories;

use App\Models\ResourceInteraction;
use App\Models\User;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResourceInteractionFactory extends Factory
{
    protected $model = ResourceInteraction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $interactionTypes = ['favorite', 'saved', 'exploited'];
        
        return [
            'user_id' => User::factory(),
            'resource_id' => Resource::factory(),
            'type' => $this->faker->randomElement($interactionTypes),
            'notes' => $this->faker->optional(0.3)->paragraph(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Configure the interaction to be a favorite.
     */
    public function favorite(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'favorite',
        ]);
    }

    /**
     * Configure the interaction to be saved.
     */
    public function saved(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'saved',
        ]);
    }

    /**
     * Configure the interaction to be exploited.
     */
    public function exploited(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'exploited',
        ]);
    }
}