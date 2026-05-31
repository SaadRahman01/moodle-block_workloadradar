# Workload Radar — Moodle block

A student-facing block that aggregates every upcoming deadline across all of a
student's courses into one view and flags *collision* periods when several
high-stakes items land close together.

This is the gap left by the default Timeline block (`block_myoverview`), which
shows a flat chronological list with no cross-course density or load analysis.

> **Status:** alpha · `MATURITY_ALPHA` · v1.0.0

## Why this block

The Timeline block answers *"what is due next?"* — a flat list. It does not
answer the question students actually have at week-start:

> *"Where am I about to get crushed?"*

Workload Radar answers it by bucketing upcoming items into **This week / Next
week / Later** and surfacing a **collision badge** when any bucket exceeds a
configurable threshold (default ≥ 3 items).

## Features (v1)

- Aggregates upcoming deadlines across **all active enrolments**.
- Buckets items into **This week / Next week / Later**.
- **Collision flag** when a bucket meets the user's threshold.
- **Hide / dim completed** items so the view shows remaining load.
- Click-through to each activity.
- Per-user preferences: lookahead length, hide-completed, collision threshold.
- Compact dashboard block **+** a "View full radar" page.
- Per-user MUC cache with short TTL so the dashboard does not pay the calendar
  cost on every paint.

### Deliberately deferred to v2+

- Grade-weighted / effort-weighted load scoring.
- iCal / Google Calendar export.
- Per-type effort estimates the student can tune.
- Instructor "cohort heatmap" view (capability `:viewother` reserved).
- "Heavy week ahead" notification digest.

## Requirements

- Moodle **4.0** or later. Supported up to and including **5.2**.
- PHP **7.4+** (matches Moodle 4.0's floor).
- No external services. No third-party API calls. No telemetry.

## Install

1. Copy this directory to `blocks/workloadradar` inside your Moodle
   installation, or install via *Site administration → Plugins → Install
   plugins*.
2. Visit *Site administration → Notifications* and complete the upgrade.
3. Add the block to your **Dashboard** as a student.

## Configuration

Site administrators can set defaults at *Site administration → Plugins → Blocks
→ Workload Radar*:

| Setting | Default | Purpose |
|---|---|---|
| Default lookahead (days) | 21 | How far ahead the radar scans by default. |
| Default collision threshold | 3 | Items per bucket before the collision badge fires. |
| Hide completed by default | on | Initial state of the per-user toggle. |

Users override these via the full-page view (`/blocks/workloadradar/view.php`).

## Privacy

This block stores **no plugin-owned data**. It persists only three values, all
via the core *user preferences* API:

- `block_workloadradar_lookahead`
- `block_workloadradar_hidecompleted`
- `block_workloadradar_threshold`

All three are exported and described by `\block_workloadradar\privacy\provider`,
which implements `metadata\provider` and `request\user_preference_provider`.

The radar itself is computed on the fly from data the student already has
access to (calendar + completion + enrolment).

## Architecture

```
block_workloadradar/
├── version.php
├── block_workloadradar.php
├── settings.php
├── view.php
├── styles.css
├── db/
│   ├── access.php          — capabilities
│   ├── services.php        — AJAX endpoints
│   └── caches.php          — MUC definitions
├── classes/
│   ├── preferences.php     — user-pref + admin defaults helper
│   ├── external/
│   │   ├── get_radar.php
│   │   └── set_preferences.php
│   ├── output/
│   │   ├── radar.php       — renderable + templatable
│   │   └── renderer.php
│   ├── local/
│   │   ├── collector.php   — wraps core_calendar action events + pagination
│   │   └── scorer.php      — pure bucketing + collision logic (unit-tested)
│   └── privacy/
│       └── provider.php
├── templates/
│   ├── radar_compact.mustache
│   └── radar_full.mustache
├── amd/src/radar.js
├── lang/en/block_workloadradar.php
└── tests/scorer_test.php
```

### Data sources used

| Concern | Moodle API |
|---|---|
| Deadlines | `\core_calendar\local\api::get_action_events_by_timesort` (50-per-call cap, paginated via `aftereventid`) |
| Completion | `completion_info::get_data()` per cm |
| Enrolment | `enrol_get_users_courses()` |
| Preferences | `get_user_preferences` / `set_user_preference` |
| Cache | MUC `application` store, TTL 600s |
| Frontend | Output API + Mustache + AMD (`core/ajax`, `core/notification`) |

### Capabilities

| Capability | Default for | Notes |
|---|---|---|
| `block/workloadradar:myaddinstance` | user | Add to Dashboard |
| `block/workloadradar:addinstance` | editingteacher, manager | Add anywhere with risks `RISK_SPAM | RISK_XSS` |
| `block/workloadradar:viewother` | none | **Reserved**, not implemented in v1 |

## Development

### Run the unit tests

From your Moodle root:

```bash
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --filter scorer_test blocks/workloadradar/tests/scorer_test.php
```

`scorer_test` is intentionally a `basic_testcase` — the scoring layer is pure
and needs no DB reset.

### Build the AMD bundle

```bash
npx grunt amd --root=blocks/workloadradar
```

### Coding standards

```bash
vendor/bin/phpcs --standard=moodle blocks/workloadradar
```

## Contributing

Issues and PRs welcome. Please:

1. Keep v1 lean — anything in *deferred to v2+* goes in a separate PR with a
   design note.
2. Run `phpcs` and `phpunit` before opening a PR.
3. Do not introduce third-party network calls.

## License

GPL v3 or later — see [LICENSE](LICENSE). Same license as Moodle itself.
