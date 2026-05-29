# WS Crawl Tracker

Track how Googlebot and SEO/AI crawlers explore your WordPress site. Logs every bot hit into a dedicated table and surfaces a complete dashboard: crawl timeline, navigation path, page heatmap, technical health and automatic recommendations.

Built for SEO **and** GEO (Generative Engine Optimization) — tracks Googlebot, Bingbot, GPTBot, OAI-SearchBot, ClaudeBot and PerplexityBot out of the box.

## Features

- **Multi-bot tracking** — search engines and AI crawlers, fully configurable
- **Reverse DNS verification** — PTR + forward lookup to filter out fake user-agents, cached one week per IP
- **Crawl timeline** — daily hit volume over the selected period
- **Per-bot & hourly breakdown** — who crawls, when
- **Technical health** — HTTP status codes returned to bots (200 / 301 / 404 / 5xx)
- **Page heatmap** — where bots spend their crawl budget
- **Crawl path** — step-by-step reconstruction of a bot session
- **Automatic recommendations** — prioritized actions derived from your data
- **Automatic purge** — configurable retention, cleaned daily via cron

## Architecture

Standard WordPress Plugin Boilerplate (WPPB):

```
ws-crawl-tracker/
├── ws-crawl-tracker.php          # bootstrap, constants, activation hooks
├── uninstall.php                 # full cleanup (table + options + transients)
├── includes/
│   ├── class-ws-crawl-tracker.php            # orchestrator
│   ├── class-ws-crawl-tracker-loader.php     # hook loader
│   ├── class-ws-crawl-tracker-i18n.php
│   ├── class-ws-crawl-tracker-activator.php  # table schema + defaults
│   ├── class-ws-crawl-tracker-deactivator.php
│   ├── class-ws-crawl-tracker-detector.php   # UA match + reverse DNS
│   ├── class-ws-crawl-tracker-repository.php # all $wpdb access
│   └── class-ws-crawl-tracker-analyzer.php   # recommendations engine
├── public/
│   └── class-ws-crawl-tracker-public.php     # front-end hit capture
└── admin/
    ├── class-ws-crawl-tracker-admin.php      # menu, AJAX, settings, cron
    ├── css/ js/ partials/
```

## Data flow

```
template_redirect (priority 1)  → detect bot, resolve IP, verify DNS, open session
shutdown                        → capture HTTP status, insert hit row
wp (admin)                      → schedule daily purge cron
wsct_daily_purge                → delete rows older than retention
```

## Requirements

- WordPress 5.6+
- PHP 7.4+

## Development

Test dependencies are provisioned manually (Composer is proxy-blocked in CI):

```bash
phpunit            # full suite, executionOrder=random
phpunit --filter DetectorTest
```

Test stack: PHPUnit 9.6, WP_Mock 0.5.0, Mockery, Patchwork, Hamcrest.

## License

GPL-2.0-or-later © WebStrategy
