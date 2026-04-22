# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A PHP 8.3 web application for creating and taking online tests (quiz platform). Russian-language UI. No framework — pure PHP with a function-based architecture.

## Development Commands

```bash
# Format code (PHP + JS/CSS)
npx prettier --write .

# Run load tests (concurrent users simulation)
node docs/loadtest_stage12.js

# Apply a migration manually (connect to MySQL first)
mysql -u root -p constructor_tests < database/migrations/<file>.sql
```

No automated test suite exists. Testing is currently manual/exploratory.

## Architecture

**Entry point & routing**: `public/index.php` — bootstraps the app, then routes via regex matching on `$_SERVER['REQUEST_URI']` and HTTP method. Each route calls a controller action function and exits.

**Controllers** (`app/controllers/`): Four files — `HomeController.php`, `AuthController.php`, `TestsController.php`, `AccountController.php`. Each file contains plain functions (not classes) for each action.

**Core services** (`app/core/`): Standalone utility functions. Key files:
- `tests.php` — all DB operations for tests, questions, options, attempts, answers, ratings, bookmarks
- `db.php` — PDO singleton accessed via `db()` helper
- `cache.php` — file-based + Redis caching (5-min TTL for test payloads)
- `rate_limit.php` — IP-based rate limiting, file or Redis backend
- `session.php` — session storage (file or Redis)
- `auth.php`, `csrf.php`, `security.php` — authentication and request security
- `view.php`, `flash.php`, `error.php` — rendering helpers

**Views** (`app/views/`): Server-rendered PHP templates. All share `layout.php`. Reusable UI in `views/partials/`.

**Database**: MySQL 8.2, direct PDO prepared statements — no ORM. All queries live in `app/core/tests.php`. Migrations are plain SQL files in `database/migrations/` — apply manually and in order.

**Frontend**: Vanilla JS modules in `public/assets/js/` (one module per feature: bookmarks, copy-link, date-picker, etc.). No build step required.

## Key Patterns

**Request flow**: `index.php` → action function in a controller → calls core functions → renders a view or returns JSON.

**Answer types**: `radio` (single choice), `checkbox` (multiple choice), `input` (free text). Text answers are normalized via `normalize_input_answer()` (lowercased, trimmed, ё→е).

**Attempt snapshots**: When a test is submitted, the question/answer state is captured in the attempt record to preserve historical accuracy if the test is later edited.

**Dual-backend services**: Cache, rate limiting, and sessions each support file-based (dev) or Redis (production) backends. Controlled by `.env` variables and `config/config.php`.

**Rate limiting rules**: Defined in `security_post_limit_rule()` in `app/core/security.php`. Current limits: login/register 10/10 min; test finish 120/60 s.

**Error tracking**: Global exception handler in `index.php` logs to `storage/logs/app.log` with a unique error ID returned to the user.

## Environment & Configuration

Copy `.env.example` to `.env`. Key variables: `DB_*` for MySQL, `REDIS_*` for Redis, `CACHE_DRIVER`/`SESSION_DRIVER`/`RATE_LIMIT_DRIVER` (`file` or `redis`).

Config is loaded by `app/core/env.php` and `config/database.php`. The full DB schema + seed data is in `constructor_tests.sql`.

## Database Migrations

Migration files are in `database/migrations/`. Apply them in chronological order (filename prefix is timestamp). See `database/migrations/README.md` for instructions.
