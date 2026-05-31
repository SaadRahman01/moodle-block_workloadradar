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

namespace block_workloadradar\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;
use block_workloadradar\local\collector;
use block_workloadradar\local\scorer;
use block_workloadradar\preferences;

/**
 * AJAX endpoint returning the current user's bucketed radar.
 *
 * @package    block_workloadradar
 * @copyright  2026 Saad Rahman
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_radar extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'full' => new external_value(PARAM_BOOL, 'Return full view data', VALUE_DEFAULT, false),
        ]);
    }

    public static function execute(bool $full = false): array {
        global $USER;

        self::validate_parameters(self::execute_parameters(), ['full' => $full]);
        $context = \context_user::instance($USER->id);
        self::validate_context($context);

        $prefs = preferences::get((int)$USER->id);
        $now = time();
        $events = collector::collect((int)$USER->id, $prefs->lookaheaddays);
        $buckets = scorer::bucket($events, $prefs->threshold, (bool)$prefs->hidecompleted, $now);
        $heatmap = scorer::days_heatmap($events, (int)$prefs->lookaheaddays, (int)$prefs->threshold,
                (bool)$prefs->hidecompleted, $now);
        $mode = scorer::mode_for_week($heatmap);

        $out = [];
        $totalitems = 0;
        $hascollision = false;

        foreach ($buckets as $bucket) {
            $items = [];
            foreach ($bucket->items as $item) {
                $items[] = [
                    'id' => $item->id,
                    'name' => format_string($item->name),
                    'coursename' => format_string($item->coursename),
                    'modulename' => (string)$item->modulename,
                    'url' => $item->url,
                    'timesort' => (int)$item->timesort,
                    'duedate' => userdate($item->timesort, get_string('strftimedatetimeshort', 'langconfig')),
                    'countdown' => scorer::countdown_label((int)$item->timesort, $now),
                    'urgency' => scorer::urgency((int)$item->timesort, $now),
                    'completed' => !empty($item->completed),
                ];
            }

            $out[] = [
                'key' => $bucket->key,
                'count' => (int)$bucket->count,
                'collision' => (bool)$bucket->collision,
                'threshold' => (int)$bucket->threshold,
                'items' => $items,
            ];
            $totalitems += $bucket->count;
            if ($bucket->collision) {
                $hascollision = true;
            }
        }

        $heatcells = [];
        foreach ($heatmap as $cell) {
            $heatcells[] = [
                'offset' => (int)$cell->offset,
                'timestamp' => (int)$cell->timestamp,
                'count' => (int)$cell->count,
                'intensity' => (int)$cell->intensity,
                'collision' => (bool)$cell->collision,
                'istoday' => (bool)$cell->istoday,
                'daylabel' => (string)$cell->daylabel,
                'daynum' => (int)$cell->daynum,
            ];
        }

        return [
            'totalitems' => $totalitems,
            'hascollision' => $hascollision,
            'buckets' => $out,
            'heatmap' => $heatcells,
            'mode' => [
                'key' => $mode['key'],
                'emoji' => $mode['emoji'],
                'label' => get_string('mode_' . $mode['key'], 'block_workloadradar'),
                'sub' => get_string('mode_' . $mode['key'] . '_sub', 'block_workloadradar'),
            ],
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'totalitems' => new external_value(PARAM_INT, 'Total items across buckets'),
            'hascollision' => new external_value(PARAM_BOOL, 'Any bucket flagged as collision'),
            'buckets' => new external_multiple_structure(
                new external_single_structure([
                    'key' => new external_value(PARAM_ALPHANUMEXT, 'Bucket key'),
                    'count' => new external_value(PARAM_INT, 'Items in bucket'),
                    'collision' => new external_value(PARAM_BOOL, 'Collision flag'),
                    'threshold' => new external_value(PARAM_INT, 'Collision threshold'),
                    'items' => new external_multiple_structure(
                        new external_single_structure([
                            'id' => new external_value(PARAM_INT, 'Event id'),
                            'name' => new external_value(PARAM_TEXT, 'Event name'),
                            'coursename' => new external_value(PARAM_TEXT, 'Course shortname'),
                            'modulename' => new external_value(PARAM_PLUGIN, 'Activity module', VALUE_OPTIONAL),
                            'url' => new external_value(PARAM_URL, 'Action URL'),
                            'timesort' => new external_value(PARAM_INT, 'Sort timestamp'),
                            'duedate' => new external_value(PARAM_TEXT, 'Formatted due date'),
                            'countdown' => new external_value(PARAM_TEXT, 'Short countdown label'),
                            'urgency' => new external_value(PARAM_ALPHA, 'red/amber/green urgency'),
                            'completed' => new external_value(PARAM_BOOL, 'Completed by user'),
                        ])
                    ),
                ])
            ),
            'heatmap' => new external_multiple_structure(
                new external_single_structure([
                    'offset' => new external_value(PARAM_INT, 'Day offset from today'),
                    'timestamp' => new external_value(PARAM_INT, 'Start-of-day timestamp'),
                    'count' => new external_value(PARAM_INT, 'Items due that day'),
                    'intensity' => new external_value(PARAM_INT, 'Heat intensity bucket 0..4'),
                    'collision' => new external_value(PARAM_BOOL, 'Day exceeds collision threshold'),
                    'istoday' => new external_value(PARAM_BOOL, 'Whether this is today'),
                    'daylabel' => new external_value(PARAM_TEXT, 'Short day-of-week label'),
                    'daynum' => new external_value(PARAM_INT, 'Day-of-month number'),
                ])
            ),
            'mode' => new external_single_structure([
                'key' => new external_value(PARAM_ALPHA, 'Mode key: easymode/steady/crunch/bossweek'),
                'emoji' => new external_value(PARAM_RAW, 'Mode emoji'),
                'label' => new external_value(PARAM_TEXT, 'Translated mode label'),
                'sub' => new external_value(PARAM_TEXT, 'Translated supporting copy'),
            ]),
        ]);
    }
}
