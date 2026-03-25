# Stage 11: Baseline Observability

Date: 2026-03-25

## 1) PHP error logging

- Application bootstrap now forces PHP errors into:
  - `storage/logs/php-error.log`
- App-level exceptions are logged into:
  - `storage/logs/app.log`

Changed files:
- `app/core/observability.php`
- `public/index.php`

Quick checks:
- `storage/logs/php-error.log` exists and is writable.
- `storage/logs/app.log` keeps exception lines with `error_id=...`.

## 2) MySQL slow query log (OSPanel / MySQL-8.2)

Runtime and persisted settings were applied:

- `slow_query_log = ON`
- `long_query_time = 0.5`
- `min_examined_row_limit = 50`
- `log_queries_not_using_indexes = OFF`
- log file:
  - `C:\OSPanel\data\MySQL-8.2\default\DESKTOP-IJML8MR-slow.log`

Applied with:

```sql
SET GLOBAL slow_query_log=ON;
SET GLOBAL long_query_time=0.5;
SET GLOBAL min_examined_row_limit=50;
SET GLOBAL log_queries_not_using_indexes=OFF;

SET PERSIST slow_query_log=ON;
SET PERSIST long_query_time=0.5;
SET PERSIST min_examined_row_limit=50;
SET PERSIST log_queries_not_using_indexes=OFF;
```

Verification query:

```sql
SHOW VARIABLES LIKE 'slow_query_log';
SHOW VARIABLES LIKE 'long_query_time';
SHOW VARIABLES LIKE 'min_examined_row_limit';
SHOW VARIABLES LIKE 'log_queries_not_using_indexes';
SHOW VARIABLES LIKE 'slow_query_log_file';
```

## 3) Hot pages and likely heavy queries

Hot pages:
- `/` (home feed)
- `/my/tests`
- `/my/bookmarks`
- `/my/results`
- `/tests/{id}/pass`
- `/tests/{id}/finish`

Heavy query zones:
- feed/list endpoints with per-row subqueries:
  - `tests_list_for_home(...)`
  - `tests_list_by_user_id_paginated(...)`
  - `tests_list_bookmarked_by_user_id_paginated(...)`
- results filters:
  - `attempts_count_by_user_id_filtered(...)`
  - `attempts_list_by_user_id_filtered(...)`
- finish pipeline:
  - `answers_insert_batch(...)`
  - `attempt_finish_update(...)`

## 4) What to review before/after load test

- PHP:
  - `storage/logs/php-error.log`
  - `storage/logs/app.log`
- MySQL:
  - slow log file above
  - top recurring slow statements
- Redis:
  - availability and key growth (`sess:*`, `rl:v1:*`, `cache:v1:*`)
