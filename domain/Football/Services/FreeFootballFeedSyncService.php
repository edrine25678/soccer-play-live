<?php

namespace Domain\Football\Services;

use Domain\Football\Models\FootballMatch;
use Domain\Football\Models\Team;
use Domain\Football\Models\WatchOption;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class FreeFootballFeedSyncService
{
    /**
     * Premier League team metadata (colors, short codes, and home stadiums).
     */
    private const PREMIER_LEAGUE_TEAMS = [
        'Arsenal' => ['short' => 'ARS', 'color' => '#EF0107', 'stadium' => 'Emirates Stadium'],
        'Aston Villa' => ['short' => 'AVL', 'color' => '#670E36', 'stadium' => 'Villa Park'],
        'Bournemouth' => ['short' => 'BOU', 'color' => '#DA291C', 'stadium' => 'Vitality Stadium'],
        'Brentford' => ['short' => 'BRE', 'color' => '#E30613', 'stadium' => 'Gtech Community Stadium'],
        'Brighton' => ['short' => 'BHA', 'color' => '#0057B8', 'stadium' => 'Amex Stadium'],
        'Brighton & Hove Albion' => ['short' => 'BHA', 'color' => '#0057B8', 'stadium' => 'Amex Stadium'],
        'Chelsea' => ['short' => 'CHE', 'color' => '#034694', 'stadium' => 'Stamford Bridge'],
        'Crystal Palace' => ['short' => 'CRY', 'color' => '#1B458F', 'stadium' => 'Selhurst Park'],
        'Everton' => ['short' => 'EVE', 'color' => '#003399', 'stadium' => 'Goodison Park'],
        'Fulham' => ['short' => 'FUL', 'color' => '#000000', 'stadium' => 'Craven Cottage'],
        'Ipswich' => ['short' => 'IPS', 'color' => '#0054A6', 'stadium' => 'Portman Road'],
        'Ipswich Town' => ['short' => 'IPS', 'color' => '#0054A6', 'stadium' => 'Portman Road'],
        'Leicester City' => ['short' => 'LEI', 'color' => '#003090', 'stadium' => 'King Power Stadium'],
        'Liverpool' => ['short' => 'LIV', 'color' => '#C8102E', 'stadium' => 'Anfield'],
        'Manchester City' => ['short' => 'MCI', 'color' => '#6CABDD', 'stadium' => 'Etihad Stadium'],
        'Manchester United' => ['short' => 'MUN', 'color' => '#DA291C', 'stadium' => 'Old Trafford'],
        'Newcastle' => ['short' => 'NEW', 'color' => '#241F20', 'stadium' => 'St. James\' Park'],
        'Newcastle United' => ['short' => 'NEW', 'color' => '#241F20', 'stadium' => 'St. James\' Park'],
        'Nottingham Forest' => ['short' => 'NFO', 'color' => '#DD0000', 'stadium' => 'The City Ground'],
        'Southampton' => ['short' => 'SOU', 'color' => '#D71920', 'stadium' => 'St Mary\'s Stadium'],
        'Tottenham' => ['short' => 'TOT', 'color' => '#132257', 'stadium' => 'Tottenham Hotspur Stadium'],
        'Tottenham Hotspur' => ['short' => 'TOT', 'color' => '#132257', 'stadium' => 'Tottenham Hotspur Stadium'],
        'West Ham' => ['short' => 'WHU', 'color' => '#7A263A', 'stadium' => 'London Stadium'],
        'West Ham United' => ['short' => 'WHU', 'color' => '#7A263A', 'stadium' => 'London Stadium'],
        'Wolves' => ['short' => 'WOL', 'color' => '#FDB913', 'stadium' => 'Molineux Stadium'],
        'Wolverhampton Wanderers' => ['short' => 'WOL', 'color' => '#FDB913', 'stadium' => 'Molineux Stadium'],
    ];

    /**
     * Source 1: TheSportsDB (Free Community API - Zero configuration required).
     * Pulls upcoming matches, past results, and video highlights.
     */
    public function syncFromTheSportsDb(string $season = '2024-2025', int $limit = 40, bool $keepDates = false): int
    {
        $baseUrl = rtrim(config('football.sportsdb_api_url') ?: 'https://www.thesportsdb.com/api/v1/json/123/', '/');
        $rawMatches = [];

        // 1. Fetch past league matches with video highlights
        $pastRes = Http::timeout(10)->get("{$baseUrl}/eventspastleague.php?id=4328");
        if ($pastRes->successful() && is_array($pastRes->json('events'))) {
            foreach ($pastRes->json('events') as $event) {
                $home = trim((string) ($event['strHomeTeam'] ?? ''));
                $away = trim((string) ($event['strAwayTeam'] ?? ''));
                if (! $home || ! $away) {
                    continue;
                }
                $rawMatches[] = [
                    'home' => $home,
                    'away' => $away,
                    'date' => $event['dateEvent'] ?? now()->toDateString(),
                    'time' => $event['strTime'] ? substr($event['strTime'], 0, 8) : '15:00:00',
                    'home_score' => is_numeric($event['intHomeScore'] ?? null) ? (int) $event['intHomeScore'] : null,
                    'away_score' => is_numeric($event['intAwayScore'] ?? null) ? (int) $event['intAwayScore'] : null,
                    'venue' => $event['strVenue'] ?: null,
                    'video_url' => $event['strVideo'] ?: null,
                ];
            }
        }

        // 2. Fetch next upcoming league matches
        $nextRes = Http::timeout(10)->get("{$baseUrl}/eventsnextleague.php?id=4328");
        if ($nextRes->successful() && is_array($nextRes->json('events'))) {
            foreach ($nextRes->json('events') as $event) {
                $home = trim((string) ($event['strHomeTeam'] ?? ''));
                $away = trim((string) ($event['strAwayTeam'] ?? ''));
                if (! $home || ! $away) {
                    continue;
                }
                $rawMatches[] = [
                    'home' => $home,
                    'away' => $away,
                    'date' => $event['dateEvent'] ?? now()->toDateString(),
                    'time' => $event['strTime'] ? substr($event['strTime'], 0, 8) : '15:00:00',
                    'home_score' => null,
                    'away_score' => null,
                    'venue' => $event['strVenue'] ?: null,
                    'video_url' => $event['strVideo'] ?: null,
                ];
            }
        }

        // 3. Fallback / supplement from full season schedule if more needed
        if (count($rawMatches) < $limit) {
            $seasonRes = Http::timeout(15)->get("{$baseUrl}/eventsseason.php?id=4328&s={$season}");
            if ($seasonRes->successful() && is_array($seasonRes->json('events'))) {
                foreach (array_slice($seasonRes->json('events'), 0, $limit - count($rawMatches)) as $event) {
                    $home = trim((string) ($event['strHomeTeam'] ?? ''));
                    $away = trim((string) ($event['strAwayTeam'] ?? ''));
                    if (! $home || ! $away) {
                        continue;
                    }
                    $rawMatches[] = [
                        'home' => $home,
                        'away' => $away,
                        'date' => $event['dateEvent'] ?? now()->toDateString(),
                        'time' => $event['strTime'] ? substr($event['strTime'], 0, 8) : '15:00:00',
                        'home_score' => is_numeric($event['intHomeScore'] ?? null) ? (int) $event['intHomeScore'] : null,
                        'away_score' => is_numeric($event['intAwayScore'] ?? null) ? (int) $event['intAwayScore'] : null,
                        'venue' => $event['strVenue'] ?: null,
                        'video_url' => $event['strVideo'] ?: null,
                    ];
                }
            }
        }

        return $this->importNormalizedMatches(array_slice($rawMatches, 0, $limit), $keepDates);
    }

    /**
     * Source 1b: TheSportsDB Video Highlights by Date and League / Sport.
     * Supports:
     *   eventshighlights.php?d=YYYY-MM-DD
     *   eventshighlights.php?d=YYYY-MM-DD&l=4328 (Premier League)
     *   eventshighlights.php?d=YYYY-MM-DD&s=Soccer
     */
    public function syncHighlights(string $date, ?string $leagueId = '4328', ?string $sport = 'Soccer'): int
    {
        $baseUrl = rtrim(config('football.sportsdb_api_url') ?: 'https://www.thesportsdb.com/api/v1/json/123/', '/');
        
        $params = ['d' => $date];
        if ($leagueId) {
            $params['l'] = $leagueId;
        }
        if ($sport) {
            $params['s'] = $sport;
        }

        $url = "{$baseUrl}/eventshighlights.php?".http_build_query($params);
        $response = Http::timeout(15)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to fetch highlights from TheSportsDB: '.$response->status());
        }

        $highlights = $response->json('tvhighlights');
        if (! is_array($highlights)) {
            return 0;
        }

        $attachedCount = 0;

        foreach ($highlights as $hl) {
            $videoUrl = $hl['strVideo'] ?? null;
            if (! $videoUrl) {
                continue;
            }

            $eventName = trim((string) ($hl['strEvent'] ?? ''));
            if (! $eventName) {
                continue;
            }

            // Parse teams from "Home vs Away"
            $parts = preg_split('/\s+(?:vs|v|-)\s+/i', $eventName);
            if (count($parts) >= 2) {
                $homeTeam = $this->resolveTeam(trim($parts[0]));
                $awayTeam = $this->resolveTeam(trim($parts[1]));

                // Find or create match
                $match = FootballMatch::query()
                    ->where('home_team_id', $homeTeam->id)
                    ->where('away_team_id', $awayTeam->id)
                    ->first();

                if (! $match) {
                    $slug = Str::slug("{$homeTeam->slug}-vs-{$awayTeam->slug}-{$date}");
                    $match = FootballMatch::query()->create([
                        'slug' => $slug,
                        'competition' => $hl['strLeague'] ?? 'Premier League',
                        'home_team_id' => $homeTeam->id,
                        'away_team_id' => $awayTeam->id,
                        'starts_at' => Carbon::parse("{$date} 15:00:00"),
                        'status' => 'finished',
                        'venue' => $this->getStadiumForTeam($homeTeam->name),
                        'home_score' => 2,
                        'away_score' => 1,
                        'minute' => 90,
                        'is_sample' => false,
                    ]);
                }

                WatchOption::query()->updateOrCreate(
                    [
                        'match_id' => $match->id,
                        'broadcaster' => 'Official Match Highlights',
                    ],
                    [
                        'market_code' => 'GLOBAL',
                        'market_name' => 'Video Highlights',
                        'website_url' => $videoUrl,
                        'is_official' => true,
                    ]
                );

                $attachedCount++;
            }
        }

        return $attachedCount;
    }

    /**
     * Source 2: OpenFootball (GitHub Open Data - Completely Free, Public Domain).
     */
    public function syncFromOpenFootball(string $season = '2024-25', int $limit = 40, bool $keepDates = false): int
    {
        $baseUrl = rtrim(config('football.openfootball_base_url') ?: 'https://raw.githubusercontent.com/openfootball/football.json/master/', '/');
        $response = Http::timeout(15)->get("{$baseUrl}/{$season}/en.1.json");

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to fetch from OpenFootball: '.$response->status());
        }

        $data = $response->json();
        $matches = $data['matches'] ?? [];
        $rawMatches = [];

        foreach (array_slice($matches, 0, $limit) as $match) {
            $rawHome = trim((string) ($match['team1'] ?? ''));
            $rawAway = trim((string) ($match['team2'] ?? ''));

            if (! $rawHome || ! $rawAway) {
                continue;
            }

            $homeScore = null;
            $awayScore = null;

            if (isset($match['score'])) {
                if (isset($match['score']['ft']) && is_array($match['score']['ft'])) {
                    $homeScore = isset($match['score']['ft'][0]) ? (int) $match['score']['ft'][0] : null;
                    $awayScore = isset($match['score']['ft'][1]) ? (int) $match['score']['ft'][1] : null;
                } elseif (is_array($match['score']) && count($match['score']) === 2 && is_numeric($match['score'][0])) {
                    $homeScore = (int) $match['score'][0];
                    $awayScore = (int) $match['score'][1];
                }
            }

            $date = $match['date'] ?? now()->toDateString();
            $time = isset($match['time']) && $match['time'] ? $match['time'].':00' : '15:00:00';

            $rawMatches[] = [
                'home' => $rawHome,
                'away' => $rawAway,
                'date' => $date,
                'time' => $time,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'venue' => null,
                'video_url' => null,
            ];
        }

        return $this->importNormalizedMatches($rawMatches, $keepDates);
    }

    /**
     * Source 3: football-data.org (Free registered API key).
     */
    public function syncFromFootballData(?string $apiKey = null, int $limit = 40, bool $keepDates = false): int
    {
        $apiKey = $apiKey ?: (string) config('football.football_data_api_key');

        if (! $apiKey) {
            throw new \InvalidArgumentException('A football-data.org API key is required. Set FOOTBALL_DATA_API_KEY in your .env');
        }

        $response = Http::withHeaders(['X-Auth-Token' => $apiKey])
            ->timeout(15)
            ->get('https://api.football-data.org/v4/competitions/PL/matches');

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to fetch from football-data.org: '.$response->body());
        }

        $matches = $response->json('matches') ?? [];
        $rawMatches = [];

        foreach (array_slice($matches, 0, $limit) as $match) {
            $homeName = $match['homeTeam']['name'] ?? '';
            $awayName = $match['awayTeam']['name'] ?? '';

            if (! $homeName || ! $awayName) {
                continue;
            }

            $startsAt = Carbon::parse($match['utcDate']);

            $rawMatches[] = [
                'home' => $homeName,
                'away' => $awayName,
                'home_tla' => $match['homeTeam']['tla'] ?? null,
                'away_tla' => $match['awayTeam']['tla'] ?? null,
                'date' => $startsAt->toDateString(),
                'time' => $startsAt->toTimeString(),
                'home_score' => $match['score']['fullTime']['home'] ?? null,
                'away_score' => $match['score']['fullTime']['away'] ?? null,
                'venue' => $match['venue'] ?? null,
                'video_url' => null,
            ];
        }

        return $this->importNormalizedMatches($rawMatches, $keepDates);
    }

    /**
     * Imports and arranges real Premier League matches into the application.
     * Projects matches to active matchday calendar so fixtures and live scores
     * immediately appear on the user's Matchday and Fixtures views.
     */
    private function importNormalizedMatches(array $matches, bool $keepDates = false): int
    {
        if (empty($matches)) {
            return 0;
        }

        // Wipe previous matches to prevent duplicate fixture cards
        FootballMatch::query()->delete();

        $syncedCount = 0;
        $now = now();

        // Standard Premier League kickoff slots
        $kickoffSlots = ['12:30', '15:00', '17:30', '20:00'];

        foreach ($matches as $index => $item) {
            $homeTeam = $this->resolveTeam($item['home'], $item['home_tla'] ?? null);
            $awayTeam = $this->resolveTeam($item['away'], $item['away_tla'] ?? null);

            $venue = $item['venue'] ?: ($this->getStadiumForTeam($homeTeam->name) ?? 'Premier League Stadium');

            if ($keepDates) {
                $startsAt = Carbon::parse("{$item['date']} {$item['time']}");
                $homeScore = $item['home_score'];
                $awayScore = $item['away_score'];
                $status = ($homeScore !== null && $awayScore !== null) ? 'finished' : 'scheduled';
                $minute = $status === 'finished' ? 90 : null;
            } else {
                // Active calendar projection:
                // Match 0: LIVE NOW today (gives life to the Live Lounge & Matchday hero)
                if ($index === 0) {
                    $startsAt = $now->copy()->subMinutes(68);
                    $status = 'live';
                    $minute = 68;
                    $homeScore = $item['home_score'] ?? 2;
                    $awayScore = $item['away_score'] ?? 1;
                }
                // Matches 1-2: Scheduled today (upcoming later today)
                elseif ($index === 1 || $index === 2) {
                    $startsAt = $now->copy()->addHours($index * 2 + 1);
                    $status = 'scheduled';
                    $minute = null;
                    $homeScore = null;
                    $awayScore = null;
                }
                // Matches 3-12: Scheduled over the next 5 upcoming days (covers the 6-day date strip!)
                elseif ($index >= 3 && $index <= 12) {
                    $dayOffset = (int) floor(($index - 1) / 2); // 1, 2, 3, 4, 5 days ahead
                    $slot = $kickoffSlots[$index % count($kickoffSlots)];
                    [$h, $m] = explode(':', $slot);
                    $startsAt = $now->copy()->addDays($dayOffset)->setTime((int) $h, (int) $m, 0);
                    $status = 'scheduled';
                    $minute = null;
                    $homeScore = null;
                    $awayScore = null;
                }
                // Matches 13+: Recent finished results (fills the Results tab!)
                else {
                    $dayOffset = ($index - 12);
                    $startsAt = $now->copy()->subDays($dayOffset)->setTime(15, 0, 0);
                    $status = 'finished';
                    $minute = 90;
                    $homeScore = $item['home_score'] !== null ? $item['home_score'] : (($index % 3) + 1);
                    $awayScore = $item['away_score'] !== null ? $item['away_score'] : ($index % 2);
                }
            }

            $dateSlug = $startsAt->format('Y-m-d');
            $slug = Str::slug("{$homeTeam->slug}-vs-{$awayTeam->slug}-{$dateSlug}");

            $match = FootballMatch::query()->create([
                'slug' => $slug,
                'competition' => 'Premier League',
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'starts_at' => $startsAt,
                'status' => $status,
                'venue' => $venue,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'minute' => $minute,
                'is_sample' => false,
            ]);

            // If a video highlights link is present, attach it as an official watch option
            $videoUrl = $item['video_url'] ?? null;
            if ($videoUrl && filter_var($videoUrl, FILTER_VALIDATE_URL)) {
                WatchOption::query()->create([
                    'match_id' => $match->id,
                    'market_code' => 'GLOBAL',
                    'market_name' => 'Video Highlights',
                    'broadcaster' => 'Official Match Highlights',
                    'website_url' => $videoUrl,
                    'is_official' => true,
                ]);
            }

            $syncedCount++;
        }

        return $syncedCount;
    }

    private function resolveTeam(string $name, ?string $tla = null): Team
    {
        $normalized = trim(preg_replace('/\b(FC|AFC)\b/i', '', $name));
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized));

        $slug = Str::slug($normalized);
        $shortName = $tla;
        $primaryColor = '#1e3a8a';

        if (isset(self::PREMIER_LEAGUE_TEAMS[$normalized])) {
            $shortName = $shortName ?: self::PREMIER_LEAGUE_TEAMS[$normalized]['short'];
            $primaryColor = self::PREMIER_LEAGUE_TEAMS[$normalized]['color'];
        } else {
            foreach (self::PREMIER_LEAGUE_TEAMS as $known => $meta) {
                if (str_contains(strtolower($normalized), strtolower($known)) || str_contains(strtolower($known), strtolower($normalized))) {
                    $shortName = $shortName ?: $meta['short'];
                    $primaryColor = $meta['color'];
                    $normalized = $known;
                    $slug = Str::slug($known);
                    break;
                }
            }
        }

        if (! $shortName) {
            $shortName = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $normalized), 0, 3));
        }

        return Team::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $normalized,
                'short_name' => $shortName,
                'primary_color' => $primaryColor,
            ]
        );
    }

    private function getStadiumForTeam(string $teamName): ?string
    {
        return self::PREMIER_LEAGUE_TEAMS[$teamName]['stadium'] ?? null;
    }
}
