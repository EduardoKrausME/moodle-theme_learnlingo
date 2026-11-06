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
use moodle_exception;

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
     * Partial JSON updates only the provided setting, preserving others.
     */
    public function test_partial_import_updates_only_submitted_fields(): void {
        $this->resetAfterTest();
        set_config('brandcolor', '#112233', 'theme_learnlingo');
        set_config('accentcolor', '#445566', 'theme_learnlingo');

        $validation = branding_exchange::validate_import('{"settings":{"brandcolor":"#abcdef"}}');
        $this->assertSame(['brandcolor' => '#abcdef'], $validation['valid']);
        $this->assertSame([], $validation['issues']);

        $result = branding_exchange::apply_import($validation['valid']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(0, $result['unchanged']);
        $this->assertSame('#abcdef', get_config('theme_learnlingo', 'brandcolor'));
        $this->assertSame('#445566', get_config('theme_learnlingo', 'accentcolor'));
    }

    /**
     * Invalid and unknown fields are reported without blocking valid settings.
     */
    public function test_invalid_fields_do_not_block_valid_settings(): void {
        $this->resetAfterTest();
        set_config('showwelcome', 1, 'theme_learnlingo');
        set_config('footerscss', '.old { color: red; }', 'theme_learnlingo');

        $data = [
            'settings' => [
                'brandcolor' => '#123abc',
                'showwelcome' => 'false',
                'footerscss' => '@import "remote.css";',
                'extra_field' => 'unknown',
            ],
        ];
        $validation = branding_exchange::validate_import(branding_exchange::json($data));

        $this->assertSame(['brandcolor' => '#123abc'], $validation['valid']);
        $this->assertCount(3, $validation['issues']);
        $this->assertSame('settings.showwelcome', $validation['issues'][0]['name']);
        $this->assertSame('"false"', $validation['issues'][0]['value']);
        $this->assertSame('settings.footerscss', $validation['issues'][1]['name']);
        $this->assertSame('settings.extra_field', $validation['issues'][2]['name']);

        $result = branding_exchange::apply_import($validation['valid']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame('#123abc', get_config('theme_learnlingo', 'brandcolor'));
        $this->assertSame('1', (string)get_config('theme_learnlingo', 'showwelcome'));
        $this->assertSame('.old { color: red; }', get_config('theme_learnlingo', 'footerscss'));
    }

    /**
     * Long HTML and SCSS must not be rejected by arbitrary byte limits.
     */
    public function test_large_html_and_scss_fields_are_accepted(): void {
        $data = [
            'settings' => [
                'homepagehtml' => str_repeat('<p>Exemplo</p>', 15000),
                'homepagescss' => str_repeat('.example { padding: 1px; }', 4000),
            ],
        ];
        $validation = branding_exchange::validate_import(branding_exchange::json($data));
        $this->assertSame([], $validation['issues']);
        $this->assertSame($data['settings'], $validation['valid']);
    }

    /**
     * The importer must not impose its former 400 KB JSON payload limit.
     */
    public function test_large_json_payload_is_accepted(): void {
        $source = branding_exchange::json([
            'settings' => ['homepagehtml' => str_repeat('<p>Content</p>', 35000)],
        ]);
        $this->assertGreaterThan(400000, strlen($source));
        $validation = branding_exchange::validate_import($source);
        $this->assertSame([], $validation['issues']);
        $this->assertSame(str_repeat('<p>Content</p>', 35000), $validation['valid']['homepagehtml']);
    }

    /**
     * Invalid identity metadata rejects the import before changes.
     */
    public function test_identity_mismatch_is_rejected(): void {
        $this->expectException(moodle_exception::class);
        branding_exchange::validate_import('{"schema_version":99,"settings":{"brandcolor":"#abcdef"}}');
    }

    /**
     * Malformed input rejects the import instead of partially changing settings.
     */
    public function test_invalid_json_is_rejected(): void {
        $this->expectException(moodle_exception::class);
        branding_exchange::validate_import('{"settings":');
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
