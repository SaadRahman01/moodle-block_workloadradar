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

/**
 * Block class for Workload Radar.
 *
 * @package    block_workloadradar
 * @copyright  2026 Saad Rahman
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_workloadradar extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_workloadradar');
    }

    public function applicable_formats() {
        return [
            'my' => true,
            'site-index' => true,
            'course-view' => false,
        ];
    }

    public function has_config() {
        return true;
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function get_content() {
        global $USER, $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            $this->content->text = '';
            return $this->content;
        }

        $renderable = new \block_workloadradar\output\radar($USER->id, false);
        $renderer = $PAGE->get_renderer('block_workloadradar');
        $this->content->text = $renderer->render($renderable);

        $PAGE->requires->js_call_amd('block_workloadradar/radar', 'init', [
            $this->context->id,
        ]);

        return $this->content;
    }
}
