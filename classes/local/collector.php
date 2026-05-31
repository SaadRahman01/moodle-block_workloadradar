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
 * Collects upcoming action events for a user across all active enrolments.
 *
 * @package    block_workloadradar
 * @copyright  2026 Saad Rahman
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class collector {

    /** @var int Hard cap per calendar API call (Moodle imposes 1..50). */
    const PAGE_SIZE = 50;

    /** @var int Safety cap so a runaway loop cannot scan forever. */
    const MAX_EVENTS = 500;

    /**
     * Return upcoming action events for the given user within the lookahead window.
     *
     * @param int $userid User id.
     * @param int $lookaheaddays Days from now to scan.
     * @return array List of normalised event records.
     */
    public static function collect(int $userid, int $lookaheaddays): array {
        global $USER;

        // The core calendar API only exposes results for $USER, so guard accordingly.
        if ((int)$USER->id !== $userid) {
            return [];
        }

        $from = time();
        $to = $from + ($lookaheaddays * DAYSECS);

        $events = self::fetch_paginated($from, $to);
        $courses = enrol_get_users_courses($userid, true, ['id', 'shortname', 'fullname']);

        return self::normalise($events, $courses, $userid);
    }

    /**
     * Fetch action events from the core calendar API, paginating via aftereventid.
     *
     * @param int $from Unix timestamp lower bound (inclusive).
     * @param int $to Unix timestamp upper bound (exclusive).
     * @return array Raw event exporter objects.
     */
    private static function fetch_paginated(int $from, int $to): array {
        $collected = [];
        $aftereventid = null;

        do {
            $result = \core_calendar\local\api::get_action_events_by_timesort(
                $from,
                $to,
                $aftereventid,
                self::PAGE_SIZE,
                true
            );

            if (empty($result)) {
                break;
            }

            foreach ($result as $event) {
                $collected[] = $event;
                if (count($collected) >= self::MAX_EVENTS) {
                    break 2;
                }
            }

            $last = end($result);
            $aftereventid = $last ? $last->get_id() : null;

            // Stop if the page wasn't full — no more results.
            if (count($result) < self::PAGE_SIZE) {
                break;
            }
        } while ($aftereventid !== null);

        return $collected;
    }

    /**
     * Convert event exporter objects into a flat array suitable for scoring/rendering.
     *
     * @param array $events
     * @param array $courses Map of courseid => course record.
     * @param int $userid
     * @return array
     */
    private static function normalise(array $events, array $courses, int $userid): array {
        $out = [];

        foreach ($events as $event) {
            $course = $event->get_course();
            $courseid = $course ? (int)$course->get('id') : 0;

            // Skip events from courses the user is no longer enrolled in (site-level events keep courseid 0).
            if ($courseid > 0 && !isset($courses[$courseid])) {
                continue;
            }

            $action = $event->get_action();
            $url = $action ? $action->get_url() : null;
            $cm = $event->get_course_module();

            $out[] = (object)[
                'id' => (int)$event->get_id(),
                'name' => $event->get_name(),
                'timesort' => (int)$event->get_times()->get_sort_time()->getTimestamp(),
                'courseid' => $courseid,
                'coursename' => isset($courses[$courseid]) ? $courses[$courseid]->shortname : '',
                'modulename' => $cm ? (string)$cm->get('modname') : '',
                'url' => $url ? $url->out(false) : '',
                'completed' => self::is_completed($event, $userid),
            ];
        }

        return $out;
    }

    /**
     * Determine whether the activity behind an event is already completed by the user.
     *
     * @param mixed $event Calendar event exporter object.
     * @param int $userid
     * @return bool
     */
    private static function is_completed($event, int $userid): bool {
        $cm = $event->get_course_module();
        if (!$cm) {
            return false;
        }

        try {
            $course = get_course($cm->get('course'));
            $info = new \completion_info($course);
            if (!$info->is_enabled()) {
                return false;
            }
            $cminfo = get_fast_modinfo($course, $userid)->get_cm($cm->get('id'));
            $data = $info->get_data($cminfo, false, $userid);
            return in_array((int)$data->completionstate, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true);
        } catch (\Throwable $e) {
            debugging('block_workloadradar completion lookup failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }
}
