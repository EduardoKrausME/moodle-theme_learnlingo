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
use core\exception\moodle_exception;
use core\output\context_header;
use core\output\html_writer;
use core_user;
use moodle_url;

/**
 * Keep the global site logo everywhere except on the login layout.
 */
class core_renderer extends \theme_boost\output\core_renderer {
    /**
     * Use the login-specific logo when configured, otherwise the native site logo.
     *
     * @param null $maxwidth Requested logo width.
     * @param int $maxheight Requested logo height.
     * @return moodle_url|false
     * @throws \dml_exception
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

    /**
     * Wrapper for header elements.
     *
     * @return string HTML to display the main header.
     * @throws moodle_exception
     */
    public function full_header() {
        $pagetype = $this->page->pagetype;
        $homepage = get_home_page();
        $homepagetype = null;
        // Add a special case since /my/courses is a part of the /my subsystem.
        if ($homepage == HOMEPAGE_MY || $homepage == HOMEPAGE_MYCOURSES) {
            $homepagetype = 'my-index';
        } else if ($homepage == HOMEPAGE_SITE) {
            $homepagetype = 'site-index';
        }
        if (
            $this->page->include_region_main_settings_in_header_actions() &&
            !$this->page->blocks->is_block_present('settings')
        ) {
            // Only include the region main settings if the page has requested it and it doesn't already have
            // the settings block on it. The region main settings are included in the settings block and
            // duplicating the content causes behat failures.
            $this->page->add_header_action(html_writer::div(
                $this->region_main_settings_menu(),
                'd-print-none',
                ['id' => 'region-main-settings-menu']
            ));
        }

        $contextheader = $this->context_header();
        $header = [
            "settingsmenu" => $this->context_header_settings_menu(),
            "contextheader" => $contextheader,
            "hasnavbar" => empty($this->page->layout_options['nonavbar']) && isset($contextheader[3]),
            "navbar" => $this->navbar(),
            "pageheadingbutton" => $this->page_heading_button(),
            "courseheader" => $this->course_header(),
            "headeractions" => $this->page->get_header_actions(),
        ];
        if (!empty($pagetype) && !empty($homepagetype) && $pagetype == $homepagetype) {
            $header["welcomemessage"] = core_user::welcome_message();
        }

        if (empty(array_filter($header))) {
            return "";
        }
        return $this->render_from_template("core/full_header", $header);
    }

    /**
     * Renders the header bar.
     *
     * @param context_header $contextheader Header bar object.
     * @return string HTML for the header bar.
     * @throws moodle_exception
     * @todo MDL-82163 This will be deleted in Moodle 6.0.
     * @deprecated since 4.5 Please use core_renderer::render($contextheader) instead
     */
    protected function render_context_header(context_header $contextheader) {
        $context = $contextheader->export_for_template($this);
        if (!empty(array_filter($context))) {
            return "";
        }
        return $this->render_from_template('core/context_header', $context);
    }
}
