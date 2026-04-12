<?php

namespace Database\Factories;

use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebhookLogFactory extends Factory
{
    protected $model = WebhookLog::class;

    public function definition(): array
    {
        $txId = Str::uuid()->toString();
        return [
            'event_type'     => fake()->randomElement(['Delivery', 'Open', 'Click', 'Bounce']),
            'transaction_id' => $txId,
            'to_email'       => fake()->safeEmail(),
            'payload'        => [
                'EventType'     => 'Delivery',
                'TransactionID' => $txId,
                'To'            => fake()->safeEmail(),
                'Date'          => now()->toIso8601String(),
            ],
            'processed_at'   => null,
            'error'          => null,
        ];
    }

    public function forEvent(string $event, string $txId, string $email): static
    {
        return $this->state([
            'event_type'     => $event,
            'transaction_id' => $txId,
            'to_email'       => $email,
            'payload'        => [
                'EventType'     => $event,
                'TransactionID' => $txId,
                'To'            => $email,
                'Date'          => now()->toIso8601String(),
            ],
        ]);
    }

    public function processed(): static
    {
        return $this->state(['processed_at' => now()]);
    }

    public function failed(): static
    {
        return $this->state(['error' => 'Subscriber not found']);
    }
}
