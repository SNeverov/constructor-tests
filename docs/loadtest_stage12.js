/* eslint-disable no-console */
'use strict';

const BASE_URL = process.env.BASE_URL || 'http://constructor-tests.local';
const TEST_ID = Number(process.env.TEST_ID || 89);
const TIMEOUT_MS = Number(process.env.TIMEOUT_MS || 60000);

function nowMs() {
  return Number(process.hrtime.bigint() / 1000000n);
}

function parseHidden(html, name) {
  const re = new RegExp(
    `<input[^>]*name=["']${name}["'][^>]*value=["']([^"']*)["'][^>]*>`,
    'i'
  );
  const m = html.match(re);
  return m ? m[1] : '';
}

function parseSetCookies(response) {
  if (typeof response.headers.getSetCookie === 'function') {
    return response.headers.getSetCookie();
  }
  const single = response.headers.get('set-cookie');
  return single ? [single] : [];
}

function applySetCookies(jar, setCookies) {
  for (const raw of setCookies) {
    if (!raw) continue;
    const sessMatch = raw.match(/PHPSESSID=([^;,\s]+)/i);
    if (sessMatch && sessMatch[1]) {
      jar.set('PHPSESSID', sessMatch[1]);
      continue;
    }

    const first = raw.split(';', 1)[0].trim();
    const eq = first.indexOf('=');
    if (eq > 0) {
      jar.set(first.slice(0, eq), first.slice(eq + 1));
    }
  }
}

function cookieHeader(jar) {
  if (jar.size === 0) return '';
  return Array.from(jar.entries())
    .map(([k, v]) => `${k}=${v}`)
    .join('; ');
}

async function requestWithJar(url, options, jar) {
  const headers = new Headers(options?.headers || {});
  const cookie = cookieHeader(jar);
  if (cookie) {
    headers.set('Cookie', cookie);
  }

  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), TIMEOUT_MS);
  try {
    const response = await fetch(url, {
      ...(options || {}),
      headers,
      redirect: 'manual',
      signal: controller.signal,
    });
    applySetCookies(jar, parseSetCookies(response));
    return response;
  } finally {
    clearTimeout(timeout);
  }
}

function percentile(values, p) {
  if (values.length === 0) return 0;
  const arr = [...values].sort((a, b) => a - b);
  const idx = Math.min(arr.length - 1, Math.max(0, Math.ceil((p / 100) * arr.length) - 1));
  return arr[idx];
}

async function vuGetPassOnly() {
  const jar = new Map();
  const url = `${BASE_URL}/tests/${TEST_ID}/pass`;
  const t0 = nowMs();
  const response = await requestWithJar(url, { method: 'GET' }, jar);
  const body = await response.text();
  const t1 = nowMs();

  if (response.status !== 200) {
    throw new Error(`GET /pass status=${response.status}`);
  }
  if (!body.includes('id="testPassForm"')) {
    throw new Error('GET /pass: form not found');
  }
  return t1 - t0;
}

async function vuGetPassAndFinish() {
  const jar = new Map();
  const passUrl = `${BASE_URL}/tests/${TEST_ID}/pass`;
  const t0 = nowMs();

  const passResponse = await requestWithJar(passUrl, { method: 'GET' }, jar);
  const passHtml = await passResponse.text();
  if (passResponse.status !== 200) {
    throw new Error(`GET /pass status=${passResponse.status}`);
  }

  const csrf = parseHidden(passHtml, 'csrf_token');
  const attemptId = parseHidden(passHtml, 'attempt_id');
  if (!csrf || !attemptId) {
    throw new Error('Missing csrf_token or attempt_id');
  }

  const finishBody = new URLSearchParams();
  finishBody.set('csrf_token', csrf);
  finishBody.set('attempt_id', attemptId);

  const finishUrl = `${BASE_URL}/tests/${TEST_ID}/finish`;
  const finishResponse = await requestWithJar(
    finishUrl,
    {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: finishBody.toString(),
    },
    jar
  );

  const t1 = nowMs();
  if (finishResponse.status !== 302 && finishResponse.status !== 303) {
    const body = await finishResponse.text();
    throw new Error(`POST /finish status=${finishResponse.status} body=${body.slice(0, 120)}`);
  }

  return t1 - t0;
}

async function runScenario(name, concurrency, worker) {
  const latencies = [];
  const errors = [];
  const startedAt = nowMs();

  const tasks = Array.from({ length: concurrency }, async () => {
    try {
      const ms = await worker();
      latencies.push(ms);
    } catch (e) {
      errors.push(String(e && e.message ? e.message : e));
    }
  });

  await Promise.all(tasks);
  const endedAt = nowMs();
  const totalMs = endedAt - startedAt;
  const success = latencies.length;
  const failed = errors.length;

  console.log(`\n=== ${name} ===`);
  console.log(`concurrency: ${concurrency}`);
  console.log(`total: ${totalMs} ms`);
  console.log(`success: ${success}`);
  console.log(`failed: ${failed}`);
  if (success > 0) {
    console.log(`latency p50: ${percentile(latencies, 50)} ms`);
    console.log(`latency p95: ${percentile(latencies, 95)} ms`);
    console.log(`latency max: ${Math.max(...latencies)} ms`);
  }
  if (failed > 0) {
    const grouped = new Map();
    for (const err of errors) {
      grouped.set(err, (grouped.get(err) || 0) + 1);
    }
    console.log('errors:');
    for (const [err, count] of grouped.entries()) {
      console.log(`  ${count}x ${err}`);
    }
  }

  return { name, concurrency, totalMs, success, failed, latencies, errors };
}

async function main() {
  console.log(`BASE_URL=${BASE_URL}`);
  console.log(`TEST_ID=${TEST_ID}`);
  console.log(`TIMEOUT_MS=${TIMEOUT_MS}`);

  // warmup
  await vuGetPassOnly();

  const results = [];
  results.push(await runScenario('GET /pass x50', 50, vuGetPassOnly));
  results.push(await runScenario('GET /pass + POST /finish x50', 50, vuGetPassAndFinish));
  results.push(await runScenario('GET /pass x100', 100, vuGetPassOnly));
  results.push(await runScenario('GET /pass + POST /finish x100', 100, vuGetPassAndFinish));

  const hasFailures = results.some((r) => r.failed > 0);
  if (hasFailures) {
    process.exitCode = 1;
  }
}

main().catch((e) => {
  console.error(e);
  process.exitCode = 1;
});
