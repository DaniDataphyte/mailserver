<?php

namespace Tests\Feature;

use App\Http\Controllers\CP\Newsletter\GdprController;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Tests for GDPR data export and erasure.
 *
 * We call the controller methods directly to keep tests independent of
 * Statamic's CP authentication middleware.
 */
class GdprTest extends TestCase
{
    private GdprController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new GdprController();
    }

    /* ------------------------------------------------------------------ */
    /* Erasure Business Rules                                               */
    /* ------------------------------------------------------------------ */

    public function test_erase_anonymises_personal_data(): void
    {
        $subscriber = Subscriber::factory()->create([
            'email'      => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'ip_address' => '192.168.1.1',
            'status'     => 'active',
        ]);

        $this->callErase($subscriber);

        $fresh = $subscriber->fresh();

        $this->assertEquals('erased', $fresh->status);
        $this->assertStringContainsString('@deleted.invalid', $fresh->email);
        $this->assertNull($fresh->first_name);
        $this->assertNull($fresh->last_name);
        $this->assertNull($fresh->ip_address);
    }

    public function test_erase_detaches_subscriber_from_all_sub_groups(): void
    {
        $group    = SubscriberGroup::factory()->create();
        $subGroup = SubscriberSubGroup::factory()->create(['subscriber_group_id' => $group->id]);

        $subscriber = Subscriber::factory()->create();
        $subscriber->allSubGroups()->attach($subGroup->id, ['subscribed_at' => now()]);

        $this->assertCount(1, $subscriber->allSubGroups()->get());

        $this->callErase($subscriber);

        $this->assertCount(0, $subscriber->allSubGroups()->get());
    }

    public function test_erase_preserves_campaign_send_rows_for_statistics(): void
    {
        $subscriber = Subscriber::factory()->create();
        CampaignSend::factory()->delivered()->create(['subscriber_id' => $subscriber->id]);

        $this->callErase($subscriber);

        // The send row must remain — subscriber_id FK is intact for statistical integrity
        $this->assertDatabaseHas('campaign_sends', ['subscriber_id' => $subscriber->id]);
    }

    public function test_erase_fails_without_correct_confirmation_word(): void
    {
        $subscriber = Subscriber::factory()->create(['status' => 'active']);

        $request = Request::create('/gdpr/erase', 'DELETE', ['confirm' => 'WRONG']);

        $this->expectException(ValidationException::class);

        $this->controller->erase($request, $subscriber);
    }

    public function test_erase_sets_anonymised_email_using_subscriber_id(): void
    {
        $subscriber = Subscriber::factory()->create([
            'email' => 'real@example.com',
        ]);

        $id = $subscriber->id;
        $this->callErase($subscriber);

        $this->assertEquals("deleted-{$id}@deleted.invalid", $subscriber->fresh()->email);
    }

    /* ------------------------------------------------------------------ */
    /* Export                                                               */
    /* ------------------------------------------------------------------ */

    public function test_export_returns_json_stream_response(): void
    {
        $subscriber = Subscriber::factory()->create([
            'email'      => 'export.test@example.com',
            'first_name' => 'Export',
            'last_name'  => 'Test',
        ]);

        $response = $this->controller->export($subscriber);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    public function test_export_contains_subscriber_profile(): void
    {
        $subscriber = Subscriber::factory()->create([
            'email'      => 'profile@example.com',
            'first_name' => 'Profile',
            'last_name'  => 'User',
            'status'     => 'active',
        ]);

        $response = $this->controller->export($subscriber);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $data = json_decode($output, true);

        $this->assertArrayHasKey('profile', $data);
        $this->assertEquals('profile@example.com', $data['profile']['email']);
        $this->assertEquals('Profile', $data['profile']['first_name']);
        $this->assertArrayHasKey('exported_at', $data);
        $this->assertArrayHasKey('subscriptions', $data);
        $this->assertArrayHasKey('campaign_history', $data);
    }

    public function test_export_filename_contains_email_slug(): void
    {
        $subscriber = Subscriber::factory()->create([
            'email' => 'test.user@example.com',
        ]);

        $response = $this->controller->export($subscriber);

        $disposition = $response->headers->get('Content-Disposition');

        // Str::slug() transliterates '@' → 'at', check that the file is consistently named
        $this->assertStringContainsString('subscriber-data-', $disposition);
        $this->assertStringContainsString('.json', $disposition);
        $this->assertStringContainsString('example', $disposition);
    }

    public function test_export_includes_campaign_history(): void
    {
        $subscriber = Subscriber::factory()->create();
        $campaign   = Campaign::factory()->sent()->create();
        CampaignSend::factory()->opened()->create([
            'subscriber_id' => $subscriber->id,
            'campaign_id'   => $campaign->id,
        ]);

        $response = $this->controller->export($subscriber);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $data = json_decode($output, true);

        $this->assertCount(1, $data['campaign_history']);
        $this->assertEquals($campaign->name, $data['campaign_history'][0]['campaign']);
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                              */
    /* ------------------------------------------------------------------ */

    /**
     * Call the erase action on the controller, tolerating the
     * RouteNotFoundException thrown when generating the redirect URL —
     * the DB transaction commits before the redirect, so the erasure is
     * complete regardless of whether the named route is registered.
     */
    private function callErase(Subscriber $subscriber): void
    {
        $request = Request::create('/gdpr/erase', 'DELETE', ['confirm' => 'ERASE']);

        try {
            $this->controller->erase($request, $subscriber);
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException|\Symfony\Component\Routing\Exception\RouteNotFoundException|\InvalidArgumentException $e) {
            // Redirect target route not registered in test environment — expected
            if (! str_contains($e->getMessage(), 'not defined') && ! str_contains($e->getMessage(), 'Route')) {
                throw $e;
            }
        }
    }
}
