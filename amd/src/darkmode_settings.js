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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Move the optional Dark Mode installer into an independent POST form.
 *
 * @module     theme_learnlingo/darkmode_settings
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    'use strict';

    return {
        init: () => {
            const holder = document.getElementById('ll-boost-dark-form-template');
            if (!holder || document.getElementById('ll-boost-dark-install-settings')) {
                return;
            }
            const form = holder.content.querySelector('form');
            if (!form) {
                return;
            }

            // This is a separate form; only install fields and sesskey are sent.
            document.body.appendChild(form);
        },
    };
});
