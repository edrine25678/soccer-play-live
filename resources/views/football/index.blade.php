@extends('layouts.app')

@section('title', ($isResults ? 'Results' : 'Fixtures').' · '.config('app.name'))

@section('content')
    @php
        $dayOptions = $isResults
            ? collect(range(0, 5))->map(fn (int $offset) => now()->startOfDay()->subDays($offset))
            : collect(range(0, 5))->map(fn (int $offset) => now()->startOfDay()->addDays($offset));
        $headingDate = $selectedDate
            ? \Illuminate\Support\Carbon::parse($selectedDate)->translatedFormat('l, j F')
            : 'All matchdays';
    @endphp

    <div class="page-heading">
        <div>
            <div class="eyebrow"><span class="eyebrow-dot"></span>THE PREMIER LEAGUE, AT A GLANCE</div>
            <h1>{{ $isResults ? 'The final whistle.' : 'Make room for matchday.' }}</h1>
            <p class="page-intro">
                {{ $isResults ? 'Recent results, fixtures and the moments that matter.' : 'Kick-off times, live updates and a proper seat at the game.' }}
            </p>
        </div>
        <div class="heading-date">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3.5v4m10-4v4M4.5 9h15m-14-4h13A1.5 1.5 0 0 1 20 6.5v12A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5v-12A1.5 1.5 0 0 1 5.5 5Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"></path></svg>
            <span>{{ $headingDate }}</span>
        </div>
    </div>

    @if ($matches->contains(fn ($m) => $m->is_sample) || $liveMatches->contains(fn ($m) => $m->is_sample))
    <section class="intro-banner" aria-label="Fixture schedule information">
        <div class="intro-banner-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"></circle><path d="M12 11v5m0-8h.01" stroke="currentColor" stroke-linecap="round" stroke-width="2"></path></svg>
        </div>
        <div>
            <strong>These are sample fixtures, not a live Premier League schedule.</strong>
            <span>Connect your verified fixture provider to show real match times and scores.</span>
        </div>
        <span class="sample-stamp">DEMO DATA</span>
    </section>
    @endif

    <div class="content-grid">
        <section class="primary-column" aria-label="{{ $isResults ? 'Match results' : 'Upcoming fixtures' }}">
            @if ($matches->isNotEmpty())
                @php($featured = $matches->first())
                <article class="featured-match">
                    <div class="feature-topline">
                        <span class="feature-competition"><span class="feature-star" aria-hidden="true">✳</span> {{ $featured->competition }} <span class="feature-separator">/</span> MATCHDAY FEATURE</span>
                        @if ($featured->is_sample)
                            <span class="demo-pill"><span></span>SAMPLE</span>
                        @endif
                    </div>

                    <div class="feature-label">
                        @if ($featured->status === 'live')
                            <span class="live-pill"><span></span>LIVE NOW <span class="live-minute">{{ $featured->minute ? $featured->minute.'′' : '' }}</span></span>
                        @elseif ($featured->status === 'finished')
                            <span class="finished-pill">FULL TIME</span>
                        @else
                            <span class="feature-kickoff">{{ $featured->starts_at->format('D, j M') }} <span aria-hidden="true">·</span> {{ $featured->starts_at->format('H:i') }}</span>
                        @endif
                        @if ($featured->venue)
                            <span class="feature-venue">{{ $featured->venue }}</span>
                        @endif
                    </div>

                    <div class="feature-matchup">
                        <div class="feature-team feature-home">
                            <span class="team-crest feature-crest" style="--team-color: {{ $featured->homeTeam->primary_color }}">
                                @if ($featured->homeTeam->crest_url)
                                    <img class="crest-img" src="{{ $featured->homeTeam->crest_url }}" alt="{{ $featured->homeTeam->name }}" loading="eager" onerror="this.style.display='none'; this.parentElement.classList.add('has-no-crest'); if (this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                                    <span class="crest-fallback" style="display: none;">{{ $featured->homeTeam->short_name }}</span>
                                @else
                                    {{ $featured->homeTeam->short_name }}
                                @endif
                            </span>
                            <span class="feature-team-name">{{ $featured->homeTeam->name }}</span>
                        </div>
                        <div class="feature-score">
                            @if ($featured->status !== 'scheduled')
                                <span>{{ $featured->home_score ?? '–' }}</span><span class="score-divider">:</span><span>{{ $featured->away_score ?? '–' }}</span>
                            @else
                                <span class="feature-vs">VS</span>
                            @endif
                        </div>
                        <div class="feature-team feature-away">
                            <span class="team-crest feature-crest" style="--team-color: {{ $featured->awayTeam->primary_color }}">
                                @if ($featured->awayTeam->crest_url)
                                    <img class="crest-img" src="{{ $featured->awayTeam->crest_url }}" alt="{{ $featured->awayTeam->name }}" loading="eager" onerror="this.style.display='none'; this.parentElement.classList.add('has-no-crest'); if (this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                                    <span class="crest-fallback" style="display: none;">{{ $featured->awayTeam->short_name }}</span>
                                @else
                                    {{ $featured->awayTeam->short_name }}
                                @endif
                            </span>
                            <span class="feature-team-name">{{ $featured->awayTeam->name }}</span>
                        </div>
                    </div>

                    <div class="feature-footer">
                        <span>All times shown in {{ config('app.timezone') }}</span>
                        <a class="feature-link" href="{{ route('matches.show', $featured) }}">Match centre <span aria-hidden="true">↗</span></a>
                    </div>
                    <span class="feature-watermark" aria-hidden="true">90</span>
                </article>
            @else
                <section class="empty-state">
                    <span class="empty-state-icon" aria-hidden="true">◷</span>
                    <h2>Nothing on the touchline yet.</h2>
                    <p>There are no {{ $isResults ? 'results' : 'fixtures' }} for those filters. Try another date or clear your search.</p>
                    <a class="button button-dark" href="{{ $isResults ? route('results.index') : route('fixtures.index') }}">Clear filters</a>
                </section>
            @endif

            <div class="date-strip">
                <div class="date-strip-title">
                    <div><h2>Pick your matchday</h2><span>Find a date that works for you.</span></div>
                    <a href="{{ $isResults ? route('results.index') : route('fixtures.index') }}">Clear date</a>
                </div>
                <div class="date-options" aria-label="Filter fixtures by date">
                    @foreach ($dayOptions as $day)
                        @php($daySelected = $selectedDate === $day->toDateString())
                        <a class="date-option {{ $daySelected ? 'is-selected' : '' }}" href="{{ route($isResults ? 'results.index' : 'fixtures.index', array_filter(['date' => $day->toDateString(), 'q' => $search, 'status' => $selectedStatus])) }}" @if ($daySelected) aria-current="date" @endif>
                            <span>{{ $day->isToday() ? 'TODAY' : $day->translatedFormat('D') }}</span>
                            <strong>{{ $day->format('d') }}</strong>
                            <small>{{ $day->translatedFormat('M') }}</small>
                        </a>
                    @endforeach
                </div>
            </div>

            <section class="fixture-section">
                <div class="section-title-row">
                    <div>
                        <div class="eyebrow"><span class="eyebrow-dot"></span>{{ $isResults ? 'RECENT RESULTS' : 'YOUR FIXTURE LIST' }}</div>
                        <h2>{{ $isResults ? 'The latest scores' : 'Coming up' }}</h2>
                    </div>
                    <span class="fixture-total">{{ $matches->total() }} {{ \Illuminate\Support\Str::plural('match', $matches->total()) }}</span>
                </div>

                <div class="fixture-filters" aria-label="Filter matches">
                    <a class="filter-chip {{ ! $selectedStatus ? 'is-active' : '' }}" href="{{ route($isResults ? 'results.index' : 'fixtures.index', array_filter(['date' => $selectedDate, 'q' => $search])) }}">{{ $isResults ? 'All results' : 'All fixtures' }}</a>
                    @unless ($isResults)
                        <a class="filter-chip {{ $selectedStatus === 'live' ? 'is-active' : '' }}" href="{{ route('fixtures.index', array_filter(['status' => 'live', 'date' => $selectedDate, 'q' => $search])) }}"><span class="filter-live-dot"></span>Live</a>
                        <a class="filter-chip {{ $selectedStatus === 'scheduled' ? 'is-active' : '' }}" href="{{ route('fixtures.index', array_filter(['status' => 'scheduled', 'date' => $selectedDate, 'q' => $search])) }}">Upcoming</a>
                    @endunless
                </div>

                <div class="fixture-list" data-fixture-list>
                    @forelse ($matches as $fixture)
                        <article class="fixture-row {{ $fixture->status === 'live' ? 'is-live' : '' }}" data-fixture-card data-fixture-slug="{{ $fixture->slug }}">
                            <a class="fixture-time" href="{{ route('matches.show', $fixture) }}">
                                @if ($fixture->status === 'live')
                                    <span class="fixture-status status-live"><span></span>LIVE @if ($fixture->minute) {{ $fixture->minute }}′ @endif</span>
                                @elseif ($fixture->status === 'finished')
                                    <span class="fixture-status status-finished">FULL TIME</span>
                                @else
                                    <time datetime="{{ $fixture->starts_at->toIso8601String() }}">
                                        <span>{{ $fixture->starts_at->isToday() ? 'TODAY' : $fixture->starts_at->format('D, j M') }}</span>
                                        <strong>{{ $fixture->starts_at->format('H:i') }}</strong>
                                    </time>
                                @endif
                            </a>
                            <a class="fixture-teams" href="{{ route('matches.show', $fixture) }}" aria-label="{{ $fixture->homeTeam->name }} versus {{ $fixture->awayTeam->name }}">
                                <span class="fixture-team-line">
                                    <span class="team-crest mini-crest" style="--team-color: {{ $fixture->homeTeam->primary_color }}">
                                        @if ($fixture->homeTeam->crest_url)
                                            <img class="crest-img" src="{{ $fixture->homeTeam->crest_url }}" alt="{{ $fixture->homeTeam->name }}" loading="lazy" onerror="this.style.display='none'; this.parentElement.classList.add('has-no-crest'); if (this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                                            <span class="crest-fallback" style="display: none;">{{ $fixture->homeTeam->short_name }}</span>
                                        @else
                                            {{ $fixture->homeTeam->short_name }}
                                        @endif
                                    </span>
                                    <span class="fixture-team-name">{{ $fixture->homeTeam->name }}</span>
                                    @if ($fixture->status !== 'scheduled')<strong class="row-score">{{ $fixture->home_score ?? '–' }}</strong>@endif
                                </span>
                                <span class="fixture-team-line">
                                    <span class="team-crest mini-crest" style="--team-color: {{ $fixture->awayTeam->primary_color }}">
                                        @if ($fixture->awayTeam->crest_url)
                                            <img class="crest-img" src="{{ $fixture->awayTeam->crest_url }}" alt="{{ $fixture->awayTeam->name }}" loading="lazy" onerror="this.style.display='none'; this.parentElement.classList.add('has-no-crest'); if (this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                                            <span class="crest-fallback" style="display: none;">{{ $fixture->awayTeam->short_name }}</span>
                                        @else
                                            {{ $fixture->awayTeam->short_name }}
                                        @endif
                                    </span>
                                    <span class="fixture-team-name">{{ $fixture->awayTeam->name }}</span>
                                    @if ($fixture->status !== 'scheduled')<strong class="row-score">{{ $fixture->away_score ?? '–' }}</strong>@endif
                                </span>
                            </a>
                            <div class="fixture-competition">
                                <span>{{ $fixture->competition }}</span>
                                <small>{{ $fixture->venue ?: ($fixture->is_sample ? 'Example fixture' : 'Venue TBC') }}</small>
                            </div>
                            <a class="row-watch {{ $fixture->status === 'live' ? 'row-watch-live' : '' }}" href="{{ route('matches.show', $fixture) }}">
                                <span>{{ $fixture->status === 'live' ? 'Match centre' : ($fixture->status === 'finished' ? 'Summary' : 'Match details') }}</span>
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path></svg>
                            </a>
                            <button class="favourite-button" type="button" data-favourite="{{ $fixture->slug }}" aria-label="Add {{ $fixture->homeTeam->name }} versus {{ $fixture->awayTeam->name }} to your watchlist" aria-pressed="false">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20s-7.5-4.4-7.5-10A4.2 4.2 0 0 1 12 7.7a4.2 4.2 0 0 1 7.5 2.3c0 5.6-7.5 10-7.5 10Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"></path></svg>
                            </button>
                            @if ($fixture->is_sample)<span class="fixture-sample-tag">DEMO</span>@endif
                        </article>
                    @empty
                        <div class="fixture-empty">No matches found. Try a different search or date.</div>
                    @endforelse
                    <div class="fixture-empty" data-watchlist-empty hidden>Your watchlist is empty for this view. Tap a heart beside any fixture to save it.</div>
                </div>

                {{ $matches->links('pagination.touchline') }}
            </section>

            <section class="bottom-note" aria-label="About match schedules">
                <span class="bottom-note-icon" aria-hidden="true">✳</span>
                <div>
                    <strong>Fixture times and broadcaster availability can change.</strong>
                    <span>Verify each match with your official local broadcaster before kick-off.</span>
                </div>
                <a href="{{ route('fixtures.index') }}">Browse fixtures <span aria-hidden="true">→</span></a>
            </section>
        </section>

        <aside class="right-column" aria-label="Live match updates">
            <section class="live-card">
                <div class="live-card-heading">
                    <div><span class="live-card-kicker"><span></span>THE LIVE LOUNGE</span><h2>In the moment.</h2></div>
                    <span class="live-counter" data-live-counter>{{ $liveMatches->count() }} LIVE</span>
                </div>
                <div data-live-match-list>
                    @forelse ($liveMatches as $liveFixture)
                    <a class="live-match" href="{{ route('matches.show', $liveFixture) }}" data-live-match="{{ $liveFixture->slug }}">
                        <span class="live-match-league">{{ $liveFixture->competition }}{{ $liveFixture->is_sample ? ' · DEMO' : '' }}</span>
                        <span class="live-scoreboard">
                            <span class="live-side">
                                <span class="team-crest live-crest" style="--team-color: {{ $liveFixture->homeTeam->primary_color }}">
                                    @if ($liveFixture->homeTeam->crest_url)
                                        <img class="crest-img" src="{{ $liveFixture->homeTeam->crest_url }}" alt="{{ $liveFixture->homeTeam->name }}" loading="lazy" onerror="this.style.display='none'; this.parentElement.classList.add('has-no-crest'); if (this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                                        <span class="crest-fallback" style="display: none;">{{ $liveFixture->homeTeam->short_name }}</span>
                                    @else
                                        {{ $liveFixture->homeTeam->short_name }}
                                    @endif
                                </span>
                                <span>{{ $liveFixture->homeTeam->name }}</span>
                            </span>
                            <strong data-live-score="{{ $liveFixture->slug }}">{{ $liveFixture->home_score ?? 0 }} <i>:</i> {{ $liveFixture->away_score ?? 0 }}</strong>
                            <span class="live-side live-side-away">
                                <span class="team-crest live-crest" style="--team-color: {{ $liveFixture->awayTeam->primary_color }}">
                                    @if ($liveFixture->awayTeam->crest_url)
                                        <img class="crest-img" src="{{ $liveFixture->awayTeam->crest_url }}" alt="{{ $liveFixture->awayTeam->name }}" loading="lazy" onerror="this.style.display='none'; this.parentElement.classList.add('has-no-crest'); if (this.nextElementSibling) this.nextElementSibling.style.display='inline';">
                                        <span class="crest-fallback" style="display: none;">{{ $liveFixture->awayTeam->short_name }}</span>
                                    @else
                                        {{ $liveFixture->awayTeam->short_name }}
                                    @endif
                                </span>
                                <span>{{ $liveFixture->awayTeam->name }}</span>
                            </span>
                        </span>
                        <span class="live-match-footer"><span class="live-live-mark"><span></span>LIVE <span data-live-minute="{{ $liveFixture->slug }}">{{ $liveFixture->minute ? $liveFixture->minute.'′' : '' }}</span></span><span>Open match centre <span aria-hidden="true">↗</span></span></span>
                    </a>
                    @empty
                    <div class="live-empty">
                        <span class="live-empty-icon" aria-hidden="true">◷</span>
                        <strong>No verified live scores right now.</strong>
                        <span>Live matches appear here when your official fixture feed is connected.</span>
                    </div>
                    @endforelse
                </div>
                <div class="live-card-footer"><span class="status-pip"></span><span data-live-update-state>Updates automatically every 30 seconds</span></div>
            </section>

            <section class="guide-card">
                <span class="guide-card-icon" aria-hidden="true">💡</span>
                <span class="guide-card-kicker">THE SMART WATCHER</span>
                <h2>First the fixtures.<br>Then the good stuff.</h2>
                <p>Find a match, check its details and follow verified broadcasts for your region.</p>
                <a href="{{ route('fixtures.index') }}">Find your next match <span aria-hidden="true">↗</span></a>
            </section>

            <section class="guide-card">
                <ol class="guide-steps">
                    <li><span class="step-num">01</span><div><strong>Local kick-off times</strong><span>Shown in {{ config('app.timezone') }}</span></div></li>
                    <li><span class="step-num">02</span><div><strong>Live score updates</strong><span>Refresh every 30 seconds</span></div></li>
                    <li><span class="step-num">03</span><div><strong>Official streams only</strong><span>No third-party video embeds</span></div></li>
                </ol>
            </section>
        </aside>
    </div>
@endsection
