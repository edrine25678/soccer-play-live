<?php

namespace App\Http\Controllers\Football;

use App\Http\Controllers\Controller;
use Domain\Football\Services\FixtureBoard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class FixtureController extends Controller
{
    public function index(Request $request, FixtureBoard $board): View
    {
        return $this->showBoard($request, $board);
    }

    public function results(Request $request, FixtureBoard $board): View
    {
        return $this->showBoard($request, $board, results: true);
    }

    private function showBoard(Request $request, FixtureBoard $board, bool $results = false): View
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'q' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'in:live,scheduled'],
        ]);
        $liveMatches = $board->live();

        return view('football.index', [
            'matches' => $board->fixtures(
                $filters['date'] ?? null,
                isset($filters['q']) ? trim($filters['q']) : null,
                $filters['status'] ?? null,
                $results,
            ),
            'liveMatches' => $liveMatches,
            'isResults' => $results,
            'selectedDate' => $filters['date'] ?? null,
            'search' => $filters['q'] ?? '',
            'selectedStatus' => $filters['status'] ?? null,
        ]);
    }
}
