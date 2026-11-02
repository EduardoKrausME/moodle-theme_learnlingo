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
 * Tests for the modern colour setting.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;

/**
 * Moodle's colour validation and stored config remain compatible with the new editor.
 *
 * @covers \theme_learnlingo\admin_setting_configcolourmodern
 */
final class colour_settings_test extends advanced_testcase {
    /**
     * The new setting continues to use Moodle's normal persistence and validation.
     */
    public function test_colour_setting_uses_core_storage_and_validation(): void {
        $this->resetAfterTest();
        $setting = new admin_setting_configcolourmodern(
            'theme_learnlingo/brandcolor', 'Primary', '', '#58cc02'
        );

        $this->assertInstanceOf(\admin_setting_configcolourpicker::class, $setting);
        $this->assertSame('', $setting->write_setting('#123456'));
        $this->assertSame('#123456', get_config('theme_learnlingo', 'brandcolor'));
        $this->assertNotSame('', $setting->write_setting('not-a-valid-colour'));
        $this->assertSame('#123456', get_config('theme_learnlingo', 'brandcolor'));

        // The core setting accepts valid CSS colour formats as well as hex values.
        $this->assertSame('', $setting->write_setting('rgb(11, 22, 33)'));
        $this->assertSame('rgb(11, 22, 33)', get_config('theme_learnlingo', 'brandcolor'));
    }

    /**
     * HTML preserves stored values, uses safe native input colours, and escapes user input.
     */
    public function test_colour_setting_renders_accessible_form(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $setting = new admin_setting_configcolourmodern(
            'theme_learnlingo/brandcolor', 'Primary', 'My theme colour', '#58cc02'
        );

        $html = $setting->output_html('#abc');
        $this->assertStringContainsString('data-ll-colour-picker', $html);
        $this->assertStringContainsString('data-default="#58CC02"', $html);
        $this->assertStringContainsString('value="#abc"', $html);
        $this->assertStringContainsString('value="#AABBCC"', $html);
        $this->assertStringContainsString('type="color"', $html);
        $this->assertStringContainsString('aria-pressed="false"', $html);

        $html = $setting->output_html('rgb(11, 22, 33)');
        $this->assertStringContainsString('value="rgb(11, 22, 33)"', $html);
        $this->assertStringContainsString('value="#58CC02"', $html);

        $html = $setting->output_html('"><script>alert(1)</script>');
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * New labels must be available in both of the theme's bundled languages.
     */
    public function test_colour_picker_strings_are_translated(): void {
        global $CFG;

        $identifiers = [
            'colourcustom', 'colourhexvalue', 'colourhexhelp', 'colourinvalid',
            'colourpalette', 'colourpreset', 'colourpreview', 'colourreset',
        ];
        foreach (['en', 'pt_br'] as $lang) {
            $string = [];
            require($CFG->dirroot . '/theme/learnlingo/lang/' . $lang . '/theme_learnlingo.php');
            foreach ($identifiers as $id) {
                $this->assertArrayHasKey($id, $string, "Missing {$lang} string: {$id}");
            }
        }
    }
}
