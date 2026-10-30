import './bootstrap';

const watchlistKey = 'touchline.watchlist.v1';
const fixtureCards = document.querySelectorAll('[data-fixture-card]');
const liveUpdateState = document.querySelector('[data-live-update-state]');
const savedLink = document.querySelector('[data-saved-link]');
const matchdayLink = document.querySelector('[data-matchday-link]');
const watchlistEmpty = document.querySelector('[data-watchlist-empty]');
const watchlistCounts = document.querySelectorAll('[data-watchlist-count]');
const favouriteButtons = document.querySelectorAll('[data-favourite]');
let savedFixtures = new Set();

try {
    const stored = JSON.parse(window.localStorage.getItem(watchlistKey) || '[]');
    if (Array.isArray(stored)) {
        savedFixtures = new Set(stored.filter((slug) => typeof slug === 'string'));
    }
} catch (error) {
    console.error('Your watchlist could not be loaded from this browser.', error);
}

function isWatchlistView() {
    return window.location.hash === '#saved';
}

function updateWatchlist() {
    watchlistCounts.forEach((element) => {
        element.textContent = String(savedFixtures.size);
    });

    favouriteButtons.forEach((button) => {
        const saved = savedFixtures.has(button.dataset.favourite);
        const fixture = button.closest('[data-fixture-card]');
        button.setAttribute('aria-pressed', String(saved));
        button.setAttribute('aria-label', `${saved ? 'Remove' : 'Add'} this fixture ${saved ? 'from' : 'to'} your watchlist`);
        fixture.hidden = isWatchlistView() && !saved;
    });

    fixtureCards.forEach((fixture) => {
        fixture.hidden = isWatchlistView() && !savedFixtures.has(fixture.dataset.fixtureSlug);
    });

    if (watchlistEmpty) {
        watchlistEmpty.hidden = !isWatchlistView() || [...fixtureCards].some((fixture) => savedFixtures.has(fixture.dataset.fixtureSlug));
    }

    if (savedLink) {
        savedLink.setAttribute('aria-current', isWatchlistView() ? 'page' : 'false');
        savedLink.classList.toggle('is-active', isWatchlistView());
    }

    if (matchdayLink) {
        matchdayLink.classList.toggle('is-active', !isWatchlistView() && window.location.pathname === '/');
    }
}

favouriteButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const { favourite: slug } = button.dataset;

        if (savedFixtures.has(slug)) {
            savedFixtures.delete(slug);
        } else {
            savedFixtures.add(slug);
        }

        try {
            window.localStorage.setItem(watchlistKey, JSON.stringify([...savedFixtures]));
        } catch (error) {
            console.error('Your watchlist could not be saved in this browser.', error);
        }

        updateWatchlist();
    });
});

window.addEventListener('hashchange', updateWatchlist);
updateWatchlist();

const menuToggle = document.querySelector('[data-menu-toggle]');
const sidebar = document.querySelector('#site-sidebar');
const sidebarBackdrop = document.querySelector('[data-menu-close]');

function setNavigationOpen(isOpen) {
    if (!menuToggle || !sidebar || !sidebarBackdrop) {
        return;
    }

    sidebar.classList.toggle('is-open', isOpen);
    sidebarBackdrop.classList.toggle('is-visible', isOpen);
    menuToggle.setAttribute('aria-expanded', String(isOpen));
    menuToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
}

menuToggle?.addEventListener('click', () => {
    setNavigationOpen(menuToggle.getAttribute('aria-expanded') !== 'true');
});

sidebarBackdrop?.addEventListener('click', () => setNavigationOpen(false));

sidebar?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => setNavigationOpen(false));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setNavigationOpen(false);
    }

    if (
        event.key === '/'
        && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)
        && !event.ctrlKey
        && !event.metaKey
    ) {
        event.preventDefault();
        document.querySelector('#site-search')?.focus();
    }
});

const liveMatchList = document.querySelector('[data-live-match-list]');
const liveCounter = document.querySelector('[data-live-counter]');

function createLiveMatch(match) {
    const element = document.createElement('a');
    element.className = 'live-match';
    element.href = `/matches/${encodeURIComponent(match.slug)}`;
    element.dataset.liveMatch = match.slug;

    const league = document.createElement('span');
    league.className = 'live-match-league';
    league.textContent = `${match.competition}${match.is_sample ? ' · DEMO' : ''}`;

    const scoreboard = document.createElement('span');
    scoreboard.className = 'live-scoreboard';

    const home = createLiveTeam(match.home_team, match.home_short_name, match.home_color);
    const score = document.createElement('strong');
    score.dataset.liveScore = match.slug;

    const away = createLiveTeam(match.away_team, match.away_short_name, match.away_color);
    away.classList.add('live-side-away');
    const awayScore = document.createElement('i');
    awayScore.textContent = ' : ';
    score.textContent = `${match.home_score ?? 0}`;
    score.append(awayScore, document.createTextNode(` ${match.away_score ?? 0}`));
    scoreboard.append(home, score, away);

    const footer = document.createElement('span');
    footer.className = 'live-match-footer';

    const status = document.createElement('span');
    status.className = 'live-live-mark';
    const liveDot = document.createElement('span');
    liveDot.textContent = '';
    const minute = document.createElement('span');
    minute.dataset.liveMinute = match.slug;
    minute.textContent = match.minute ? `${match.minute}′` : '';
    status.append(liveDot, document.createTextNode('LIVE'), minute);

    const linkLabel = document.createElement('span');
    linkLabel.append(document.createTextNode('Open match centre '));
    const arrow = document.createElement('span');
    arrow.setAttribute('aria-hidden', 'true');
    arrow.textContent = '↗';
    linkLabel.append(arrow);

    footer.append(status, linkLabel);
    element.append(league, scoreboard, footer);

    return element;
}

