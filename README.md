# Touchline

Touchline is an original, responsive Premier League matchday hub built on the domain-oriented Laravel application structure of [threls/laravel-skeleton](https://github.com/threls/laravel-skeleton).

Browse fixtures and results, search for teams, save matches to a browser watchlist, see authorized live score updates, and open a match player for HLS or MP4 feeds you are entitled to distribute. The visual design is original; demo fixtures and scores are clearly labelled as examples, never as the real schedule.

## Requirements

- PHP 8.2 or newer and Composer
- Node.js 20.19+ or 22.12+ and npm
- SQLite, MySQL, or another Laravel-supported database

The upstream template currently targets Laravel 13 and PHP 8.5. This application keeps the skeleton's `Domain\`-based organization and places the user and football domain models in `domain/`, while retaining Laravel 12/PHP 8.2 compatibility with the runtime available for this project.

## Run locally (Windows PowerShell)

From the project directory:

```powershell
composer install
npm install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
php artisan migrate --seed
```

In one terminal, start Laravel:

```powershell
php artisan serve
```

In a second terminal, start the asset watcher:

```powershell
npm run dev
```

Open the address printed by `php artisan serve` (normally `http://127.0.0.1:8000`). For a production asset build, run `npm run build`.

The seed command creates explicitly labelled example Premier League fixtures and one example result. Fixture dates are relative to the day the database is seeded. Seeded scores and schedules are not current real-world data.

## Syncing Real Fixtures with Football APIs

You can pull real Premier League fixtures into Touchline using any of the following verified providers:

1. **football-data.org (Free Tier - 10 requests / min):**
   - Free API Key via [Instant Signup](https://www.football-data.org/client/register)
   - Add your key to `.env`:
     ```env
     FOOTBALL_DATA_API_KEY=your_football_data_token_here
     ```
   - Run sync:
     ```powershell
     php artisan football:sync --source=football-data
     ```

2. **API-Football / RapidAPI (Free Tier - 100 requests / day):**
   - Free API Key via [API-Football](https://www.api-football.com) or RapidAPI
   - Add your key to `.env`:
     ```env
     API_FOOTBALL_KEY=your_api_sports_or_rapidapi_key_here
     API_FOOTBALL_HOST=v3.football.api-sports.io
     ```
   - Run sync:
     ```powershell
     php artisan football:sync --source=api-football
     ```

3. **OpenFootball (GitHub Open Data - Zero configuration required):**
   ```powershell
   php artisan football:sync --source=openfootball
   ```
   Syncs full 2024-25 Premier League season fixtures with venues and matchday rounds directly from public domain open data.

4. **TheSportsDB (Free Community API & Video Highlights):**
   ```powershell
   # Sync fixtures & past match results
   php artisan football:sync --source=thesportsdb

   # Sync official YouTube video highlights for any date
   php artisan football:sync --highlights --date=2024-05-19
   ```

5. **Auto-Detect / Sync All:**
   ```powershell
   php artisan football:sync --source=all
   ```
   Automatically uses `football-data.org` or `API-Football` if configured in `.env`, falling back smoothly to OpenFootball and TheSportsDB.

## Connecting a real fixture provider

No fixtures, live scores, or broadcaster schedules are fetched from an unofficial website. Connect a data provider that you are licensed to use and POST its normalized match data to `POST /api/fixture-feed`. Sign the **exact JSON request body** with the server-only `FOOTBALL_FEED_SECRET`:

```text
X-Football-Timestamp: <current Unix timestamp in seconds>
X-Football-Signature: sha256=<HMAC-SHA256 of "<timestamp>.<exact request body">
Content-Type: application/json
```

Timestamps must be within five minutes; signatures use a constant-time comparison. Generate a long random secret locally:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Add it to the server's untracked `.env`, not to source control, and run `php artisan config:clear` after changing configuration.

The feed accepts batches of up to 50 fixtures. Example request body:

```json
{
  "matches": [
    {
      "slug": "arsenal-vs-chelsea",
      "competition": "Premier League",
      "status": "live",
      "starts_at": "2026-10-30T19:45:00Z",
      "venue": "Example Stadium",
      "home_score": 1,
      "away_score": 0,
      "minute": 38,
      "home": {
        "name": "Arsenal",
        "slug": "arsenal",
        "short_name": "ARS",
        "primary_color": "#EF0107"
      },
      "away": {
        "name": "Chelsea",
        "slug": "chelsea",
        "short_name": "CHE",
        "primary_color": "#034694"
      },
      "watch_options": [
        {
          "market_code": "GB",
          "market_name": "United Kingdom",
          "broadcaster": "Official local broadcaster",
          "website_url": "https://provider.example.com/football",
          "is_official": true
        }
      ]
    }
  ]
}
```

`starts_at` is an ISO 8601 timestamp. Team slugs, short names, and colors are validated; match slugs must be lowercase hyphen-separated values. A `live` match should include its current scores and minute. Watch options are optional and replace that match's previous options when supplied.

Only verified broadcaster options are shown. Their links must be HTTPS. The public `GET /api/live-matches` endpoint exposes live score data, **not** stream URLs; the site polls it every 30 seconds. Incorrect payloads return validation errors, invalid or expired signatures return `401`, and an unset or too-short signing secret returns `503`.

## Configuring authorized playback

Before enabling playback, confirm that you hold the necessary distribution rights for the content, viewers, and territories served. The player supports HLS (`.m3u8`) and MP4 (`.mp4`); HLS playback uses the locally bundled `hls.js` player where native browser support is unavailable.

Add a mapping from a fixture's exact slug to an authorized HTTPS URL, and allowlist its CDN host in `.env`:

```dotenv
FOOTBALL_AUTHORIZED_STREAMS='{"arsenal-vs-chelsea":"https://licensed-cdn.example.com/live/match.m3u8"}'
FOOTBALL_STREAM_ALLOWED_HOSTS=licensed-cdn.example.com
```

Run `php artisan config:clear` after updating `.env`. The resolver fails closed unless the URL is HTTPS, its host exactly matches an allowlisted hostname, it has no embedded credentials, and its path ends in `.m3u8` or `.mp4`. The browser does not request the source until the viewer explicitly presses **Play stream**.

Only use publicly distributable feed URLs. Do not put broadcaster usernames, passwords, private API tokens, or authorization cookies in a stream URL, fixture, or committed file. If your provider requires private viewer authentication, integrate its official player or an appropriately secured, rights-cleared delivery service instead of exposing credentials in the browser.

## Project structure

```text
app/Http/Controllers/Football/      HTTP controllers and signed-feed boundary
app/Http/Middleware/                Signed fixture-feed verification
config/football.php                 Playback host allowlist and feed settings
domain/Football/Models/              Teams, matches, and regional watch options
domain/Football/Services/            Matchday queries and authorized-stream resolution
domain/Users/Models/                 Domain-organized Laravel user model
database/migrations/                Teams, fixtures, and watch-option tables
database/seeders/                   Clearly labelled demonstration data
resources/views/football/           Fixture board, match centre, and player
routes/api.php                      Public live-score feed and signed provider webhook
```

## Verification

```powershell
php artisan test
npm run build
```
