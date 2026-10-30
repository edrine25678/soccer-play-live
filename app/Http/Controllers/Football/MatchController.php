<?php

namespace App\Http\Controllers\Football;

use App\Http\Controllers\Controller;
use Domain\Football\Models\FootballMatch;
use Illuminate\Contracts\View\View;

final class MatchController extends Controller
{
    public function show(FootballMatch $fixture): View
    {
        return view('football.show', [
            'fixture' => $fixture->load(['homeTeam', 'awayTeam']),
            'watchOptions' => $fixture->watchOptions()
                ->where('is_official', true)
                ->orderBy('market_name')
                ->get(),
        ]);
    }
}
