<?php

namespace App\Domains\Email\Services;

use App\Domains\Email\Contracts\EmailProviderInterface;
use App\Domains\Email\Services\Connectors\LogEmailConnector;
use App\Domains\Email\Services\Connectors\ResendEmailConnector;
use App\Domains\Email\Services\Connectors\SmtpEmailConnector;
use InvalidArgumentException;

class EmailConnectorRegistry
{
    /** @var array<string, EmailProviderInterface> */
    private array $connectors;

    public function __construct(
        ?LogEmailConnector $log = null,
        ?SmtpEmailConnector $smtp = null,
        ?ResendEmailConnector $resend = null,
    ) {
        $this->connectors = collect([
            $log ?? new LogEmailConnector,
            $smtp ?? new SmtpEmailConnector,
            $resend ?? new ResendEmailConnector,
        ])->keyBy(fn (EmailProviderInterface $connector) => $connector->slug())->all();
    }

    public function get(string $slug): EmailProviderInterface
    {
        $driver = config('email_platform.provider_drivers.'.$slug, $slug);

        if (! isset($this->connectors[$driver])) {
            throw new InvalidArgumentException("No email connector registered for provider [{$slug}].");
        }

        return $this->connectors[$driver];
    }
}
