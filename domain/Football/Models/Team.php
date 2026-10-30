<?php

namespace Domain\Football\Models;

use Domain\Football\Services\FreeFootballFeedSyncService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Team extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'short_name',
        'primary_color',
        'crest_url',
    ];

    public function getCrestUrlAttribute(?string $value): ?string
    {
        if ($value) {
            return $value;
        }

        return FreeFootballFeedSyncService::PREMIER_LEAGUE_2024_25[$this->slug]['crest_url'] ?? null;
    }

    public function homeMatches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'home_team_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'away_team_id');
    }
}
