<?php

namespace Database\Factories;

use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resource>
 */
class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'published' => $this->faker->boolean(),
            'validated' => $this->faker->boolean(),
            'link' => $this->faker->url(),
            'file_path' => $this->faker->filePath(),
            'file_type' => $this->faker->fileExtension(),
            'file_size' => $this->faker->numberBetween(100, 5000), // en Ko
            'type_id' => \App\Models\Type::inRandomOrder()->value('id'),
            'category_id' => \App\Models\Category::inRandomOrder()->value('id'),
            'visibility_id' => \App\Models\Visibility::inRandomOrder()->value('id'),
            'user_id' => \App\Models\User::inRandomOrder()->value('id'),
            'origin_id' => \App\Models\Origin::inRandomOrder()->value('id'),
        ];
    }
}
