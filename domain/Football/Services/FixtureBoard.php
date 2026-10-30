<?php

namespace Domain\Football\Services;

use Domain\Football\Models\FootballMatch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class FixtureBoard
{
    public function fixtures(
        ?string $date = null,
        ?string $search = null,
        ?string $status = null,
        bool $results = false,
    ): LengthAwarePaginator {
        $query = FootballMatch::query()->with(['homeTeam', 'awayTeam']);

        if ($results) {
            $query->where('status', 'finished');
        } elseif ($status === 'live') {
            $query->where('status', 'live');
        } elseif ($status === 'scheduled') {
            $query->where('status', 'scheduled');
        } else {
            // By default on fixtures view, show live and scheduled matches
            $query->where(function (Builder $query): void {
                $query->where('status', 'live')
                    ->orWhere('status', 'scheduled');
            });
        }

        if ($date !== null) {
            $query->whereDate('starts_at', $date);
        }

        if ($search !== null && $search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('competition', 'like', '%'.$search.'%')
                    ->orWhereHas('homeTeam', fn (Builder $team): Builder => $team->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('awayTeam', fn (Builder $team): Builder => $team->where('name', 'like', '%'.$search.'%'));
            });
        }

        return $query
            ->orderBy('starts_at', $results ? 'desc' : 'asc')
            ->paginate(8)
            ->withQueryString();
    }

    /**
     * @return Collection<int, FootballMatch>
     */
    public function live(): Collection
    {
        return FootballMatch::query()
            ->with(['homeTeam', 'awayTeam'])
            ->where('status', 'live')
            ->orderBy('starts_at')
            ->get();
    }
}
