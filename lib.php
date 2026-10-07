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

    $brandcolor = !empty($theme->settings->brandcolor) ? $theme->settings->brandcolor : "#ef3340";
    $accentcolor = !empty($theme->settings->accentcolor) ? $theme->settings->accentcolor : "#ff6670";

    $canvascolor = !empty($theme->settings->canvascolor) ? $theme->settings->canvascolor : '#f5f6f8';
    $textcolor = !empty($theme->settings->textcolor) ? $theme->settings->textcolor : '#252a37';

    $scss = "\$primary: {$brandcolor};\n";
    $scss .= "\$pulsomed-accent: {$accentcolor};\n";
    $scss .= "\$body-bg: {$canvascolor};\n";
    $scss .= "\$body-color: {$textcolor};\n";
    $scss .= "\$headings-color: {$textcolor};\n";
    $scss .= file_get_contents("{$CFG->dirroot}/theme/learnlingo/scss/_pre.scss");

    if (!empty($theme->settings->scsspre)) {
        $scss .= PHP_EOL . $theme->settings->scsspre;
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

    $scss = file_get_contents("{$CFG->dirroot}/theme/learnlingo/scss/_post.scss");

    if (!empty($theme->settings->scss)) {
        $scss .= "\n{$theme->settings->scss}";
    }

    return $scss;
}

/**
 * URL for an uploaded theme logo.
 *
 * @param theme_config $theme Theme settings.
 * @param string $area File area.
 * @return string|null
 */
function theme_learnlingo_logo_url(theme_config $theme, string $area): ?string {
    $url = $theme->setting_file_url($area, $area);
    return $url ? (string) $url : null;
}

/**
 * Serves logos uploaded through the theme configuration.
 */
function theme_learnlingo_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []): void {
    if ($context->contextlevel !== CONTEXT_SYSTEM || !in_array($filearea, ['logo', 'loginlogo'], true)) {
        send_file_not_found();
    }
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
    $file = get_file_storage()->get_file($context->id, 'theme_learnlingo', $filearea, 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }
    send_stored_file($file, 86400, 0, false, $options);
}
