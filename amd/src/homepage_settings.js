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
 * homepage_settings.js
 *
 * @package   theme_learnlingo
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Toggle settings for the selected homepage mode, without clearing values.
 */
export const init = () => {
    const select = document.getElementById('id_s_theme_learnlingo_homepagemode');
    if (!select) {
        return;
    }
    const setVisible = (name, visible) => {
        const input = document.getElementById('id_s_theme_learnlingo_' + name);
        const wrapper = document.getElementById('admin-' + name) ||
            input?.closest('.form-item, .fitem, .form-group');
        if (wrapper) {
            wrapper.classList.toggle('d-none', !visible);
            wrapper.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }
    };
    const refresh = () => {
        const isLearnlingo = select.value === 'learnlingo';
        const isHtml = select.value === 'html';
        setVisible('homepagehtml', isLearnlingo || isHtml);
        // The stylesheet also controls shared navbar and footer elements on other pages.
        setVisible('homepagescss', true);
        setVisible('homepagefullwidth', isLearnlingo || isHtml);
    };
    select.addEventListener('change', refresh);
    refresh();
};
