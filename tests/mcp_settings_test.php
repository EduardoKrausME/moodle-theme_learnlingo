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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU GPL for details.

/**
 * Tests for full LearnLingo MCP settings integration.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_learnlingo;

use advanced_testcase;
use local_mcp\security\authenticated_identity;
use theme_learnlingo\mcp\get_settings;
use theme_learnlingo\mcp\provider;
use theme_learnlingo\mcp\update_settings;

defined('MOODLE_INTERNAL') || die();

/**
 * Run with the optional local_mcp installed.
 */
final class mcp_settings_test extends advanced_testcase {
    private function require_mcp(): void {
        if (!\core_component::get_component_directory('local_mcp')) {
            $this->markTestSkipped('This integration requires local_mcp.');
        }
    }

    private function identity(): authenticated_identity {
        $this->setAdminUser();
        return new authenticated_identity((int)get_admin()->id, 'test', ['mcp:read', 'mcp:write']);
    }

    public function test_provider_registers_full_settings_read_and_write_tools(): void {
        $this->require_mcp();
        $provider = new provider();
        $readnames = array_map(static fn($tool): string => $tool->get_name(), $provider->get_read_tools());
        $writenames = array_map(static fn($tool): string => $tool->get_name(), $provider->get_write_tools());
        $this->assertContains('learnlingo_get_settings', $readnames);
        $this->assertContains('learnlingo_update_settings', $writenames);
        $schema = (new update_settings())->get_input_schema();
        $this->assertArrayHasKey('scsspre', (array)$schema['properties']['settings']['properties']);
        $this->assertArrayHasKey('scss', (array)$schema['properties']['settings']['properties']);
        $this->assertArrayHasKey('loginlogo', $schema['properties']['images']['properties']);
        $this->assertSame('moodle/site:config', (new update_settings())->get_required_capability());
        $this->assertTrue((new update_settings())->requires_confirmation());
        $this->assertTrue((new update_settings())->supports_dry_run());
        $issues = [];
        \local_mcp\protocol\tool_schema::normalize($schema, $issues);
        $this->assertSame([], array_values(array_filter($issues, static fn($issue): bool => !$issue['repaired'])));
    }

    public function test_partial_update_preserves_unrelated_settings_and_accepts_both_scss_fields(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $reader = new get_settings();
        $writer = new update_settings();
        $before = $reader->execute([], $identity);
        $patch = [
            'expected_hash' => $before['state_hash'],
            'settings' => [
                'brandcolor' => '#123abc',
                'scsspre' => '$border-radius: 0.25rem;',
                'scss' => '.custom-header { color: #123abc; }',
                'showcourseheroprogress' => false,
            ],
        ];
        $preview = $writer->preview($patch, $identity);
        $this->assertContains('scsspre', $preview['changed_settings']);
        $this->assertArrayNotHasKey('html', $preview);
        $result = $writer->execute($patch, $identity);
        $this->assertTrue($result['success']);
        $after = $reader->execute([], $identity);
        $this->assertSame('#123abc', $after['settings']['brandcolor']);
        $this->assertSame('$border-radius: 0.25rem;', $after['settings']['scsspre']);
        $this->assertSame('.custom-header { color: #123abc; }', $after['settings']['scss']);
        $this->assertFalse($after['settings']['showcourseheroprogress']);
        $this->assertSame($before['settings']['homepagemode'], $after['settings']['homepagemode']);
        $this->assertNotSame($before['state_hash'], $after['state_hash']);
    }

    public function test_stale_hash_is_rejected_without_writing(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $writer = new update_settings();
        $this->expectException(\local_mcp\exception\api_exception::class);
        $writer->preview(['settings' => ['brandcolor' => '#abcdef'],
            'expected_hash' => str_repeat('0', 64)], $identity);
    }

    public function test_malformed_scss_is_rejected_by_import_validator(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $this->expectException(\invalid_parameter_exception::class);
        (new update_settings())->preview(
            ['settings' => ['scss' => '@import url(https://example.com/malicious.css);']],
            $identity
        );
    }

    public function test_unknown_setting_is_rejected(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $this->expectException(\local_mcp\exception\api_exception::class);
        (new update_settings())->preview(['settings' => ['adminpassword' => 'ignored']], $identity);
    }

    public function test_uploaded_logo_can_be_read_replaced_and_removed(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $reader = new get_settings();
        $writer = new update_settings();
        // A real 1x1 PNG, not a fake extension or a synthetic MIME declaration.
        $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/y9sAAAAASUVORK5CYII=';
        $before = $reader->execute([], $identity);
        $arguments = ['expected_hash' => $before['state_hash'],
            'images' => ['logo' => ['image_base64' => $png]]];
        $preview = $writer->preview($arguments, $identity);
        $this->assertSame('replace', $preview['images_summary']['logo']['action']);
        $writer->execute($arguments, $identity);
        $after = $reader->execute(['include_images' => true], $identity);
        $this->assertSame('image/png', $after['images']['logo']['uploaded']['mimetype']);
        $this->assertNotEmpty($after['_mcp_content']);
        $this->assertSame($before['settings'], $after['settings']);

        $writer->execute(['images' => ['logo' => ['remove' => true]],
            'expected_hash' => $after['state_hash']], $identity);
        $removed = $reader->execute([], $identity);
        $this->assertNull($removed['images']['logo']['uploaded']);
    }

    public function test_image_operation_rejects_ambiguous_sources(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $this->expectException(\local_mcp\exception\api_exception::class);
        (new update_settings())->preview(
            ['images' => ['logo' => ['remove' => true, 'image_url' => 'https://example.com/logo.png']]],
            $identity
        );
    }
}
