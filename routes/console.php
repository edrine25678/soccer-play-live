<?php

use Domain\Football\Services\FreeFootballFeedSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('football:sync {--source=all : API provider: all, thesportsdb, openfootball, or football-data} {--season= : Season (e.g. 2024-2025 for TheSportsDB or 2024-25 / 2025-26 for OpenFootball)} {--key= : API key for football-data.org} {--limit=30 : Maximum number of matches to import} {--keep-dates : Keep exact raw calendar dates without active matchday projection}', function (FreeFootballFeedSyncService $syncService) {
    $source = strtolower((string) $this->option('source'));
    $limit = (int) $this->option('limit');
    $keepDates = (bool) $this->option('keep-dates');
    $this->info("Fetching and syncing real Premier League fixtures from [{$source}]...");

    try {
        $total = 0;

        if ($source === 'football-data') {
            $apiKey = (string) ($this->option('key') ?: env('FOOTBALL_DATA_API_KEY'));
            if (! $apiKey) {
                $this->error('Missing API key! Provide --key=YOUR_KEY or set FOOTBALL_DATA_API_KEY in .env');
                $this->line('Get a free key at: https://www.football-data.org/client/register');
                return 1;
            }
            $total = $syncService->syncFromFootballData($apiKey, $limit, $keepDates);
        } elseif ($source === 'openfootball') {
            $season = (string) ($this->option('season') ?: '2024-25');
            $total = $syncService->syncFromOpenFootball($season, $limit, $keepDates);
        } elseif ($source === 'thesportsdb') {
            $season = (string) ($this->option('season') ?: '2024-2025');
            $total = $syncService->syncFromTheSportsDb($season, $limit, $keepDates);
        } else {
            // Default: sync both OpenFootball and TheSportsDB
            $this->line('-> Syncing real clubs and fixtures from OpenFootball (GitHub Open Data)...');
            $count1 = $syncService->syncFromOpenFootball('2024-25', $limit, $keepDates);
            $this->line("   Synced {$count1} matches.");

            $total = $count1;
        }

        $this->info("✓ Successfully synced {$total} real Premier League matches into Touchline!");
        $this->line('Real fixtures are now active across your Matchday Hub, Date Strip, Live Lounge, and Results!');
        return 0;
    } catch (\Throwable $e) {
        $this->error('Sync failed: ' . $e->getMessage());
        return 1;
    }
})->purpose('Fetch and sync live or scheduled Premier League fixtures from free football APIs');
