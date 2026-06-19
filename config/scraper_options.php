<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Business types (warm channels: Google Maps, Yelp, OSM)
    |--------------------------------------------------------------------------
    | Sent as scrape keyword. Grouped for the scraper UI dropdown.
    */
    'business_types' => [
        'Food & Beverage' => [
            ['value' => 'restaurant', 'label' => 'Restaurant'],
            ['value' => 'cafe', 'label' => 'Cafe / Coffee shop'],
            ['value' => 'bakery', 'label' => 'Bakery'],
            ['value' => 'fast food', 'label' => 'Fast food'],
            ['value' => 'bar', 'label' => 'Bar / Pub'],
            ['value' => 'catering', 'label' => 'Catering'],
            ['value' => 'food truck', 'label' => 'Food truck'],
        ],
        'Healthcare & Wellness' => [
            ['value' => 'dentist', 'label' => 'Dentist'],
            ['value' => 'clinic', 'label' => 'Clinic / Doctors'],
            ['value' => 'hospital', 'label' => 'Hospital'],
            ['value' => 'pharmacy', 'label' => 'Pharmacy'],
            ['value' => 'physiotherapy', 'label' => 'Physiotherapy'],
            ['value' => 'veterinary', 'label' => 'Veterinary / Pet clinic'],
            ['value' => 'gym', 'label' => 'Gym / Fitness centre'],
            ['value' => 'spa', 'label' => 'Spa / Wellness'],
            ['value' => 'salon', 'label' => 'Salon / Beauty'],
            ['value' => 'barber', 'label' => 'Barber'],
        ],
        'Retail & Shopping' => [
            ['value' => 'supermarket', 'label' => 'Supermarket / Grocery'],
            ['value' => 'electronics', 'label' => 'Electronics'],
            ['value' => 'computer', 'label' => 'Computer shop'],
            ['value' => 'clothing', 'label' => 'Clothing / Fashion'],
            ['value' => 'jewelry', 'label' => 'Jewelry'],
            ['value' => 'furniture', 'label' => 'Furniture'],
            ['value' => 'hardware', 'label' => 'Hardware store'],
            ['value' => 'pet', 'label' => 'Pet shop'],
            ['value' => 'florist', 'label' => 'Florist'],
            ['value' => 'bookstore', 'label' => 'Bookstore'],
            ['value' => 'mobile phone', 'label' => 'Mobile phone shop'],
        ],
        'Home & Trade Services' => [
            ['value' => 'plumber', 'label' => 'Plumber'],
            ['value' => 'electrician', 'label' => 'Electrician'],
            ['value' => 'contractor', 'label' => 'General contractor'],
            ['value' => 'cleaning', 'label' => 'Cleaning service'],
            ['value' => 'pest control', 'label' => 'Pest control'],
            ['value' => 'locksmith', 'label' => 'Locksmith'],
            ['value' => 'painter', 'label' => 'Painter / Decorator'],
            ['value' => 'landscaping', 'label' => 'Landscaping / Gardening'],
            ['value' => 'hvac', 'label' => 'HVAC / Air conditioning'],
            ['value' => 'roofing', 'label' => 'Roofing'],
        ],
        'Automotive' => [
            ['value' => 'garage', 'label' => 'Auto repair / Garage'],
            ['value' => 'mechanic', 'label' => 'Mechanic'],
            ['value' => 'car wash', 'label' => 'Car wash'],
            ['value' => 'car dealer', 'label' => 'Car dealer'],
            ['value' => 'tyre shop', 'label' => 'Tyre shop'],
            ['value' => 'auto parts', 'label' => 'Auto parts'],
        ],
        'Professional Services' => [
            ['value' => 'lawyer', 'label' => 'Lawyer / Law firm'],
            ['value' => 'accountant', 'label' => 'Accountant / Tax'],
            ['value' => 'real estate', 'label' => 'Real estate agent'],
            ['value' => 'insurance', 'label' => 'Insurance agency'],
            ['value' => 'consultant', 'label' => 'Consultant'],
            ['value' => 'architect', 'label' => 'Architect'],
            ['value' => 'marketing agency', 'label' => 'Marketing agency'],
            ['value' => 'web design', 'label' => 'Web design / Digital agency'],
        ],
        'Education & Training' => [
            ['value' => 'school', 'label' => 'School'],
            ['value' => 'university', 'label' => 'University / College'],
            ['value' => 'training centre', 'label' => 'Training centre'],
            ['value' => 'language school', 'label' => 'Language school'],
            ['value' => 'driving school', 'label' => 'Driving school'],
            ['value' => 'tutoring', 'label' => 'Tutoring'],
        ],
        'Hospitality & Travel' => [
            ['value' => 'hotel', 'label' => 'Hotel'],
            ['value' => 'guest house', 'label' => 'Guest house / B&B'],
            ['value' => 'travel agency', 'label' => 'Travel agency'],
            ['value' => 'event venue', 'label' => 'Event venue'],
        ],
        'Finance & Property' => [
            ['value' => 'bank', 'label' => 'Bank'],
            ['value' => 'atm', 'label' => 'ATM / Branch'],
            ['value' => 'mortgage broker', 'label' => 'Mortgage broker'],
            ['value' => 'property management', 'label' => 'Property management'],
        ],
        'Other Services' => [
            ['value' => 'laundry', 'label' => 'Laundry / Dry cleaning'],
            ['value' => 'tailor', 'label' => 'Tailor'],
            ['value' => 'photography', 'label' => 'Photography studio'],
            ['value' => 'printing', 'label' => 'Printing / Copy shop'],
            ['value' => 'courier', 'label' => 'Courier / Logistics'],
            ['value' => 'storage', 'label' => 'Storage / Warehouse'],
            ['value' => 'daycare', 'label' => 'Daycare / Nursery'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reddit intent keywords (hot channel — keyword field)
    |--------------------------------------------------------------------------
    */
    'intent_keywords' => [
        'Website & Development' => [
            ['value' => 'need a website', 'label' => 'Need a website'],
            ['value' => 'looking for a website', 'label' => 'Looking for a website'],
            ['value' => 'need a developer', 'label' => 'Need a developer'],
            ['value' => 'redesign my website', 'label' => 'Redesign my website'],
            ['value' => 'need a landing page', 'label' => 'Need a landing page'],
            ['value' => 'need an app', 'label' => 'Need an app'],
            ['value' => 'build me a website', 'label' => 'Build me a website'],
        ],
        'SEO & Marketing' => [
            ['value' => 'need SEO help', 'label' => 'Need SEO help'],
            ['value' => 'looking for SEO', 'label' => 'Looking for SEO'],
            ['value' => 'digital marketing help', 'label' => 'Digital marketing help'],
            ['value' => 'social media manager', 'label' => 'Social media manager'],
            ['value' => 'google ads help', 'label' => 'Google Ads help'],
        ],
        'Design & Branding' => [
            ['value' => 'need a logo', 'label' => 'Need a logo'],
            ['value' => 'brand identity help', 'label' => 'Brand identity help'],
            ['value' => 'UI UX designer', 'label' => 'UI/UX designer'],
        ],
        'Business & Recommendations' => [
            ['value' => 'recommend a web agency', 'label' => 'Recommend a web agency'],
            ['value' => 'looking for agency', 'label' => 'Looking for agency'],
            ['value' => 'who can build', 'label' => 'Who can build…'],
            ['value' => 'feedback on my website', 'label' => 'Feedback on my website'],
        ],
    ],

];
