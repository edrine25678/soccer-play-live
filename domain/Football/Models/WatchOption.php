<?php

namespace Domain\Football\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WatchOption extends Model
{
    protected $fillable = [
        'match_id',
        'market_code',
        'market_name',
        'broadcaster',
        'website_url',
        'is_official',
    ];

    protected function casts(): array
    {
        return [
            'is_official' => 'boolean',
        ];
    }

    public function footballMatch(): BelongsTo
    {
        return $this->belongsTo(FootballMatch::class, 'match_id');
    }
}
