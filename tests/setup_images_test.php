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
 * Regression checks for site-wide logo and favicon storage.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

/**
 * Existing file settings must remain untouched when an upload is omitted.
 */
final class setup_images_test extends \advanced_testcase {
    /**
     * Saving a new logo changes only its core_admin area and configuration.
     *
     * @return void
     */
    public function test_new_logo_does_not_change_favicon(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $source = "{$CFG->dirroot}/theme/learnlingo/pix/default-logo.png";
        $this->assertFileExists($source);

        set_config('favicon', '/unchanged.ico', 'core_admin');
        setup_images::save([
            'logo' => [
                'tmp_name' => $source,
                'extension' => 'png',
                'mimetype' => 'image/png',
            ],
        ]);

        $expected = '/learnlingo-logo-' . substr(hash_file('sha256', $source), 0, 16) . '.png';
        $this->assertSame($expected, get_config('core_admin', 'logo'));
        $this->assertSame('/unchanged.ico', get_config('core_admin', 'favicon'));
        $this->assertNotSame('', setup_images::current_url('logo'));
    }

    /**
     * Unselected file inputs never change existing settings.
     *
     * @return void
     */
    public function test_no_upload_preserves_both_assets(): void {
        $this->resetAfterTest();
        set_config('logo', '/existing-logo.png', 'core_admin');
        set_config('favicon', '/existing-favicon.ico', 'core_admin');

        $this->assertSame([], setup_images::validate([]));
        setup_images::save([]);

        $this->assertSame('/existing-logo.png', get_config('core_admin', 'logo'));
        $this->assertSame('/existing-favicon.ico', get_config('core_admin', 'favicon'));
    }
}
