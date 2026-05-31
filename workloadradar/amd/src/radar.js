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
 * Workload Radar interaction: preference syncing + AJAX refresh.
 *
 * @module     block_workloadradar/radar
 * @copyright  2026 Saad Rahman
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Ajax from 'core/ajax';
import Notification from 'core/notification';

const SELECTORS = {
    root: '[data-region="radar-root"]',
    prefs: '[data-region="radar-prefs"]',
    lookahead: '[name="lookaheaddays"]',
    threshold: '[name="threshold"]',
    hidecompleted: '[name="hidecompleted"]',
};

let debounceTimer = null;

const savePreferences = (form) => {
    const lookahead = parseInt(form.querySelector(SELECTORS.lookahead).value, 10);
    const threshold = parseInt(form.querySelector(SELECTORS.threshold).value, 10);
    const hidecompleted = form.querySelector(SELECTORS.hidecompleted).checked;

    const args = {};
    if (!Number.isNaN(lookahead)) {
        args.lookaheaddays = lookahead;
    }
    if (!Number.isNaN(threshold)) {
        args.threshold = threshold;
    }
    args.hidecompleted = hidecompleted;

    const request = {
        methodname: 'block_workloadradar_set_preferences',
        args,
    };

    Ajax.call([request])[0]
        .then(() => window.location.reload())
        .catch(Notification.exception);
};

const initPrefs = (root) => {
    const form = root.querySelector(SELECTORS.prefs);
    if (!form) {
        return;
    }

    const handler = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => savePreferences(form), 400);
    };

    form.addEventListener('change', handler);
    form.addEventListener('input', handler);
    form.addEventListener('submit', (e) => e.preventDefault());
};

export const init = () => {
    document.querySelectorAll(SELECTORS.root).forEach(initPrefs);
};
