<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookEvent;
use App\Models\Banking\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(string $event, Request $request)
    {
        $validEventTypes = ['transfer', 'payment', 'account', 'compliance'];

        if (! in_array($event, $validEventTypes)) {
            return response()->json(['error' => 'Invalid event type'], 400);
        }

        $secret = config('services.banking_webhook.secret');

        if ($secret) {
            $signature = $request->header('X-Webhook-Signature');
            $expected = hash_hmac('sha256', $request->getContent(), $secret);

            if (! is_string($signature) || ! hash_equals($expected, $signature)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $payload = $request->all();

        try {
            $webhookEvent = WebhookEvent::create([
                'event_type' => $event,
                'source' => $request->header('X-Webhook-Source', 'external'),
                'payload' => $payload,
                'status' => 'pending',
            ]);

            ProcessWebhookEvent::dispatch($webhookEvent);

            Log::info('Webhook received and dispatched', [
                'event' => $event,
                'source' => $request->header('X-Webhook-Source'),
            ]);

            return response()->json(['status' => 'accepted'], 202);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
}
