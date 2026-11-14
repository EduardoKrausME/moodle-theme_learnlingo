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
 * MCP image integration tests for LearnLingo.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use context_system;
use core_component;
use invalid_parameter_exception;
use local_mcp\exception\api_exception;
use local_mcp\protocol\tool_schema;
use local_mcp\security\authenticated_identity;
use theme_learnlingo\mcp\get_editor_images;
use theme_learnlingo\mcp\provider;
use theme_learnlingo\mcp\upload_editor_image;

/**
 * Verify preview, validation and storage through the MCP tools.
 *
 * @covers \theme_learnlingo\editor_assets
 * @covers \theme_learnlingo\mcp\upload_editor_image
 * @covers \theme_learnlingo\mcp\get_editor_images
 */
final class mcp_editor_images_test extends advanced_testcase {
    /** A minimal valid PNG image. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/y9sAAAAASUVORK5CYII=';

    /**
     * Create an authenticated administrator identity for MCP integration tests.
     *
     * @return authenticated_identity Admin identity.
     */
    private function identity(): authenticated_identity {
        if (!core_component::get_component_directory('local_mcp')) {
            $this->markTestSkipped('Optional local_mcp plugin is not installed.');
        }
        $this->setAdminUser();
        return new authenticated_identity((int)get_admin()->id, 'test', ['mcp:read', 'mcp:write']);
    }

    /**
     * Uploading does not change HTML, configuration or existing editor images.
     */
    public function test_preview_upload_and_read_images(): void {
        $this->resetAfterTest();
        $identity = $this->identity();
        $provider = new provider();
        $readnames = array_map(static fn($tool): string => $tool->get_name(), $provider->get_read_tools());
        $writenames = array_map(static fn($tool): string => $tool->get_name(), $provider->get_write_tools());
        $this->assertContains('learnlingo_get_editor_images', $readnames);
        $this->assertContains('learnlingo_upload_editor_image', $writenames);

        $tool = new upload_editor_image();
        $this->assertSame('moodle/site:config', $tool->get_required_capability());
        $this->assertTrue($tool->supports_dry_run());
        $this->assertTrue($tool->requires_confirmation());
        $issues = [];
        tool_schema::normalize($tool->get_input_schema(), $issues);
        $this->assertSame([], array_values(array_filter($issues, static fn($issue): bool => !$issue['repaired'])));

        $before = (new get_editor_images())->execute([], $identity);
        $this->assertSame(0, $before['count']);
        $arguments = ['filename' => 'imagem.png', 'image_base64' => self::PNG];
        $preview = $tool->preview($arguments, $identity);
        $this->assertSame('image/png', $preview['mimetype']);
        $this->assertSame(1, $preview['width']);
        $this->assertSame(1, $preview['height']);
        $this->assertTrue($preview['requires_confirmation']);
        $this->assertSame(0, (new get_editor_images())->execute([], $identity)['count']);

        $arguments['expected_sha256'] = $preview['sha256'];
        $created = $tool->execute($arguments, $identity);
        $this->assertTrue($created['success']);
        $this->assertSame('imagem.png', $created['file']['name']);
        $this->assertStringContainsString('pluginfile.php', $created['url']);
        $this->assertStringContainsString('/theme_learnlingo/editor_assets/', $created['url']);
        $this->assertStringContainsString($created['url'], $created['html']);

        $file = get_file_storage()->get_file_by_id($created['file']['id']);
        $this->assertNotFalse($file);
        $this->assertSame((int)context_system::instance()->id, (int)$file->get_contextid());
        $this->assertSame('theme_learnlingo', $file->get_component());
        $this->assertSame('editor_assets', $file->get_filearea());
        $this->assertSame((int)get_admin()->id, (int)$file->get_userid());
        $this->assertSame(base64_decode(self::PNG), $file->get_content());

        $second = $tool->execute($arguments, $identity);
        $this->assertNotEquals($created['file']['id'], $second['file']['id']);
        $this->assertNotEquals($created['url'], $second['url']);
        $listed = (new get_editor_images())->execute([], $identity);
        $this->assertSame(2, $listed['count']);
        $this->assertContains($created['file'], $listed['files']);
        $this->assertContains($second['file'], $listed['files']);
        $this->assertSame('', (string)get_config('theme_learnlingo', 'editor_assets'));
    }

    /**
     * Base64 data URIs are accepted, while filename/MIME mismatch is refused.
     */
    public function test_image_data_uri_and_mimetype_validation(): void {
        $this->resetAfterTest();
        $identity = $this->identity();
        $tool = new upload_editor_image();
        $valid = $tool->preview(['filename' => 'logo.png',
            'image_base64' => 'data:image/png;base64,' . self::PNG], $identity);
        $this->assertSame('image/png', $valid['mimetype']);
        $this->expectException(invalid_parameter_exception::class);
        $tool->preview(['filename' => 'logo.jpg', 'image_base64' => self::PNG], $identity);
    }

    /**
     * A malformed Base64 source cannot create an asset.
     */
    public function test_invalid_base64_is_rejected(): void {
        $this->resetAfterTest();
        $identity = $this->identity();
        $this->expectException(api_exception::class);
        (new upload_editor_image())->preview(['filename' => 'image.png', 'image_base64' => '%%%'], $identity);
    }

    /**
     * Two input sources are ambiguous, and must be rejected before accessing URLs.
     */
    public function test_multiple_sources_are_rejected(): void {
        $this->resetAfterTest();
        $identity = $this->identity();
        $this->expectException(api_exception::class);
        (new upload_editor_image())->preview(['filename' => 'image.png', 'image_base64' => self::PNG,
            'image_url' => 'https://example.org/image.png'], $identity);
    }

    /**
     * The size guard runs before decoding or storing oversized Base64 input.
     */
    public function test_oversized_image_is_rejected(): void {
        $this->resetAfterTest();
        $identity = $this->identity();
        $oversized = str_repeat('A', (int)ceil(editor_assets::MAX_BYTES * 4 / 3) + 9);
        $this->expectException(api_exception::class);
        (new upload_editor_image())->preview(['filename' => 'huge.png',
            'image_base64' => $oversized], $identity);
    }

    /**
     * A changed image URL or Base64 source cannot silently bypass its preview.
     */
    public function test_changed_image_hash_is_rejected(): void {
        $this->resetAfterTest();
        $identity = $this->identity();
        $this->expectException(api_exception::class);
        (new upload_editor_image())->execute(['filename' => 'image.png', 'image_base64' => self::PNG,
            'expected_sha256' => str_repeat('0', 64)], $identity);
    }

    /**
     * Storage inventory is not available to ordinary users.
     */
    public function test_editor_asset_inventory_requires_site_configuration_capability(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        editor_assets::list_files();
    }
}
