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
 * Administration settings registration regression tests.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use admin_settingpage;
use advanced_testcase;
use function admin_get_root;

/**
 * Ensure Moodle can find the LearnLingo page in either admin tree mode.
 *
 * @coversNothing
 */
final class settings_registration_test extends advanced_testcase {
    /**
     * Every translation used by the branding pages must exist in both bundled languages.
     */
    public function test_branding_admin_page_strings_exist_in_both_languages(): void {
        global $CFG;
        $identifiers = [];
        foreach ([
            'admin/branding_json.php',
            'admin/branding_ai.php',
            'templates/branding_json.mustache',
            'templates/branding_ai.mustache',
        ] as $path) {
            $source = file_get_contents($CFG->dirroot . '/theme/learnlingo/' . $path);
            preg_match_all("/get_string\\(\\s*'([^']+)'\\s*,\\s*'theme_learnlingo'/", $source, $phpmatches);
            preg_match_all('/\{\{#str\}\}\s*([a-z0-9_]+)\s*,\s*theme_learnlingo\s*\{\{\/str\}\}/',
                $source, $templatematches);
            $identifiers = array_merge($identifiers, $phpmatches[1], $templatematches[1]);
        }
        $identifiers = array_unique($identifiers);
        $this->assertContains('jsonpagetitle', $identifiers);
        $this->assertContains('aigenerate', $identifiers);

        foreach (['en', 'pt_br'] as $language) {
            $string = [];
            require $CFG->dirroot . '/theme/learnlingo/lang/' . $language . '/theme_learnlingo.php';
            foreach ($identifiers as $identifier) {
                $this->assertArrayHasKey($identifier, $string,
                    "Missing {$language} translation for {$identifier}");
            }
        }
    }

    /**
     * Branding page markup belongs in Mustache, not in hand-assembled PHP fragments.
     */
    public function test_branding_admin_pages_do_not_use_html_writer(): void {
        global $CFG;
        foreach (['admin/branding_ai.php', 'admin/branding_json.php', 'settings.php'] as $path) {
            $source = file_get_contents($CFG->dirroot . '/theme/learnlingo/' . $path);
            $this->assertStringNotContainsString('html_writer::', $source, $path);
        }
        foreach (['branding_ai', 'branding_json', 'branding_admin_link'] as $name) {
            $path = $CFG->dirroot . '/theme/learnlingo/templates/' . $name . '.mustache';
            $this->assertFileExists($path);
        }
    }

    /**
     * User-provided content must remain escaped inside the new Mustache forms.
     */
    public function test_branding_templates_escape_user_input(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $OUTPUT;

        $ai = $OUTPUT->render_from_template('theme_learnlingo/branding_ai', [
            'actionurl' => '/theme/learnlingo/admin/branding_ai.php',
            'sesskey' => 'test',
            'description' => '<script>alert(1)</script>',
            'error' => '<img src=x onerror=alert(1)>',
            'hasproposal' => true,
            'proposalfields' => [['label' => 'Institution', 'value' => '<b>unsafe</b>']],
        ]);
        $this->assertStringNotContainsString('<script>', $ai);
        $this->assertStringNotContainsString('<img src=x', $ai);
        $this->assertStringNotContainsString('<b>unsafe</b>', $ai);
        $this->assertStringContainsString('&lt;script&gt;', $ai);
        $this->assertStringContainsString('name="action" value="generate"', $ai);
        $this->assertStringContainsString('name="action" value="apply"', $ai);

        $json = $OUTPUT->render_from_template('theme_learnlingo/branding_json', [
            'actionurl' => '/theme/learnlingo/admin/branding_json.php',
            'sesskey' => 'test',
            'saved' => false,
            'error' => '',
            'prompt' => '<script>alert(1)</script>',
            'importjson' => '</textarea><script>alert(1)</script>',
            'exportjson' => '{"name":"<test>"}',
        ]);
        $this->assertStringNotContainsString('</textarea><script>', $json);
        $this->assertStringContainsString('&lt;script&gt;', $json);
        $this->assertStringContainsString('&lt;test&gt;', $json);
        $this->assertStringContainsString('name="action" value="import"', $json);
    }

    /**
     * The theme settings page must remain visible in the lightweight tree.
     */
    public function test_settings_page_is_registered_in_both_admin_tree_modes(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        // Moodle builds the lightweight tree for navigation before full settings are needed.
        $tree = admin_get_root(true, false);
        $this->assertInstanceOf(admin_settingpage::class, $tree->locate('themesettinglearnlingo', true));

        // The same section must resolve when admin/settings.php requests the full tree.
        $tree = admin_get_root(true, true);
        $this->assertInstanceOf(admin_settingpage::class, $tree->locate('themesettinglearnlingo', true));
    }
}
