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
 * Full-page Workload Radar view.
 *
 * @package    block_workloadradar
 * @copyright  2026 Saad Rahman
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login(null, false);

$context = context_user::instance($USER->id);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/workloadradar/view.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('fullviewtitle', 'block_workloadradar'));
$PAGE->set_heading(get_string('fullviewtitle', 'block_workloadradar'));

$renderable = new \block_workloadradar\output\radar((int)$USER->id, true);
$renderer = $PAGE->get_renderer('block_workloadradar');

$PAGE->requires->js_call_amd('block_workloadradar/radar', 'init', []);

echo $OUTPUT->header();
echo $renderer->render($renderable);
echo $OUTPUT->footer();
