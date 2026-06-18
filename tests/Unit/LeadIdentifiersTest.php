<?php

use App\Support\LeadIdentifiers;

test('normalizes email to lowercase trimmed value', function () {
    expect(LeadIdentifiers::normalizeEmail('  Jane@Example.COM '))->toBe('jane@example.com');
});

test('normalizes phone to digits only', function () {
    expect(LeadIdentifiers::normalizePhone('+1 (555) 123-4567'))->toBe('15551234567');
});

test('normalizes website by stripping protocol and www', function () {
    expect(LeadIdentifiers::normalizeWebsite('https://www.Example.com/path/'))->toBe('example.com/path');
});

test('returns null for empty identifiers', function () {
    expect(LeadIdentifiers::normalizeEmail(''))->toBeNull()
        ->and(LeadIdentifiers::normalizePhone(null))->toBeNull()
        ->and(LeadIdentifiers::normalizeWebsite('   '))->toBeNull();
});

test('rejects directory profile urls as business websites', function () {
    expect(LeadIdentifiers::sanitizeBusinessWebsite('https://maps.google.com/maps/place/foo'))->toBeNull();
});
