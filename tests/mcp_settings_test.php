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
 * Tests for full LearnLingo MCP settings integration.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_learnlingo;

use advanced_testcase;
use core_component;
use invalid_parameter_exception;
use local_mcp\exception\api_exception;
use local_mcp\protocol\tool_schema;
use local_mcp\security\authenticated_identity;
use ReflectionMethod;
use theme_learnlingo\mcp\get_homepage;
use theme_learnlingo\mcp\get_settings;
use theme_learnlingo\mcp\provider;
use theme_learnlingo\mcp\update_settings;
use theme_learnlingo\mcp\update_styles;

/**
 * Run with the optional local_mcp installed.
 *
 * @covers \theme_learnlingo\mcp\settings_service
 */
final class mcp_settings_test extends advanced_testcase {
    /**
     * Skip tests when the optional MCP plugin is missing.
     *
     * @return void
     */
    private function require_mcp(): void {
        if (!core_component::get_component_directory('local_mcp')) {
            $this->markTestSkipped('This integration requires local_mcp.');
        }
    }

    /**
     * Return an authenticated administrator identity.
     *
     * @return authenticated_identity
     */
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
        $this->assertContains('learnlingo_update_styles', $writenames);
        $this->assertContains('learnlingo_update_footer', $writenames);
        $this->assertCount(2, $readnames);
        $this->assertCount(4, $writenames);
        $schema = (new update_settings())->get_input_schema();
        $this->assertArrayHasKey('scsspre', (array)$schema['properties']['settings']['properties']);
        $this->assertArrayHasKey('scss', (array)$schema['properties']['settings']['properties']);
        $this->assertArrayHasKey('homepagescss', (array)$schema['properties']['settings']['properties']);
        $this->assertArrayHasKey('footerscss', (array)$schema['properties']['settings']['properties']);
        $this->assertArrayHasKey('footerhtml', (array)$schema['properties']['settings']['properties']);
        $this->assertArrayHasKey('footermode', (array)$schema['properties']['settings']['properties']);
        $this->assertSame(['moodle', 'html'],
            $schema['properties']['settings']['properties']['footermode']['enum']);
        $this->assertArrayHasKey('footer_html_4', (array)$schema['properties']['settings']['properties']);
        $this->assertArrayHasKey('footer_background_color', (array)$schema['properties']['settings']['properties']);
        $properties = (array)$schema['properties']['settings']['properties'];
        $definitions = branding_exchange::definitions();
        $this->assertCount(count($definitions), $properties);
        foreach ($definitions as $name => [$type, $default]) {
            $this->assertArrayHasKey($name, $properties);
            $this->assertNotSame($name . ' (' . $type . ')', $properties[$name]['description']);
        }
        $this->assertStringContainsString('EVERY page', $properties['homepagescss']['description']);
        $this->assertStringContainsString('footermode=html', $properties['footerscss']['description']);
        $this->assertStringContainsString('existing page-footer', $properties['footerhtml']['description']);
        $this->assertStringContainsString('traditional', $properties['footer_title_1']['description']);
        $this->assertStringContainsString('before boost', strtolower(
            (new get_settings())->get_description()));
        $this->assertSame('moodle/site:config', (new update_settings())->get_required_capability());
        $this->assertTrue((new update_settings())->requires_confirmation());
        $this->assertTrue((new update_settings())->supports_dry_run());
        $issues = [];
        tool_schema::normalize($schema, $issues);
        $this->assertSame([], array_values(array_filter($issues, static fn($issue): bool => !$issue['repaired'])));
    }

    /**
     * A small, explicit schema exposes the custom HTML/SCSS write fields.
     */
    public function test_styles_tool_schema_and_confirmation(): void {
        $this->require_mcp();
        $tool = new update_styles();
        $schema = $tool->get_input_schema();
        $props = (array)$schema['properties']['settings']['properties'];
        $this->assertEqualsCanonicalizing([
            'homepagemode', 'homepagehtml', 'homepagescss',
            'footermode', 'footerhtml', 'footerscss', 'scsspre', 'scss',
        ], array_keys($props));
        $this->assertSame(['moodle', 'html'], $props['footermode']['enum']);
        $this->assertStringContainsString('globally', $props['homepagescss']['description']);
        $this->assertStringContainsString('automatically scoped', $props['footerscss']['description']);
        $this->assertSame('moodle/site:config', $tool->get_required_capability());
        $this->assertTrue($tool->requires_confirmation());
        $this->assertTrue($tool->supports_dry_run());
        $issues = [];
        tool_schema::normalize($schema, $issues);
        $this->assertSame([], array_values(array_filter($issues, static fn($issue): bool => !$issue['repaired'])));
    }

    /**
     * Moving shared rules from homepage to global must be a single safe write.
     */
    public function test_styles_tool_moves_css_without_duplicates_and_preserves_other_fields(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        set_config('homepagescss', '.navbar { color: #123456; }', 'theme_learnlingo');
        set_config('scss', '.other { display: block; }', 'theme_learnlingo');
        set_config('footermode', 'moodle', 'theme_learnlingo');
        set_config('footerhtml', '', 'theme_learnlingo');
        set_config('footerscss', '', 'theme_learnlingo');
        $reader = new get_settings();
        $before = $reader->execute([], $identity);
        $patch = [
            'expected_hash' => $before['state_hash'],
            'settings' => [
                'scss' => $before['settings']['scss'] . "\n" . $before['settings']['homepagescss'],
                'homepagescss' => '',
                'footermode' => 'html',
                'footerhtml' => '<section><p>About us</p></section>',
                'footerscss' => '& { padding: 2rem; }',
            ],
        ];
        $tool = new update_styles();
        $preview = $tool->preview($patch, $identity);
        $this->assertEqualsCanonicalizing(array_keys($patch['settings']), $preview['changed_settings']);
        $this->assertSame(0, $preview['settings_summary']['homepagescss']['bytes_after']);
        $this->assertTrue($preview['requires_confirmation']);
        $result = $tool->execute($patch, $identity);
        $this->assertTrue($result['success']);
        $after = $reader->execute([], $identity);
        $this->assertSame('', $after['settings']['homepagescss']);
        $this->assertSame(1, substr_count($after['settings']['scss'], '.navbar { color: #123456; }'));
        $this->assertSame('html', $after['settings']['footermode']);
        $this->assertSame('<section><p>About us</p></section>', $after['settings']['footerhtml']);
        $this->assertSame('& { padding: 2rem; }', $after['settings']['footerscss']);
        $this->assertSame($before['settings']['footer_title_1'], $after['settings']['footer_title_1']);
        $this->assertSame($before['settings']['homepagehtml'], $after['settings']['homepagehtml']);
    }

    /**
     * The new writer cannot silently bypass read-before-write or its allowlist.
     */
    public function test_styles_tool_requires_hash_and_rejects_other_settings(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $tool = new update_styles();
        try {
            $tool->preview(['settings' => ['homepagescss' => '']], $identity);
            $this->fail('A missing expected_hash must be rejected.');
        } catch (api_exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
        $before = (new get_settings())->execute([], $identity);
        $this->expectException(api_exception::class);
        $tool->preview([
            'expected_hash' => $before['state_hash'],
            'settings' => ['brandcolor' => '#ffffff'],
        ], $identity);
    }

    /**
     * Reuse the JSON importer constraints for dangerous SCSS.
     */
    public function test_styles_tool_rejects_invalid_scoped_scss(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $before = (new get_settings())->execute([], $identity);
        $this->expectException(invalid_parameter_exception::class);
        (new update_styles())->preview([
            'expected_hash' => $before['state_hash'],
            'settings' => ['footerscss' => '@at-root body { display: none; }'],
        ], $identity);
    }

    /**
     * One missing class must not break unrelated MCP tools.
     *
     * @return void
     */
    public function test_provider_skips_unavailable_tool_and_keeps_homepage_tool(): void {
        $this->require_mcp();
        $method = new ReflectionMethod(provider::class, 'available_tools');
        $tools = $method->invoke(null, [
            'learnlingo_get_homepage' => get_homepage::class,
            'missing_tool' => 'theme_learnlingo\\mcp\\intentionally_missing',
        ]);
        $this->assertCount(1, $tools);
        $this->assertSame('learnlingo_get_homepage', $tools[0]->get_name());
    }

    /**
     * Verify the expected behavior.
     *
     * @return void
     */
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
                'homepagescss' => '.welcome { color: #123abc; }',
                'footerscss' => '& { background: #abcdef; }',
                'showcourseheroprogress' => false,
                'footermode' => 'html',
                'footerhtml' => '<section><p>Custom footer</p></section>',
                'footer_background_color' => '#123456',
                'footer_title_1' => 'Contact',
                'footer_html_1' => '<p>Example contact</p>',
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
        $this->assertSame('.welcome { color: #123abc; }', $after['settings']['homepagescss']);
        $this->assertSame('& { background: #abcdef; }', $after['settings']['footerscss']);
        $this->assertFalse($after['settings']['showcourseheroprogress']);
        $this->assertSame('html', $after['settings']['footermode']);
        $this->assertSame('<section><p>Custom footer</p></section>', $after['settings']['footerhtml']);
        $this->assertSame('#123456', $after['settings']['footer_background_color']);
        $this->assertSame('Contact', $after['settings']['footer_title_1']);
        $this->assertSame('<p>Example contact</p>', $after['settings']['footer_html_1']);
        $this->assertSame($before['settings']['homepagemode'], $after['settings']['homepagemode']);
        $this->assertNotSame($before['state_hash'], $after['state_hash']);
    }

    /**
     * Full settings updates also normalize course image and summary URLs in HTML fields.
     */
    public function test_settings_normalize_legacy_course_file_urls_in_html(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $reader = new get_settings();
        $writer = new update_settings();
        $before = $reader->execute([], $identity);
        $arguments = [
            'expected_hash' => $before['state_hash'],
            'settings' => [
                'homepagehtml' => '<img src="/pluginfile.php/23/course/overviewfiles/0/cover.png">',
                'footerhtml' => '<a href="/pluginfile.php/23/course/summary/0/guide.pdf">Guide</a>',
                'footer_html_1' => '<img src="/pluginfile.php/23/course/section/0/keep.png">',
            ],
        ];
        $preview = $writer->preview($arguments, $identity);
        $this->assertContains('homepagehtml', $preview['changed_settings']);
        $this->assertContains('footerhtml', $preview['changed_settings']);
        $writer->execute($arguments, $identity);
        $after = $reader->execute([], $identity);
        $this->assertSame('<img src="/pluginfile.php/23/course/overviewfiles/cover.png">',
            $after['settings']['homepagehtml']);
        $this->assertSame('<a href="/pluginfile.php/23/course/summary/guide.pdf">Guide</a>',
            $after['settings']['footerhtml']);
        $this->assertSame('<img src="/pluginfile.php/23/course/section/0/keep.png">',
            $after['settings']['footer_html_1']);
    }

    public function test_stale_hash_is_rejected_without_writing(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $writer = new update_settings();
        $this->expectException(api_exception::class);
        $writer->preview(['settings' => ['brandcolor' => '#abcdef'],
            'expected_hash' => str_repeat('0', 64)], $identity);
    }

    public function test_malformed_scss_is_rejected_by_import_validator(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $this->expectException(invalid_parameter_exception::class);
        (new update_settings())->preview(
            ['settings' => ['scss' => '@import url(https://example.com/malicious.css);']],
            $identity
        );
    }

    public function test_unknown_setting_is_rejected(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $this->expectException(api_exception::class);
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
        $this->assertSame('/' . $after['images']['logo']['uploaded']['filename'],
            get_config('core_admin', 'logo'));
        $this->assertNotEmpty($after['_mcp_content']);
        $this->assertNotFalse(get_file_storage()->get_file(
            \context_system::instance()->id, 'core_admin', 'logo', 0, '/',
            $after['images']['logo']['uploaded']['filename']
        ));
        $this->assertSame($before['settings'], $after['settings']);

        $writer->execute(['images' => ['logo' => ['remove' => true]],
            'expected_hash' => $after['state_hash']], $identity);
        $removed = $reader->execute([], $identity);
        $this->assertNull($removed['images']['logo']['uploaded']);
        $this->assertSame('', get_config('core_admin', 'logo'));
    }

    public function test_image_operation_rejects_ambiguous_sources(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $this->expectException(api_exception::class);
        (new update_settings())->preview(
            ['images' => ['logo' => ['remove' => true, 'image_url' => 'https://example.com/logo.png']]],
            $identity
        );
    }
}
