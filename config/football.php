<?php

$authorizedHosts = array_map(
    static fn (string $host): string => strtolower(rtrim(trim($host), '.')),
    array_filter(explode(',', (string) env('FOOTBALL_STREAM_ALLOWED_HOSTS', ''))),
);

return [
    'authorized_streams' => json_decode(
        (string) env('FOOTBALL_AUTHORIZED_STREAMS', '{}'),
        true,
        flags: JSON_THROW_ON_ERROR,
    ),
    'authorized_stream_hosts' => array_values($authorizedHosts),
    'feed_secret' => (string) env('FOOTBALL_FEED_SECRET', ''),

    // Official Registered APIs
    'football_data_api_key' => env('FOOTBALL_DATA_API_KEY'),
    'api_football_key' => env('API_FOOTBALL_KEY'),
    'api_football_host' => env('API_FOOTBALL_HOST', 'v3.football.api-sports.io'),

    // Free & Open Data Feeds
    'sportsdb_api_url' => env('THESPORTSDB_API_URL', 'https://www.thesportsdb.com/api/v1/json/123/'),
    'openfootball_base_url' => env('OPENFOOTBALL_BASE_URL', 'https://raw.githubusercontent.com/openfootball/football.json/master/'),
];
