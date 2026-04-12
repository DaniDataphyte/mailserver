<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\Newsletter\ProcessWebhookJob;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Receives inbound HTTP notifications from Elastic Email.
 *
 * Elastic Email sends a POST for each delivery event:
 *   Delivery, Open, Click, Unsubscribe, Complaint, Bounce, Error
 *
 * Security: optional shared-secret header check via
 *   ELASTIC_EMAIL_WEBHOOK_SECRET env key.
 *
 * The controller intentionally does minimal work — it logs the raw payload
 * and immediately queues ProcessWebhookJob, keeping response time < 200ms
 * so Elastic Email doesn't retry the request.
 */
class WebhookController extends Controller
{
    public function receive(Request $request): Response
    {
        // Optional secret verification
        if (! $this->verifySecret($request)) {
            Log::warning('WebhookController: invalid secret', [
                'ip' => $request->ip(),
            ]);
            return response('Unauthorized', 401);
        }

        $payload = $this->parsePayload($request);

        if (empty($payload)) {
            return response('Bad Request', 400);
        }

        $eventType     = $this->extractField($payload, ['eventtype', 'EventType', 'event_type', 'status']);
        $transactionId = $this->extractField($payload, ['transactionid', 'TransactionID', 'transaction_id', 'msgid', 'MsgID']);
        $toEmail       = $this->extractField($payload, ['to', 'To', 'recipient', 'Recipient']);

        $log = WebhookLog::create([
            'event_type'     => $eventType,
            'transaction_id' => $transactionId,
            'to_email'       => $toEmail,
            'payload'        => $payload,
        ]);

        ProcessWebhookJob::dispatch($log->id)->onQueue('webhooks');

        return response('OK', 200);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Elastic Email can POST either JSON or form-encoded bodies.
     * Support both so we're not tied to a specific webhook version.
     */
    private function parsePayload(Request $request): array
    {
        $contentType = $request->header('Content-Type', '');

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($request->getContent(), true);
            return is_array($decoded) ? $decoded : [];
        }

        // form-encoded (older Elastic Email notifications)
        return $request->all();
    }

    /**
     * Case-insensitive multi-key lookup — Elastic Email field names
     * vary between notification versions.
     */
    private function extractField(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && $payload[$key] !== '') {
                return (string) $payload[$key];
            }
        }
        return null;
    }

    private function verifySecret(Request $request): bool
    {
        $secret = config('newsletter.webhook_secret');

        if (empty($secret)) {
            return true; // secret not configured — allow all
        }

        // Elastic Email lets you append ?secret=xxx or send X-Webhook-Secret header
        $provided = $request->query('secret')
            ?? $request->header('X-Webhook-Secret');

        return hash_equals($secret, (string) $provided);
    }
}
