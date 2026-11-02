<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Tests of modern stored-file settings.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;

/**
 * Check that the upload widget preserves Moodle's File API and draft previews.
 *
 * @covers \theme_learnlingo\admin_setting_configstoredfilemodern
 */
final class storedfile_settings_test extends advanced_testcase {
    /**
     * The custom class must remain a core stored-file setting.
     */
    public function test_modern_setting_extends_core_file_setting(): void {
        $setting = new admin_setting_configstoredfilemodern(
            'theme_learnlingo/loginlogo',
            'Login logo',
            'Upload a logo.',
            'loginlogo',
            0,
            ['maxfiles' => 1, 'accepted_types' => ['.png']]
        );
        $this->assertInstanceOf(\admin_setting_configstoredfile::class, $setting);
    }

    /**
     * Admin setting renders the native filemanager and new upload elements.
     */
    public function test_modern_setting_renders_native_filemanager(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $setting = new admin_setting_configstoredfilemodern(
            'theme_learnlingo/loginlogo',
            'Login logo',
            'Upload a logo.',
            'loginlogo',
            0,
            ['maxfiles' => 1, 'accepted_types' => ['.png']]
        );

        $html = $setting->output_html('');
        $this->assertStringContainsString('data-ll-storedfile', $html);
        $this->assertStringContainsString('data-file-dropzone', $html);
        $this->assertStringContainsString('data-file-choose', $html);
        $this->assertStringContainsString('data-file-canvas', $html);
        $this->assertStringContainsString('data-fieldtype="filemanager"', $html);
        $this->assertStringContainsString('type="hidden"', $html);
    }

    /**
     * Preview the existing image in a user-accessible draft area.
     */
    public function test_modern_setting_previews_existing_image(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $context = \context_system::instance();
        $record = [
            'contextid' => $context->id,
            'component' => 'theme_learnlingo',
            'filearea' => 'loginlogo',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'white-logo.png',
        ];

        // Tiny valid PNG, stored via the same API as the existing setting.
        $bytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGP4////fwAJ' .
            '+wP9KobjigAAAABJRU5ErkJggg=='
        );
        get_file_storage()->create_file_from_string($record, $bytes);
        set_config('loginlogo', '/white-logo.png', 'theme_learnlingo');

        $setting = new admin_setting_configstoredfilemodern(
            'theme_learnlingo/loginlogo',
            'Login logo',
            'Upload a logo.',
            'loginlogo',
            0,
            ['maxfiles' => 1, 'accepted_types' => ['.png']]
        );
        $html = $setting->output_html($setting->get_setting());

        $this->assertStringContainsString('alt="white-logo.png"', $html);
        $this->assertStringContainsString('data-draft-pattern=', $html);
        $this->assertStringContainsString('draftfile.php', $html);
        $this->assertStringContainsString('data-file-image', $html);
        $this->assertSame('/white-logo.png', $setting->get_setting());
    }

    /**
     * Interface labels should be present in the two bundled languages.
     */
    public function test_modern_file_labels_are_translated(): void {
        global $CFG;

        $identifiers = [
            'filemodernchoose', 'filemoderncontrast', 'filemoderncurrent',
            'filemoderndrop', 'filemodernempty', 'filemodernhelp', 'filemodernnew',
            'filemodernnoimage', 'filemodernor',
        ];

        foreach (['en', 'pt_br'] as $lang) {
            $string = [];
            require($CFG->dirroot . '/theme/learnlingo/lang/' . $lang . '/theme_learnlingo.php');
            foreach ($identifiers as $identifier) {
                $this->assertArrayHasKey($identifier, $string, "Missing {$lang} string: {$identifier}");
            }
        }
    }
}
