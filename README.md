# Industrial Policy Digest

A small Laravel app that emails a daily digest of articles mentioning **"industrial policy"** from six major publications.

Every morning at 5:00am Pacific, it queries Google News RSS for the phrase across NYT, The Economist, Financial Times, Foreign Affairs, WSJ, and the Washington Post, filters to the last 24 hours, and sends a single HTML email via Resend to one or more recipients.

No UI, no subscriber model, no queue. One scheduled Artisan command, one Mailable, one Blade view.

## Stack

- PHP 8.4 / Laravel 13
- Resend (mail)
- Pest 4 (tests)
- Deployed on [Laravel Cloud](https://cloud.laravel.com)

## How it works

```
Cloud cron (every minute)            ->  php artisan schedule:run
   `routes/console.php` says...      ->  Schedule::command('digest:industrial-policy')
                                            ->dailyAt('05:00')
                                            ->timezone('America/Los_Angeles')
   When the time matches...          ->  app/Console/Commands/SendIndustrialPolicyDigest
                                            ->  fetch 6 RSS feeds (10s timeout each)
                                            ->  parse + filter to last 24h
                                            ->  Mail::to(recipients)->send(IndustrialPolicyDigest)
                                            ->  Resend ships the email
```

If zero articles match across all six publications, the command logs `No articles found` and exits without sending — your inbox stays clean on quiet news days.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

Set these in `.env`:

```
MAIL_MAILER=resend
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS="hello@yourverifieddomain.com"   # must be a verified domain in Resend
MAIL_FROM_NAME="Morning Digest"
DIGEST_RECIPIENT=you@example.com,teammate@example.com
```

Then run:

```bash
php artisan digest:industrial-policy   # send one now (real RSS, real email)
php artisan schedule:list              # confirm 0 12 * * * digest:industrial-policy
php artisan test                       # run the suite
```

## Configuration

Multiple recipients are supported by setting `DIGEST_RECIPIENT` to a comma-separated list. They all receive the same email (one send, multiple `To:` headers).

To change the send time, edit `routes/console.php`:

```php
Schedule::command('digest:industrial-policy')
    ->dailyAt('05:00')                  // change this
    ->timezone('America/Los_Angeles');  // or this
```

To add a publication, edit the `PUBLICATIONS` constant in `app/Console/Commands/SendIndustrialPolicyDigest.php`:

```php
private const PUBLICATIONS = [
    'NYT' => 'nytimes.com',
    // ...add here, key is display name, value is the bare domain
];
```

## Tests

Two Pest smoke tests in `tests/Feature/IndustrialPolicyDigestTest.php` use `Http::fake()` and `Mail::fake()` to cover:

- The 24-hour filter (recent items kept, old items dropped) and recipient routing.
- The empty-results short-circuit (no recent items → no email sent, info log written).

```bash
php artisan test --compact --filter=IndustrialPolicyDigest
```

## Deployment

Deployed to Laravel Cloud with push-to-deploy on `main`. Three things have to be true on the Cloud environment:

1. **Env vars set** — same five as in `.env`. Set via `cloud environment:variables` or the dashboard.
2. **Scheduler toggle ON** on the App compute cluster (Cloud dashboard → infrastructure canvas → App → Scheduler). This installs the every-minute heartbeat that fires `schedule:run`.
3. **Verified Resend sender** — `MAIL_FROM_ADDRESS` must be a domain you've verified in Resend, otherwise sends 403.

Manual trigger from your laptop:

```bash
cloud command:run --cmd='php artisan digest:industrial-policy'
```

## Notes on scale

The Mailable is **not** `ShouldQueue` on purpose — the digest is a once-a-day single Resend HTTP call. Queueing would add infrastructure for no benefit.

If you ever scale to more than one replica, add `->onOneServer()` to the schedule entry in `routes/console.php` so the digest doesn't fire from each replica.
