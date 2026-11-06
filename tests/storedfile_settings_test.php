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
     * Admin setting renders just one image editor, without a legacy filemanager.
     */
    public function test_modern_setting_renders_one_image_editor(): void {
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
        $this->assertStringContainsString('data-file-delete', $html);
        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringNotContainsString('data-fieldtype="filemanager"', $html);
        $this->assertStringNotContainsString('fp-btn-add', $html);
    }

    /**
     * The AJAX endpoint must receive the config key, not the settings form field name.
     */
    public function test_ajax_uses_canonical_setting_name_for_all_image_fields(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $settings = [
            ['core_admin/logo', 'logo'],
            ['core_admin/favicon', 'favicon'],
            ['theme_boost/loginbackgroundimage', 'loginbackgroundimage'],
            ['theme_learnlingo/loginlogo', 'loginlogo'],
        ];
        foreach ($settings as [$name, $area]) {
            $setting = new admin_setting_configstoredfilemodern(
                $name, 'Image', '', $area, 0, ['maxfiles' => 1, 'accepted_types' => ['image']]
            );
            $html = $setting->output_html('');
            $this->assertStringContainsString('data-setting="' . $name . '"', $html);
            $this->assertStringContainsString(
                'name="s_' . str_replace('/', '_', $name) . '"', $html
            );
        }
    }

    /**
     * Older rendered forms must still be able to upload or delete an image.
     */
    public function test_ajax_normalises_legacy_setting_names_without_bypassing_allowlist(): void {
        $settings = [
            'core_admin/logo',
            'core_admin/favicon',
            'theme_boost/loginbackgroundimage',
            'theme_learnlingo/loginlogo',
        ];
        foreach ($settings as $name) {
            $this->assertSame($name, admin_setting_configstoredfilemodern::normalise_ajax_setting_name(
                $name, $settings
            ));
            $this->assertSame($name, admin_setting_configstoredfilemodern::normalise_ajax_setting_name(
                's_' . str_replace('/', '_', $name), $settings
            ));
        }
        $this->assertNull(admin_setting_configstoredfilemodern::normalise_ajax_setting_name(
            's_theme_learnlingo_unknown', $settings
        ));
        $this->assertNull(admin_setting_configstoredfilemodern::normalise_ajax_setting_name(
            'core_admin/unknown', $settings
        ));
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
        $this->assertStringContainsString('data-upload-url=', $html);
        $this->assertStringContainsString('draftfile.php', $html);
        $this->assertStringContainsString('data-file-image', $html);
        $this->assertSame('/white-logo.png', $setting->get_setting());
    }

    /**
     * Core still saves and deletes actual File API entries from drafts.
     */
    public function test_modern_setting_keeps_core_file_persistence(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $USER;

        $setting = new admin_setting_configstoredfilemodern(
            'theme_learnlingo/loginlogo',
            'Login logo',
            '',
            'loginlogo',
            0,
            ['maxfiles' => 1, 'accepted_types' => ['.png']]
        );
        $draftid = file_get_unused_draft_itemid();
        file_prepare_draft_area(
            $draftid,
            \context_system::instance()->id,
            'theme_learnlingo',
            'loginlogo',
            0,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.png']]
        );

        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => 'new-logo.png',
        ], 'image-content');

        $this->assertSame('', $setting->write_setting($draftid));
        $this->assertSame('/new-logo.png', $setting->get_setting());
        $stored = get_file_storage()->get_file(
            \context_system::instance()->id,
            'theme_learnlingo',
            'loginlogo',
            0,
            '/',
            'new-logo.png'
        );
        $this->assertNotFalse($stored);

        $emptydraftid = file_get_unused_draft_itemid();
        file_prepare_draft_area(
            $emptydraftid,
            \context_system::instance()->id,
            'theme_learnlingo',
            'loginlogo',
            0,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.png']]
        );
        $files = get_file_storage()->get_area_files(
            \context_user::instance($USER->id)->id,
            'user',
            'draft',
            $emptydraftid,
            'sortorder,filepath,filename',
            false
        );
        foreach ($files as $file) {
            $file->delete();
        }
        // A real filemanager maintains the root directory even after deletion.
        // This is required by core's safety check for an existing file area.
        get_file_storage()->create_directory(
            \context_user::instance($USER->id)->id,
            'user',
            'draft',
            $emptydraftid,
            '/'
        );
        $this->assertSame('', $setting->write_setting($emptydraftid));
        $this->assertSame('', $setting->get_setting());
        $this->assertFalse(get_file_storage()->get_file(
            \context_system::instance()->id,
            'theme_learnlingo',
            'loginlogo',
            0,
            '/',
            'new-logo.png'
        ));
    }

    /**
     * Interface labels should be present in the two bundled languages.
     */
    public function test_modern_file_labels_are_translated(): void {
        global $CFG;

        $identifiers = [
            'filemodernchoose', 'filemoderncontrast', 'filemoderncurrent',
            'filemoderndrop', 'filemodernempty', 'filemodernnew',
            'filemodernnoimage', 'filemodernor', 'filemoderndelete',
            'filemoderndeleteconfirm', 'filemodernuploading', 'filemoderndeleting',
            'filemodernuploaded', 'filemoderndeleted', 'filemodernuploaderror',
            'filemoderntypeerror', 'filemodernsizeerror',
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
