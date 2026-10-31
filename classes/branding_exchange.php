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
 * Portable LearnLingo JSON configuration and ChatGPT instructions.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_learnlingo;

use invalid_parameter_exception;
use JsonException;

/**
 * Versioned, strictly validated configuration interchange.
 */
final class branding_exchange {
    /** Schema version. */
    public const SCHEMA_VERSION = 1;
    /** Maximum JSON payload size. */
    public const MAX_JSON_LENGTH = 400000;

    /**
     * All portable theme settings, with their types and default values.
     *
     * Uploaded logos, including SVG, remain in Moodle File API.
     *
     * @return array
     */
    public static function definitions(): array {
        return [
            'brandcolor' => ['color', '#58cc02'],
            'accentcolor' => ['color', '#8be24a'],
            'canvascolor' => ['color', '#f7faf5'],
            'textcolor' => ['color', '#263238'],
            'dashboardeyebrowtext' => ['text', ''],
            'dashboarddescriptiontext' => ['text', ''],
            'loginpaneltitletext' => ['text', ''],
            'loginpaneldescriptiontext' => ['text', ''],
            'loginpaneltaglinetext' => ['text', ''],
            'homepagemode' => ['homepage', 'learnlingo'],
            'homepagehtml' => ['html', ''],
            'homepagescss' => ['scss', ''],
            'homepagefullwidth' => ['bool', true],
            'footermode' => ['footermode', 'moodle'],
            'footerhtml' => ['html', ''],
            'footerscss' => ['scss', ''],
            'footer_background_color' => ['color', '#314755'],
            'footer_title_1' => ['text', ''],
            'footer_html_1' => ['html', ''],
            'footer_title_2' => ['text', ''],
            'footer_html_2' => ['html', ''],
            'footer_title_3' => ['text', ''],
            'footer_html_3' => ['html', ''],
            'footer_title_4' => ['text', ''],
            'footer_html_4' => ['html', ''],
            'footer_show_copywriter' => ['bool', true],
            'showdashboardhero' => ['bool', true],
            'showcoursehero' => ['bool', true],
            'courseheroeyebrow' => ['text', get_string('courseheroeyebrowdefault', 'theme_learnlingo')],
            'showcourseherosummary' => ['bool', true],
            'showcourseheroprogress' => ['bool', true],
            'showpersonalxp' => ['bool', true],
            'scsspre' => ['scss', ''],
            'scss' => ['scss', ''],
        ];
    }

