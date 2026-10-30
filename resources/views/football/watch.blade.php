@extends('layouts.app')

@section('title', 'Watch '.$fixture->homeTeam->name.' vs '.$fixture->awayTeam->name.' · '.config('app.name'))

@section('content')
    <div class="detail-back-row">
        <a class="back-link" href="{{ route('matches.show', $fixture) }}"><span aria-hidden="true">←</span> Back to match centre</a>
        <span class="detail-breadcrumb">WATCH <span>/</span> {{ $fixture->competition }}</span>
    </div>

    @if ($fixture->is_sample)
        <div class="detail-sample-notice"><strong>Sample fixture.</strong> No real match or broadcast is represented here.</div>
    @endif

    <div class="watch-page-heading">
        <div class="eyebrow"><span class="eyebrow-dot"></span>{{ $fixture->competition }}</div>
        <h1>{{ $fixture->homeTeam->name }} <span>vs</span> {{ $fixture->awayTeam->name }}</h1>
        <p>{{ $fixture->starts_at->translatedFormat('l, j F · H:i') }} {{ config('app.timezone') }} @if ($fixture->status === 'live')<span class="watch-heading-live"><span></span>Live match</span>@endif</p>
    </div>

    <section class="player-panel" aria-label="Authorized match player">
        @if ($playbackUrl)
            <div class="video-frame" data-player>
                <video playsinline controls preload="none" aria-label="Authorized match stream" data-authorized-stream="{{ $playbackUrl }}"></video>
                <div class="player-consent" data-player-consent>
                    <span class="player-play-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="m9 6 10 6-10 6V6Z" fill="currentColor"></path></svg>
                    </span>
                    <strong>Your authorized broadcast is ready.</strong>
                    <span>Loading the stream connects your browser to its licensed provider.</span>
                    <button class="button button-lime" type="button" data-play-authorized>Play stream <span aria-hidden="true">→</span></button>
                </div>
                <p class="player-error" role="status" hidden data-player-error></p>
            </div>
            <div class="player-caption"><span><span class="status-pip"></span>Verified source configured</span><span>Available under your provider’s terms</span></div>
        @else
            <div class="player-placeholder">
                <span class="player-orbit player-orbit-one"></span><span class="player-orbit player-orbit-two"></span>
                <span class="player-empty-ball" aria-hidden="true">
                    <svg viewBox="0 0 64 64" fill="none"><circle cx="32" cy="32" r="26" stroke="currentColor" stroke-width="1.5"></circle><path d="m32 18 9 7-3 11H26l-3-11 9-7Zm-18 6 9 1m27-1-9 1M15 42l11-6m23 6-11-6M29 57l-3-21m9 21 3-21" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path></svg>
                </span>
                <span class="player-placeholder-tag"><span></span>OFFICIAL STREAM ONLY</span>
                <h2>No licensed stream connected.</h2>
                <p>Add this fixture’s authorized HLS or MP4 URL and its CDN host to your server configuration. We never embed unauthorized broadcast feeds.</p>
                <a class="button button-lime" href="{{ route('matches.show', $fixture) }}">See official watch options <span aria-hidden="true">→</span></a>
            </div>
            <div class="player-caption"><span><span class="status-pip"></span>Player ready when your authorized provider is</span><span>No third-party stream is loaded</span></div>
        @endif
    </section>

    <section class="watch-legal-note">
        <span class="bottom-note-icon" aria-hidden="true">✳</span>
        <div><strong>Watch fairly.</strong><span>Only play streams that you own or are licensed to distribute. Provider login credentials never belong in the app’s stream URLs.</span></div>
    </section>
@endsection
