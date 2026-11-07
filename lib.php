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

use theme_learnlingo\homepage_context;

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
    $footerbg = $theme->settings->footer_background_color ?? '#314755';
    if (!is_string($footerbg) || !preg_match('/^#[0-9a-fA-F]{6}$/D', $footerbg)) {
        $footerbg = '#314755';
    }
    // W3C relative luminance determines whether the footer needs light text.
    $rgb = [hexdec(substr($footerbg, 1, 2)), hexdec(substr($footerbg, 3, 2)),
        hexdec(substr($footerbg, 5, 2))];
    $linear = array_map(static function ($value) {
        $channel = $value / 255;
        return $channel <= 0.04045 ? $channel / 12.92 : pow(($channel + 0.055) / 1.055, 2.4);
    }, $rgb);
    $luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    $footerink = $luminance > 0.179 ? '#263238' : '#ffffff';
    $scss .= "\$learnlingo-footer-bg: {$footerbg};\n";
    $scss .= "\$learnlingo-footer-ink: {$footerink};\n";
    $scss .= file_get_contents("{$CFG->dirroot}/theme/learnlingo/scss/_pre.scss");

    if (!empty($theme->settings->scsspre)) {
        $scss .= "\n{$theme->settings->scsspre}";
    }

    return $scss;
}

/**
 * Nest component SCSS under its DOM root while rebasing explicit root selectors.
 *
 * @param string $scss Configured component stylesheet.
 * @param string $scope Component root selector.
 * @param string[] $rootselectors Accepted document/component root selectors.
 * @return string
 */
function theme_learnlingo_scope_scss(string $scss, string $scope, array $rootselectors): string {
    // Dark-mode selectors describe ancestors of the component.
    $themeattribute = '(?:html|body)?\\[data-bs-theme\\s*=\\s*'
        . '(?:"(?:dark|light)"|\\x27(?:dark|light)\\x27|(?:dark|light))\\]';
    $themedpattern = '/(^|[{},])(\\s*)(' . $themeattribute
        . ')(\\s+)(?=[.#a-zA-Z*:])/m';
    $scss = preg_replace($themedpattern, '$1$2$3 &$4', $scss);

    // Prefer longer roots, so body#page-site-index wins over body.
    usort($rootselectors, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
    $escaped = array_map(static fn(string $selector): string => preg_quote($selector, '/'), $rootselectors);
    $pattern = '/(^|[{},])(\\s*)(?:' . implode('|', $escaped)
        . ')(?!\\[data-bs-theme)(?=(?:\\s*[\\{.#:\\[>,+~]|\\s+[a-zA-Z*]))/m';
    $normalized = preg_replace($pattern, '$1$2&', $scss);

    return "\n{$scope} {\n{$normalized}\n}\n";
}

/**
 * Reject directives that could emit styles outside the component's selector.
 *
 * @param string $scss Configured SCSS.
 * @return bool
 */
function theme_learnlingo_safe_component_scss(string $scss): bool {
    return !preg_match('~@(at-root|import|use|forward|extend|keyframes|font-face|charset|namespace|property)\\b~i',
        $scss);
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

    // Admin page-tree styling belongs to the theme stylesheet, not to settings.php.
    // Moodle also reads theme settings after the page head has already been output.
    $scss .= "\n" . file_get_contents("{$CFG->dirroot}/theme/learnlingo/admin/styles/pages.css");

    // The existing SCSS setting remains global for backwards compatibility.
    if (!empty($theme->settings->scss)) {
        $scss .= "\n{$theme->settings->scss}";
    }

    // Global SCSS is the only editor that may modify shared navbar and layout styles.
    if (!empty($theme->settings->homepagescss)) {
        if (theme_learnlingo_safe_component_scss($theme->settings->homepagescss)) {
            $scss .= theme_learnlingo_scope_scss(
                $theme->settings->homepagescss,
                'body#page-site-index .learnlingo-home',
                ['body#page-site-index .learnlingo-home', 'body#page-site-index',
                    '.learnlingo-home', ':root', 'body', 'html']
            );
        } else {
            debugging('LearnLingo: unsupported directive in homepagescss.', DEBUG_DEVELOPER);
        }
    }

    // Custom footer SCSS is limited to the existing HTML footer element.
    if (($theme->settings->footermode ?? 'moodle') === 'html' && !empty($theme->settings->footerscss)) {
        if (theme_learnlingo_safe_component_scss($theme->settings->footerscss)) {
            $scss .= theme_learnlingo_scope_scss(
                $theme->settings->footerscss,
                '#page-footer.learnlingo-site-footer.learnlingo-footer-html-mode',
                ['#page-footer.learnlingo-site-footer.learnlingo-footer-html-mode',
                    '#page-footer.learnlingo-site-footer', '#page-footer', ':root', 'body', 'html']
            );
        } else {
            debugging('LearnLingo: unsupported directive in footerscss.', DEBUG_DEVELOPER);
        }
    }

    // Page-specific styles are compiled with Boost and scoped to an individual page.
    // This avoids executing a Sass compiler on every anonymous page request.
    global $DB;
    if ($DB->get_manager()->table_exists('theme_learnlingo_pages')) {
        $styles = $DB->get_records_select('theme_learnlingo_pages',
            'scss IS NOT NULL', null, 'id ASC', 'id,scss');
        foreach ($styles as $page) {
            if (trim((string)$page->scss) === '') {
                continue;
            }
            $scss .= theme_learnlingo_scope_scss((string)$page->scss,
                'body.learnlingo-page-' . (int)$page->id, ['body', ':root']);
        }
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
    // Images uploaded through the visual editor are public assets used by
    // the homepage, footer and published custom pages.
    if ($filearea === 'editor_assets') {
        $itemid = (int)array_shift($args);
        $filename = array_shift($args);
        if ($itemid < 1 || !$filename || $args) {
            send_file_not_found();
        }
        $file = get_file_storage()->get_file($context->id, 'theme_learnlingo',
            'editor_assets', $itemid, '/', $filename);
        if (!$file || $file->is_directory()) {
            send_file_not_found();
        }
        send_stored_file($file, 86400, 0, $forcedownload, $options);
        return;
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
    $mode = (string)($theme->settings->homepagemode ?? 'learnlingo');
    return in_array($mode, ['learnlingo', 'moodle', 'html'], true) ? $mode : 'learnlingo';
}

/**
 * Get configured home HTML (it is cleaned only when rendered).
 *
 * @param theme_config $theme Active theme configuration.
 * @return string
 */
function theme_learnlingo_homepage_html(theme_config $theme): string {
    $html = (string)($theme->settings->homepagehtml ?? '');
    // Fresh installations store this exact sentinel to open the visual wizard
    // for administrators. Never expose it as literal homepage content.
    return $html === 'config' ? '' : $html;
}

/**
 * Invalidate the guest catalogue after home settings change in the admin UI.
 *
 * @return void
 */
function theme_learnlingo_homepage_cache_reset(): void {
    homepage_context::purge_public_cache();
    theme_reset_all_caches();
}
