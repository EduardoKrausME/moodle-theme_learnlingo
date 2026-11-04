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
 * Run onboarding only after Moodle has finished installing the plugin.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use core\hook\after_config;
use moodle_url;

/**
 * Offer configuration from the site homepage while its HTML has the sentinel.
 */
final class hook_listener {
    /**
     * Identify only the site's actual /index.php, not /admin/index.php or
     * /course/index.php. A Moodle installation may live in a subdirectory.
     *
     * @param string $script PHP SCRIPT_NAME.
     * @param string $wwwroot Moodle site's configured URL.
     * @return bool
     */
    public static function is_site_frontpage_request(string $script, string $wwwroot): bool {
        $root = rtrim((string)parse_url($wwwroot, PHP_URL_PATH), '/');
        $script = str_replace('\\', '/', $script);
        return $script !== '' && $script === $root . '/index.php';
    }

    /**
     * Offer the wizard only to authenticated administrators visiting the home.
     * Never redirect during an installation/upgrade or from admin/index.php.
     *
     * @param after_config $hook Bootstrap hook.
     * @return void
     */
    public static function after_config(after_config $hook): void {
        global $CFG;

        if ((defined('CLI_SCRIPT') && CLI_SCRIPT)
            || (defined('PHPUNIT_TEST') && PHPUNIT_TEST)
            || (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING)
            || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            return;
        }

        // Custom pages share the site's index.php but must bypass the Home
        // wizard and homepagemode. Detect using the request URL so PATH_INFO
        // installations which append to SCRIPT_NAME are also supported.
        if (($CFG->theme ?? '') === 'learnlingo' && !during_initial_install()
            && empty($CFG->upgraderunning) && !moodle_needs_upgrading()) {
            $path = pages::request_path();
            if ($path !== null) {
                pages::dispatch($path);
            }
        }

        if (!self::is_site_frontpage_request(
            (string)($_SERVER['SCRIPT_NAME'] ?? ''), (string)$CFG->wwwroot)) {
            return;
        }

        // Guests and regular learners always see the real site homepage.
        // Check the user before querying theme configuration.
        if (!isloggedin() || isguestuser() || !is_siteadmin()) {
            return;
        }

        // A literal "config" is the explicit request to start onboarding.
        // Finished sites and custom HTML pages never trigger this redirect.
        if ((string)get_config('theme_learnlingo', 'homepagehtml') !== 'config') {
            return;
        }

        if (during_initial_install() || !empty($CFG->upgraderunning)
            || !empty($CFG->maintenance_enabled) || moodle_needs_upgrading()) {
            return;
        }

        // Resume the next uncompleted step if the administrator left the
        // wizard and then returned to the site homepage.
        $completed = max(0, (int)get_config('theme_learnlingo', 'setup_completed_step'));
        $nextstep = setup_steps::next($CFG, $completed);
        redirect(new moodle_url('/theme/learnlingo/admin/setup.php', ['step' => $nextstep]));
    }
}
