<?php

return [

    'stages' => [
        'qualified' => ['label' => 'Qualified', 'sort' => 10, 'probability' => 20],
        'proposal' => ['label' => 'Proposal sent', 'sort' => 20, 'probability' => 40],
        'negotiation' => ['label' => 'Negotiation', 'sort' => 30, 'probability' => 70],
        'won' => ['label' => 'Won', 'sort' => 40, 'probability' => 100, 'terminal' => true],
        'lost' => ['label' => 'Lost', 'sort' => 50, 'probability' => 0, 'terminal' => true],
    ],

    'default_stage' => 'qualified',

    'default_currency' => 'USD',

    'lost_reasons' => [
        'no_budget' => 'No budget',
        'no_response' => 'No response',
        'chose_competitor' => 'Chose competitor',
        'bad_fit' => 'Bad fit',
        'timing' => 'Bad timing',
        'other' => 'Other',
    ],

];
