<?php

namespace App\Domains\Email\Contracts;

use App\Domains\Email\DataTransferObjects\EmailDispatchResult;
use App\Domains\Email\DataTransferObjects\EmailMessagePayload;
use App\Domains\Email\Models\EmailProvider;

interface EmailProviderInterface
{
    public function slug(): string;

    public function send(EmailProvider $provider, EmailMessagePayload $message): EmailDispatchResult;
}
