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
use function theme_learnlingo_install_default_logos;
use function xmldb_theme_learnlingo_install;

/**
 * Installation callback should fill missing logos without losing uploads.
 *
 * @coversNothing
 */
final class default_logo_test extends advanced_testcase {
    /**
     * Reset a logo file area to its empty state.
     *
     * @param string $area File area.
     * @return void
     */
    private function clear_logo(string $area): void {
        $context = context_system::instance();
        $fs = get_file_storage();
        foreach ($fs->get_area_files($context->id, 'theme_learnlingo', $area, 0, 'id', false) as $file) {
            $file->delete();
        }
        set_config($area, '', 'theme_learnlingo');
    }

    /**
     * Both logos should be populated with the same bundled PNG.
     *
     * @return void
     */
    public function test_install_creates_both_missing_logos(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/install.php');

        foreach (['logo', 'loginlogo'] as $area) {
            $this->clear_logo($area);
        }

        xmldb_theme_learnlingo_install();

        $fs = get_file_storage();
        $context = context_system::instance();
        $contenthashes = [];
        foreach (['logo', 'loginlogo'] as $area) {
            $this->assertSame('/default-logo.png', get_config('theme_learnlingo', $area));
            $file = $fs->get_file($context->id, 'theme_learnlingo', $area, 0, '/', 'default-logo.png');
            $this->assertNotFalse($file);
            $this->assertSame('image/png', $file->get_mimetype());
            $this->assertSame("\x89PNG\r\n\x1a\n", substr($file->get_content(), 0, 8));
            $contenthashes[] = $file->get_contenthash();
        }
        $this->assertSame($contenthashes[0], $contenthashes[1]);

        // Running the helper twice should not create duplicate files.
        theme_learnlingo_install_default_logos();
        foreach (['logo', 'loginlogo'] as $area) {
            $this->assertCount(1, $fs->get_area_files($context->id, 'theme_learnlingo', $area, 0, 'id', false));
        }
    }

    /**
     * Existing logos must not be replaced with the theme's default.
     *
     * @return void
     */
    public function test_install_preserves_uploaded_logo(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/install.php');

        $this->clear_logo('logo');
        $this->clear_logo('loginlogo');
        $context = context_system::instance();
        $fs = get_file_storage();
        $file = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'theme_learnlingo',
            'filearea' => 'logo',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'institution.png',
            'mimetype' => 'image/png',
        ], 'institution-logo');
        set_config('logo', '/institution.png', 'theme_learnlingo');

        theme_learnlingo_install_default_logos();

        $this->assertSame('/institution.png', get_config('theme_learnlingo', 'logo'));
        $this->assertSame('institution-logo', $file->get_content());
        $this->assertFalse($fs->get_file($context->id, 'theme_learnlingo', 'logo', 0, '/', 'default-logo.png'));
        $this->assertSame('/default-logo.png', get_config('theme_learnlingo', 'loginlogo'));
    }

    /**
     * Repair the setting when an old file was deleted but its path remained.
     *
     * @return void
     */
    public function test_install_repairs_stale_logo_path(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/install.php');
        $this->clear_logo('logo');
        set_config('logo', '/missing-old-logo.svg', 'theme_learnlingo');

        theme_learnlingo_install_default_logos();

        $this->assertSame('/default-logo.png', get_config('theme_learnlingo', 'logo'));
        $file = get_file_storage()->get_file(context_system::instance()->id,
            'theme_learnlingo', 'logo', 0, '/', 'default-logo.png');
        $this->assertNotFalse($file);
    }

    /**
     * Upgrading replaces only the original bundled SVG, not institutional uploads.
     */
    public function test_migration_replaces_only_packaged_svg(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/install.php');

        $this->clear_logo('logo');
        $this->clear_logo('loginlogo');

        $png = file_get_contents($CFG->dirroot . '/theme/learnlingo/pix/default-logo.png');
        // This is the real SVG shipped before switching the packaged logo to PNG.
        // A generated SVG using today's PNG would have a different content hash.
        $svg = file_get_contents(__DIR__ . '/fixtures/default-logo-legacy.svg');
        $this->assertNotFalse($svg);
        $this->assertSame('52a77c5f3cbd973eff6274e14c9f299a97dcb565', sha1($svg));

        $context = context_system::instance();
        $fs = get_file_storage();
        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'theme_learnlingo',
            'filearea' => 'logo',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'default-logo.svg',
            'mimetype' => 'image/svg+xml',
        ], $svg);
        set_config('logo', '/default-logo.svg', 'theme_learnlingo');

        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'theme_learnlingo',
            'filearea' => 'loginlogo',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'institution.png',
            'mimetype' => 'image/png',
        ], 'institution-logo');
        set_config('loginlogo', '/institution.png', 'theme_learnlingo');

        theme_learnlingo_migrate_default_logos();

        $this->assertSame('/default-logo.png', get_config('theme_learnlingo', 'logo'));
        $this->assertFalse($fs->get_file($context->id, 'theme_learnlingo', 'logo', 0, '/', 'default-logo.svg'));
        $newlogo = $fs->get_file($context->id, 'theme_learnlingo', 'logo', 0, '/', 'default-logo.png');
        $this->assertNotFalse($newlogo);
        $this->assertSame($png, $newlogo->get_content());
        $this->assertSame('/institution.png', get_config('theme_learnlingo', 'loginlogo'));
        $this->assertSame('institution-logo', $fs->get_file($context->id,
            'theme_learnlingo', 'loginlogo', 0, '/', 'institution.png')->get_content());
    }
    /**
     * A customised logo must be kept even when it uses the former default filename.
     *
     * @return void
     */
    public function test_migration_preserves_custom_svg_with_default_filename(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/install.php');

        $this->clear_logo('logo');
        $customsvg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64">'
            . '<title>Institution</title><rect width="64" height="64" fill="blue"/></svg>';
        $context = context_system::instance();
        $fs = get_file_storage();
        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'theme_learnlingo',
            'filearea' => 'logo',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'default-logo.svg',
            'mimetype' => 'image/svg+xml',
        ], $customsvg);
        set_config('logo', '/default-logo.svg', 'theme_learnlingo');

        theme_learnlingo_migrate_default_logos();

        $this->assertSame('/default-logo.svg', get_config('theme_learnlingo', 'logo'));
        $file = $fs->get_file($context->id, 'theme_learnlingo', 'logo', 0, '/', 'default-logo.svg');
        $this->assertNotFalse($file);
        $this->assertSame($customsvg, $file->get_content());
    }

}
