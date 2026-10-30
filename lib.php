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
 * Theme callbacks.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns SCSS inserted before Boost's main SCSS.
 *
 * Use scss/pre.scss for variables that need to exist before Bootstrap is compiled.
 *
 * @param theme_config $theme Theme configuration.
 * @return string
 */
function theme_learnlingo_get_pre_scss(theme_config $theme): string {
    global $CFG;

    $brandcolor = !empty($theme->settings->brandcolor) ? $theme->settings->brandcolor : "#58cc02";
    $accentcolor = !empty($theme->settings->accentcolor) ? $theme->settings->accentcolor : "#8be24a";

    $canvascolor = !empty($theme->settings->canvascolor) ? $theme->settings->canvascolor : '#f7faf5';
    $textcolor = !empty($theme->settings->textcolor) ? $theme->settings->textcolor : '#263238';

    $scss = "\$primary: {$brandcolor};\n";
    $scss .= "\$learnlingo-accent: {$accentcolor};\n";
    $scss .= "\$body-bg: {$canvascolor};\n";
    $scss .= "\$body-color: {$textcolor};\n";
    $scss .= "\$headings-color: {$textcolor};\n";
    $scss .= file_get_contents("{$CFG->dirroot}/theme/learnlingo/scss/_pre.scss");

    if (!empty($theme->settings->scsspre)) {
        $scss .= "\n{$theme->settings->scsspre}";
    }

    return $scss;
}

/**
 * Returns SCSS inserted after Boost's main SCSS.
 *
 * Use scss/post.scss for LearnLingo component styles and visual overrides.
 *
 * @param theme_config $theme Theme configuration.
 * @return string
 */
function theme_learnlingo_get_extra_scss(theme_config $theme): string {
    global $CFG;

    $scss = file_get_contents("{$CFG->dirroot}/theme/learnlingo/scss/_poss.scss");

    if (!empty($theme->settings->scss)) {
        $scss .= "\n{$theme->settings->scss}";
    }

    return $scss;
}

/**
 * URL for the shared Moodle site logo or the theme-specific login logo.
 *
 * @param theme_config $theme Theme settings.
 * @param string $area File area.
 * @return string|null
 */
function theme_learnlingo_logo_url(theme_config $theme, string $area): ?string {
    if ($area === 'logo') {
        global $OUTPUT;
        $url = $OUTPUT->get_logo_url();
        return $url ? $url->out(false) : null;
    }
    $url = $theme->setting_file_url($area, $area);
    return $url ?: null;
}

/**
 * Serves the theme-specific login logo (the site logo is served by core_admin).
 * @param $course
 * @param $cm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param array $options
 * @throws \core\exception\coding_exception
 * @throws moodle_exception
 */
function theme_learnlingo_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []): void {
    if ($context->contextlevel !== CONTEXT_SYSTEM) {
        send_file_not_found();
    }
    // Moodle adds the theme revision to the URL. The core helper removes it
    // and resolves the configured file in the system context.
    theme_config::load('learnlingo')->setting_file_serve($filearea, $args, $forcedownload, $options);
}

/**
 * Return configurable institutional text, with translated defaults.
 *
 * @param string $key Configuration key.
 * @param string $fallback Moodle language string identifier.
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 */
function theme_learnlingo_brand_text(string $key, string $fallback): string {
    $configured = get_config('theme_learnlingo', $key);
    return is_string($configured) && trim($configured) !== ''
        ? $configured : get_string($fallback, 'theme_learnlingo');
}

/**
 * Resolve the site homepage mode, preserving the pre-existing LearnLingo home.
 *
 * @param theme_config $theme Active theme configuration.
 * @return string 'learnlingo', 'moodle' or 'html'.
 */
function theme_learnlingo_homepage_mode(theme_config $theme): string {
    $mode = (string) ($theme->settings->homepagemode ?? 'learnlingo');
    return in_array($mode, ['learnlingo', 'moodle', 'html'], true) ? $mode : 'learnlingo';
}

/**
 * Get configured home HTML (it is cleaned only when rendered).
 *
 * @param theme_config $theme Active theme configuration.
 * @return string
 */
function theme_learnlingo_homepage_html(theme_config $theme): string {
    return $theme->settings->homepagehtml ?? '';
}
