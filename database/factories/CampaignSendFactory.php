<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CampaignSendFactory extends Factory
{
    protected $model = CampaignSend::class;

    public function definition(): array
    {
        return [
            'campaign_id'                     => Campaign::factory()->sent(),
            'subscriber_id'                   => Subscriber::factory(),
            'status'                          => 'sent',
            'elastic_email_transaction_id'    => Str::uuid()->toString(),
            'sent_at'                         => now()->subHours(1),
            'delivered_at'                    => null,
            'opened_at'                       => null,
            'clicked_at'                      => null,
            'bounced_at'                      => null,
            'failed_at'                       => null,
            'bounce_reason'                   => null,
        ];
    }

    public function delivered(): static
    {
        return $this->state(['status' => 'delivered', 'delivered_at' => now()->subMinutes(50)]);
    }

    public function opened(): static
    {
        return $this->state([
            'status'       => 'opened',
            'delivered_at' => now()->subMinutes(50),
            'opened_at'    => now()->subMinutes(30),
        ]);
    }

    public function bounced(): static
    {
        return $this->state([
            'status'       => 'bounced',
            'bounced_at'   => now()->subMinutes(45),
            'bounce_reason'=> 'mailbox does not exist',
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status'    => 'failed',
            'failed_at' => now()->subMinutes(45),
        ]);
    }
}
