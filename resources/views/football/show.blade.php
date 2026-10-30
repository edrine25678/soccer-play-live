@extends('layouts.app')

@section('title', $fixture->homeTeam->name.' vs '.$fixture->awayTeam->name.' · '.config('app.name'))

@section('content')
    <div class="detail-back-row">
        <a class="back-link" href="{{ route('fixtures.index') }}"><span aria-hidden="true">←</span> Back to fixtures</a>
        <span class="detail-breadcrumb">MATCH CENTRE <span>/</span> {{ $fixture->competition }}</span>
    </div>

    @if ($fixture->is_sample)
        <div class="detail-sample-notice"><strong>Sample fixture.</strong> This demonstration match does not describe a real Premier League schedule, score, or broadcast.</div>
    @endif

    <article class="match-detail-card">
        <div class="match-detail-topline"><span>{{ $fixture->competition }}</span>@if ($fixture->status === 'live')<span class="live-pill"><span></span>LIVE NOW</span>@elseif ($fixture->status === 'finished')<span class="finished-pill">FULL TIME</span>@else<span class="feature-kickoff">{{ $fixture->starts_at->translatedFormat('l, j F · H:i') }}</span>@endif</div>
        <div class="detail-scoreboard">
            <div class="detail-team">
                <span class="team-crest detail-crest" style="--team-color: {{ $fixture->homeTeam->primary_color }}">{{ $fixture->homeTeam->short_name }}</span>
                <strong>{{ $fixture->homeTeam->name }}</strong>
                <span>HOME</span>
            </div>
            <div class="detail-result">
                @if ($fixture->status === 'scheduled')
                    <span>VS</span>
                    <time datetime="{{ $fixture->starts_at->toIso8601String() }}">{{ $fixture->starts_at->format('H:i') }}</time>
                @else
                    <span class="detail-score">{{ $fixture->home_score ?? '–' }} <i>:</i> {{ $fixture->away_score ?? '–' }}</span>
                    @if ($fixture->status === 'live')<small>{{ $fixture->minute ? $fixture->minute.'′' : 'IN PLAY' }}</small>@endif
                @endif
            </div>
            <div class="detail-team">
                <span class="team-crest detail-crest" style="--team-color: {{ $fixture->awayTeam->primary_color }}">{{ $fixture->awayTeam->short_name }}</span>
                <strong>{{ $fixture->awayTeam->name }}</strong>
                <span>AWAY</span>
            </div>
        </div>
        <div class="detail-meta"><span>{{ $fixture->starts_at->translatedFormat('l, j F Y') }}</span><span>{{ $fixture->starts_at->format('H:i') }} {{ config('app.timezone') }}</span>@if ($fixture->venue)<span>{{ $fixture->venue }}</span>@endif</div>
    </article>

    <div class="detail-grid">
        <section class="watch-panel">
            <div class="eyebrow"><span class="eyebrow-dot"></span>YOUR WAY INTO THE GAME</div>
            <h1>{{ $fixture->status === 'live' ? 'The match is on.' : ($fixture->status === 'finished' ? 'The match has finished.' : 'Find your place in the stands.') }}</h1>
            <p>Watch links are only shown when the match has a configured, verified broadcast partner for your region.</p>

            @if ($fixture->status === 'live')
                <a class="button button-dark watch-primary-button" href="{{ route('matches.watch', $fixture) }}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 10 6-10 6V6Z" fill="currentColor"></path></svg>
                    Open the match player
                    <span aria-hidden="true">→</span>
                </a>
            @elseif ($fixture->status === 'scheduled')
                <a class="button button-dark watch-primary-button" href="{{ route('matches.watch', $fixture) }}">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m9 6 10 6-10 6V6Z" fill="currentColor"></path></svg>
                    Check watch options
                    <span aria-hidden="true">→</span>
                </a>
            @endif

            @if ($watchOptions->isNotEmpty())
                <div class="broadcaster-list">
                    <h2>Official ways to watch</h2>
                    @foreach ($watchOptions as $option)
                        @php($safeUrl = filter_var($option->website_url, FILTER_VALIDATE_URL) && parse_url($option->website_url, PHP_URL_SCHEME) === 'https')
                        @if ($safeUrl)
                            <a class="broadcaster-link" href="{{ $option->website_url }}" target="_blank" rel="noopener noreferrer">
                                <span class="broadcaster-initial">{{ \Illuminate\Support\Str::substr($option->broadcaster, 0, 1) }}</span>
                                <span><strong>{{ $option->broadcaster }}</strong><small>{{ $option->market_name }}</small></span>
                                <span class="broadcaster-external" aria-hidden="true">↗</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="watch-coming-soon">
                    <span class="watch-coming-soon-icon" aria-hidden="true">⌁</span>
                    <div>
                        <strong>Local watch options aren’t configured yet.</strong>
                        <span>Connect an official broadcaster for this market, or add a licensed stream source.</span>
                    </div>
                </div>
            @endif
        </section>

        <aside class="detail-right-column">
            <section class="match-info-card">
                <span class="guide-label">MATCH INFORMATION</span>
                <h2>A good game starts here.</h2>
                <div class="info-row"><span>Competition</span><strong>{{ $fixture->competition }}</strong></div>
                <div class="info-row"><span>Kick-off</span><strong>{{ $fixture->starts_at->format('H:i') }} {{ config('app.timezone') }}</strong></div>
                <div class="info-row"><span>Date</span><strong>{{ $fixture->starts_at->translatedFormat('j M Y') }}</strong></div>
                <div class="info-row"><span>Venue</span><strong>{{ $fixture->venue ?: 'To be confirmed' }}</strong></div>
                @if ($fixture->is_sample)<div class="info-sample"><span class="status-pip"></span>Example match · verify actual fixtures</div>@endif
            </section>
            <a class="button button-light back-to-fixtures" href="{{ route('fixtures.index') }}">Explore all fixtures <span aria-hidden="true">→</span></a>
        </aside>
    </div>
@endsection
