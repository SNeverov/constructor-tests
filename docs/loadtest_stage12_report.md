# Stage 12: Local Mini Load Test Report

Date: 2026-03-25
Tool: `node docs/loadtest_stage12.js`
Target:
- `GET /tests/{id}/pass`
- `GET /tests/{id}/pass + POST /tests/{id}/finish`

Settings:
- `BASE_URL=http://constructor-tests.local`
- `TEST_ID=89`
- `TIMEOUT_MS=60000`

## Results

### 1) 50 concurrent users open one test (`GET /pass`)
- success: 50
- failed: 0
- p50: 146 ms
- p95: 160 ms
- max: 163 ms

### 2) 50 concurrent users finish one test (`GET /pass + POST /finish`)
- success: 23
- failed: 27
- p50: 178 ms
- p95: 217 ms
- max: 218 ms
- failures:
  - `429` (rate limit)
  - `403` (csrf/session mismatch in synthetic flow)

### 3) 100 concurrent users open one test (`GET /pass`)
- success: 66
- failed: 34
- p50: 133 ms
- p95: 156 ms
- max: 60068 ms
- failures:
  - request timeout (`aborted`)

### 4) 100 concurrent users finish one test (`GET /pass + POST /finish`)
- success: 24
- failed: 76
- p50: 203 ms
- p95: 5105 ms
- max: 5117 ms
- failures:
  - `403` (csrf/session mismatch in synthetic flow)
  - request timeout (`aborted`)

## Key bottlenecks observed

1. **Application rate limit on finish endpoint**
- `POST /tests/{id}/finish` is limited by IP in app security rules (`tests-finish`: `30/60s`).
- With 50/100 concurrent clients from one host/IP, part of traffic is expected to receive `429`.

2. **Synthetic client limits for CSRF/session-heavy flow**
- `403` appears in script-only flow when session/csrf pairing breaks under aggressive concurrency.
- This is partly a tool artifact; browser-like engines (k6/browser, Playwright, JMeter with cookie manager) are better for this exact scenario.

3. **No DB slow queries captured in this local run**
- MySQL slow log remained effectively empty for this dataset and local hardware.
- Means current bottleneck in this run is primarily request policy/session simulation, not SQL latency.

## Recommendations before VPS/prod sizing

1. For a realistic finish-load test, temporarily run one of:
- dedicated test profile with relaxed `tests-finish` limit,
- or multiple source IPs.

2. Re-run with a browser-like load tool for CSRF/session correctness:
- k6 script with cookie jar + csrf extraction,
- or JMeter with HTTP Cookie Manager.

3. Keep observability from Stage 11 enabled during every test run:
- `storage/logs/php-error.log`
- `storage/logs/app.log`
- MySQL slow log file.
