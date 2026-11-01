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
 * Contract and integration tests for the dedicated LearnLingo footer MCP tool.
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
use theme_learnlingo\mcp\get_settings;
use theme_learnlingo\mcp\provider;
use theme_learnlingo\mcp\update_footer;

/**
 * Footer write tool is available when the optional local_mcp plugin is installed.
 *
 * @covers \theme_learnlingo\mcp\update_footer
 */
final class mcp_footer_test extends advanced_testcase {
    /**
     * Skip when optional integration is not installed.
     */
    private function require_mcp(): void {
        if (!core_component::get_component_directory('local_mcp')) {
            $this->markTestSkipped('This integration requires local_mcp.');
        }
    }

    /**
     * Administrator for MCP tool tests.
     *
     * @return authenticated_identity
     */
    private function identity(): authenticated_identity {
        $this->setAdminUser();
        return new authenticated_identity((int)get_admin()->id, 'test', ['mcp:read', 'mcp:write']);
    }

    /**
     * Every one of the thirteen footer fields is represented in the schema.
     */
    public function test_footer_schema_exposes_all_thirteen_fields(): void {
        $this->require_mcp();
        $provider = new provider();
        $writenames = array_map(static fn($tool): string => $tool->get_name(), $provider->get_write_tools());
        $this->assertContains('learnlingo_update_footer', $writenames);

        $tool = new update_footer();
        $schema = $tool->get_input_schema();
        $properties = (array)$schema['properties']['settings']['properties'];
        $this->assertEqualsCanonicalizing([
            'footermode', 'footerhtml', 'footerscss', 'footer_background_color',
            'footer_title_1', 'footer_html_1',
            'footer_title_2', 'footer_html_2',
            'footer_title_3', 'footer_html_3',
            'footer_title_4', 'footer_html_4',
            'footer_show_copywriter',
        ], array_keys($properties));
        $this->assertSame(['moodle', 'html'], $properties['footermode']['enum']);
        $this->assertSame('boolean', $properties['footer_show_copywriter']['type']);
        $this->assertSame('^#[A-Fa-f0-9]{6}$', $properties['footer_background_color']['pattern']);
        $this->assertStringContainsString('footer', $properties['footerscss']['description']);
        $this->assertSame('moodle/site:config', $tool->get_required_capability());
        $this->assertTrue($tool->requires_confirmation());
        $this->assertTrue($tool->supports_dry_run());
        $issues = [];
        tool_schema::normalize($schema, $issues);
        $this->assertSame([], array_values(array_filter($issues, static fn($issue): bool => !$issue['repaired'])));
    }

    /**
     * A patch can update the entire footer and preserve unrelated settings.
     */
    public function test_footer_write_roundtrip_preserves_unrelated_settings(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $reader = new get_settings();
        $before = $reader->execute([], $identity);
        $changes = [
            'footermode' => 'html',
            'footerhtml' => '<section><p>Custom footer</p></section>',
            'footerscss' => '& { padding: 1.5rem; }',
            'footer_background_color' => '#123456',
            'footer_title_1' => 'About',
            'footer_html_1' => '<p>About us</p>',
            'footer_title_2' => 'Resources',
            'footer_html_2' => '<ul><li>Docs</li></ul>',
            'footer_title_3' => 'Courses',
            'footer_html_3' => '<p>Our courses</p>',
            'footer_title_4' => 'Contact',
            'footer_html_4' => '<p>Write to us</p>',
            'footer_show_copywriter' => false,
        ];
        $tool = new update_footer();
        $args = ['settings' => $changes, 'expected_hash' => $before['state_hash']];
        $preview = $tool->preview($args, $identity);
        $this->assertEqualsCanonicalizing(array_keys($changes), $preview['changed_settings']);
        $this->assertTrue($preview['requires_confirmation']);
        $this->assertArrayNotHasKey('footerhtml', $preview);
        $result = $tool->execute($args, $identity);
        $this->assertTrue($result['success']);
        $after = $reader->execute([], $identity);
        foreach ($changes as $name => $value) {
            $this->assertSame($value, $after['settings'][$name], $name);
        }
        $this->assertSame($before['settings']['homepagemode'], $after['settings']['homepagemode']);
        $this->assertSame($before['settings']['homepagescss'], $after['settings']['homepagescss']);
        $this->assertSame($before['settings']['scss'], $after['settings']['scss']);
    }

    /**
     * Switching footer mode must retain the old columns and custom HTML.
     */
    public function test_footer_mode_switch_preserves_inactive_values(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        set_config('footermode', 'html', 'theme_learnlingo');
        set_config('footerhtml', '<p>Custom</p>', 'theme_learnlingo');
        set_config('footerscss', '& { padding: 1rem; }', 'theme_learnlingo');
        set_config('footer_title_1', 'Existing', 'theme_learnlingo');
        $reader = new get_settings();
        $before = $reader->execute([], $identity);
        $tool = new update_footer();
        $tool->execute(['settings' => ['footermode' => 'moodle'],
            'expected_hash' => $before['state_hash']], $identity);
        $after = $reader->execute([], $identity);
        $this->assertSame('moodle', $after['settings']['footermode']);
        $this->assertSame('<p>Custom</p>', $after['settings']['footerhtml']);
        $this->assertSame('& { padding: 1rem; }', $after['settings']['footerscss']);
        $this->assertSame('Existing', $after['settings']['footer_title_1']);
    }

    /**
     * The writer requires a fresh hash and rejects unrelated settings.
     */
    public function test_footer_write_rejects_missing_or_stale_hash_and_other_keys(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $tool = new update_footer();
        try {
            $tool->preview(['settings' => ['footer_title_1' => 'About']], $identity);
            $this->fail('Missing expected_hash was accepted.');
        } catch (api_exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
        $before = (new get_settings())->execute([], $identity);
        try {
            $tool->preview(['settings' => ['footer_title_1' => 'About'],
                'expected_hash' => str_repeat('0', 64)], $identity);
            $this->fail('Stale expected_hash was accepted.');
        } catch (api_exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
        $this->expectException(api_exception::class);
        $tool->preview(['settings' => ['scss' => '.navbar { color: red; }'],
            'expected_hash' => $before['state_hash']], $identity);
    }

    /**
     * Invalid colors and scripts must be rejected by the common importer.
     */
    public function test_footer_write_rejects_unsafe_html(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $before = (new get_settings())->execute([], $identity);
        $this->expectException(invalid_parameter_exception::class);
        (new update_footer())->preview([
            'settings' => ['footer_html_1' => '<script>alert(1)</script>'],
            'expected_hash' => $before['state_hash'],
        ], $identity);
    }

    /**
     * Invalid colors must be rejected before writing.
     */
    public function test_footer_write_rejects_invalid_color(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $identity = $this->identity();
        $before = (new get_settings())->execute([], $identity);
        $this->expectException(invalid_parameter_exception::class);
        (new update_footer())->preview([
            'settings' => ['footer_background_color' => 'transparent'],
            'expected_hash' => $before['state_hash'],
        ], $identity);
    }
}
