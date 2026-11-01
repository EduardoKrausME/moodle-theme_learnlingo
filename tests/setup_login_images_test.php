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
 * Verify independent login artwork and GD resizing during setup.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

/**
 * @covers \theme_learnlingo\setup_login_images
 */
final class setup_login_images_test extends \advanced_testcase {
    /**
     * A login upload should not modify the global site logo or other images.
     */
    public function test_login_logo_is_resized_and_keeps_site_logo(): void {
        global $CFG;

        if (!extension_loaded('gd')) {
            $this->markTestSkipped('PHP GD is required.');
        }
        $this->resetAfterTest();
        $this->setAdminUser();

        set_config('logo', '/keep-site-logo.png', 'core_admin');
        set_config('loginbackgroundimage', '/keep-background.jpg', 'theme_boost');
        $source = tempnam($CFG->tempdir, 'll-login-test-logo-');
        $input = imagecreatetruecolor(2400, 800);
        imagealphablending($input, false);
        imagesavealpha($input, true);
        imagefill($input, 0, 0, imagecolorallocatealpha($input, 0, 0, 0, 127));
        $green = imagecolorallocatealpha($input, 10, 160, 100, 0);
        imagefilledrectangle($input, 400, 200, 2000, 600, $green);
        imagepng($input, $source);
        imagedestroy($input);

        try {
            setup_login_images::save(['loginlogo' => ['tmp_name' => $source]]);
            $saved = (string)get_config('theme_learnlingo', 'loginlogo');
            $this->assertStringStartsWith('/learnlingo-loginlogo-', $saved);
            $this->assertStringEndsWith('.png', $saved);
            $this->assertSame('/keep-site-logo.png', get_config('core_admin', 'logo'));
            $this->assertSame('/keep-background.jpg', get_config('theme_boost', 'loginbackgroundimage'));

            $contextid = \context_system::instance()->id;
            $hash = sha1("/{$contextid}/theme_learnlingo/loginlogo/0/" . ltrim($saved, '/'));
            $file = get_file_storage()->get_file_by_hash($hash);
            $this->assertNotFalse($file);

            $image = imagecreatefromstring($file->get_content());
            $this->assertSame(1200, imagesx($image));
            $this->assertSame(400, imagesy($image));
            $corner = imagecolorsforindex($image, imagecolorat($image, 0, 0));
            $this->assertSame(127, $corner['alpha']);
            imagedestroy($image);
        } finally {
            @unlink($source);
        }
    }

    /**
     * Login background scales to a maximum of 1920x1080 and becomes JPEG.
     */
    public function test_login_background_is_optimized_without_cropping(): void {
        global $CFG;

        if (!extension_loaded('gd')) {
            $this->markTestSkipped('PHP GD is required.');
        }
        $this->resetAfterTest();
        $this->setAdminUser();

        $source = tempnam($CFG->tempdir, 'll-login-test-bg-');
        $input = imagecreatetruecolor(2560, 1440);
        $color = imagecolorallocate($input, 220, 220, 240);
        imagefill($input, 0, 0, $color);
        imagejpeg($input, $source, 90);
        imagedestroy($input);
        try {
            setup_login_images::save(['loginbackground' => ['tmp_name' => $source]]);
            $saved = (string)get_config('theme_boost', 'loginbackgroundimage');
            $this->assertStringStartsWith('/learnlingo-loginbackgroundimage-', $saved);
            $this->assertStringEndsWith('.jpg', $saved);

            $contextid = \context_system::instance()->id;
            $hash = sha1("/{$contextid}/theme_boost/loginbackgroundimage/0/" . ltrim($saved, '/'));
            $file = get_file_storage()->get_file_by_hash($hash);
            $this->assertNotFalse($file);
            $info = getimagesizefromstring($file->get_content());
            $this->assertSame(1920, $info[0]);
            $this->assertSame(1080, $info[1]);
            $this->assertSame(IMAGETYPE_JPEG, $info[2]);
        } finally {
            @unlink($source);
        }
    }

    /**
     * Skipped uploads leave existing login assets unchanged.
     */
    public function test_no_upload_does_not_clear_login_assets(): void {
        $this->resetAfterTest();
        set_config('loginlogo', '/existing-login.png', 'theme_learnlingo');
        set_config('loginbackgroundimage', '/existing-bg.jpg', 'theme_boost');

        $this->assertSame([], setup_login_images::validate([]));
        setup_login_images::save([]);

        $this->assertSame('/existing-login.png', get_config('theme_learnlingo', 'loginlogo'));
        $this->assertSame('/existing-bg.jpg', get_config('theme_boost', 'loginbackgroundimage'));
    }
}
