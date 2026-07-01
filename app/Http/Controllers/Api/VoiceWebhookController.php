<?php

namespace App\Http\Controllers\Api;

use App\Domains\Voice\Models\VoiceProvider;
use App\Domains\Voice\Services\VoiceGateway;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoiceWebhookController extends Controller
{
    public function __construct(
        private VoiceGateway $gateway,
    ) {}

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $voiceProvider = VoiceProvider::query()->where('slug', $slug)->firstOrFail();
        if ($voiceProvider->webhook_secret !== null) {
            $provided = $request->header('X-Voice-Webhook-Secret')
                ?? $request->header('X-Retell-Signature')
                ?? $request->input('secret');

            if (! hash_equals((string) $voiceProvider->webhook_secret, (string) $provided)) {
                return response()->json(['message' => 'Invalid webhook secret.'], 401);
            }
        }

        $payload = $request->all();

        if (config('voice_platform.log_webhook_payloads', true)) {
            logger()->info('Voice webhook received', [
                'provider' => $voiceProvider->slug,
                'payload' => mb_substr(json_encode($payload) ?: '', 0, (int) config('voice_platform.log_payload_max_chars', 12000)),
            ]);
        }

        $call = $this->gateway->handleWebhook($voiceProvider, $payload);

        return response()->json([
            'received' => true,
            'call_id' => $call?->id,
            'status' => $call?->status?->value ?? $call?->status,
        ]);
    }
}
