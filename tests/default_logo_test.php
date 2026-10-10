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

/**
 * Tests for the packaged default theme logos.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

defined('MOODLE_INTERNAL') || die;

/**
 * Installation callback should fill missing logos without losing uploads.
 */
final class default_logo_test extends \advanced_testcase {
    /**
     * Reset a logo file area to its empty state.
     *
     * @param string $area File area.
     * @return void
     */
    private function clear_logo(string $area): void {
        $context = \context_system::instance();
        $fs = get_file_storage();
        foreach ($fs->get_area_files($context->id, 'theme_learnlingo', $area, 0, 'id', false) as $file) {
            $file->delete();
        }
        set_config($area, '', 'theme_learnlingo');
    }

    /**
     * Both logos should be populated with the same bundled SVG.
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

        \xmldb_theme_learnlingo_install();

        $fs = get_file_storage();
        $context = \context_system::instance();
        $contenthashes = [];
        foreach (['logo', 'loginlogo'] as $area) {
            $this->assertSame('/default-logo.svg', get_config('theme_learnlingo', $area));
            $file = $fs->get_file($context->id, 'theme_learnlingo', $area, 0, '/', 'default-logo.svg');
            $this->assertNotFalse($file);
            $this->assertSame('image/svg+xml', $file->get_mimetype());
            $this->assertStringContainsString('<svg', $file->get_content());
            $contenthashes[] = $file->get_contenthash();
        }
        $this->assertSame($contenthashes[0], $contenthashes[1]);

        // Running the helper twice should not create duplicate files.
        \theme_learnlingo_install_default_logos();
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
        $context = \context_system::instance();
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

        \theme_learnlingo_install_default_logos();

        $this->assertSame('/institution.png', get_config('theme_learnlingo', 'logo'));
        $this->assertSame('institution-logo', $file->get_content());
        $this->assertFalse($fs->get_file($context->id, 'theme_learnlingo', 'logo', 0, '/', 'default-logo.svg'));
        $this->assertSame('/default-logo.svg', get_config('theme_learnlingo', 'loginlogo'));
    }
}
