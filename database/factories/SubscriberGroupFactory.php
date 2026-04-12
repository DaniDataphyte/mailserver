<?php

namespace Database\Factories;

use App\Models\SubscriberGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubscriberGroupFactory extends Factory
{
    protected $model = SubscriberGroup::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        return [
            'name'        => ucwords($name),
            'slug'        => Str::slug($name),
            'description' => fake()->sentence(),
        ];
    }

    public function insight(): static
    {
        return $this->state(['name' => 'Insight Subscribers', 'slug' => 'insight-subscribers']);
    }

    public function foundation(): static
    {
        return $this->state(['name' => 'Foundation', 'slug' => 'foundation']);
    }
}
