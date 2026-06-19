<?php

use App\Support\ScraperOptions;

test('scraper options expose grouped business types and intent keywords', function () {
    $options = ScraperOptions::forUi();

    expect($options['business_type_groups'])->not->toBeEmpty()
        ->and($options['intent_keyword_groups'])->not->toBeEmpty()
        ->and($options['business_type_groups'][0])->toHaveKeys(['label', 'options'])
        ->and(ScraperOptions::keywordValuesFor('google_maps'))->toContain('restaurant', 'dentist');
});

test('reddit channel uses intent keywords', function () {
    expect(ScraperOptions::keywordValuesFor('reddit'))->toContain('need a website');
});
