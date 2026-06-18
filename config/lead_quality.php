<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Directory lead sources
  |--------------------------------------------------------------------------
  | Leads from these sources are merged on re-ingest when new data fills gaps.
  */
  'directory_sources' => ['google_maps', 'yelp', 'openstreetmap'],

  /*
  |--------------------------------------------------------------------------
  | Quality fields
  |--------------------------------------------------------------------------
  | Used to compute metadata.data_quality for directory leads.
  */
  'fields' => ['company', 'phone', 'website', 'address'],

];
