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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Tests for AJAX-based LearnLingo editor asset management.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use theme_learnlingo\external\editor_files;

/**
 * Editor assets must only be available to site administrators.
 *
 * @covers \theme_learnlingo\external\editor_files
 */
final class editor_files_test extends advanced_testcase {
    /**
     * Store one file in a selected Moodle File API area.
     *
     * @param string $filearea File area
     * @return \stored_file
     */
    private function create_asset(string $filearea = 'editor_assets'): \stored_file {
        global $USER;
        return get_file_storage()->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'theme_learnlingo',
            'filearea' => $filearea,
            'itemid' => 1,
            'filepath' => '/',
            'filename' => 'image.png',
            'userid' => $USER->id,
        ], 'image data');
    }

    /**
     * API exposes the existing studio asset format and can delete assets.
     */
    public function test_admin_can_list_and_delete_editor_assets(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $asset = $this->create_asset();

        $entries = editor_files::list_files();
        $matching = array_values(array_filter($entries, static function(array $entry) use ($asset): bool {
            return $entry['id'] === $asset->get_id();
        }));
        $this->assertCount(1, $matching);
        $this->assertSame('image.png', $matching[0]['name']);
        $this->assertSame('image', $matching[0]['type']);
        $this->assertSame(true, $matching[0]['delete']);
        $this->assertStringContainsString('pluginfile.php', $matching[0]['src']);

        $this->assertSame(['success' => true], editor_files::delete_file($asset->get_id()));
        $this->assertFalse(get_file_storage()->get_file_by_id($asset->get_id()));
    }

    /**
     * Assets outside this specific file area must not be deleted.
     */
    public function test_delete_rejects_foreign_file_area(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $asset = $this->create_asset('other_assets');
        try {
            editor_files::delete_file($asset->get_id());
            $this->fail('Expected rejection of a different file area.');
        } catch (\invalid_parameter_exception $exception) {
            $this->assertNotFalse(get_file_storage()->get_file_by_id($asset->get_id()));
        }
    }

    /**
     * Non-admins cannot list editor images.
     */
    public function test_normal_user_cannot_list_editor_assets(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        editor_files::list_files();
    }

    /**
     * Non-admins cannot delete editor images.
     */
    public function test_normal_user_cannot_delete_editor_assets(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $asset = $this->create_asset();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        editor_files::delete_file($asset->get_id());
    }

    /**
     * Both methods are registered as AJAX-capable external services.
     */
    public function test_services_are_registered_for_ajax(): void {
        global $CFG;
        require($CFG->dirroot . '/theme/learnlingo/db/services.php');
        $this->assertTrue($functions['theme_learnlingo_list_editor_files']['ajax']);
        $this->assertTrue($functions['theme_learnlingo_delete_editor_file']['ajax']);
        $this->assertSame('read', $functions['theme_learnlingo_list_editor_files']['type']);
        $this->assertSame('write', $functions['theme_learnlingo_delete_editor_file']['type']);
    }
}
