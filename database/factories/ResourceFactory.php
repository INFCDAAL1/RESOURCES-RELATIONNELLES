<?php

namespace Database\Factories;

use App\Models\Resource;
use App\Models\User;
use App\Models\Type;
use App\Models\Category;
use App\Models\Visibility;
use App\Models\Origin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resource>
 */
class ResourceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Resource::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'published' => true,
            'validated' => true,
            'link' => $this->faker->url(),
            'type_id' => Type::factory(),
            'category_id' => Category::factory(),
            'visibility_id' => Visibility::factory(),
            'user_id' => User::factory(),
            'origin_id' => Origin::factory(),
        ];
    }

    /**
     * Define a resource that is private (not published).
     *
     * @return static
     */
    public function private(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'published' => false,
                'validated' => false,
            ];
        });
    }
}