<?php

namespace App\Http\Controllers\Football;

use App\Http\Controllers\Controller;
use Domain\Football\Models\FootballMatch;
use Domain\Football\Services\AuthorizedStreamResolver;
use Illuminate\Contracts\View\View;

final class MatchWatchController extends Controller
{
    public function __invoke(FootballMatch $fixture, AuthorizedStreamResolver $streams): View
    {
        return view('football.watch', [
            'fixture' => $fixture->load(['homeTeam', 'awayTeam']),
            'playbackUrl' => $streams->resolve($fixture),
        ]);
    }
}
