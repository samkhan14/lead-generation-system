<?php

namespace App\Domains\AI\Events;

use App\Domains\AI\DataTransferObjects\AiRequest;
use App\Domains\AI\DataTransferObjects\ProviderSelection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiRequestSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AiRequest $request,
        public ProviderSelection $selection,
    ) {}
}
