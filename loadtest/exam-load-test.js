// k6 load test: concurrent students taking an exam.
//
// Simulates the full student exam flow (login -> instructions -> begin -> take
// -> periodic save-progress -> submit -> result) against a running exam-portal
// instance, to measure how many students can sit an exam at the same time
// before latency/errors become unacceptable.
//
// Run with:
//   k6 run loadtest/exam-load-test.js
//   SCENARIO=spike k6 run loadtest/exam-load-test.js
//   SCENARIO=stress MODE=hammer BASE_URL=http://staging.example k6 run loadtest/exam-load-test.js
//
// See loadtest/README.md for setup and interpretation.

import http from 'k6/http';
import { check, sleep, fail } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const EXAM_ID = __ENV.EXAM_ID || '1';
const STUDENT_COUNT = parseInt(__ENV.STUDENT_COUNT || '500', 10);
const STUDENT_PREFIX = __ENV.STUDENT_PREFIX || 'LT';
const STUDENT_PIN = __ENV.STUDENT_PIN || '1234';
const SCENARIO = __ENV.SCENARIO || 'ramping';
const MODE = __ENV.MODE || 'realistic';

const examsCompleted = new Counter('exams_completed');
const loginSuccessRate = new Rate('login_success_rate');
const submitDuration = new Trend('submit_duration', true);

const SCENARIOS = {
    ramping: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '30s', target: 10 },
            { duration: '1m',  target: 50 },
            { duration: '1m',  target: 100 },
            { duration: '1m',  target: 200 },
            { duration: '2m',  target: 200 },
            { duration: '30s', target: 0 },
        ],
        gracefulRampDown: '30s',
    },
    stress: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '1m', target: 50 },
            { duration: '1m', target: 100 },
            { duration: '1m', target: 200 },
            { duration: '2m', target: 400 },
            { duration: '2m', target: 800 },
            { duration: '1m', target: 0 },
        ],
        gracefulRampDown: '30s',
    },
    spike: {
        executor: 'ramping-vus',
        startVUs: 0,
        stages: [
            { duration: '10s', target: 300 },
            { duration: '1m',  target: 300 },
            { duration: '10s', target: 0 },
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
        http_req_failed: [{ threshold: SCENARIO === 'stress' ? 'rate<0.05' : 'rate<0.01', abortOnFail: SCENARIO === 'stress' }],
        'http_req_duration{name:submit}': [SCENARIO === 'stress' ? 'p(95)<10000' : 'p(95)<3000'],
        'http_req_duration{name:login}': ['p(95)<2000'],
        checks: [SCENARIO === 'stress' ? 'rate>0.95' : 'rate>0.99'],
        login_success_rate: ['rate>0.99'],
    },
    discardResponseBodies: false,
    noConnectionReuse: false,
};

function pickCredentials() {
    const idx = ((__VU - 1) % STUDENT_COUNT) + 1;
    const num = String(idx).padStart(6, '0');
    return { student_number: `${STUDENT_PREFIX}-${num}`, pin: STUDENT_PIN };
}

function extractCsrf(body) {
    const m = body.match(/name="_token"\s+value="([^"]+)"/);
    if (!m) return null;
    return m[1];
}

function extractAnswerableOptions(body) {
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
            // match: answers[qid][optRowId]=matchPairValue
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
    sleep(min + Math.random() * (max - min));
}

