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
 * Site-level admin settings for block_workloadradar.
 *
 * @package    block_workloadradar
 * @copyright  2026 Saad Rahman
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'block_workloadradar/lookaheaddays',
        get_string('settings_lookaheaddays', 'block_workloadradar'),
        get_string('settings_lookaheaddays_desc', 'block_workloadradar'),
        21,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'block_workloadradar/threshold',
        get_string('settings_threshold', 'block_workloadradar'),
        get_string('settings_threshold_desc', 'block_workloadradar'),
        3,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_workloadradar/hidecompleted',
        get_string('settings_hidecompleted', 'block_workloadradar'),
        get_string('settings_hidecompleted_desc', 'block_workloadradar'),
        1
    ));
}
