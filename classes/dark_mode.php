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
 * Optional local_boost_dark integration for LearnLingo.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use moodle_url;

/**
 * Detect the installed Moodle plugin without changing its configuration.
 */
final class dark_mode {
    /**
     * Installed means Moodle has registered the plugin version, not merely
     * that its files are present on the server.
     *
     * @return bool
     */
    public static function installed(): bool {
        return get_config('local_boost_dark', 'version') !== false;
    }

    /**
     * Whether the plugin directory has been deployed.
     *
     * @return bool
     */
    public static function code_available(): bool {
        global $CFG;
        return is_file($CFG->dirroot . '/local/boost_dark/version.php');
    }

    /**
     * Whether user-facing Dark Mode is currently enabled.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return self::installed() && (bool)get_config('local_boost_dark', 'enable');
    }

    /**
     * Return data for MCP tools; never export a sesskey.
     *
     * @return array
     */
    public static function status(): array {
        return [
            'plugin' => 'local_boost_dark',
            'installed' => self::installed(),
            'enabled' => self::enabled(),
            'css_selector' => '[data-bs-theme="dark"]',
            'repository' => 'https://github.com/EduardoKrausME/moodle-local_boost_dark',
        ];
    }

    /**
     * Use Moodle's configured admin directory; never assume a site URL.
     *
     * @return moodle_url
     */
    public static function install_url(): moodle_url {
        global $CFG;
        return new moodle_url('/' . ($CFG->admin ?? 'admin') . '/index.php');
    }
}
