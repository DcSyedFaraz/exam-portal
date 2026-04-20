// k6 load test: concurrent students taking an exam.
//
// Simulates the full student exam flow (login -> instructions -> begin -> take
// -> periodic save-progress -> submit -> result) against a running exam-portal
// instance, to measure how many students can sit an exam at the same time
// before latency/errors become unacceptable.
//
// Run with:
//   k6 run loadtest/exam-load-test.js
//   k6 run --env SCENARIO=stress loadtest/exam-load-test.js
//   k6 run --env SCENARIO=spike  loadtest/exam-load-test.js
//   k6 run --env SCENARIO=stress --env MODE=hammer loadtest/exam-load-test.js
//
// ⚠️  NEVER add --http-debug on stress runs — it prints every header to stdout
//     and will hang your terminal / PC at high VU counts.
//
// BEFORE each run: clear previous attempts so students aren't "already passed":
//   php artisan tinker --execute="App\Models\ExamAttempt::where('exam_id',1)->delete();"
//
// See loadtest/README.md for setup and interpretation.

import http from 'k6/http';
import { check, sleep, fail } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

const BASE_URL       = __ENV.BASE_URL       || 'http://localhost:8000';
const EXAM_ID        = __ENV.EXAM_ID        || '1';
const STUDENT_COUNT  = parseInt(__ENV.STUDENT_COUNT || '500', 10);
const STUDENT_PREFIX = __ENV.STUDENT_PREFIX || 'LT';
const STUDENT_PIN    = __ENV.STUDENT_PIN    || '1234';
const SCENARIO       = __ENV.SCENARIO       || 'ramping';
const MODE           = __ENV.MODE           || 'realistic';

// Think-time scale: stress/spike use 0.2 (5× shorter) to avoid session pile-up.
// Override with --env THINK_SCALE=1.0 to restore full realistic pacing.
const THINK_SCALE = parseFloat(
    __ENV.THINK_SCALE || (SCENARIO === 'ramping' ? '1.0' : '0.2')
);

const examsCompleted  = new Counter('exams_completed');
const loginSuccessRate = new Rate('login_success_rate');
const submitDuration  = new Trend('submit_duration', true);
const alreadyPassed   = new Counter('already_passed_skipped');

const SCENARIOS = {
    // Gentle warm-up ramp — good for a first run to find the ceiling
    ramping: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '30s', target: 10  },
            { duration: '1m',  target: 50  },
            { duration: '1m',  target: 100 },
            { duration: '1m',  target: 150 },
            { duration: '2m',  target: 150 },
            { duration: '30s', target: 0   },
        ],
        gracefulRampDown: '30s',
    },
    // Stress test — pushes past comfortable limits. Keep ≤300 VUs on a local
    // dev server (Laragon/Apache); 800 VUs will saturate the thread pool.
    stress: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '1m', target: 50  },
            { duration: '1m', target: 100 },
            { duration: '1m', target: 200 },
            { duration: '2m', target: 300 },
            { duration: '2m', target: 300 },
            { duration: '1m', target: 0   },
        ],
        gracefulRampDown: '30s',
    },
    // Spike: sudden burst (e.g. exam opens at 09:00 and 200 students click at once)
    spike: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '10s', target: 200 },
            { duration: '1m',  target: 200 },
            { duration: '10s', target: 0   },
        ],
        gracefulRampDown: '10s',
    },
};

if (!SCENARIOS[SCENARIO]) {
    throw new Error(`Unknown SCENARIO "${SCENARIO}". Use one of: ${Object.keys(SCENARIOS).join(', ')}`);
}

export const options = {
    scenarios: { [SCENARIO]: SCENARIOS[SCENARIO] },
    thresholds: {
        // Allow up to 5–10 % failure rate in stress (timeouts at high VU count are expected)
        http_req_failed: [
            {
                threshold:   SCENARIO === 'stress' ? 'rate<0.10' : 'rate<0.02',
                abortOnFail: false,   // never abort mid-run; review results afterwards
            },
        ],
        // submit p95: stress threshold is generous — DB contention at 300 VUs is real.
        // With THINK_SCALE=0.2 this should drop to ~5–8 s in normal stress runs.
        'http_req_duration{name:submit}': [
            {
                threshold:   SCENARIO === 'stress' ? 'p(95)<30000' : 'p(95)<5000',
                abortOnFail: false,
            },
        ],
        'http_req_duration{name:login}': [{ threshold: 'p(95)<10000', abortOnFail: false }],
        checks: [{ threshold: SCENARIO === 'stress' ? 'rate>0.88' : 'rate>0.95', abortOnFail: false }],
        login_success_rate: [{ threshold: SCENARIO === 'stress' ? 'rate>0.90' : 'rate>0.95', abortOnFail: false }],
    },
    discardResponseBodies: false,
    noConnectionReuse: false,
    // Give the server breathing room on timeouts
    httpDebug: 'none',
};

