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
use core_external\external_value;
use block_workloadradar\preferences;

/**
 * AJAX endpoint persisting per-user radar preferences.
 *
 * @package    block_workloadradar
 * @copyright  2026 Saad Rahman
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_preferences extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'lookaheaddays' => new external_value(PARAM_INT, 'Lookahead window in days', VALUE_OPTIONAL),
            'hidecompleted' => new external_value(PARAM_BOOL, 'Hide completed items', VALUE_OPTIONAL),
            'threshold' => new external_value(PARAM_INT, 'Collision threshold', VALUE_OPTIONAL),
        ]);
    }

    public static function execute(?int $lookaheaddays = null, ?bool $hidecompleted = null, ?int $threshold = null): array {
        global $USER;

        $params = [];
        if ($lookaheaddays !== null) {
            $params['lookaheaddays'] = $lookaheaddays;
        }
        if ($hidecompleted !== null) {
            $params['hidecompleted'] = $hidecompleted;
        }
        if ($threshold !== null) {
            $params['threshold'] = $threshold;
        }

        self::validate_parameters(self::execute_parameters(), $params);
        $context = \context_user::instance($USER->id);
        self::validate_context($context);

        preferences::set((int)$USER->id, $params);

        \cache::make('block_workloadradar', 'radar')->purge();

        $current = preferences::get((int)$USER->id);
        return [
            'lookaheaddays' => (int)$current->lookaheaddays,
            'hidecompleted' => (bool)$current->hidecompleted,
            'threshold' => (int)$current->threshold,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'lookaheaddays' => new external_value(PARAM_INT, 'Saved lookahead'),
            'hidecompleted' => new external_value(PARAM_BOOL, 'Saved hide-completed flag'),
            'threshold' => new external_value(PARAM_INT, 'Saved threshold'),
        ]);
    }
}