function createLiveTeam(name, shortName, color) {
    const team = document.createElement('span');
    team.className = 'live-side';

    const crest = document.createElement('span');
    crest.className = 'team-crest live-crest';
    crest.style.setProperty('--team-color', /^#[\da-f]{6}$/i.test(color) ? color : '#35604b');
    crest.textContent = shortName;

    const label = document.createElement('span');
    label.textContent = name;
    team.append(crest, label);

    return team;
}

function renderLiveMatches(matches) {
    if (!liveMatchList || !Array.isArray(matches)) {
        return;
    }

    liveMatchList.replaceChildren();

    if (matches.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'live-empty';
        empty.innerHTML = '<span class="live-empty-icon" aria-hidden="true">◷</span>';

        const heading = document.createElement('strong');
        heading.textContent = 'No verified live scores right now.';

        const detail = document.createElement('span');
        detail.textContent = 'Live matches appear here when your official fixture feed is connected.';
        empty.append(heading, detail);
        liveMatchList.append(empty);
        return;
    }

    matches.forEach((match) => {
        const existing = document.querySelector(`[data-live-match="${CSS.escape(match.slug)}"]`);
        const liveMatch = existing || createLiveMatch(match);
        const score = liveMatch.querySelector('[data-live-score]');
        const minute = liveMatch.querySelector('[data-live-minute]');

        if (score) {
            const homeScore = match.home_score ?? 0;
            const awayScore = match.away_score ?? 0;
            score.replaceChildren(
                document.createTextNode(String(homeScore)),
                Object.assign(document.createElement('i'), { textContent: ' :' }),
                document.createTextNode(` ${awayScore}`),
            );
        }

        if (minute) {
            minute.textContent = match.minute ? `${match.minute}′` : '';
        }

        liveMatchList.append(liveMatch);
    });
}

async function refreshLiveMatches() {
    try {
        const response = await fetch('/api/live-matches', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`The live match feed returned HTTP ${response.status}.`);
        }

        const result = await response.json();
        renderLiveMatches(result.matches);

        if (liveCounter && Array.isArray(result.matches)) {
            liveCounter.textContent = `${result.matches.length} LIVE`;
        }

        if (liveUpdateState) {
            liveUpdateState.textContent = `Live scores · ${new Intl.DateTimeFormat(undefined, { hour: 'numeric', minute: '2-digit' }).format(new Date(result.updated_at))}`;
        }
    } catch (error) {
        console.error('Live scores could not be refreshed.', error);

        if (liveUpdateState) {
            liveUpdateState.textContent = 'Live score updates are temporarily unavailable.';
        }
    }
}

if (liveMatchList) {
    window.setInterval(refreshLiveMatches, 30_000);
}

const playButton = document.querySelector('[data-play-authorized]');
const player = document.querySelector('video[data-authorized-stream]');
const playerError = document.querySelector('[data-player-error]');
const playerConsent = document.querySelector('[data-player-consent]');
let hlsPlayer;

function showPlaybackError(message, error) {
    console.error(message, error);

    if (playButton) {
        playButton.disabled = false;
        playButton.textContent = 'Try the stream again';
    }

    playerConsent?.classList.remove('is-playing');

    if (playerError) {
        playerError.textContent = message;
        playerError.hidden = false;
    }

    if (hlsPlayer) {
        hlsPlayer.destroy();
        hlsPlayer = undefined;
    }
}

async function startAuthorizedPlayback() {
    if (!player || !playButton) {
        return;
    }

    const streamUrl = player.dataset.authorizedStream;

    if (!streamUrl) {
        showPlaybackError('This match does not have a configured licensed stream.');
        return;
    }

    playButton.disabled = true;
    playButton.textContent = 'Connecting to the authorized stream…';

    if (playerError) {
        playerError.hidden = true;
    }

    try {
        if (player.canPlayType('application/vnd.apple.mpegurl')) {
            player.src = streamUrl;
            await player.play();
        } else if (/\.m3u8(?:[?#]|$)/i.test(streamUrl)) {
            const { default: Hls } = await import('hls.js');

            if (!Hls.isSupported()) {
                showPlaybackError('This browser cannot play this HLS stream.', new Error('Media Source Extensions are unavailable.'));
                return;
            }

            hlsPlayer = new Hls({ enableWorker: true, lowLatencyMode: true });

            hlsPlayer.on(Hls.Events.ERROR, (_event, data) => {
                if (data.fatal) {
                    showPlaybackError('The licensed stream could not be played. Try again or check with your broadcaster.', data);
                }
            });

            hlsPlayer.attachMedia(player);
            hlsPlayer.loadSource(streamUrl);
            hlsPlayer.on(Hls.Events.MANIFEST_PARSED, async () => {
                try {
                    await player.play();
                } catch (error) {
                    showPlaybackError('Playback was blocked by the browser. Use the player controls to try again.', error);
                }
            });
        } else if (/\.mp4(?:[?#]|$)/i.test(streamUrl)) {
            player.src = streamUrl;
            await player.play();
        } else {
            showPlaybackError('This browser cannot play this licensed HLS or MP4 stream.', new Error('The configured stream type is unsupported.'));
            return;
        }

        playerConsent?.classList.add('is-playing');
    } catch (error) {
        showPlaybackError('The licensed stream could not be started. Check the provider connection and try again.', error);
        playButton.disabled = false;
        playButton.textContent = 'Try the stream again';
    }
}

playButton?.addEventListener('click', startAuthorizedPlayback);
