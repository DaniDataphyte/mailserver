<?php

namespace App\Console\Commands\Newsletter;

use App\Mail\NewsletterMailable;
use App\Models\Campaign;
use App\Models\Subscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Send a test email for a given campaign to one or more addresses.
 *
 * Usage:
 *   php artisan newsletter:test-send --campaign=5 --to=editor@dataphyte.com
 *   php artisan newsletter:test-send --campaign=5 --to=a@b.com,c@d.com
 *
 * The test uses a real (or synthetic) Subscriber record so all personalisation,
 * signed URLs, and UTM injection work exactly as in production.
 */
class TestSend extends Command
{
    protected $signature = 'newsletter:test-send
                              {--campaign=  : Campaign ID to preview}
                              {--to=        : Comma-separated recipient email(s)}';

    protected $description = 'Send a test email for a campaign to a given address';

    public function handle(): int
    {
        $campaignId = $this->option('campaign');
        $toRaw      = $this->option('to');

        if (! $campaignId) {
            $campaignId = $this->ask('Campaign ID?');
        }

        if (! $toRaw) {
            $toRaw = $this->ask('Recipient email(s)? (comma-separated)');
        }

        $campaign = Campaign::find($campaignId);

        if (! $campaign) {
            $this->error("Campaign #{$campaignId} not found.");
            return self::FAILURE;
        }

        $recipients = array_map('trim', explode(',', $toRaw));

        foreach ($recipients as $email) {
            // Resolve or create a synthetic subscriber for the mailable
            $subscriber = Subscriber::where('email', $email)->first()
                ?? $this->syntheticSubscriber($email);

            try {
                $mailable = new NewsletterMailable(
                    campaign: $campaign,
                    subscriber: $subscriber,
                    campaignSendId: 'test-' . now()->timestamp,
                );

                Mail::to($email)->send($mailable);

                $this->info("✓ Test sent to {$email}");

            } catch (\Throwable $e) {
                $this->error("✗ Failed for {$email}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->line("Campaign: <comment>{$campaign->name}</comment>");
        $this->line("Subject:  <comment>{$campaign->subject}</comment>");
        $this->line("From:     <comment>" . implode(' / ', array_values($campaign->sender())) . "</comment>");

        return self::SUCCESS;
    }

    /**
     * Build an in-memory (unsaved) subscriber for the mailable so
     * unsubscribe/preferences signed URLs still resolve.
     */
    private function syntheticSubscriber(string $email): Subscriber
    {
        $subscriber = new Subscriber([
            'email'              => $email,
            'first_name'         => 'Test',
            'last_name'          => 'Recipient',
            'status'             => 'active',
            'confirmation_token' => \Illuminate\Support\Str::uuid()->toString(),
        ]);

        // Give it a fake ID so signed-URL generation doesn't fail
        $subscriber->id = 0;

        return $subscriber;
    }
}
