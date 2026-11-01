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
    $footerbg = $theme->settings->footer_background_color ?? '#314755';
    if (!is_string($footerbg) || !preg_match('/^#[0-9a-fA-F]{6}$/D', $footerbg)) {
        $footerbg = '#314755';
    }
    // W3C relative luminance determines whether the footer needs light text.
    $rgb = [hexdec(substr($footerbg, 1, 2)), hexdec(substr($footerbg, 3, 2)),
        hexdec(substr($footerbg, 5, 2))];
    $linear = array_map(static function($value) {
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
 * Scope an author's SCSS while recognising selectors that refer to the scope root.
 *
 * A complete CSS stylesheet can start with :root, body or #page-footer.
 * Blindly nesting these selectors creates descendants that cannot match the DOM.
 * Rebase only root selectors to Sass's parent selector (&); leave ordinary selectors
 * and nested @media rules intact.
 *
 * @param string $scss Configured CSS/SCSS.
 * @param string $scope Selector limiting where it may apply.
 * @param string[] $rootselectors Root selectors accepted by this scope, longest first.
 * @return string
 */
function theme_learnlingo_scope_scss(string $scss, string $scope, array $rootselectors): string {
    $escaped = array_map(static function(string $selector): string {
        return preg_quote($selector, '/');
    }, $rootselectors);
    // Match selector boundaries, not similar text inside a class name or property value.
    $pattern = '/(^|[{},])(\s*)(?:' . implode('|', $escaped)
        . ')(?=(?:\s*[\{.#:\[>,+~]|\s+[a-zA-Z*]))/m';
    $normalized = preg_replace($pattern, '$1$2&', $scss);

    return "\n{$scope} {\n{$normalized}\n}\n";
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

    // The existing SCSS setting remains global for backwards compatibility.
    if (!empty($theme->settings->scss)) {
        $scss .= "\n{$theme->settings->scss}";
    }

    // Homepage SCSS is a full stylesheet: it also styles shared navigation and footer elements.
    // Do not wrap it in a frontpage selector or disable it when the Moodle homepage mode is selected.
    if (!empty($theme->settings->homepagescss)) {
        $scss .= "\n{$theme->settings->homepagescss}";
    }

    // HTML footer styles must target the existing #page-footer, not a nested copy.
    if (($theme->settings->footermode ?? 'moodle') === 'html' && !empty($theme->settings->footerscss)) {
        $scss .= theme_learnlingo_scope_scss(
            $theme->settings->footerscss,
            '#page-footer.learnlingo-site-footer.learnlingo-footer-html-mode',
            [
                '#page-footer.learnlingo-site-footer.learnlingo-footer-html-mode',
                '#page-footer.learnlingo-site-footer',
                '#page-footer',
                ':root',
            ]
        );
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


/**
 * Open LearnLingo's visual setup after a new plugin installation finishes.
 *
 * This callback is intentionally limited to a plain GET of admin/index.php,
 * when the site is fully installed, not upgrading, and the current user is
 * an authenticated site administrator. It does not alter core admin files.
 *
 * @return void
 */
function theme_learnlingo_after_config(): void {
    global $CFG;

    if ((defined('CLI_SCRIPT') && CLI_SCRIPT)
            || (defined('PHPUNIT_TEST') && PHPUNIT_TEST)
            || (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING)
            || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        return;
    }

    // Avoid database queries on every request. The callback runs site-wide.
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $adminpath = '/' . ($CFG->admin ?? 'admin') . '/index.php';
    if (!str_ends_with($script, $adminpath)) {
        return;
    }

    // Never interrupt the Moodle install/upgrade/confirmation workflow.
    foreach (['confirmplugincheck', 'confirmupgrade', 'sessionstarted', 'cache', 'agreelicense'] as $parameter) {
        if (isset($_GET[$parameter])) {
            return;
        }
    }
    if (during_initial_install() || !empty($CFG->upgraderunning)
            || !empty($CFG->maintenance_enabled) || moodle_needs_upgrading()) {
        return;
    }

    if ((string)get_config('theme_learnlingo', 'setup_pending') !== '1'
            || !isloggedin() || isguestuser() || !is_siteadmin()) {
        return;
    }

    redirect(new moodle_url('/theme/learnlingo/admin/setup.php'));
}
