<?php

namespace Database\Factories;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubscriberFactory extends Factory
{
    protected $model = Subscriber::class;

    public function definition(): array
    {
        return [
            'email'              => fake()->unique()->safeEmail(),
            'first_name'         => fake()->firstName(),
            'last_name'          => fake()->lastName(),
            'status'             => 'active',
            'confirmation_token' => Str::uuid()->toString(),
            'confirmed_at'       => now(),
            'ip_address'         => fake()->ipv4(),
            'user_agent'         => fake()->userAgent(),
            'metadata'           => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function unsubscribed(): static
    {
        return $this->state([
            'status'          => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);
    }

    public function bounced(): static
    {
        return $this->state(['status' => 'bounced']);
    }

    public function erased(): static
    {
        return $this->state([
            'status'     => 'erased',
            'email'      => 'deleted-' . fake()->randomNumber() . '@deleted.invalid',
            'first_name' => null,
            'last_name'  => null,
        ]);
    }
}
