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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

namespace theme_learnlingo;

use advanced_testcase;
use theme_config;

/**
 * Check that configurable SCSS does not leak outside its intended area.
 *
 * @package theme_learnlingo
 * @coversNothing
 */
final class scoped_scss_test extends advanced_testcase {
    /**
     * Home, HTML footer and global SCSS retain independent selectors.
     */
    public function test_each_scss_field_has_its_own_scope(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/lib.php');

        set_config('homepagemode', 'learnlingo', 'theme_learnlingo');
        set_config('footermode', 'html', 'theme_learnlingo');
        set_config('homepagescss', '.homepage-marker { padding: 1rem; }', 'theme_learnlingo');
        set_config('footerscss', '.footer-marker { margin: 2rem; }', 'theme_learnlingo');
        set_config('scss', '.global-marker { display: block; }', 'theme_learnlingo');
        theme_reset_all_caches();

        $scss = theme_learnlingo_get_extra_scss(theme_config::load('learnlingo'));
        $this->assertStringContainsString('.global-marker { display: block; }', $scss);
        $this->assertStringContainsString("body#page-site-index {\n.homepage-marker", $scss);
        $this->assertStringContainsString(
            "#page-footer.learnlingo-site-footer.learnlingo-footer-html-mode {\n.footer-marker", $scss
        );
    }

    /**
     * Saved scoped styles do not apply in modes where their editors are hidden.
     */
    public function test_native_modes_disable_scoped_scss_without_erasing_it(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/lib.php');

        set_config('homepagemode', 'moodle', 'theme_learnlingo');
        set_config('footermode', 'moodle', 'theme_learnlingo');
        set_config('homepagescss', '.homepage-marker { padding: 1rem; }', 'theme_learnlingo');
        set_config('footerscss', '.footer-marker { margin: 2rem; }', 'theme_learnlingo');
        theme_reset_all_caches();

        $scss = theme_learnlingo_get_extra_scss(theme_config::load('learnlingo'));
        $this->assertStringNotContainsString('.homepage-marker { padding: 1rem; }', $scss);
        $this->assertStringNotContainsString('.footer-marker { margin: 2rem; }', $scss);
        $this->assertSame('.homepage-marker { padding: 1rem; }',
            get_config('theme_learnlingo', 'homepagescss'));
        $this->assertSame('.footer-marker { margin: 2rem; }',
            get_config('theme_learnlingo', 'footerscss'));
    }

    /**
     * CSS custom properties on :root must be assigned to the frontpage body.
     */
    public function test_homepage_full_stylesheet_root_selectors_are_rebased(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/lib.php');

        set_config('homepagemode', 'html', 'theme_learnlingo');
        set_config('homepagescss',
            ':root{--home-ink:#123456}body#page-site-index .hero{color:var(--home-ink)}'
                . '@media(max-width:600px){body .hero{padding:0}}', 'theme_learnlingo');
        theme_reset_all_caches();

        $scss = theme_learnlingo_get_extra_scss(theme_config::load('learnlingo'));

        $this->assertStringContainsString(
            "body#page-site-index {\n&{--home-ink:#123456}& .hero{color:var(--home-ink)}", $scss
        );
        $this->assertStringContainsString('@media(max-width:600px){& .hero{padding:0}}', $scss);
        $this->assertStringNotContainsString('body#page-site-index :root', $scss);
        $this->assertStringNotContainsString('body#page-site-index body', $scss);
    }

    /**
     * Footer CSS with an explicit footer root must not target a nonexistent nested footer.
     */
    public function test_html_footer_full_stylesheet_root_selectors_are_rebased(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/lib.php');

        set_config('footermode', 'html', 'theme_learnlingo');
        set_config('footerscss',
            '#page-footer{background:#101f38}#page-footer .o90-footer{color:#fff}'
                . '@media(max-width:600px){#page-footer .o90-footer{padding:1rem}}',
            'theme_learnlingo');
        theme_reset_all_caches();

        $scss = theme_learnlingo_get_extra_scss(theme_config::load('learnlingo'));

        $this->assertStringContainsString(
            "#page-footer.learnlingo-site-footer.learnlingo-footer-html-mode {\n&{background:#101f38}"
                . '& .o90-footer{color:#fff}', $scss
        );
        $this->assertStringContainsString(
            '@media(max-width:600px){& .o90-footer{padding:1rem}}', $scss
        );
        $this->assertStringNotContainsString('#page-footer #page-footer', $scss);
    }
}
