<?php

namespace Domain\Football\Services;

use Domain\Football\Models\FootballMatch;
use InvalidArgumentException;

final class AuthorizedStreamResolver
{
    public function resolve(FootballMatch $fixture): ?string
    {
        $streams = config('football.authorized_streams');

        if (! is_array($streams)) {
            throw new InvalidArgumentException('FOOTBALL_AUTHORIZED_STREAMS must be a JSON object of fixture slugs to HTTPS stream URLs.');
        }

        if (! array_key_exists($fixture->slug, $streams)) {
            return null;
        }

        $url = $streams[$fixture->slug];
        $parts = is_string($url) ? parse_url($url) : false;

        $validUrl = is_string($url) && filter_var($url, FILTER_VALIDATE_URL) !== false;

        if (
            ! $validUrl
            || ! is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            throw new InvalidArgumentException("The configured stream for [{$fixture->slug}] must be a valid HTTPS URL with no embedded credentials.");
        }

        $host = strtolower(rtrim($parts['host'] ?? '', '.'));
        $allowedHosts = config('football.authorized_stream_hosts');
        $extension = strtolower(pathinfo($parts['path'] ?? '', PATHINFO_EXTENSION));

        if (
            ! is_array($allowedHosts)
            || ! in_array($host, $allowedHosts, true)
            || ! in_array($extension, ['m3u8', 'mp4'], true)
        ) {
            throw new InvalidArgumentException("The stream for [{$fixture->slug}] must use an allowed HTTPS host and an .m3u8 or .mp4 path. Check FOOTBALL_STREAM_ALLOWED_HOSTS.");
        }

        return $url;
    }
}
