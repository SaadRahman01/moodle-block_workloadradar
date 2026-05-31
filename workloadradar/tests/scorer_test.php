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

namespace block_workloadradar;

use block_workloadradar\local\scorer;

/**
 * Unit tests for the pure bucketing/scoring logic.
 *
 * @package    block_workloadradar
 * @copyright  2026 Saad Rahman
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_workloadradar\local\scorer
 */
final class scorer_test extends \basic_testcase {

    private function event(int $timesort, bool $completed = false): \stdClass {
        return (object)[
            'id' => $timesort,
            'name' => 'Event ' . $timesort,
            'timesort' => $timesort,
            'courseid' => 1,
            'coursename' => 'C1',
            'modulename' => 'assign',
            'url' => 'https://example.test',
            'completed' => $completed,
        ];
    }

    public function test_bucket_places_events_correctly(): void {
        // Pin "now" to a Monday at noon so week boundaries are deterministic.
        $now = strtotime('2026-06-01 12:00:00');

        $events = [
            $this->event($now + 2 * DAYSECS),       // This week.
            $this->event($now + 9 * DAYSECS),       // Next week.
            $this->event($now + 30 * DAYSECS),      // Later.
        ];

        $buckets = scorer::bucket($events, 3, false, $now);

        $this->assertSame(1, $buckets[scorer::BUCKET_THIS_WEEK]->count);
        $this->assertSame(1, $buckets[scorer::BUCKET_NEXT_WEEK]->count);
        $this->assertSame(1, $buckets[scorer::BUCKET_LATER]->count);
        $this->assertFalse($buckets[scorer::BUCKET_THIS_WEEK]->collision);
    }

    public function test_collision_flag_triggers_at_threshold(): void {
        $now = strtotime('2026-06-01 12:00:00');
        $events = [
            $this->event($now + DAYSECS),
            $this->event($now + 2 * DAYSECS),
            $this->event($now + 3 * DAYSECS),
        ];

        $buckets = scorer::bucket($events, 3, false, $now);
        $this->assertTrue($buckets[scorer::BUCKET_THIS_WEEK]->collision);

        $buckets = scorer::bucket($events, 4, false, $now);
        $this->assertFalse($buckets[scorer::BUCKET_THIS_WEEK]->collision);
    }

    public function test_hide_completed_removes_finished_items(): void {
        $now = strtotime('2026-06-01 12:00:00');
        $events = [
            $this->event($now + DAYSECS, true),
            $this->event($now + 2 * DAYSECS, false),
        ];

        $buckets = scorer::bucket($events, 3, true, $now);
        $this->assertSame(1, $buckets[scorer::BUCKET_THIS_WEEK]->count);

        $buckets = scorer::bucket($events, 3, false, $now);
        $this->assertSame(2, $buckets[scorer::BUCKET_THIS_WEEK]->count);
    }

    public function test_events_are_sorted_chronologically(): void {
        $now = strtotime('2026-06-01 12:00:00');
        $events = [
            $this->event($now + 3 * DAYSECS),
            $this->event($now + DAYSECS),
            $this->event($now + 2 * DAYSECS),
        ];

        $buckets = scorer::bucket($events, 99, false, $now);
        $items = $buckets[scorer::BUCKET_THIS_WEEK]->items;

        $this->assertSame($now + DAYSECS, $items[0]->timesort);
        $this->assertSame($now + 2 * DAYSECS, $items[1]->timesort);
        $this->assertSame($now + 3 * DAYSECS, $items[2]->timesort);
    }
}
