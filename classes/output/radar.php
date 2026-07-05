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

namespace block_workloadradar\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use block_workloadradar\local\collector;
use block_workloadradar\local\scorer;
use block_workloadradar\preferences;

/**
 * Renderable for the radar block (compact + full views).
 *
 * @package    block_workloadradar
 * @copyright  2026 Saad Rahman
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class radar implements renderable, templatable {

    /** @var int */
    private $userid;

    /** @var bool */
    private $full;

    /**
     * @param int $userid
     * @param bool $full True for the full-page view, false for the compact block.
     */
    public function __construct(int $userid, bool $full = false) {
        $this->userid = $userid;
        $this->full = $full;
    }

    public function export_for_template(renderer_base $output): array {
        $prefs = preferences::get($this->userid);
        $now = time();

        // Schema version baked into cache key so structural changes invalidate stale entries automatically.
        $schema = 'v2';
        $cache = \cache::make('block_workloadradar', 'radar');
        $cachekey = "{$schema}_{$this->userid}_{$prefs->lookaheaddays}_"
                . ($prefs->hidecompleted ? 1 : 0) . "_{$prefs->threshold}";
        $cached = $cache->get($cachekey);

        if ($cached === false || !is_object($cached) || !isset($cached->heatmap, $cached->buckets, $cached->events)) {
            $events = collector::collect($this->userid, $prefs->lookaheaddays);
            $buckets = scorer::bucket($events, $prefs->threshold, (bool)$prefs->hidecompleted, $now);
            $heatmap = scorer::days_heatmap($events, (int)$prefs->lookaheaddays, (int)$prefs->threshold,
                    (bool)$prefs->hidecompleted, $now);
            $cached = (object)['buckets' => $buckets, 'heatmap' => $heatmap, 'events' => $events];
            $cache->set($cachekey, $cached);
        }

        $buckets = $cached->buckets;
        $heatmap = $cached->heatmap;
        $allevents = $cached->events;

        $bucketlabels = [
            scorer::BUCKET_THIS_WEEK => get_string('bucket_thisweek', 'block_workloadradar'),
            scorer::BUCKET_NEXT_WEEK => get_string('bucket_nextweek', 'block_workloadradar'),
            scorer::BUCKET_LATER     => get_string('bucket_later', 'block_workloadradar'),
        ];

        $bucketdata = [];
        $totalitems = 0;
        $hascollision = false;

        foreach ($buckets as $bucket) {
            $items = [];
            foreach ($bucket->items as $item) {
                if (empty($item->completed) || !$prefs->hidecompleted) {
                    $items[] = $this->item_to_template($item, $now);
                }
            }

            $bucketdata[] = [
                'key' => $bucket->key,
                'label' => $bucketlabels[$bucket->key] ?? $bucket->key,
                'items' => $items,
                'count' => $bucket->count,
                'collision' => $bucket->collision,
                'threshold' => $bucket->threshold,
                'hasitems' => $bucket->count > 0,
            ];

            $totalitems += $bucket->count;
            if ($bucket->collision) {
                $hascollision = true;
            }
        }

        // Mode badge for the gamified header (Easy mode / Steady / Crunch / Boss week).
        $mode = scorer::mode_for_week($heatmap);
        $modedata = [
            'key' => $mode['key'],
            'emoji' => $mode['emoji'],
            'label' => get_string('mode_' . $mode['key'], 'block_workloadradar'),
            'sub' => get_string('mode_' . $mode['key'] . '_sub', 'block_workloadradar'),
        ];

        // Heatmap cells for the day-strip. Compact view shows next 7, full view shows lookahead.
        $cells = $this->heatmap_to_template($heatmap, $this->full ? (int)$prefs->lookaheaddays : 7);

        // Top items: 3 most-imminent for compact, all for full.
        $sorted = $allevents;
        if ($prefs->hidecompleted) {
            $sorted = array_values(array_filter($sorted, static fn($e) => empty($e->completed)));
        }
        usort($sorted, static fn($a, $b) => $a->timesort <=> $b->timesort);
        $topcount = $this->full ? count($sorted) : 3;
        $topitems = [];
        foreach (array_slice($sorted, 0, $topcount) as $item) {
            $topitems[] = $this->item_to_template($item, $now);
        }

        return [
            'full' => $this->full,
            'totalitems' => $totalitems,
            'hasitems' => $totalitems > 0,
            'hascollision' => $hascollision,
            'mode' => $modedata,
            'heatmap' => $cells,
            'topitems' => $topitems,
            'topitemscount' => count($topitems),
            'hidden_when_collapsed' => max(0, count($sorted) - count($topitems)),
            'buckets' => $bucketdata,
            'prefs' => [
                'lookaheaddays' => (int)$prefs->lookaheaddays,
                'hidecompleted' => (bool)$prefs->hidecompleted,
                'threshold' => (int)$prefs->threshold,
            ],
            'fullviewurl' => (new \moodle_url('/blocks/workloadradar/view.php'))->out(false),
        ];
    }

    /**
     * Normalise a heatmap cell list for the Mustache template.
     *
     * @param array $heatmap
     * @param int $limit Number of cells to include from the start.
     * @return array
     */
    private function heatmap_to_template(array $heatmap, int $limit): array {
        $out = [];
        foreach (array_slice($heatmap, 0, $limit) as $cell) {
            $out[] = [
                'offset' => $cell->offset,
                'count' => $cell->count,
                'intensity' => $cell->intensity,
                'collision' => $cell->collision,
                'istoday' => $cell->istoday,
                'isweekend' => $cell->dow >= 6,
                'daylabel' => userdate($cell->timestamp, get_string('strftimedayabbr', 'block_workloadradar')),
                'daynum' => $cell->daynum,
                'datestr' => userdate($cell->timestamp, get_string('strftimedaydate', 'langconfig')),
            ];
        }
        return $out;
    }

    /**
     * Shape an event into the template payload, including urgency + countdown.
     *
     * @param \stdClass $item
     * @param int $now
     * @return array
     */
    private function item_to_template(\stdClass $item, int $now): array {
        return [
            'id' => $item->id,
            'name' => format_string($item->name),
            'coursename' => format_string($item->coursename),
            'modulename' => $item->modulename,
            'url' => $item->url,
            'timesort' => $item->timesort,
            'duedate' => userdate($item->timesort, get_string('strftimedatetimeshort', 'langconfig')),
            'countdown' => scorer::countdown_label((int)$item->timesort, $now),
            'urgency' => scorer::urgency((int)$item->timesort, $now),
            'completed' => !empty($item->completed),
        ];
    }
}
