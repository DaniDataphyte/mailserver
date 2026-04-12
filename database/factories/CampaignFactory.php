<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'name'             => fake()->sentence(4),
            'collection'       => fake()->randomElement(['insight_newsletters', 'foundation_newsletters']),
            'entry_id'         => null,
            'subject'          => fake()->sentence(),
            'from_name'        => fake()->name(),
            'from_email'       => fake()->safeEmail(),
            'reply_to'         => null,
            'status'           => 'draft',
            'scheduled_at'     => null,
            'sent_at'          => null,
            'total_recipients' => 0,
            'created_by'       => null,
        ];
    }

    public function insight(): static
    {
        return $this->state([
            'collection' => 'insight_newsletters',
            'from_email' => null,
            'from_name'  => null,
        ]);
    }

    public function foundation(): static
    {
        return $this->state([
            'collection' => 'foundation_newsletters',
            'from_email' => null,
            'from_name'  => null,
        ]);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function scheduled(): static
    {
        return $this->state([
            'status'       => 'scheduled',
            'scheduled_at' => now()->addHours(2),
        ]);
    }

    public function due(): static
    {
        return $this->state([
            'status'       => 'scheduled',
            'scheduled_at' => now()->subMinutes(5),
        ]);
    }

    public function sent(): static
    {
        return $this->state([
            'status'  => 'sent',
            'sent_at' => now()->subHours(1),
        ]);
    }
}
