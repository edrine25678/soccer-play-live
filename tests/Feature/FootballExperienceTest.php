<?php

namespace Tests\Feature;

use Domain\Football\Models\FootballMatch;
use Domain\Football\Models\Team;
use Domain\Football\Services\AuthorizedStreamResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FootballExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_matchday_board_displays_seeded_sample_fixtures(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertSee('These are sample fixtures')
            ->assertSee('Liverpool')
            ->assertSee('SAMPLE');
    }

    public function test_fixture_search_returns_a_match_for_the_requested_team(): void
    {
        $this->seed();

        $this->get('/matches?q=Newcastle')
            ->assertOk()
            ->assertSee('Newcastle United');
    }

    public function test_completed_fixtures_are_available_on_the_results_page(): void
    {
        $this->seed();

        $this->get('/results')
            ->assertOk()
            ->assertSee('The latest scores')
            ->assertSee('Arsenal')
            ->assertSee('Newcastle United');
    }

    public function test_match_centre_never_claims_an_unconfigured_broadcast_is_available(): void
    {
        $fixture = $this->fixture();

        $this->get(route('matches.show', $fixture))
            ->assertOk()
            ->assertSee('Local watch options')
            ->assertSee('Connect an official broadcaster');
    }

    public function test_player_shows_a_clear_message_when_a_stream_is_not_configured(): void
    {
        $fixture = $this->fixture();

        $this->get(route('matches.watch', $fixture))
            ->assertOk()
            ->assertSee('No licensed stream connected.')
            ->assertDontSee('<video', false);
    }

    public function test_player_embeds_only_a_configured_authorized_stream(): void
    {
        $fixture = $this->fixture();
        $url = 'https://cdn.example.test/live/match.m3u8';

        config([
            'football.authorized_streams' => [$fixture->slug => $url],
            'football.authorized_stream_hosts' => ['cdn.example.test'],
        ]);

        $this->get(route('matches.watch', $fixture))
            ->assertOk()
            ->assertSee('data-authorized-stream="'.$url.'"', false)
            ->assertSee('Play stream');
    }

    public function test_resolver_rejects_a_stream_from_a_non_allowlisted_host(): void
    {
        $fixture = $this->fixture();

        config([
            'football.authorized_streams' => [$fixture->slug => 'https://untrusted.example.test/live/match.m3u8'],
            'football.authorized_stream_hosts' => ['cdn.example.test'],
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(AuthorizedStreamResolver::class)->resolve($fixture);
    }

    public function test_live_score_endpoint_returns_live_scores_without_exposing_stream_urls(): void
    {
        $fixture = $this->fixture([
            'status' => 'live',
            'home_score' => 2,
            'away_score' => 1,
            'minute' => 68,
        ]);

        $this->getJson(route('api.matches.live'))
            ->assertOk()
            ->assertJsonPath('matches.0.slug', $fixture->slug)
            ->assertJsonPath('matches.0.home_score', 2)
            ->assertJsonPath('matches.0.away_score', 1)
            ->assertJsonPath('matches.0.minute', 68)
            ->assertDontSee('stream_url')
            ->assertDontSee('authorized_stream');
    }

    public function test_fixture_feed_returns_service_unavailable_until_a_strong_secret_is_set(): void
    {
        config(['football.feed_secret' => '']);

        $this->postJson('/api/fixture-feed', ['matches' => []])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'The signed fixture feed is not configured.');
    }

    public function test_timestamped_signed_fixture_feed_persists_live_scores_and_official_watch_options(): void
    {
        $secret = str_repeat('x', 64);
        $timestamp = (string) now()->timestamp;
        $body = json_encode([
            'matches' => [[
                'slug' => 'arsenal-vs-chelsea',
                'competition' => 'Premier League',
                'status' => 'live',
                'starts_at' => now()->toIso8601String(),
                'venue' => 'Example Stadium',
                'home_score' => 1,
                'away_score' => 0,
                'minute' => 38,
                'home' => [
                    'name' => 'Arsenal',
                    'slug' => 'arsenal',
                    'short_name' => 'ARS',
                    'primary_color' => '#EF0107',
                ],
                'away' => [
                    'name' => 'Chelsea',
                    'slug' => 'chelsea',
                    'short_name' => 'CHE',
                    'primary_color' => '#034694',
                ],
                'watch_options' => [[
                    'market_code' => 'GB',
                    'market_name' => 'United Kingdom',
                    'broadcaster' => 'Official local broadcaster',
                    'website_url' => 'https://provider.example.test/football',
                    'is_official' => true,
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        config(['football.feed_secret' => $secret]);

        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);

        $this->call('POST', '/api/fixture-feed', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_FOOTBALL_TIMESTAMP' => $timestamp,
            'HTTP_X_FOOTBALL_SIGNATURE' => $signature,
        ], $body)
            ->assertOk()
            ->assertJsonPath('fixtures.0', 'arsenal-vs-chelsea');

        $this->assertDatabaseHas('matches', [
            'slug' => 'arsenal-vs-chelsea',
            'status' => 'live',
            'home_score' => 1,
            'away_score' => 0,
            'minute' => 38,
            'is_sample' => false,
        ]);

        $this->assertDatabaseHas('watch_options', [
            'market_code' => 'GB',
            'broadcaster' => 'Official local broadcaster',
            'is_official' => true,
        ]);
    }

    public function test_fixture_feed_rejects_expired_signatures(): void
    {
        config(['football.feed_secret' => str_repeat('x', 64)]);

        $timestamp = (string) now()->subMinutes(6)->timestamp;
        $body = '{"matches":[]}';

        $this->call('POST', '/api/fixture-feed', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_FOOTBALL_TIMESTAMP' => $timestamp,
            'HTTP_X_FOOTBALL_SIGNATURE' => 'sha256='.hash_hmac(
                'sha256',
                $timestamp.'.'.$body,
                str_repeat('x', 64),
            ),
        ], $body)
            ->assertUnauthorized()
            ->assertJsonPath('message', 'The fixture feed signature is invalid.');
    }

    private function fixture(array $attributes = []): FootballMatch
    {
        $home = Team::query()->create([
            'name' => 'Arsenal',
            'slug' => 'arsenal',
            'short_name' => 'ARS',
            'primary_color' => '#EF0107',
        ]);
        $away = Team::query()->create([
            'name' => 'Chelsea',
            'slug' => 'chelsea',
            'short_name' => 'CHE',
            'primary_color' => '#034694',
        ]);

        return FootballMatch::query()->create(array_merge([
            'competition' => 'Premier League',
            'slug' => 'arsenal-vs-chelsea',
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'starts_at' => now()->addHour(),
            'status' => 'scheduled',
            'is_sample' => false,
        ], $attributes));
    }
}
