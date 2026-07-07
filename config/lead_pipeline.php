<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lead pipeline stages (single source of truth)
    |--------------------------------------------------------------------------
    */
    'stages' => [
        'new' => [
            'label' => 'New',
            'sort' => 10,
            'color' => 'slate',
        ],
        'contacted' => [
            'label' => 'Contacted',
            'sort' => 20,
            'color' => 'blue',
        ],
        'qualified' => [
            'label' => 'Qualified',
            'sort' => 30,
            'color' => 'indigo',
        ],
        'proposal' => [
            'label' => 'Proposal',
            'sort' => 40,
            'color' => 'violet',
        ],
        'won' => [
            'label' => 'Won',
            'sort' => 50,
            'color' => 'emerald',
            'terminal' => true,
        ],
        'lost' => [
            'label' => 'Lost',
            'sort' => 60,
            'color' => 'red',
            'terminal' => true,
        ],
    ],

    'default_stage' => 'new',

    'lost_reasons' => [
        'no_budget' => 'No budget',
        'no_response' => 'No response',
        'chose_competitor' => 'Chose competitor',
        'bad_fit' => 'Bad fit',
        'timing' => 'Bad timing',
        'other' => 'Other',
    ],

];
