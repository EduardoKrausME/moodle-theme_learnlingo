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
 * Tests for the packaged default theme logos.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use context_system;
use function theme_learnlingo_migrate_logo_to_core;
use function xmldb_theme_learnlingo_install;

/**
 * The main logo is stored in core_admin; only loginlogo belongs to the theme.
 *
 * @coversNothing
 */
final class default_logo_test extends advanced_testcase {
    /**
     * Clear one stored file area and its matching configuration.
     *
     * @param string $component Moodle component.
     * @param string $area File area and setting name.
     */
    private function clear_logo(string $component, string $area): void {
        $context = context_system::instance();
        $fs = get_file_storage();
        foreach ($fs->get_area_files($context->id, $component, $area, 0, 'id', false) as $file) {
            $file->delete();
        }
        set_config($area, '', $component);
    }

    /**
     * Add a representative uploaded logo without going through the admin form.
     *
     * @param string $component Moodle component.
     * @param string $area File area.
     * @param string $filename Filename.
     * @param string $content File bytes.
     * @return \stored_file
     */
    private function upload_logo(string $component, string $area, string $filename, string $content): \stored_file {
        return get_file_storage()->create_file_from_string([
            'contextid' => context_system::instance()->id,
            'component' => $component,
            'filearea' => $area,
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
            'mimetype' => 'image/png',
        ], $content);
    }

    /**
     * A fresh installation sets the shared core logo, not a theme logo.
     */
    public function test_install_creates_missing_site_logo(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/install.php');

        $this->clear_logo('core_admin', 'logo');
        xmldb_theme_learnlingo_install();

        $this->assertSame('/default-logo.png', get_config('core_admin', 'logo'));
        $file = get_file_storage()->get_file(context_system::instance()->id,
            'core_admin', 'logo', 0, '/', 'default-logo.png');
        $this->assertNotFalse($file);
        $this->assertSame('image/png', $file->get_mimetype());
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($file->get_content(), 0, 8));

        xmldb_theme_learnlingo_install();
        $this->assertCount(1, get_file_storage()->get_area_files(
            context_system::instance()->id, 'core_admin', 'logo', 0, 'id', false));
    }

    /**
     * Installation must never replace an institution's existing site logo.
     */
    public function test_install_preserves_uploaded_site_logo(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/install.php');

        $this->clear_logo('core_admin', 'logo');
        $file = $this->upload_logo('core_admin', 'logo', 'institution.png', 'institution-logo');
        set_config('logo', '/institution.png', 'core_admin');

        xmldb_theme_learnlingo_install();

        $this->assertSame('/institution.png', get_config('core_admin', 'logo'));
        $this->assertSame('institution-logo', $file->get_content());
        $this->assertFalse(get_file_storage()->get_file(context_system::instance()->id,
            'core_admin', 'logo', 0, '/', 'default-logo.png'));
    }

    /**
     * A stale configuration is recovered from existing files, or from the packaged PNG.
     */
    public function test_install_recovers_missing_or_stale_config(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/install.php');

        $this->clear_logo('core_admin', 'logo');
        $this->upload_logo('core_admin', 'logo', 'existing.png', 'existing-logo');
        set_config('logo', '/missing.png', 'core_admin');
        xmldb_theme_learnlingo_install();
        $this->assertSame('/existing.png', get_config('core_admin', 'logo'));

        $this->clear_logo('core_admin', 'logo');
        set_config('logo', '/missing.png', 'core_admin');
        xmldb_theme_learnlingo_install();
        $this->assertSame('/default-logo.png', get_config('core_admin', 'logo'));
    }

    /**
     * On upgrade a legacy theme logo is copied, leaving the old upload untouched.
     */
    public function test_upgrade_migrates_legacy_logo_to_core(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/upgrade.php');

        $this->clear_logo('core_admin', 'logo');
        $this->clear_logo('theme_learnlingo', 'logo');
        $legacy = $this->upload_logo('theme_learnlingo', 'logo', 'institution.png', 'custom');
        set_config('logo', '/institution.png', 'theme_learnlingo');

        theme_learnlingo_migrate_logo_to_core();

        $this->assertSame('/institution.png', get_config('core_admin', 'logo'));
        $copy = get_file_storage()->get_file(context_system::instance()->id,
            'core_admin', 'logo', 0, '/', 'institution.png');
        $this->assertNotFalse($copy);
        $this->assertSame($legacy->get_contenthash(), $copy->get_contenthash());
        $this->assertSame('/institution.png', get_config('theme_learnlingo', 'logo'));
    }

    /**
     * An existing site logo always wins over the legacy theme logo.
     */
    public function test_upgrade_keeps_existing_core_logo(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/upgrade.php');

        $this->clear_logo('core_admin', 'logo');
        $this->clear_logo('theme_learnlingo', 'logo');
        $this->upload_logo('core_admin', 'logo', 'existing.png', 'core-logo');
        set_config('logo', '/existing.png', 'core_admin');
        $this->upload_logo('theme_learnlingo', 'logo', 'legacy.png', 'old-theme-logo');
        set_config('logo', '/legacy.png', 'theme_learnlingo');

        theme_learnlingo_migrate_logo_to_core();

        $this->assertSame('/existing.png', get_config('core_admin', 'logo'));
        $this->assertFalse(get_file_storage()->get_file(context_system::instance()->id,
            'core_admin', 'logo', 0, '/', 'legacy.png'));
    }

    /**
     * Installing the global logo must not modify loginlogo.
     */
    public function test_install_preserves_independent_login_logo(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/install.php');

        $this->clear_logo('core_admin', 'logo');
        $this->clear_logo('theme_learnlingo', 'loginlogo');
        $this->upload_logo('theme_learnlingo', 'loginlogo', 'login.png', 'login-image');
        set_config('loginlogo', '/login.png', 'theme_learnlingo');

        xmldb_theme_learnlingo_install();

        $this->assertSame('/login.png', get_config('theme_learnlingo', 'loginlogo'));
        $this->assertSame('/default-logo.png', get_config('core_admin', 'logo'));
    }
}
