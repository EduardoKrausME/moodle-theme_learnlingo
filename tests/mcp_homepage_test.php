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
 * Tests LearnLingo's MCP provider and homepage tools.
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
use local_mcp\extension\manager;
use local_mcp\security\authenticated_identity;
use theme_learnlingo\mcp\provider;
use theme_learnlingo\mcp\get_homepage;
use theme_learnlingo\mcp\update_homepage;

/**
 * Tests run with local_mcp installed, as the integration is optional.
 *
 * @covers \theme_learnlingo\mcp\home_service
 */
final class mcp_homepage_test extends advanced_testcase {
    /**
     * Verify the expected behavior.
     *
     * @return void
     */
    private function require_mcp(): void {
        if (!core_component::get_component_directory('local_mcp')) {
            $this->markTestSkipped('This optional integration requires local_mcp.');
        }
    }

    /**
     * Verify the expected behavior.
     *
     * @return void
     */
    public function test_provider_registers_separate_read_and_write_tools(): void {
        $this->require_mcp();
        $provider = new provider();
        $this->assertInstanceOf(get_homepage::class, $provider->get_read_tools()[0]);
        $this->assertInstanceOf(update_homepage::class, $provider->get_write_tools()[0]);
        $this->assertSame('learnlingo_get_homepage', $provider->get_read_tools()[0]->get_name());
        $schema = $provider->get_read_tools()[0]->get_input_schema();
        $this->assertSame('{"type":"object","properties":{},"additionalProperties":false}',
            json_encode($schema));
        $this->assertSame('learnlingo_update_homepage', $provider->get_write_tools()[0]->get_name());
        $readproviders = manager::read_providers();
        $writeproviders = manager::write_providers();
        $this->assertNotEmpty(array_filter($readproviders,
            static fn($item): bool => $item instanceof provider));
        $this->assertNotEmpty(array_filter($writeproviders,
            static fn($item): bool => $item instanceof provider));
    }

    /**
     * Verify the expected behavior.
     *
     * @return void
     */
    public function test_homepage_can_be_previewed_changed_and_read_back(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $identity = new authenticated_identity((int)get_admin()->id,
            'test', ['mcp:read', 'mcp:write']);
        $reader = new get_homepage();
        $writer = new update_homepage();
        $before = $reader->execute([], $identity);
        $this->assertArrayHasKey('mustache', $before);
        $this->assertSame('homepagehtml', $before['mustache']['field']);
        $this->assertArrayHasKey('mycourses', $before['mustache']['fields']);
        $this->assertArrayHasKey('coursepagination', $before['mustache']['fields']);
        $this->assertSame('4', $before['publiccoursesperpage']);
        $this->assertArrayHasKey('courses', $before['mustache']['fields']);
        $this->assertArrayHasKey('cohorts', $before['mustache']['fields']);
        $this->assertNotEmpty($before['mustache']['instructions']);
        $instructions = implode(' ', $before['mustache']['instructions']);
        $this->assertStringContainsString('homepagehtml', $instructions);
        $this->assertStringContainsString('{{#isloggedin}}', $instructions);
        $this->assertStringContainsString('{{#mycourses}}', $instructions);
        $this->assertStringContainsString('{{#hasprogress}}', $instructions);
        $this->assertStringContainsString('MUC', $instructions);
        $this->assertStringContainsString('instead of hardcoding', $instructions);
        $this->assertStringContainsString('[data-bs-theme="dark"]', $instructions);
        $this->assertStringContainsString('global scss', $instructions);
        $this->assertStringContainsString('logo', $instructions);
        $this->assertStringContainsString('FOOTER', $instructions);
        $html = '<section class="welcome"><h1>New Moodle home</h1></section>';
        $arguments = [
            'html' => $html,
            'fullwidth' => true,
            'homepagescss' => '.welcome { padding: 1rem; }',
            'expected_hash' => $before['state_hash'],
        ];

        $this->assertSame('moodle/site:config', $writer->get_required_capability());
        $schema = $writer->get_input_schema();
        $this->assertArrayHasKey('homepagescss', $schema['properties']);
        $this->assertArrayHasKey('publiccourses', $schema['properties']);
        $this->assertArrayHasKey('publiccoursesperpage', $schema['properties']);
        $this->assertStringContainsString('ONLY', $schema['properties']['publiccoursesperpage']['description']);
        $this->assertStringContainsString('Only mycourses',
            $schema['properties']['html']['description']);
        $this->assertStringContainsString('Mustache', $reader->get_description());
        $this->assertStringContainsString('mustache.instructions', $reader->get_description());
        $this->assertStringContainsString('mustache.instructions', $writer->get_description());
        $this->assertStringContainsString('[data-bs-theme="dark"]', $writer->get_description());
        $this->assertStringContainsString('[data-bs-theme="dark"]', $reader->get_description());
        $this->assertStringContainsString('[data-bs-theme="dark"]',
            $schema['properties']['homepagescss']['description']);
        $this->assertStringContainsString('every page',
            $schema['properties']['homepagescss']['description']);
        $this->assertStringContainsString('footer',
            $schema['properties']['homepagescss']['description']);
        $this->assertStringContainsString('every page', $reader->get_description());
        $this->assertStringContainsString('learnlingo_update_settings', $writer->get_description());
        $this->assertTrue($writer->requires_confirmation());
        $this->assertTrue($writer->supports_dry_run());
        $preview = $writer->preview($arguments, $identity);
        $this->assertSame('learnlingo', $preview['mode_after']);
        $this->assertArrayNotHasKey('html', $preview);

        $result = $writer->execute($arguments, $identity);
        $this->assertTrue($result['success']);
        $this->assertTrue($result['theme_cache_purged']);
        $this->assertSame(theme_get_revision(), $result['theme_revision']);
        $after = $reader->execute([], $identity);
        $this->assertSame('learnlingo', $after['mode']);
        $this->assertSame($html, $after['html']);
        $this->assertSame('', $after['publiccourses']);
        $this->assertSame('4', $after['publiccoursesperpage']);
        $this->assertSame('.welcome { padding: 1rem; }', $after['homepagescss']);
        $this->assertSame(strlen('.welcome { padding: 1rem; }'), $result['homepagescss_bytes']);
        $this->assertTrue($after['fullwidth']);
        $this->assertNotSame($before['state_hash'], $after['state_hash']);
    }

