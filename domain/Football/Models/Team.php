<?php

namespace Domain\Football\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Team extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'short_name',
        'primary_color',
    ];

    /**
     * Official high-resolution club crests mapped by code and slug.
     */
    public const CRESTS = [
        'ARS' => 'https://crests.football-data.org/57.png',
        'arsenal' => 'https://crests.football-data.org/57.png',

        'AVL' => 'https://crests.football-data.org/58.png',
        'aston-villa' => 'https://crests.football-data.org/58.png',

        'BOU' => 'https://crests.football-data.org/1044.png',
        'bournemouth' => 'https://crests.football-data.org/1044.png',
        'afc-bournemouth' => 'https://crests.football-data.org/1044.png',

        'BRE' => 'https://crests.football-data.org/402.png',
        'brentford' => 'https://crests.football-data.org/402.png',

        'BHA' => 'https://crests.football-data.org/397.png',
        'brighton' => 'https://crests.football-data.org/397.png',
        'brighton-hove-albion' => 'https://crests.football-data.org/397.png',

        'CHE' => 'https://crests.football-data.org/61.png',
        'chelsea' => 'https://crests.football-data.org/61.png',

        'CRY' => 'https://crests.football-data.org/354.png',
        'crystal-palace' => 'https://crests.football-data.org/354.png',

        'EVE' => 'https://crests.football-data.org/62.png',
        'everton' => 'https://crests.football-data.org/62.png',

        'FUL' => 'https://crests.football-data.org/63.png',
        'fulham' => 'https://crests.football-data.org/63.png',

        'IPS' => 'https://crests.football-data.org/349.png',
        'ipswich' => 'https://crests.football-data.org/349.png',
        'ipswich-town' => 'https://crests.football-data.org/349.png',

        'LEI' => 'https://crests.football-data.org/338.png',
        'leicester' => 'https://crests.football-data.org/338.png',
        'leicester-city' => 'https://crests.football-data.org/338.png',

        'LIV' => 'https://crests.football-data.org/64.png',
        'liverpool' => 'https://crests.football-data.org/64.png',

        'MCI' => 'https://crests.football-data.org/65.png',
        'manchester-city' => 'https://crests.football-data.org/65.png',

        'MUN' => 'https://crests.football-data.org/66.png',
        'manchester-united' => 'https://crests.football-data.org/66.png',

        'NEW' => 'https://crests.football-data.org/67.png',
        'newcastle' => 'https://crests.football-data.org/67.png',
        'newcastle-united' => 'https://crests.football-data.org/67.png',

        'NFO' => 'https://crests.football-data.org/351.png',
        'nottingham-forest' => 'https://crests.football-data.org/351.png',

        'SOU' => 'https://crests.football-data.org/340.png',
        'southampton' => 'https://crests.football-data.org/340.png',

        'TOT' => 'https://crests.football-data.org/73.png',
        'tottenham' => 'https://crests.football-data.org/73.png',
        'tottenham-hotspur' => 'https://crests.football-data.org/73.png',

        'WHU' => 'https://crests.football-data.org/563.png',
        'west-ham' => 'https://crests.football-data.org/563.png',
        'west-ham-united' => 'https://crests.football-data.org/563.png',

        'WOL' => 'https://crests.football-data.org/76.png',
        'wolves' => 'https://crests.football-data.org/76.png',
        'wolverhampton' => 'https://crests.football-data.org/76.png',
        'wolverhampton-wanderers' => 'https://crests.football-data.org/76.png',
    ];

    public function getCrestUrlAttribute(): ?string
    {
        return self::CRESTS[$this->short_name] 
            ?? self::CRESTS[$this->slug] 
            ?? null;
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
