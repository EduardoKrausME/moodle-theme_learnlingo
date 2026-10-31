<?php
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
 * Core renderer overrides for the LearnLingo theme.
  *
  * @package    theme_learnlingo
  * @copyright  2026 Eduardo Kraus
  * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\output;

use context_system;
use moodle_url;
use theme_boost\output\core_renderer as boost_core_renderer;

/**
 * Keep the global site logo everywhere except on the login layout.
 */
class core_renderer extends boost_core_renderer {
    /**
     * Use the login-specific logo when configured, otherwise the native site logo.
     *
     * @param int|null $maxwidth Requested logo width.
     * @param int|null $maxheight Requested logo height.
     * @return moodle_url|false
     */
    public function get_logo_url($maxwidth = null, $maxheight = 200) {
        if ($this->page->pagelayout === 'login' && !empty($this->page->theme->settings->loginlogo)) {
            global $CFG;

            // Use the same revisioned pluginfile URL convention as theme_config::setting_file_url().
            $filepath = $this->page->theme->settings->loginlogo;
            return moodle_url::make_file_url(
                "$CFG->wwwroot/pluginfile.php",
                '/' . context_system::instance()->id . '/theme_learnlingo/loginlogo/' .
                    theme_get_revision() . $filepath
            );
        }

        return parent::get_logo_url($maxwidth, $maxheight);
    }
}
