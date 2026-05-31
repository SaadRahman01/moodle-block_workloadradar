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
 * Web service definitions for block_workloadradar.
 *
 * @package    block_workloadradar
 * @copyright  2026 Saad Rahman
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_workloadradar_get_radar' => [
        'classname'   => 'block_workloadradar\external\get_radar',
        'methodname'  => 'execute',
        'description' => 'Return the current user\'s aggregated upcoming deadlines and collision scoring.',
        'type'        => 'read',
        'ajax'        => true,
        'loginrequired' => true,
    ],
    'block_workloadradar_set_preferences' => [
        'classname'   => 'block_workloadradar\external\set_preferences',
        'methodname'  => 'execute',
        'description' => 'Update per-user radar preferences (lookahead, hide completed, collision threshold).',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
    ],
];
