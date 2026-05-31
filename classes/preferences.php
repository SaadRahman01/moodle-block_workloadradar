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

defined('MOODLE_INTERNAL') || die();

/**
 * Per-user preference helpers. Wraps Moodle's user preferences API with site-level defaults.
 *
 * @package    block_workloadradar
 * @copyright  2026 Saad Rahman
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preferences {

    const PREF_LOOKAHEAD     = 'block_workloadradar_lookahead';
    const PREF_HIDECOMPLETED = 'block_workloadradar_hidecompleted';
    const PREF_THRESHOLD     = 'block_workloadradar_threshold';

    const DEFAULT_LOOKAHEAD     = 21;
    const DEFAULT_HIDECOMPLETED = 1;
    const DEFAULT_THRESHOLD     = 3;

    const MIN_LOOKAHEAD = 7;
    const MAX_LOOKAHEAD = 90;
    const MIN_THRESHOLD = 2;
    const MAX_THRESHOLD = 20;

    /**
     * Read the user's effective preferences, falling back to site defaults.
     *
     * @param int $userid
     * @return \stdClass
     */
    public static function get(int $userid): \stdClass {
        $sitedefaults = self::site_defaults();

        return (object)[
            'lookaheaddays' => (int)get_user_preferences(self::PREF_LOOKAHEAD, $sitedefaults->lookaheaddays, $userid),
            'hidecompleted' => (int)get_user_preferences(self::PREF_HIDECOMPLETED, $sitedefaults->hidecompleted, $userid),
            'threshold'     => (int)get_user_preferences(self::PREF_THRESHOLD, $sitedefaults->threshold, $userid),
        ];
    }

    /**
     * Persist user preferences, clamping values to allowed ranges.
     *
     * @param int $userid
     * @param array $values Subset of {lookaheaddays, hidecompleted, threshold}.
     */
    public static function set(int $userid, array $values): void {
        if (isset($values['lookaheaddays'])) {
            $v = max(self::MIN_LOOKAHEAD, min(self::MAX_LOOKAHEAD, (int)$values['lookaheaddays']));
            set_user_preference(self::PREF_LOOKAHEAD, $v, $userid);
        }
        if (isset($values['hidecompleted'])) {
            set_user_preference(self::PREF_HIDECOMPLETED, !empty($values['hidecompleted']) ? 1 : 0, $userid);
        }
        if (isset($values['threshold'])) {
            $v = max(self::MIN_THRESHOLD, min(self::MAX_THRESHOLD, (int)$values['threshold']));
            set_user_preference(self::PREF_THRESHOLD, $v, $userid);
        }
    }

    /**
     * Return site-level defaults configured by an admin via settings.php.
     *
     * @return \stdClass
     */
    public static function site_defaults(): \stdClass {
        return (object)[
            'lookaheaddays' => (int)(get_config('block_workloadradar', 'lookaheaddays') ?: self::DEFAULT_LOOKAHEAD),
            'hidecompleted' => (int)(get_config('block_workloadradar', 'hidecompleted') ?: self::DEFAULT_HIDECOMPLETED),
            'threshold'     => (int)(get_config('block_workloadradar', 'threshold') ?: self::DEFAULT_THRESHOLD),
        ];
    }
}
