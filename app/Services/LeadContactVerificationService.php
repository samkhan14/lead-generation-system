<?php

namespace App\Services;

use App\Support\LeadIdentifiers;
use Illuminate\Support\Facades\Http;

class LeadContactVerificationService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{passed: bool, email: array<string, mixed>, phone: array<string, mixed>, errors: array<int, string>}
     */
    public function verify(array $payload): array
    {
        $email = $this->verifyEmail($payload);
        $phone = $this->verifyPhone($payload['phone'] ?? null);

        $errors = [];

        if (! $email['passed']) {
            $errors[] = $email['message'];
        }

        if (! $phone['passed']) {
            $errors[] = $phone['message'];
        }

        return [
            'passed' => $email['passed'] && $phone['passed'],
            'email' => $email,
            'phone' => $phone,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{passed: bool, value: ?string, method: string, message: string}
     */
    private function verifyEmail(array $payload): array
    {
        $email = $payload['email'] ?? null;
        $email = is_string($email) ? strtolower(trim($email)) : '';

        if ($email === '') {
            $email = $this->discoverEmailFromWebsite($payload['website'] ?? null);
        }

        if ($email === '') {
            return [
                'passed' => false,
                'value' => null,
                'method' => 'local_validation',
                'message' => 'Email is required before lead storage.',
            ];
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'passed' => false,
                'value' => $email,
                'method' => 'local_validation',
                'message' => 'Email format is invalid.',
            ];
        }

        if ((bool) config('lead_quality.contact_verification.email_dns_check', false)) {
            $domain = substr($email, strrpos($email, '@') + 1);

            if (! checkdnsrr($domain, 'MX') && ! checkdnsrr($domain, 'A')) {
                return [
                    'passed' => false,
                    'value' => $email,
                    'method' => 'dns_check',
                    'message' => 'Email domain has no reachable DNS records.',
                ];
            }
        }

        return [
            'passed' => true,
            'value' => $email,
            'method' => (bool) config('lead_quality.contact_verification.email_dns_check', false)
                ? 'format_dns_check'
                : 'format_check',
            'message' => 'Email passed pre-store verification.',
        ];
    }

    private function discoverEmailFromWebsite(mixed $website): string
    {
        if (! (bool) config('lead_quality.contact_verification.discover_email_from_website', true)) {
            return '';
        }

        $website = is_string($website) ? LeadIdentifiers::sanitizeBusinessWebsite($website) : null;

        if ($website === null || $this->isUnsafeDiscoveryUrl($website)) {
            return '';
        }

        try {
            $response = Http::timeout((int) config('lead_quality.contact_verification.website_email_timeout', 6))
                ->withOptions(['allow_redirects' => true])
                ->get($website);
        } catch (\Throwable) {
            return '';
        }

        if (! $response->successful()) {
            return '';
        }

        $body = html_entity_decode($response->body());

        if (preg_match('/mailto:([a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,})/i', $body, $match)) {
            return strtolower($match[1]);
        }

        if (preg_match('/\b([a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,})\b/i', $body, $match)) {
            return strtolower($match[1]);
        }

        return '';
    }

    private function isUnsafeDiscoveryUrl(string $website): bool
    {
        $host = strtolower((string) parse_url($website, PHP_URL_HOST));

        return $host === ''
            || $host === 'localhost'
            || str_ends_with($host, '.local')
            || filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
                && filter_var($host, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * @return array{passed: bool, value: ?string, normalized: ?string, method: string, message: string}
     */
    private function verifyPhone(mixed $phone): array
    {
        $phone = is_string($phone) ? trim($phone) : '';
        $digits = LeadIdentifiers::normalizePhone($phone);
        $minDigits = (int) config('lead_quality.contact_verification.min_phone_digits', 7);

        if ($phone === '' || $digits === null) {
            return [
                'passed' => false,
                'value' => null,
                'normalized' => null,
                'method' => 'local_validation',
                'message' => 'Phone is required before lead storage.',
            ];
        }

        if (strlen($digits) < $minDigits) {
            return [
                'passed' => false,
                'value' => $phone,
                'normalized' => $digits,
                'method' => 'digit_length_check',
                'message' => "Phone must have at least {$minDigits} digits.",
            ];
        }

        if (preg_match('/^(\d)\1+$/', $digits) === 1) {
            return [
                'passed' => false,
                'value' => $phone,
                'normalized' => $digits,
                'method' => 'pattern_check',
                'message' => 'Phone appears fake or repeated.',
            ];
        }

        return [
            'passed' => true,
            'value' => $phone,
            'normalized' => $digits,
            'method' => 'digit_pattern_check',
            'message' => 'Phone passed pre-store verification.',
        ];
    }
}
