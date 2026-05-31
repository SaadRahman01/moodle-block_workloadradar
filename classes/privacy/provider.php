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

namespace block_workloadradar\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use block_workloadradar\preferences;

/**
 * Privacy provider for block_workloadradar.
 *
 * The plugin stores no data of its own; it persists only three user preferences
 * via core's user_preferences table. We declare them via the user_preference
 * provider so admins can see exactly what is stored.
 *
 * @package    block_workloadradar
 * @copyright  2026 Saad Rahman
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference(
            preferences::PREF_LOOKAHEAD,
            'privacy:metadata:preference:lookahead'
        );
        $collection->add_user_preference(
            preferences::PREF_HIDECOMPLETED,
            'privacy:metadata:preference:hidecompleted'
        );
        $collection->add_user_preference(
            preferences::PREF_THRESHOLD,
            'privacy:metadata:preference:threshold'
        );
        return $collection;
    }

    public static function export_user_preferences(int $userid) {
        $prefs = [
            preferences::PREF_LOOKAHEAD     => 'privacy:metadata:preference:lookahead',
            preferences::PREF_HIDECOMPLETED => 'privacy:metadata:preference:hidecompleted',
            preferences::PREF_THRESHOLD     => 'privacy:metadata:preference:threshold',
        ];

        foreach ($prefs as $name => $stringid) {
            $value = get_user_preferences($name, null, $userid);
            if ($value !== null) {
                writer::export_user_preference(
                    'block_workloadradar',
                    $name,
                    $value,
                    get_string($stringid, 'block_workloadradar')
                );
            }
        }
    }
}
