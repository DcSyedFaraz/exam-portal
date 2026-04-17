# Exam Portal Load Testing

A [k6](https://k6.io) script that simulates concurrent students sitting an exam,
so you can measure how many students the server can support before it degrades.

## What it does

Each virtual user (VU) runs the full student flow:

1. `GET /login` → extract CSRF token
2. `POST /login` with student number + PIN
3. `GET /student/exams/{id}/instructions`
4. `POST /student/exams/{id}/begin` — creates an `exam_attempts` row
5. `GET /student/exams/{id}/take` — parses questions and option IDs from HTML
6. 2 × `POST /student/exams/{id}/save-progress` — simulates the browser beacon
7. `POST /student/exams/{id}/submit` — the heaviest request (transactional grading)
8. `GET /student/exams/{id}/result`

Each VU logs in as a different pre-seeded student (`LT-000001`, `LT-000002`, …)
so sessions and exam attempts do not collide.

## Prerequisites

- **k6** installed locally. macOS: `brew install k6`. Linux: see
  [k6 install docs](https://k6.io/docs/get-started/installation/). Docker users
  can run `docker run --rm -i -v $PWD:/src grafana/k6 run /src/loadtest/exam-load-test.js`.
- The exam portal running somewhere reachable from your machine (defaults to
  `http://localhost:8000`).
- The `student` role must exist (created by `RoleSeeder`).

## Setup

```bash
# 1. Install, migrate, seed roles + the 3 default exams.
composer install
php artisan migrate:fresh --seed

# 2. Seed 500 predictable load-test students (LT-000001 .. LT-000500).
php artisan loadtest:seed-students --count=500

# 3. Start the server in another terminal.
php artisan serve
```

### Seeder options

```bash
php artisan loadtest:seed-students \
    --count=1000 \
    --prefix=LT \
    --class="Class One" \
    --pin=1234 \
    --fresh   # delete existing LT-* students first
```

- `--count` — number of students to create (default `500`).
- `--prefix` — student-number prefix, 1–4 uppercase letters (default `LT`).
  Keep it distinct from production prefixes (`P1`..`P7`, `F1`..`F6`) so
  real accounts are never touched.
- `--class` — `class_level` stored on each profile. Must be one of the values
  in `StudentProfile::CLASS_LEVELS`. The class level must match the target
  exam's `class_level` (or the exam's `class_level` must be `null`), otherwise
  the exam will not be visible to the VU.
- `--pin` — 4-digit PIN, hashed with bcrypt (same cost as production).
- `--fresh` — delete any existing rows with the given prefix before seeding.

## Running

Defaults target `http://localhost:8000`, exam `1` (the seeded "General Knowledge
MCQ"), 500 students, `ramping` scenario, `realistic` mode.

```bash
# Default: ramp to 200 VUs over ~6 min, realistic exam flow.
k6 run loadtest/exam-load-test.js

# Spike: 0 -> 300 VUs in 10s, hold for 1 min.
SCENARIO=spike k6 run loadtest/exam-load-test.js

# Stress: ramps to 800 VUs; aborts when the error-rate threshold breaks.
SCENARIO=stress k6 run loadtest/exam-load-test.js

# Hit a staging server, hammer mode (no think-time, no save-progress beacons).
BASE_URL=https://staging.example.com MODE=hammer k6 run loadtest/exam-load-test.js

# Smoke test the script itself before running big profiles.
k6 run --vus 2 --iterations 2 loadtest/exam-load-test.js
```

### Env vars

| Var             | Default                  | Notes                                              |
| --------------- | ------------------------ | -------------------------------------------------- |
| `BASE_URL`      | `http://localhost:8000`  | Target server.                                     |
| `EXAM_ID`       | `1`                      | Must be published and visible to the seeded class. |
| `STUDENT_COUNT` | `500`                    | Must match `--count` used when seeding.            |
| `STUDENT_PREFIX`| `LT`                     | Must match `--prefix` used when seeding.           |
| `STUDENT_PIN`   | `1234`                   | Must match `--pin` used when seeding.              |
| `SCENARIO`      | `ramping`                | `ramping` \| `stress` \| `spike`                   |
| `MODE`          | `realistic`              | `realistic` \| `hammer`                            |

### Scenarios

- **ramping** (default) — stages `10 → 50 → 100 → 200` VUs, 1 min each, plus a
  2 min hold at peak. Good for "how does p95 behave as load grows?".
- **stress** — ramps `50 → 100 → 200 → 400 → 800`. The `http_req_failed`
  threshold has `abortOnFail: true` in this scenario only, so the run stops
  the moment error rate exceeds 1%. The last stable stage before the abort is
  your practical concurrency ceiling.
- **spike** — 0 → 300 VUs in 10 seconds, held for 1 minute. Simulates every
  student hitting *Begin Exam* at the same moment.

### VU → student mapping

`VU N` logs in as `{PREFIX}-{zero-padded N}`. If the peak VU count is higher
than `STUDENT_COUNT`, VUs wrap with modulo — multiple VUs then share a student,
which the server handles because `getOrCreateInProgressAttempt` reuses the
existing in-progress attempt. For a truly independent-student test, seed as
many students as your peak VU count (e.g. `--count=800` before running
`SCENARIO=stress`).

## Reading the output

k6 prints per-endpoint timings tagged by name (`login`, `begin`, `take`,
`save_progress`, `submit`, `result`). Focus on:

- `http_req_duration{name:submit}` **p(95)** — the slowest real request. If
  this blows past 3 s, you have found your ceiling.
- `http_req_failed` rate — any non-zero value in a ramp is a symptom.
- `checks` rate — drops below 100% when redirects or the result page don't
  return the expected content.
- `exams_completed` — how many VUs made it from login to a rendered result
  page in the run.

A JSON dump of every metric is written to `loadtest/results/summary.json`
(gitignored) after each run for offline analysis.

## Known bottlenecks to expect

The default local stack is not production-grade. Before quoting a number as
"how many students the portal supports", move these off the critical path:

- **bcrypt cost 12 on login** — CPU-bound. Lowering to 10 for load tests makes
  the numbers optimistic; either leave it at 12 and accept slower logins, or
  measure login and exam-flow separately.
- **`SESSION_DRIVER=database`** and **`CACHE_STORE=database`** on SQLite — a
  single writer lock. Switch to MySQL/Postgres for the DB, and Redis for
  sessions + cache, before comparing numbers to real capacity.
- **`BROADCAST_CONNECTION=log`** — fine for this test since the app doesn't
  use real-time features yet.
- **Single `php artisan serve`** — a dev server, not a production worker pool.
  Use PHP-FPM + Nginx or Octane when benchmarking realistic capacity.

## Cleaning up

```bash
# Re-seed with --fresh to drop existing LT-* rows first:
php artisan loadtest:seed-students --count=500 --fresh

# Or wipe the whole database:
php artisan migrate:fresh --seed
```
