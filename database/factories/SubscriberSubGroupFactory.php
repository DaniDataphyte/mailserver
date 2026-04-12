<?php

namespace Database\Factories;

use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubscriberSubGroupFactory extends Factory
{
    protected $model = SubscriberSubGroup::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();
        return [
            'subscriber_group_id' => SubscriberGroup::factory(),
            'name'                => ucfirst($name),
            'slug'                => Str::slug($name),
            'description'         => fake()->sentence(),
        ];
    }
}
