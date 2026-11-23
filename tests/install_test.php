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
 * Verify initial LearnLingo settings.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use context_system;
use function xmldb_theme_learnlingo_install;

/**
 * Installation must persist defaults without changing configured values.
 *
 * @coversNothing
 */
final class install_test extends advanced_testcase {
    /**
     * Verify every portable setting has a stored value after installation.
     */
    public function test_install_preserves_existing_values_and_seeds_defaults(): void {
        global $CFG;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/install.php');

        $definitions = branding_exchange::definitions();
        foreach (array_keys($definitions) as $name) {
            unset_config($name, 'theme_learnlingo');
        }
        unset_config('setup_pending', 'theme_learnlingo');
        set_config('brandcolor', '#123456', 'theme_learnlingo');
        set_config('favicon', 'custom.ico', 'core_admin');

        xmldb_theme_learnlingo_install();

        foreach ($definitions as $name => [$type, $default]) {
            $expected = $name === 'brandcolor' ? '#123456'
                : (string)($type === 'bool' ? (int)$default : $default);
            $this->assertSame($expected, (string)get_config('theme_learnlingo', $name), $name);
        }
        $this->assertSame('config', (string)get_config('theme_learnlingo', 'homepagehtml'));
        $this->assertSame('1', (string)get_config('theme_learnlingo', 'setup_pending'));
        $this->assertSame('custom.ico', get_config('core_admin', 'favicon'));
    }
    /**
     * A configured site logo must never be overwritten, even when its stored
     * file is temporarily unavailable or another upload exists in the area.
     */
    public function test_install_preserves_configured_site_logo_without_matching_file(): void {
        global $CFG;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/install.php');

        $context = context_system::instance();
        $fs = get_file_storage();
        set_config('logo', '/institution-logo.svg', 'core_admin');
        $before = array_keys($fs->get_area_files($context->id, 'core_admin', 'logo', 0, 'id', false));

        xmldb_theme_learnlingo_install();

        $this->assertSame('/institution-logo.svg', get_config('core_admin', 'logo'));
        $after = array_keys($fs->get_area_files($context->id, 'core_admin', 'logo', 0, 'id', false));
        $this->assertSame($before, $after);
    }

    /**
     * An existing stored logo is reused rather than replaced by the default.
     */
    public function test_install_recovers_existing_logo_when_configuration_is_empty(): void {
        global $CFG;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/install.php');

        $context = context_system::instance();
        $fs = get_file_storage();
        foreach ($fs->get_area_files($context->id, 'core_admin', 'logo', 0, 'id', false) as $file) {
            $file->delete();
        }
        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'core_admin',
            'filearea' => 'logo',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'institution-logo.svg',
        ], '<svg xmlns="http://www.w3.org/2000/svg"/>');
        set_config('logo', '', 'core_admin');

        xmldb_theme_learnlingo_install();

        $this->assertSame('/institution-logo.svg', get_config('core_admin', 'logo'));
        $this->assertCount(1, $fs->get_area_files($context->id, 'core_admin', 'logo', 0, 'id', false));
    }

    /**
     * Only an installation without a configured or stored logo gets the default.
     */
    public function test_install_seeds_default_logo_only_when_site_logo_is_missing(): void {
        global $CFG;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/install.php');

        $context = context_system::instance();
        $fs = get_file_storage();
        foreach ($fs->get_area_files($context->id, 'core_admin', 'logo', 0, 'id', false) as $file) {
            $file->delete();
        }
        set_config('logo', '', 'core_admin');

        xmldb_theme_learnlingo_install();

        $this->assertSame('/default-logo.svg', get_config('core_admin', 'logo'));
        $this->assertTrue($fs->file_exists($context->id, 'core_admin', 'logo', 0, '/', 'default-logo.svg'));
    }
}
