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
 * Tests LearnLingo's MCP provider and homepage tools.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use theme_learnlingo\mcp\provider;
use theme_learnlingo\mcp\get_homepage;
use theme_learnlingo\mcp\update_homepage;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests run with local_mcp installed, as the integration is optional.
 */
final class mcp_homepage_test extends advanced_testcase {
    /**
     * @return void
     */
    private function require_mcp(): void {
        if (!\core_component::get_component_directory('local_mcp')) {
            $this->markTestSkipped('This optional integration requires local_mcp.');
        }
    }

    /**
     * @return void
     */
    public function test_provider_registers_separate_read_and_write_tools(): void {
        $this->require_mcp();
        $provider = new provider();
        $this->assertInstanceOf(get_homepage::class, $provider->get_read_tools()[0]);
        $this->assertInstanceOf(update_homepage::class, $provider->get_write_tools()[0]);
        $this->assertSame('learnlingo_get_homepage', $provider->get_read_tools()[0]->get_name());
        $this->assertSame('learnlingo_update_homepage', $provider->get_write_tools()[0]->get_name());
        $readproviders = \local_mcp\extension\manager::read_providers();
        $writeproviders = \local_mcp\extension\manager::write_providers();
        $this->assertNotEmpty(array_filter($readproviders,
            static fn($item): bool => $item instanceof provider));
        $this->assertNotEmpty(array_filter($writeproviders,
            static fn($item): bool => $item instanceof provider));
    }

    /**
     * @return void
     */
    public function test_homepage_can_be_previewed_changed_and_read_back(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $identity = new \local_mcp\security\authenticated_identity((int)get_admin()->id,
            'test', ['mcp:read', 'mcp:write']);
        $reader = new get_homepage();
        $writer = new update_homepage();
        $before = $reader->execute([], $identity);
        $html = '<section class="welcome"><h1>New Moodle home</h1></section>';
        $arguments = [
            'html' => $html,
            'fullwidth' => true,
            'expected_hash' => $before['state_hash'],
        ];

        $this->assertSame('moodle/site:config', $writer->get_required_capability());
        $this->assertTrue($writer->requires_confirmation());
        $this->assertTrue($writer->supports_dry_run());
        $preview = $writer->preview($arguments, $identity);
        $this->assertSame('html', $preview['mode_after']);
        $this->assertArrayNotHasKey('html', $preview);

        $result = $writer->execute($arguments, $identity);
        $this->assertTrue($result['success']);
        $after = $reader->execute([], $identity);
        $this->assertSame('html', $after['mode']);
        $this->assertSame($html, $after['html']);
        $this->assertTrue($after['fullwidth']);
        $this->assertNotSame($before['state_hash'], $after['state_hash']);
    }

    /**
     * @return void
     */
    public function test_stale_state_hash_is_rejected(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $identity = new \local_mcp\security\authenticated_identity((int)get_admin()->id,
            'test', ['mcp:read', 'mcp:write']);
        $writer = new update_homepage();
        $this->expectException(\local_mcp\exception\api_exception::class);
        $writer->preview(['html' => '<p>Safe</p>', 'expected_hash' => str_repeat('0', 64)], $identity);
    }

    /**
     * @return void
     */
    public function test_script_tag_in_home_html_is_rejected(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $identity = new \local_mcp\security\authenticated_identity((int)get_admin()->id,
            'test', ['mcp:read', 'mcp:write']);
        $writer = new update_homepage();
        $this->expectException(\invalid_parameter_exception::class);
        $writer->preview(['html' => '<script>alert(1)</script><h1>Home</h1>'], $identity);
    }
}