export default function () {
    const creds = pickCredentials();

    // 1) GET /login -> extract CSRF
    let res = http.get(`${BASE_URL}/login`, { tags: { name: 'login_page' } });
    if (!check(res, { 'login page 200': (r) => r.status === 200 })) {
        fail(`login page failed status=${res.status}`);
    }
    let token = extractCsrf(res.body);
    if (!token) fail('no CSRF token on login page');

    // 2) POST /login
    res = http.post(
        `${BASE_URL}/login`,
        {
            student_number: creds.student_number,
            pin: creds.pin,
            _token: token,
        },
        { tags: { name: 'login' }, redirects: 0 }
    );
    const loggedIn = res.status === 302 && (res.headers['Location'] || '').includes('/student/');
    loginSuccessRate.add(loggedIn);
    if (!check(res, { 'login redirect to /student': () => loggedIn })) {
        fail(`login failed for ${creds.student_number} status=${res.status}`);
    }

    think(0.5, 1.5);

    // 3) GET instructions
    res = http.get(`${BASE_URL}/student/exams/${EXAM_ID}/instructions`, {
        tags: { name: 'instructions' },
    });
    check(res, { 'instructions 200': (r) => r.status === 200 });
    token = extractCsrf(res.body) || token;

    think(1, 3);

    // 4) POST begin
    res = http.post(
        `${BASE_URL}/student/exams/${EXAM_ID}/begin`,
        { _token: token },
        { tags: { name: 'begin' }, redirects: 0 }
    );
    if (!check(res, { 'begin redirect': (r) => r.status === 302 })) {
        fail(`begin failed status=${res.status}`);
    }

    // 5) GET take page -> parse questions
    res = http.get(`${BASE_URL}/student/exams/${EXAM_ID}/take`, {
        tags: { name: 'take' },
    });
    if (!check(res, { 'take 200': (r) => r.status === 200 })) {
        fail(`take page failed status=${res.status}`);
    }
    token = extractCsrf(res.body) || token;
    const questions = extractAnswerableOptions(res.body);
    if (Object.keys(questions).length === 0) {
        fail('no questions parsed from take page');
    }
    const answers = buildAnswersPayload(questions);

    // 6) Simulate answering with save-progress beacons (realistic mode only)
    if (MODE === 'realistic') {
        const beacons = 2;
        const qids = Object.keys(questions);
        for (let b = 1; b <= beacons; b++) {
            think(4, 8);
            const subset = { _token: token };
            const upto = Math.ceil((qids.length * b) / beacons);
            for (let i = 0; i < upto; i++) {
                const qid = qids[i];
                if (questions[qid].type !== 'radio') continue; // beacon skips match
                const key = `answers[${qid}]`;
                if (answers[key] !== undefined) subset[key] = answers[key];
            }
            http.post(`${BASE_URL}/student/exams/${EXAM_ID}/save-progress`, subset, {
                tags: { name: 'save_progress' },
            });
        }
    }

    think(1, 2);

    // 7) POST submit
    const submitRes = http.post(
        `${BASE_URL}/student/exams/${EXAM_ID}/submit`,
        Object.assign({ _token: token }, answers),
        { tags: { name: 'submit' }, redirects: 0 }
    );
    submitDuration.add(submitRes.timings.duration);
    const submitted = submitRes.status === 302;
    if (!check(submitRes, { 'submit redirect': () => submitted })) {
        fail(`submit failed status=${submitRes.status} body=${submitRes.body && submitRes.body.substring(0, 200)}`);
    }

    // 8) GET result
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

export function handleSummary(data) {
    const lines = [];
    lines.push('');
    lines.push('=== Exam Load Test Summary ===');
    lines.push(`Scenario: ${SCENARIO}   Mode: ${MODE}   Exam ID: ${EXAM_ID}`);
    lines.push(`Target:   ${BASE_URL}`);
    lines.push(`Students: ${STUDENT_PREFIX}-000001 .. ${STUDENT_PREFIX}-${String(STUDENT_COUNT).padStart(6, '0')}`);
    lines.push('');

    const metric = (name) => data.metrics[name];
    const get = (m, k) => (m && m.values && m.values[k] !== undefined ? m.values[k] : 'n/a');

    const dur = metric('http_req_duration');
    const failed = metric('http_req_failed');
    const completed = metric('exams_completed');
    const logins = metric('login_success_rate');
    const submit = metric('submit_duration');

    lines.push(`Exams completed:        ${get(completed, 'count')}`);
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
