<?php

namespace App\Http\Controllers\Football;

use App\Http\Controllers\Controller;
use Domain\Football\Models\FootballMatch;
use Domain\Football\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class FixtureFeedController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'matches' => ['required', 'array', 'min:1', 'max:50'],
            'matches.*.slug' => ['required', 'string', 'max:120', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', 'distinct'],
            'matches.*.competition' => ['required', 'string', 'max:80'],
            'matches.*.status' => ['required', 'in:scheduled,live,finished'],
            'matches.*.starts_at' => ['required', 'date'],
            'matches.*.venue' => ['nullable', 'string', 'max:120'],
            'matches.*.home_score' => ['nullable', 'integer', 'min:0', 'max:30'],
            'matches.*.away_score' => ['nullable', 'integer', 'min:0', 'max:30'],
            'matches.*.minute' => ['nullable', 'integer', 'min:0', 'max:130'],
            'matches.*.home' => ['required', 'array'],
            'matches.*.home.name' => ['required', 'string', 'max:80'],
            'matches.*.home.slug' => ['required', 'string', 'max:80', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/'],
            'matches.*.home.short_name' => ['required', 'string', 'regex:/\A[A-Z0-9]{2,5}\z/'],
            'matches.*.home.primary_color' => ['required', 'string', 'regex:/\A#[a-fA-F0-9]{6}\z/'],
            'matches.*.away' => ['required', 'array'],
            'matches.*.away.name' => ['required', 'string', 'max:80'],
            'matches.*.away.slug' => ['required', 'string', 'max:80', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/'],
            'matches.*.away.short_name' => ['required', 'string', 'regex:/\A[A-Z0-9]{2,5}\z/'],
            'matches.*.away.primary_color' => ['required', 'string', 'regex:/\A#[a-fA-F0-9]{6}\z/'],
            'matches.*.watch_options' => ['sometimes', 'array', 'max:20'],
            'matches.*.watch_options.*.market_code' => ['required', 'string', 'regex:/\A[A-Z]{2}\z/'],
            'matches.*.watch_options.*.market_name' => ['required', 'string', 'max:80'],
            'matches.*.watch_options.*.broadcaster' => ['required', 'string', 'max:100'],
            'matches.*.watch_options.*.website_url' => ['required', 'url:https', 'max:2048'],
            'matches.*.watch_options.*.is_official' => ['required', 'boolean'],
        ]);

        $saved = DB::transaction(function () use ($validated): array {
            $fixtures = [];

            foreach ($validated['matches'] as $match) {
                $homeTeam = $this->upsertTeam($match['home']);
                $awayTeam = $this->upsertTeam($match['away']);

                $fixture = FootballMatch::query()->updateOrCreate(
                    ['slug' => $match['slug']],
                    [
                        'competition' => $match['competition'],
                        'home_team_id' => $homeTeam->id,
                        'away_team_id' => $awayTeam->id,
                        'starts_at' => $match['starts_at'],
                        'status' => $match['status'],
                        'venue' => $match['venue'] ?? null,
                        'home_score' => $match['home_score'] ?? null,
                        'away_score' => $match['away_score'] ?? null,
                        'minute' => $match['minute'] ?? null,
                        'is_sample' => false,
                    ],
                );

                if (array_key_exists('watch_options', $match)) {
                    $fixture->watchOptions()->delete();
                    $fixture->watchOptions()->createMany($match['watch_options']);
                }

                $fixtures[] = $fixture->slug;
            }

            return $fixtures;
        });

        return response()->json([
            'message' => 'The signed fixture update was applied.',
            'fixtures' => $saved,
        ]);
    }

    /**
     * @param  array{name: string, slug: string, short_name: string, primary_color: string}  $data
     */
    private function upsertTeam(array $data): Team
    {
        return Team::query()->updateOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['name'],
                'short_name' => $data['short_name'],
                'primary_color' => $data['primary_color'],
            ],
        );
    }
}
