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
];
