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
 * Step two shows safe guidance for an existing HTML homepage.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

/**
 * The wizard must not offer replacement layouts for an existing HTML homepage.
 *
 * @coversNothing
 */
final class setup_homepage_step_test extends \advanced_testcase {
    /**
     * Prepare enough context to render the step two Moodle Mustache template.
     *
     * @param bool $htmlmode Existing HTML homepage mode.
     * @param bool $hascontent HTML content saved.
     * @return string Rendered wizard HTML.
     */
    private function render_step(bool $htmlmode, bool $hascontent): string {
        global $OUTPUT;

        $data = [
            'step' => 2,
            'stepdisplay' => 'Step 2 of 5',
            'stepcount' => 5,
            'progress' => 40,
            'steptwo' => true,
            'customhtmlmode' => $htmlmode,
            'existinghomepagehtml' => $hascontent,
            'layouts' => [
                ['value' => 'learnlingo', 'label' => 'LearnLingo', 'selected' => true, 'islearnlingo' => true],
                ['value' => 'moodle', 'label' => 'Moodle', 'selected' => false, 'ismoodle' => true],
            ],
            'steps' => [],
            'actionurl' => '/theme/learnlingo/admin/setup.php',
            'sesskey' => 'test',
            'htmlsettingsurl' => '/admin/settings.php?section=theme_learnlingo_homepage',
            'mcpurl' => 'https://github.com/EduardoKrausME/moodle-local_mcp',
            'chatgpturl' => 'https://chatgpt.com/',
            'claudeurl' => 'https://claude.ai/',
            'jsonurl' => '/theme/learnlingo/admin/branding_json.php',
            'prompt' => 'Current site settings for AI assistance',
        ];
        return $OUTPUT->render_from_template('theme_learnlingo/setup', $data);
    }

    /**
     * HTML mode provides editing and AI choices, without a switch to another layout.
     */
    public function test_existing_html_home_shows_settings_and_ai_not_layout_choices(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $html = $this->render_step(true, true);

        $this->assertStringContainsString(
            get_string('setuphtmlalreadyconfigured', 'theme_learnlingo'), $html
        );
        $this->assertStringContainsString('section=theme_learnlingo_homepage', $html);
        $this->assertStringContainsString('moodle-local_mcp', $html);
        $this->assertStringContainsString('id="learnlingo-copy-prompt"', $html);
        $this->assertStringContainsString('Current site settings for AI assistance', $html);
        $this->assertStringNotContainsString('name="homepagemode"', $html);
    }

    /**
     * An empty HTML page must not be advertised as already populated.
     */
    public function test_empty_html_mode_explains_that_content_is_missing(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $html = $this->render_step(true, false);

        $this->assertStringContainsString(
            get_string('setuphtmlalreadynocontent', 'theme_learnlingo'), $html
        );
        $this->assertStringNotContainsString('name="homepagemode"', $html);
    }

    /**
     * Standard mode still shows both selectable layouts for initial setup.
     */
    public function test_standard_mode_still_offers_layout_selection(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $html = $this->render_step(false, false);

        $this->assertStringContainsString('name="homepagemode"', $html);
        $this->assertStringContainsString('value="learnlingo"', $html);
        $this->assertStringContainsString('value="moodle"', $html);
        $this->assertStringNotContainsString('section=theme_learnlingo_homepage', $html);
    }
}
