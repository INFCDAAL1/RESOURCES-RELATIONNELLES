<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        $statuses = ['published', 'hidden', 'flagged'];
        
        return [
            'content' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement($statuses),
            'resource_id' => Resource::inRandomOrder()->value('id') ?? 1,
            'user_id' => User::inRandomOrder()->value('id') ?? 1,
            'created_at' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
            'updated_at' => fn (array $attributes) => $attributes['created_at'],
        ];
    }
    
    /**
     * Indicate that the comment is published.
     */
    public function published(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }
    
    /**
     * Indicate that the comment is hidden.
     */
    public function hidden(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'hidden',
        ]);
    }
    
    /**
     * Indicate that the comment is flagged.
     */
    public function flagged(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'flagged',
        ]);
    }
}