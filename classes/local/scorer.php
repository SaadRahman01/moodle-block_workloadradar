<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_workloadradar\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Pure scoring + bucketing logic. No DB, no globals — easy to unit test.
 *
 * @package    block_workloadradar
 * @copyright  2026 Saad Rahman
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scorer {

    const BUCKET_THIS_WEEK = 'thisweek';
    const BUCKET_NEXT_WEEK = 'nextweek';
    const BUCKET_LATER     = 'later';

    /**
     * Bucket events into this week / next week / later and flag collisions.
     *
     * @param array $events Normalised event records from collector::collect().
     * @param int $threshold Collision threshold (items per bucket).
     * @param bool $hidecompleted Exclude completed items entirely if true.
     * @param int $now Reference timestamp; defaults to time(). Injectable for tests.
     * @return array Bucket map keyed by bucket id, each with items + collision flag.
     */
    public static function bucket(array $events, int $threshold, bool $hidecompleted, int $now = 0): array {
        if ($now === 0) {
            $now = time();
        }

        if ($hidecompleted) {
            $events = array_values(array_filter($events, static fn($e) => empty($e->completed)));
        }

        usort($events, static fn($a, $b) => $a->timesort <=> $b->timesort);

        $endthisweek = self::end_of_week($now);
        $endnextweek = $endthisweek + WEEKSECS;

        $buckets = [
            self::BUCKET_THIS_WEEK => [],
            self::BUCKET_NEXT_WEEK => [],
            self::BUCKET_LATER     => [],
        ];

        foreach ($events as $event) {
            if ($event->timesort <= $endthisweek) {
                $buckets[self::BUCKET_THIS_WEEK][] = $event;
            } else if ($event->timesort <= $endnextweek) {
                $buckets[self::BUCKET_NEXT_WEEK][] = $event;
            } else {
                $buckets[self::BUCKET_LATER][] = $event;
            }
        }

        $result = [];
        foreach ($buckets as $key => $items) {
            $result[$key] = (object)[
                'key' => $key,
                'items' => $items,
                'count' => count($items),
                'collision' => count($items) >= $threshold,
                'threshold' => $threshold,
            ];
        }

        return $result;
    }

    /**
     * Compute the Unix timestamp for the end of the calendar week (Sunday 23:59:59).
     *
     * @param int $now Reference timestamp.
     * @return int
     */
    private static function end_of_week(int $now): int {
        $dow = (int)date('N', $now); // 1=Mon..7=Sun.
        $daysleft = 7 - $dow;
        $eod = strtotime('today 23:59:59', $now);
        return $eod + ($daysleft * DAYSECS);
    }

    /**
     * Build a per-day heatmap of upcoming items across the lookahead window.
     *
     * Each cell carries a count, an intensity bucket (0..4) used for CSS heat
     * shading, a collision flag, and label metadata for the template.
     *
     * @param array $events Normalised events from collector.
     * @param int $lookaheaddays
     * @param int $threshold Collision threshold per day.
     * @param bool $hidecompleted
     * @param int $now Reference timestamp; 0 = time().
     * @return array Cell objects keyed numerically (today = 0).
     */
    public static function days_heatmap(array $events, int $lookaheaddays, int $threshold,
            bool $hidecompleted, int $now = 0): array {
        if ($now === 0) {
            $now = time();
        }

        if ($hidecompleted) {
            $events = array_values(array_filter($events, static fn($e) => empty($e->completed)));
        }

        $startofday = strtotime('today 00:00:00', $now);
        $perday = array_fill(0, $lookaheaddays, 0);

        foreach ($events as $event) {
            $offset = (int)floor(($event->timesort - $startofday) / DAYSECS);
            if ($offset < 0 || $offset >= $lookaheaddays) {
                continue;
            }
            $perday[$offset]++;
        }

        $cells = [];
        foreach ($perday as $i => $count) {
            $ts = $startofday + ($i * DAYSECS);
            $cells[] = (object)[
                'offset' => $i,
                'timestamp' => $ts,
                'count' => $count,
                'intensity' => self::intensity_bucket($count, $threshold),
                'collision' => $count >= $threshold,
                'istoday' => $i === 0,
                'dow' => (int)date('N', $ts), // 1..7 (locale-independent; weekday name is localised in the output layer).
                'daynum' => (int)date('j', $ts),
            ];
        }

        return $cells;
    }

    /**
     * Map a raw item count to a 0..4 intensity bucket for the heatmap shading.
     *
     * @param int $count
     * @param int $threshold Collision threshold (anything at/above caps to 4).
     * @return int
     */
    private static function intensity_bucket(int $count, int $threshold): int {
        if ($count <= 0) {
            return 0;
        }
        if ($count >= $threshold) {
            return 4;
        }
        // Spread 1..(threshold-1) across intensities 1..3.
        $span = max(1, $threshold - 1);
        $ratio = $count / $span;
        return (int)min(3, max(1, ceil($ratio * 3)));
    }

    /**
     * Classify the overall workload "mode" of the next 7 days for the gamified badge.
     *
     * @param array $heatmap Cells returned by days_heatmap().
     * @return array {key, label_stringid, emoji}
     */
    public static function mode_for_week(array $heatmap): array {
        $week = array_slice($heatmap, 0, 7);
        $weektotal = array_sum(array_map(static fn($c) => $c->count, $week));
        $hascollision = (bool)array_filter($week, static fn($c) => $c->collision);
        $maxday = $week ? max(array_map(static fn($c) => $c->count, $week)) : 0;

        if ($weektotal === 0) {
            return ['key' => 'easymode', 'emoji' => '🌿'];
        }
        if ($hascollision || $maxday >= 4) {
            return ['key' => 'bossweek', 'emoji' => '🔥'];
        }
        if ($weektotal >= 5) {
            return ['key' => 'crunch', 'emoji' => '⚡'];
        }
        return ['key' => 'steady', 'emoji' => '🎯'];
    }

    /**
     * Pick a traffic-light urgency for a single item by how many hours away it is.
     *
     * @param int $timesort
     * @param int $now
     * @return string One of: red, amber, green.
     */
    public static function urgency(int $timesort, int $now = 0): string {
        if ($now === 0) {
            $now = time();
        }
        $hours = ($timesort - $now) / HOURSECS;
        if ($hours <= 48) {
            return 'red';
        }
        if ($hours <= 24 * 7) {
            return 'amber';
        }
        return 'green';
    }

    /**
     * Format a human countdown like "2d", "5h", "now".
     *
     * @param int $timesort
     * @param int $now
     * @return string
     */
    public static function countdown_label(int $timesort, int $now = 0): string {
        if ($now === 0) {
            $now = time();
        }
        $delta = $timesort - $now;
        if ($delta <= 0) {
            return get_string('countdown_now', 'block_workloadradar');
        }
        if ($delta < HOURSECS) {
            $mins = max(1, (int)round($delta / MINSECS));
            return get_string('countdown_minutes', 'block_workloadradar', $mins);
        }
        if ($delta < DAYSECS) {
            $hours = max(1, (int)round($delta / HOURSECS));
            return get_string('countdown_hours', 'block_workloadradar', $hours);
        }
        $days = max(1, (int)round($delta / DAYSECS));
        return get_string('countdown_days', 'block_workloadradar', $days);
    }
}