    /**
     * Export current site-specific settings.
     *
     * @return array
     */
    public static function export(): array {
        $settings = [];
        foreach (self::definitions() as $name => [$type, $default]) {
            $value = get_config('theme_learnlingo', $name);
            if ($value === false || $value === null) {
                $value = $default;
            }
            $settings[$name] = $type === 'bool' ? (bool) $value : (string) $value;
        }
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'plugin' => 'theme_learnlingo',
            'repository' => 'https://github.com/EduardoKrausME/moodle-theme_learnlingo',
            'settings' => $settings,
        ];
    }

    /**
     * Encode the stable exchange format.
     *
     * @param array $data Document.
     * @return string
     */
    public static function json(array $data): string {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Build a prompt which starts a guided conversation, not an immediate rewrite.
     *
     * @param array $data Original configuration.
     * @return string
     */
    public static function prompt(array $data): string {
        global $USER;

        // Use the user's configured Moodle language for all AI responses.
        $userlang = !empty($USER->lang) ? $USER->lang : current_language();
        $instructions = <<<'PROMPT'
You are an expert in visual branding and Moodle themes, helping a user
customize a LearnLingo installation.

Plugin: theme_learnlingo
Repository: https://github.com/EduardoKrausME/moodle-theme_learnlingo

REQUIRED RESPONSE LANGUAGE
The Moodle user's configured language ($USER->lang) is: {USER_LANGUAGE}
Respond EXCLUSIVELY in this language throughout the entire conversation,
including questions, suggestions, explanations, confirmations, and summaries.
Write all new user-facing text in the final JSON in this language as well.
Do not switch languages because these instructions are written in English.
Keep JSON keys, fixed identifiers, technical syntax, and proper names unchanged.

The JSON at the end of this message was exported from a real installation.
Your goal is to INTERVIEW the user and produce a new JSON document with
EXACTLY the same structure for import into Moodle, but only after the interview.

HOW TO CONDUCT THE CONVERSATION
1. Start by asking which institution will use the theme and who the target
   audience is. If this information is already available in the conversation,
   do not ask again. Ask natural questions, usually one at a time.
2. Explore the colors: primary, accent, background, and text.
   Ask whether a light or dark appearance is preferred and suggest color
   palettes if the user is unsure.
3. Ask whether the user wants to change the logo. Logos are files managed
   by Moodle (PNG, JPG, WebP, or SVG), not JSON strings.
   Do not include SVG in the JSON; use Moodle's global logo setting
   to upload the file separately.
4. Ask which homepage mode is preferred: "moodle" (native Moodle),
   "learnlingo" (LearnLingo layout), or "html" (custom homepage).
   Explain where homepagehtml is displayed and that homepagescss is
   compiled on EVERY page, even with homepagemode="moodle".
   Ask separately whether footermode should be "moodle" (traditional
   columns and credits) or "html" (the custom footerhtml content).
   Discuss the matching HTML and SCSS fields only when relevant, but
   preserve existing values in fields hidden by the selected mode.
   Explain that footerscss affects only the custom HTML footer; it is
   not a substitute for homepagescss or the global scss setting.
5. Ask whether the user wants to change institutional copy, login
   messaging and branding, the welcome message, course header,
   student progress, XP, and other configurable features.
   Do not modify any settings the user wants to preserve.
6. Keep asking until the requirements are clear. If the user does not
   know how to answer, suggest concrete options and ask for a choice.
7. Once the requirements are clear, summarize the decisions and ask
   whether the user wants to finalize the configuration or change anything.

CONFIGURATION FIELD GUIDE - EXPLAIN THESE DISTINCTIONS TO THE USER
Use these exact names in the JSON. Explain a field in plain language when
discussing it, so the user knows what changes and where it will appear.

Colors and institutional text:
- settings.brandcolor: primary theme color for key UI elements.
- settings.accentcolor: secondary/accent color for visual highlights.
- settings.canvascolor: the main page background color.
- settings.textcolor: the base text color.
- settings.dashboardeyebrowtext: short introductory text above the
  dashboard's main heading.
- settings.dashboarddescriptiontext: supporting text on the dashboard.
- settings.loginpaneltitletext: login panel heading.
- settings.loginpaneldescriptiontext: login panel description.
- settings.loginpaneltaglinetext: short login panel slogan.

Homepage:
- settings.homepagemode: "moodle" uses the native Moodle homepage,
  "learnlingo" uses LearnLingo's homepage, and "html" displays the
  custom homepage layout.
- settings.homepagehtml: HTML shown above course cards in "learnlingo"
  mode; becomes the homepage's main content in "html" mode. It is not
  displayed in "moodle" mode. Write markup/content only, without scripts.
- settings.homepagescss: a standalone, full SCSS stylesheet, compiled
  globally on EVERY page and in ALL homepage modes. Despite its name,
  it is NOT automatically restricted to the homepage: it can style
  homepage content, the navigation menu, navbar, footer, and other
  shared elements. Use explicit homepage selectors when a rule should
  affect ONLY the homepage; use shared selectors intentionally for
  navigation or footer changes. Do NOT nest its entire stylesheet in
  a frontpage selector, and do NOT use bare "&" as its root.
  Example selectors: .learnlingo-html-home .hero, .navbar, #page-footer.
- settings.homepagefullwidth: boolean controlling whether the LearnLingo
  or custom HTML homepage uses the full available width. Not relevant
  to the native "moodle" homepage.

Footer:
- settings.footermode: "moodle" keeps the traditional footer with up
  to four configured columns and optional credits; "html" displays
  the custom footerhtml inside the site's existing footer element.
  This choice is independent of settings.homepagemode.
- settings.footerhtml: the custom footer's INNER HTML, used on ALL
  pages only when footermode="html". The theme already renders
  <footer id="page-footer"> and a .learnlingo-footer-custom-html
  wrapper. Do not include a second <footer>, <html>, <head>, or <body>,
  and do not put SCSS, <style>, scripts, or executable code here.
  Use semantic sections, paragraphs, navigation, links and images.
- settings.footerscss: SCSS for the custom HTML footer ONLY, compiled
  only when footermode="html". The theme automatically nests it under
  #page-footer.learnlingo-site-footer.learnlingo-footer-html-mode.
  Use "&" to style that root itself (e.g. & { padding: 2rem; })
  and relative selectors to style descendants (e.g.
  .learnlingo-footer-custom-html a { color: #ffffff; }).
  Do not manually create another #page-footer wrapper or use global
  body/html selectors; they will not select the intended elements
  within this scope. Nested @media rules are allowed.
- settings.footer_background_color: background color of the traditional
  "moodle" footer; not the way to style the custom HTML footer.
- settings.footer_title_1 through settings.footer_title_4: the titles
  of the four traditional footer columns, when enabled.
- settings.footer_html_1 through settings.footer_html_4: each matching
  traditional footer column's HTML content.
- settings.footer_show_copywriter: boolean for showing the traditional
  footer's credit line. Only relevant when footermode="moodle".
  In "html" mode, the traditional column, color and credit settings
  are hidden and not rendered. Preserve their original stored values
  rather than deleting them; switching back to "moodle" restores them.

Other page features:
- settings.showdashboardhero: display the dashboard hero block.
- settings.showcoursehero: display the course header/hero block.
- settings.courseheroeyebrow: short caption above the course heading.
- settings.showcourseherosummary: show the course summary in its header.
- settings.showcourseheroprogress: show progress in the course header.
- settings.showpersonalxp: display the personal XP feature.

Advanced styles:
- settings.scsspre: global pre-SCSS, loaded BEFORE Bootstrap/Boost;
  intended for Sass variables and values needed during compilation.
- settings.scss: global SCSS loaded AFTER Boost for site-wide style
  overrides, regardless of homepage/footer modes. Use it for genuinely
  shared styles, not as a workaround for the dedicated HTML-footer SCSS.
  homepagescss is ALSO globally loaded, so selectors affecting shared
  elements there do not have to be moved into settings.scss.

SCSS AND HTML RULES
- Keep HTML in homepagehtml/footerhtml and SCSS in homepagescss,
  footerscss, scss, or scsspre, as appropriate; do not mix them.
- The page-specific SCSS fields reject @at-root, @import, @use,
  @forward, @extend, @keyframes, @font-face, @charset, @namespace,
  and @property. Do not generate these directives in homepagescss
  or footerscss. Do not generate remote CSS imports anywhere.
- Style the existing HTML structure rather than duplicating native
  page/footer containers. Use responsive styling and avoid selectors
  that accidentally change unrelated Moodle pages.
- When a mode makes a field inactive, keep its original JSON value
  unless the user explicitly asks to modify it. All fields remain in
  the JSON regardless of what the administration page hides.

MANDATORY FINAL RESPONSE FORMAT
- Return only one Markdown code block with the json language identifier,
  without paragraphs before or after the block.
- Preserve EXACTLY the four top-level keys schema_version, plugin,
  repository, and settings, as well as ALL original settings keys.
- Never add, rename, or remove fields; preserve their data types.
  schema_version must remain 1; plugin and repository must not change.
- All four colors must be "#RRGGBB" strings.
- true/false fields must be JSON booleans, not strings.
- HTML, SCSS, and all other text fields must be JSON strings with
  properly escaped quotation marks and line breaks.
- Preserve all values that do not need to change.
- Do not include secrets, PHP, JavaScript, external scripts, or any
  executable content.
- Ensure the output is valid JSON with exactly the supplied structure.
  The importer rejects missing or unknown keys, invalid types, and
  unsafe SVG.

The user will copy only the contents of the final JSON and paste them
into the second text area on the LearnLingo page, then click Import settings.

ORIGINAL CONFIGURATION:
PROMPT;
        $instructions = str_replace('{USER_LANGUAGE}', $userlang, $instructions);
        $fence = str_repeat(chr(96), 3);
        return $instructions . "\n" . $fence . 'json' . "\n"
            . self::json($data) . "\n" . $fence;
    }

    /**
     * Parse and fully validate a returned JSON document.
     *
     * @param string $source JSON content without Markdown delimiters.
     * @return array Normalized document.
     */
    public static function parse(string $source): array {
        if (strlen($source) > self::MAX_JSON_LENGTH) {
            throw new invalid_parameter_exception(get_string('jsonerrorlarge', 'theme_learnlingo'));
        }
        try {
            $data = json_decode(trim($source), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new invalid_parameter_exception(get_string('jsonerrorinvalid', 'theme_learnlingo'));
        }
        if (!is_array($data) || array_is_list($data) ||
                !self::same_keys($data, ['schema_version', 'plugin', 'repository', 'settings'])) {
            throw new invalid_parameter_exception(get_string('jsonerrorstructure', 'theme_learnlingo'));
        }
        if ($data['schema_version'] !== 1 || $data['plugin'] !== 'theme_learnlingo' ||
                $data['repository'] !== 'https://github.com/EduardoKrausME/moodle-theme_learnlingo') {
            throw new invalid_parameter_exception(get_string('jsonerroridentity', 'theme_learnlingo'));
        }
        $values = $data['settings'];
        if (!is_array($values) || array_is_list($values) ||
                !self::same_keys($values, array_keys(self::definitions()))) {
            throw new invalid_parameter_exception(get_string('jsonerrorsettings', 'theme_learnlingo'));
        }

        $normalized = [];
        foreach (self::definitions() as $name => [$type, $default]) {
            $value = $values[$name];
            if ($type === 'bool') {
                if (!is_bool($value)) {
                    self::invalid_field($name);
                }
            } else {
                if (!is_string($value)) {
                    self::invalid_field($name);
                }
                $limits = ['color' => 7, 'text' => 1500, 'homepage' => 20,
                    'footermode' => 6, 'html' => 150000, 'scss' => 80000];
                if (strlen($value) > $limits[$type]) {
                    self::invalid_field($name);
                }
                if ($type === 'color' && !preg_match('/^#[A-Fa-f0-9]{6}$/D', $value)) {
                    self::invalid_field($name);
                }
                if ($type === 'homepage' && !in_array($value, ['learnlingo', 'moodle', 'html'], true)) {
                    self::invalid_field($name);
                }
                if ($type === 'footermode' && !in_array($value, ['moodle', 'html'], true)) {
                    self::invalid_field($name);
                }
                if ($type === 'text' && (str_contains($value, '<') ||
                        str_contains($value, '>') || str_contains($value, "\0"))) {
                    self::invalid_field($name);
                }
                if (($type === 'html' || $type === 'scss') &&
                        (str_contains($value, '<?') || str_contains($value, "\0"))) {
                    self::invalid_field($name);
                }
                // No remote CSS imports or executable embeds in generated content.
                if ($type === 'scss' &&
                        preg_match('~@import\s+(?:url\s*\(|[\x22\x27]https?://)~i', $value)) {
                    self::invalid_field($name);
                }
                // Prevent page-scoped SCSS from generating unscoped Sass rules.
                if (in_array($name, ['homepagescss', 'footerscss'], true) &&
                        preg_match('~@(at-root|import|use|forward|extend|keyframes|font-face|charset|namespace|property)\b~i',
                            $value)) {
                    self::invalid_field($name);
                }
                if ($type === 'html' &&
                        preg_match('~<\s*(script|iframe|object|embed|form)\b~i', $value)) {
                    self::invalid_field($name);
                }
            }
            $normalized[$name] = $value;
        }
        return [
            'schema_version' => 1,
            'plugin' => 'theme_learnlingo',
            'repository' => $data['repository'],
            'settings' => $normalized,
        ];
    }

    /**
     * Apply only whitelisted validated fields.
     *
     * @param array $data Validated document.
     * @return void
     */
    public static function apply(array $data): void {
        foreach (self::definitions() as $name => [$type, $default]) {
            $value = $data['settings'][$name];
            set_config($name, $type === 'bool' ? (int) $value : $value, 'theme_learnlingo');
        }
        theme_reset_all_caches();
    }

    /**
     * Compare object key sets regardless of JSON object ordering.
     *
     * @param array $object JSON object.
     * @param array $expected Expected keys.
     * @return bool
     */
    private static function same_keys(array $object, array $expected): bool {
        $keys = array_keys($object);
        sort($keys);
        sort($expected);
        return $keys === $expected;
    }

    /**
     * Reject a malformed field.
     *
     * @param string $name Field.
     * @return never
     */
    private static function invalid_field(string $name): never {
        throw new invalid_parameter_exception(get_string('jsonerrorfield', 'theme_learnlingo', $name));
    }
}
