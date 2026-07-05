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
 * English language strings for block_workloadradar.
 *
 * @package    block_workloadradar
 * @copyright  2026 Saad Rahman
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Workload Radar';
$string['workloadradar:addinstance'] = 'Add a new Workload Radar block';
$string['workloadradar:myaddinstance'] = 'Add a new Workload Radar block to Dashboard';
$string['workloadradar:viewother'] = 'View workload radar for other users (reserved for future use)';

$string['fullviewtitle'] = 'Workload Radar';
$string['viewfull'] = 'Open radar';
$string['noitems'] = 'No upcoming deadlines in your lookahead window.';
$string['bucketempty'] = 'Nothing in this window. Breathe.';

$string['bucket_thisweek'] = 'This week';
$string['bucket_nextweek'] = 'Next week';
$string['bucket_later'] = 'Later';

$string['collision_badge'] = 'Heavy week ahead — multiple deadlines collide.';

// Gamified mode badges.
$string['mode_easymode']     = 'Easy mode';
$string['mode_easymode_sub'] = 'Nothing due. Go touch grass. 🌿';
$string['mode_steady']       = 'Steady';
$string['mode_steady_sub']   = 'Pace yourself — you\'ve got this.';
$string['mode_crunch']       = 'Crunch incoming';
$string['mode_crunch_sub']   = 'Lots stacking up. Time to plan.';
$string['mode_bossweek']     = 'Boss week';
$string['mode_bossweek_sub'] = 'Multiple collisions. Pick the top 3 and move.';

// Countdown chips.
$string['countdown_now'] = 'Due';
$string['countdown_minutes'] = '{$a}m';
$string['countdown_hours'] = '{$a}h';
$string['countdown_days'] = '{$a}d';

// Heatmap strip.
$string['heatmap_title'] = 'Workload heatmap';
$string['heatmap_alt'] = 'Heatmap of upcoming deadlines per day';
$string['heatmap_legend_light'] = 'light';
$string['heatmap_legend_medium'] = 'medium';
$string['heatmap_legend_heavy'] = 'heavy';
$string['heatmap_legend_collision'] = 'collision';
$string['items_in_window'] = 'items ahead';

// Date format for the heatmap day-strip weekday abbreviation. Passed to userdate(),
// which localises the weekday name; translators may adjust the format per language.
$string['strftimedayabbr'] = '%a';
$string['empty_easymode'] = 'You\'re clear. Nothing due in the window.';
$string['more'] = 'more';

// Preferences panel.
$string['prefs_title'] = 'Radar settings';
$string['pref_lookahead'] = 'Lookahead (days)';
$string['pref_threshold'] = 'Collision threshold';
$string['pref_hidecompleted'] = 'Hide completed items';

// Site-level admin settings.
$string['settings_lookaheaddays'] = 'Default lookahead (days)';
$string['settings_lookaheaddays_desc'] = 'How many days ahead the radar scans by default. Users can override this in the full view.';
$string['settings_threshold'] = 'Default collision threshold';
$string['settings_threshold_desc'] = 'Minimum number of items in a single bucket before it is flagged as a collision.';
$string['settings_hidecompleted'] = 'Hide completed by default';
$string['settings_hidecompleted_desc'] = 'When enabled, completed activities are excluded from the radar for new users.';

// Cache description (cachestore admin UI reads these).
$string['cachedef_radar'] = 'Workload Radar per-user bucket cache';

// Privacy.
$string['privacy:metadata:preference:lookahead'] = 'The lookahead window in days that the user has chosen for the radar.';
$string['privacy:metadata:preference:hidecompleted'] = 'Whether the user has chosen to hide completed items from the radar.';
$string['privacy:metadata:preference:threshold'] = 'The collision threshold (number of items per bucket) the user has chosen.';
