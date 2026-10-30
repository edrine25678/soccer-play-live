<?php

namespace App\Http\Controllers\Football;

use App\Http\Controllers\Controller;
use Domain\Football\Services\FixtureBoard;
use Illuminate\Http\JsonResponse;

final class LiveMatchFeedController extends Controller
{
    public function __invoke(FixtureBoard $board): JsonResponse
    {
        return response()->json([
            'matches' => $board->live()->map(fn ($fixture): array => [
                'slug' => $fixture->slug,
                'competition' => $fixture->competition,
                'home_team' => $fixture->homeTeam->name,
                'away_team' => $fixture->awayTeam->name,
                'home_short_name' => $fixture->homeTeam->short_name,
                'away_short_name' => $fixture->awayTeam->short_name,
                'home_color' => $fixture->homeTeam->primary_color,
                'away_color' => $fixture->awayTeam->primary_color,
                'home_score' => $fixture->home_score,
                'away_score' => $fixture->away_score,
                'minute' => $fixture->minute,
                'is_sample' => $fixture->is_sample,
                'kickoff' => $fixture->starts_at->toIso8601String(),
            ]),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
