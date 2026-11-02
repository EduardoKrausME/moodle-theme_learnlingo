// This file is part of Moodle - http://moodle.org/
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
 * branding_json.js
 *
 * @package   theme_learnlingo
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
define('theme_learnlingo/branding_json', [], function() {
    'use strict';

    /**
     * Copy the exported branding prompt to the clipboard without inline JavaScript.
     */
    const init = () => {
        const button = document.getElementById('learnlingo-copy-prompt');
        const textarea = document.getElementById('learnlingo-brand-prompt');
        if (!button || !textarea) {
            return;
        }

        button.addEventListener('click', async () => {
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(textarea.value);
                    return;
                }
            } catch (error) {
                // Fall back to the original selection-based copy behavior.
            }

            textarea.focus();
            textarea.select();
            document.execCommand('copy');
        });
    };

    return {init};
});