// ── Helpers ──────────────────────────────────────────────────────────────────

function pickCredentials() {
    const idx = ((__VU - 1) % STUDENT_COUNT) + 1;
    const num = String(idx).padStart(6, '0');
    return { student_number: `${STUDENT_PREFIX}-${num}`, pin: STUDENT_PIN };
}

function extractCsrf(body) {
    if (!body) return null;
    const m = body.match(/name="_token"\s+value="([^"]+)"/);
    return m ? m[1] : null;
}

function extractAnswerableOptions(body) {
    if (!body) return {};
    const questions = {};
    const re = /name="answers\[(\d+)\](?:\[(\d+)\])?"\s+value="([^"]+)"/g;
    let m;
    while ((m = re.exec(body)) !== null) {
        const qid = m[1];
        const optRowId = m[2];
        const value = m[3];
        if (!questions[qid]) questions[qid] = { type: optRowId ? 'match' : 'radio', options: [] };
        questions[qid].options.push({ optRowId, value });
    }
    return questions;
}

function buildAnswersPayload(questions) {
    const payload = {};
    for (const [qid, q] of Object.entries(questions)) {
        if (q.type === 'radio') {
            const pick = q.options[Math.floor(Math.random() * q.options.length)];
            payload[`answers[${qid}]`] = pick.value;
        } else {
            const values = [...new Set(q.options.map((o) => o.value))];
            for (const o of q.options) {
                const pick = values[Math.floor(Math.random() * values.length)];
                payload[`answers[${qid}][${o.optRowId}]`] = pick;
            }
        }
    }
    return payload;
}

function think(min, max) {
    if (MODE === 'hammer') return;
    const t = (min + Math.random() * (max - min)) * THINK_SCALE;
    if (t > 0.05) sleep(t); // skip sub-50ms sleeps
}

// Returns true if the page body looks like it's the already-passed / result page
// (no question inputs present) rather than a real exam take page.
function isResultOrRedirectPage(body, finalUrl) {
    if (!body) return true;
    // If we landed on /result or /exams listing, there are no question inputs
    if (finalUrl && (finalUrl.includes('/result') || finalUrl.includes('/exams') && !finalUrl.includes('/take'))) {
        return true;
    }
    // Heuristic: a take page always has at least one answers[] input
    const hasQuestions = /name="answers\[/.test(body);
    return !hasQuestions;
}

// ── Main VU function ──────────────────────────────────────────────────────────

