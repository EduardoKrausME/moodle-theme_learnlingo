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
 * Tests for the public app identity.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

/**
 * Test stable colours and the exported navbar source.
 *
 * @covers \theme_learnlingo\mobile_branding
 */
final class mobile_branding_test extends \advanced_testcase {
    public function test_export_reads_configured_colours_and_real_navbar_source(): void {
        $this->resetAfterTest();
        set_config('brandcolor', '#123abc', 'theme_learnlingo');
        set_config('accentcolor', '#fedcba', 'theme_learnlingo');
        set_config('canvascolor', 'not-a-colour', 'theme_learnlingo');

        $result = mobile_branding::export();

        $this->assertSame('#123ABC', $result['brandcolor']);
        $this->assertSame('#FEDCBA', $result['accentcolor']);
        $this->assertSame('#F7FAF5', $result['canvascolor']);
        $this->assertSame('theme_learnlingo', $result['source']);
        $this->assertArrayHasKey('logo_url', $result);
        $this->assertStringContainsString('--learnlingo-navbar-bg', $result['navbar_scss']);
        $this->assertStringContainsString('html[data-bs-theme="dark"]', $result['navbar_scss']);
        $this->assertStringNotContainsString('// Main navbar.', $result['navbar_scss']);
    }

    public function test_export_builds_a_valid_public_logo_url(): void {
        $this->resetAfterTest();
        set_config('logo', '/brand.png', 'core_admin');
        $file = [
            'contextid' => \context_system::instance()->id,
            'component' => 'core_admin',
            'filearea' => 'logo',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'brand.png',
            'mimetype' => 'image/png',
        ];
        get_file_storage()->create_file_from_string($file, 'test-logo-bytes');
        $result = mobile_branding::export();
        $this->assertStringContainsString('/core_admin/logo/0/brand.png', $result['logo_url']);
        $this->assertStringNotContainsString('//brand.png', $result['logo_url']);
    }
}
