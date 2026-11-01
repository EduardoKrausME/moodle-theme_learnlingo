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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Run onboarding only after Moodle has finished installing the plugin.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

/**
 * First-run navigation hook.
 */
final class hook_listener {
    /**
     * Open the visual setup only for the first normal administration request.
     *
     * @param \core\hook\\after_config $hook Hook dispatched during bootstrap.
     * @return void
     */
    public static function after_config(\core\hook\\after_config $hook): void {
        global $CFG;

        if ((defined('CLI_SCRIPT') && CLI_SCRIPT)
                || (defined('PHPUNIT_TEST') && PHPUNIT_TEST)
                || (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING)
                || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            return;
        }

        // The hook runs on every request. Avoid querying the DB on other pages.
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $adminpath = '/' . ($CFG->admin ?? 'admin') . '/index.php';
        if (!str_ends_with($script, $adminpath)) {
            return;
        }

        // Never interrupt the initial install, upgrade, maintenance or confirmations.
        foreach (['confirmplugincheck', 'confirmupgrade', 'sessionstarted',
                'cache', 'agreelicense'] as $parameter) {
            if (isset($_GET[$parameter])) {
                return;
            }
        }
        if (during_initial_install() || !empty($CFG->upgraderunning)
                || !empty($CFG->maintenance_enabled) || moodle_needs_upgrading()) {
            return;
        }

        if ((string)get_config('theme_learnlingo', 'setup_pending') !== '1'
                || !isloggedin() || isguestuser() || !is_siteadmin()) {
            return;
        }
        redirect(new \moodle_url('/theme/learnlingo/admin/setup.php'));
    }
}