    /**
     * Verify the expected behavior.
     *
     * @return void
     */
    /**
     * MCP HTML updates normalize legacy course file URLs before approval and persistence.
     */
    public function test_homepage_normalizes_course_file_urls_on_write(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $identity = new authenticated_identity((int)get_admin()->id,
            'test', ['mcp:read', 'mcp:write']);
        $writer = new update_homepage();
        $before = (new get_homepage())->execute([], $identity);
        $html = '<img src="/pluginfile.php/23/course/overviewfiles/0/cover.png">'
            . '<a href="/pluginfile.php/23/course/summary/0/guide.pdf">Guide</a>'
            . '<img src="/pluginfile.php/23/course/section/0/keep.png">';
        $normalized = '<img src="/pluginfile.php/23/course/overviewfiles/cover.png">'
            . '<a href="/pluginfile.php/23/course/summary/guide.pdf">Guide</a>'
            . '<img src="/pluginfile.php/23/course/section/0/keep.png">';

        $arguments = ['html' => $html, 'expected_hash' => $before['state_hash']];
        $preview = $writer->preview($arguments, $identity);
        $this->assertSame(strlen($normalized), $preview['html_bytes_after']);
        $writer->execute($arguments, $identity);
        $after = (new get_homepage())->execute([], $identity);
        $this->assertSame($normalized, $after['html']);
    }

    public function test_stale_state_hash_is_rejected(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $identity = new authenticated_identity((int)get_admin()->id,
            'test', ['mcp:read', 'mcp:write']);
        $writer = new update_homepage();
        $this->expectException(api_exception::class);
        $writer->preview(['html' => '<p>Safe</p>', 'expected_hash' => str_repeat('0', 64)], $identity);
    }

    /**
     * Verify the expected behavior.
     *
     * @return void
     */
    public function test_script_tag_in_home_html_is_rejected(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $identity = new authenticated_identity((int)get_admin()->id,
            'test', ['mcp:read', 'mcp:write']);
        $writer = new update_homepage();
        $this->expectException(invalid_parameter_exception::class);
        $writer->preview(['html' => '<script>alert(1)</script><h1>Home</h1>'], $identity);
    }
}
