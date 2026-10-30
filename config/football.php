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
    'football_data_api_key' => env('FOOTBALL_DATA_API_KEY'),
    'sportsdb_api_url' => env('THESPORTSDB_API_URL', 'https://www.thesportsdb.com/api/v1/json/123/'),
    'openfootball_base_url' => env('OPENFOOTBALL_BASE_URL', 'https://raw.githubusercontent.com/openfootball/football.json/master/'),
];
