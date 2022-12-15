<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => $this->faker->words(3, asText: true),
            'content' => [
                [
                    'section' => $this->faker->word,
                    'value' => $this->faker->text,
                ],
                [
                    'section' => $this->faker->word,
                    'value' => $this->faker->text,
                ],
            ],
            'is_premium' => $this->faker->boolean,
            'published_at' => Carbon::now(),
            'category_id' => Category::factory(),
            'user_id' => User::factory(),
            'published_by' => User::factory(),
        ];
    }

    public function unpublished(): self
    {
        return $this->state([
            'published_by' => null,
            'published_at' => null,
        ]);
    }

    public function premium(): self
    {
        return $this->state([
            'is_premium' => true,
        ]);
    }

    public function free(): self
    {
        return $this->state([
            'is_premium' => false,
        ]);
    }
}
