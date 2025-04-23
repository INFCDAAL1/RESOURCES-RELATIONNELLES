<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => $this->faker->paragraph(rand(1, 3)),
            'read' => $this->faker->boolean(60), // 60% de chance d'être lu
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Configure the message to be read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read' => true,
        ]);
    }

    /**
     * Configure the message to be unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read' => false,
        ]);
    }
}