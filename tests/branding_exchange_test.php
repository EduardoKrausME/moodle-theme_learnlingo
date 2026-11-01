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
 * Contract tests for the LearnLingo ChatGPT JSON exchange.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use invalid_parameter_exception;

/**
 * Round-trip validation and safe SVG checks.
 *
 * @covers \theme_learnlingo\branding_exchange
 */
final class branding_exchange_test extends advanced_testcase {
    /**
     * A freshly exported JSON can always be re-imported without schema drift.
     */
    public function test_original_json_roundtrip(): void {
        $this->resetAfterTest();
        $original = branding_exchange::export();
        $parsed = branding_exchange::parse(branding_exchange::json($original));
        $this->assertSame($original, $parsed);
        $prompt = branding_exchange::prompt($original);
        $this->assertStringContainsString('https://github.com/EduardoKrausME/moodle-theme_learnlingo', $prompt);
        $this->assertStringContainsString('schema_version', $prompt);
    }

    /**
     * No model-added settings may reach set_config.
     */
    public function test_extra_key_is_rejected(): void {
        $data = branding_exchange::export();
        $data['settings']['extra_setting'] = 'not allowed';
        $this->expectException(invalid_parameter_exception::class);
        branding_exchange::parse(branding_exchange::json($data));
    }

    /**
     * Boolean fields cannot be converted to strings.
     */
    public function test_changed_type_is_rejected(): void {
        $data = branding_exchange::export();
        $data['settings']['showcoursehero'] = 'true';
        $this->expectException(invalid_parameter_exception::class);
        branding_exchange::parse(branding_exchange::json($data));
    }

    /**
     * Reject executable SVG while allowing basic generated vector logos.
     */
    public function test_svg_must_be_static(): void {
        $valid = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 50">'
            . '<rect width="100" height="50" fill="#123456"/></svg>';
        $this->assertTrue(svg_logo::is_valid($valid));
        $this->assertStringStartsWith('data:image/svg+xml;base64,', svg_logo::data_url($valid));
        $this->assertFalse(svg_logo::is_valid('<svg xmlns="http://www.w3.org/2000/svg">'
            . '<script>alert(1)</script></svg>'));
        $this->assertFalse(svg_logo::is_valid('<svg xmlns="http://www.w3.org/2000/svg">'
            . '<image href="https://example.com/image.png"/></svg>'));
    }

    /**
     * Home HTML and SCSS are portable; logos remain in the Moodle File API.
     */
    public function test_html_home_and_scss_roundtrip(): void {
        $data = branding_exchange::export();
        $data['settings']['homepagemode'] = 'html';
        $data['settings']['homepagehtml'] = '<section><h1>Universidade Esportiva</h1></section>';
        $data['settings']['homepagescss'] = '.welcome { margin: 1rem; }';
        $data['settings']['footermode'] = 'html';
        $data['settings']['footerhtml'] = '<p>Footer content</p>';
        $data['settings']['footerscss'] = '& { padding: 2rem; }';
        $data['settings']['footer_background_color'] = '#304050';
        $data['settings']['footer_title_1'] = 'Contact';
        $data['settings']['footer_html_1'] = '<p>Office</p>';
        $data['settings']['footer_show_copywriter'] = false;
        $data['settings']['scss'] = '.learnlingo-html-home { padding: 2rem; }';
        $this->assertSame($data, branding_exchange::parse(branding_exchange::json($data)));
    }

    /**
     * Footer HTML must pass the same import validator as homepage HTML.
     */
    public function test_invalid_footer_mode_is_rejected(): void {
        $data = branding_exchange::export();
        $data['settings']['footermode'] = 'javascript';
        $this->expectException(invalid_parameter_exception::class);
        branding_exchange::parse(branding_exchange::json($data));
    }

    /**
     * Reject executable HTML in any footer field.
     */
    public function test_footer_script_is_rejected(): void {
        $data = branding_exchange::export();
        $data['settings']['footer_html_2'] = '<script>alert(1)</script>';
        $this->expectException(invalid_parameter_exception::class);
        branding_exchange::parse(branding_exchange::json($data));
    }

    /**
     * Scoped SCSS must not bypass the nesting root.
     */
    public function test_scoped_scss_rejects_at_root(): void {
        $data = branding_exchange::export();
        $data['settings']['footerscss'] = '@at-root body { display: none; }';
        $this->expectException(invalid_parameter_exception::class);
        branding_exchange::parse(branding_exchange::json($data));
    }
}