export default function () {
    const creds = pickCredentials();

    // ── 1) GET /login → extract CSRF ─────────────────────────────────────────
    let res = http.get(`${BASE_URL}/login`, { tags: { name: 'login_page' } });
    if (!check(res, { 'login page 200': (r) => r.status === 200 })) {
        // status=0 means a connection timeout — server saturated; bail quietly
        return;
    }
    let token = extractCsrf(res.body);
    if (!token) {
        // Couldn't parse CSRF from login page — likely a garbled / truncated response
        return;
    }

    // ── 2) POST /login ───────────────────────────────────────────────────────
    res = http.post(
        `${BASE_URL}/login`,
        { student_number: creds.student_number, pin: creds.pin, _token: token },
        { tags: { name: 'login' }, redirects: 0 }
    );
    const loggedIn = res.status === 302 && (res.headers['Location'] || '').includes('/student/');
    loginSuccessRate.add(loggedIn);
    if (!check(res, { 'login redirect to /student': () => loggedIn })) {
        // Wrong credentials or server error — log and bail
        return;
    }

    think(0.5, 1.5);

    // ── 3) GET instructions ──────────────────────────────────────────────────
    // This may 302-redirect to /result if student already passed.
    res = http.get(`${BASE_URL}/student/exams/${EXAM_ID}/instructions`, {
        tags: { name: 'instructions' },
    });
    token = extractCsrf(res.body) || token;

    // If redirected to result page (already passed), skip gracefully
    if (res.url && res.url.includes('/result')) {
        alreadyPassed.add(1);
        return; // count as a skipped VU, not a failure
    }
    check(res, { 'instructions 200': (r) => r.status === 200 });

    think(1, 3);

    // ── 4) POST begin ────────────────────────────────────────────────────────
    res = http.post(
        `${BASE_URL}/student/exams/${EXAM_ID}/begin`,
        { _token: token },
        { tags: { name: 'begin' }, redirects: 0 }
    );

    // begin may 302→/result (already passed) or 302→/take (normal)
    if (res.status === 302) {
        const loc = res.headers['Location'] || '';
        if (loc.includes('/result')) {
            alreadyPassed.add(1);
            return; // already passed — skip, not a failure
        }
    } else if (!check(res, { 'begin redirect': (r) => r.status === 302 })) {
        return; // unexpected response
    }

    // ── 5) GET take page → parse questions ──────────────────────────────────
    res = http.get(`${BASE_URL}/student/exams/${EXAM_ID}/take`, {
        tags: { name: 'take' },
    });
    if (!check(res, { 'take 200': (r) => r.status === 200 })) {
        return;
    }
    token = extractCsrf(res.body) || token;

    // Detect "already passed" redirect that k6 auto-followed to /result
    if (isResultOrRedirectPage(res.body, res.url)) {
        alreadyPassed.add(1);
        return; // graceful skip — student already passed, not an error
    }

    const questions = extractAnswerableOptions(res.body);
    if (Object.keys(questions).length === 0) {
        // Still no questions after redirect check — likely a server-side error page
        fail('no questions parsed from take page');
    }
    const answers = buildAnswersPayload(questions);

    // ── 6) Periodic save-progress beacons (realistic mode) ──────────────────
    if (MODE === 'realistic') {
        const beacons = 2;
        const qids = Object.keys(questions);
        for (let b = 1; b <= beacons; b++) {
            think(4, 8);
            const subset = { _token: token };
            const upto = Math.ceil((qids.length * b) / beacons);
            for (let i = 0; i < upto; i++) {
                const qid = qids[i];
                if (questions[qid].type !== 'radio') continue;
                const key = `answers[${qid}]`;
                if (answers[key] !== undefined) subset[key] = answers[key];
            }
            http.post(`${BASE_URL}/student/exams/${EXAM_ID}/save-progress`, subset, {
                tags: { name: 'save_progress' },
            });
        }
    }

    think(1, 2);

    // ── 7) POST submit ───────────────────────────────────────────────────────
    const submitRes = http.post(
        `${BASE_URL}/student/exams/${EXAM_ID}/submit`,
        Object.assign({ _token: token }, answers),
        { tags: { name: 'submit' }, redirects: 0 }
    );
    submitDuration.add(submitRes.timings.duration);
    const submitted = submitRes.status === 302;
    if (!check(submitRes, { 'submit redirect': () => submitted })) {
        // Non-critical: server may have returned 500 under extreme load
        return;
    }

    // ── 8) GET result ────────────────────────────────────────────────────────
    res = http.get(`${BASE_URL}/student/exams/${EXAM_ID}/result`, {
        tags: { name: 'result' },
    });
    const ok = check(res, {
        'result 200': (r) => r.status === 200,
        'result has PASSED or FAILED': (r) => /PASSED|FAILED/.test(r.body || ''),
    });
    if (ok) examsCompleted.add(1);

    think(0.5, 1.5);
}

// ── Summary ───────────────────────────────────────────────────────────────────

export function handleSummary(data) {
    const lines = [];
    lines.push('');
    lines.push('=== Exam Load Test Summary ===');
    lines.push(`Scenario: ${SCENARIO}   Mode: ${MODE}   Think scale: ${THINK_SCALE}×   Exam ID: ${EXAM_ID}`);
    lines.push(`Target:   ${BASE_URL}`);
    lines.push(`Students: ${STUDENT_PREFIX}-000001 .. ${STUDENT_PREFIX}-${String(STUDENT_COUNT).padStart(6, '0')}`);
    lines.push('');

    const metric = (name) => data.metrics[name];
    const get    = (m, k) => (m && m.values && m.values[k] !== undefined ? m.values[k] : 'n/a');

    const dur       = metric('http_req_duration');
    const failed    = metric('http_req_failed');
    const completed = metric('exams_completed');
    const skipped   = metric('already_passed_skipped');
    const logins    = metric('login_success_rate');
    const submit    = metric('submit_duration');

    lines.push(`Exams completed:        ${get(completed, 'count')}`);
    lines.push(`Already-passed skipped: ${get(skipped,   'count')}`);
    lines.push(`Login success rate:     ${(Number(get(logins, 'rate') || 0) * 100).toFixed(2)}%`);
    lines.push(`HTTP failures:          ${(Number(get(failed, 'rate') || 0) * 100).toFixed(2)}%`);
    lines.push(`http_req_duration p95:  ${Number(get(dur, 'p(95)') || 0).toFixed(0)} ms`);
    lines.push(`http_req_duration p99:  ${Number(get(dur, 'p(99)') || 0).toFixed(0)} ms`);
    lines.push(`submit p95:             ${Number(get(submit, 'p(95)') || 0).toFixed(0)} ms`);
    lines.push(`submit p99:             ${Number(get(submit, 'p(99)') || 0).toFixed(0)} ms`);
    lines.push('');

    return {
        stdout: lines.join('\n') + '\n',
        'loadtest/results/summary.json': JSON.stringify(data, null, 2),
    };
}
