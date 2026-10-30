<?php

namespace Domain\Football\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FootballMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'competition',
        'slug',
        'home_team_id',
        'away_team_id',
        'starts_at',
        'status',
        'venue',
        'home_score',
        'away_score',
        'minute',
        'is_sample',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'is_sample' => 'boolean',
        ];
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function watchOptions(): HasMany
    {
        return $this->hasMany(WatchOption::class, 'match_id');
    }
}
