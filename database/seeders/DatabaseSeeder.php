<?php

namespace Database\Seeders;

use Domain\Football\Models\FootballMatch;
use Domain\Football\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $teams = collect([
            ['name' => 'Arsenal', 'short_name' => 'ARS', 'primary_color' => '#ef0107'],
            ['name' => 'Chelsea', 'short_name' => 'CHE', 'primary_color' => '#034694'],
            ['name' => 'Liverpool', 'short_name' => 'LIV', 'primary_color' => '#c8102e'],
            ['name' => 'Manchester City', 'short_name' => 'MCI', 'primary_color' => '#6cabdd'],
            ['name' => 'Newcastle United', 'short_name' => 'NEW', 'primary_color' => '#241f20'],
            ['name' => 'Tottenham Hotspur', 'short_name' => 'TOT', 'primary_color' => '#132257'],
        ])->mapWithKeys(function (array $team): array {
            $model = Team::query()->updateOrCreate(
                ['slug' => str($team['name'])->slug()->value()],
                $team,
            );

            return [$model->short_name => $model];
        });

        $kickoff = Carbon::today()->addDay();

        $matches = [
            ['LIV', 'MCI', $kickoff->copy()->setTime(12, 30)],
            ['ARS', 'CHE', $kickoff->copy()->setTime(15, 0)],
            ['NEW', 'TOT', $kickoff->copy()->setTime(17, 30)],
            ['MCI', 'ARS', $kickoff->copy()->addDay()->setTime(16, 0)],
            ['CHE', 'TOT', $kickoff->copy()->addDays(2)->setTime(20, 0)],
            ['LIV', 'NEW', $kickoff->copy()->addDays(2)->setTime(12, 30)],
        ];

        foreach ($matches as [$home, $away, $startsAt]) {
            FootballMatch::query()->updateOrCreate(
                ['slug' => $teams[$home]->slug.'-vs-'.$teams[$away]->slug],
                [
                    'competition' => 'Premier League',
                    'home_team_id' => $teams[$home]->id,
                    'away_team_id' => $teams[$away]->id,
                    'starts_at' => $startsAt,
                    'status' => 'scheduled',
                    'venue' => null,
                    'home_score' => null,
                    'away_score' => null,
                    'minute' => null,
                    'is_sample' => true,
                ],
            );
        }

        FootballMatch::query()->updateOrCreate(
            ['slug' => 'sample-arsenal-vs-newcastle'],
            [
                'competition' => 'Premier League',
                'home_team_id' => $teams['ARS']->id,
                'away_team_id' => $teams['NEW']->id,
                'starts_at' => Carbon::yesterday()->setTime(19, 45),
                'status' => 'finished',
                'home_score' => 2,
                'away_score' => 1,
                'minute' => null,
                'is_sample' => true,
            ],
        );
    }
}
