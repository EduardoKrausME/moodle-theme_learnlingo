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
 * Regression tests for PHP GD normalization and existing asset preservation.
 */
final class setup_images_test extends \advanced_testcase {
    /**
     * Ensure PHP GD can be used for tests requiring image generation.
     *
     * @return void
     */
    private function require_gd(): void {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is not available.');
        }
    }

    /**
     * Resize a landscape logo to a 1200-by-400 bounding box without cropping.
     *
     * @return void
     */
    public function test_large_logo_is_resampled_and_does_not_change_favicon(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        $this->require_gd();

        $source = tempnam($CFG->tempdir, 'll-test-logo-');
        $image = imagecreatetruecolor(2400, 800);
        $green = imagecolorallocate($image, 0, 190, 110);
        imagefill($image, 0, 0, $green);
        imagepng($image, $source);
        imagedestroy($image);

        try {
            set_config('favicon', '/unchanged.ico', 'core_admin');
            setup_images::save([
                'logo' => ['tmp_name' => $source],
            ]);

            $storedname = (string)get_config('core_admin', 'logo');
            $this->assertStringStartsWith('/learnlingo-logo-', $storedname);
            $this->assertStringEndsWith('.png', $storedname);
            $this->assertSame('/unchanged.ico', get_config('core_admin', 'favicon'));

            $contextid = \context_system::instance()->id;
            $hash = sha1("/{$contextid}/core_admin/logo/0/" . ltrim($storedname, '/'));
            $storedfile = get_file_storage()->get_file_by_hash($hash);
            $this->assertNotFalse($storedfile);
            $details = getimagesizefromstring($storedfile->get_content());
            $this->assertSame(1200, $details[0]);
            $this->assertSame(400, $details[1]);
            $this->assertSame(IMAGETYPE_PNG, $details[2]);
            $this->assertNotSame('', setup_images::current_url('logo'));
        } finally {
            @unlink($source);
        }
    }

    /**
     * Fit non-square favicon content inside a transparent 256-pixel square.
     *
     * @return void
     */
    public function test_favicon_is_centered_and_transparent(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        $this->require_gd();

        $source = tempnam($CFG->tempdir, 'll-test-icon-');
        $image = imagecreatetruecolor(640, 320);
        $red = imagecolorallocate($image, 220, 20, 20);
        imagefill($image, 0, 0, $red);
        imagejpeg($image, $source, 90);
        imagedestroy($image);

        try {
            setup_images::save([
                'favicon' => ['tmp_name' => $source],
            ]);
            $storedname = (string)get_config('core_admin', 'favicon');
            $this->assertStringEndsWith('.png', $storedname);

            $contextid = \context_system::instance()->id;
            $hash = sha1("/{$contextid}/core_admin/favicon/0/" . ltrim($storedname, '/'));
            $storedfile = get_file_storage()->get_file_by_hash($hash);
            $this->assertNotFalse($storedfile);

            $data = $storedfile->get_content();
            $details = getimagesizefromstring($data);
            $this->assertSame(256, $details[0]);
            $this->assertSame(256, $details[1]);
            $this->assertSame(IMAGETYPE_PNG, $details[2]);

            $output = imagecreatefromstring($data);
            $corner = imagecolorsforindex($output, imagecolorat($output, 0, 0));
            $center = imagecolorsforindex($output, imagecolorat($output, 128, 128));
            $this->assertSame(127, $corner['alpha']);
            $this->assertSame(0, $center['alpha']);
            imagedestroy($output);
        } finally {
            @unlink($source);
        }
    }

    /**
     * Omitted file inputs preserve the Moodle-wide logo and favicon settings.
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
