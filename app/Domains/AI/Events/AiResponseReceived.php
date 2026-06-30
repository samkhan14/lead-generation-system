<?php

namespace App\Domains\AI\Events;

use App\Domains\AI\DataTransferObjects\AiResponse;
use App\Domains\AI\Models\AiLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiResponseReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AiResponse $response,
        public ?AiLog $log = null,
    ) {}
}
