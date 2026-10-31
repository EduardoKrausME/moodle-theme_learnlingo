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
 * MCP write access to all LearnLingo theme settings.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_learnlingo\mcp;

use context;
use context_system;
use local_mcp\security\authenticated_identity;
use local_mcp\write\tool\base_tool;
use theme_learnlingo\branding_exchange;

/**
 * Apply validated partial changes, with confirmation and optimistic locking.
 */
final class update_settings extends base_tool {
    /**
     * Return the MCP tool identifier.
     *
     * @return string Result.
     */
    public function get_name(): string {
        return 'learnlingo_update_settings';
    }

    /**
     * Return the human-readable title.
     *
     * @return string Result.
     */
    public function get_title(): string {
        return 'Update LearnLingo settings';
    }

    /**
     * Describe this tool.
     *
     * @return string Result.
     */
    public function get_description(): string {
        return 'Update LearnLingo colors, homepage modes and HTML, homepagescss '
            . '(global on every page), scsspre (before Boost), scss (global '
            . 'after Boost), footermode (moodle or html), footerhtml (inner HTML '
            . 'of the existing site footer) and footerscss (only the HTML footer), '
            . 'traditional footer columns/credits and login/dashboard/course '
            . 'options. Homepage and footer modes are independent. Only specified '
            . 'settings change; hidden/inactive fields retain their values. '
            . 'Preview and explicit confirmation are required. Read '
            . 'learnlingo_get_settings first and pass state_hash as expected_hash. '
            . 'Site logos and favicons use core_admin and can be replaced or removed '
            . 'through images.logo or images.favicon (Base64 or public HTTPS).';
    }

    /**
     * Return the required capability.
     *
     * @return string Result.
     */
    public function get_required_capability(): string {
        return 'moodle/site:config';
    }

    /**
     * Return the input JSON schema.
     *
     * @return array Result.
     */
    public function get_input_schema(): array {
        // Keep MCP field explanations in sync with the actual theme rendering behavior.
        $descriptions = [
            'brandcolor' => 'Primary brand color used by theme components.',
            'accentcolor' => 'Secondary brand/accent color for highlighted elements.',
            'canvascolor' => 'Main page background/canvas color.',
            'textcolor' => 'Default text and heading color.',
            'dashboardeyebrowtext' => 'Short label above the dashboard hero heading.',
            'dashboarddescriptiontext' => 'Supporting description in the dashboard hero.',
            'loginpaneltitletext' => 'Title displayed on the login panel.',
            'loginpaneldescriptiontext' => 'Description displayed on the login panel.',
            'loginpaneltaglinetext' => 'Short slogan displayed on the login panel.',
            'homepagemode' => 'moodle: native homepage; learnlingo: theme homepage; '
                . 'html: custom HTML homepage. Independent of footermode.',
            'homepagehtml' => 'HTML above course cards in learnlingo mode, '
                . 'main homepage content in html mode, hidden in moodle mode. '
                . 'Use HTML markup without scripts.',
            'homepagescss' => 'Global SCSS loaded on EVERY page in ALL homepage modes. '
                . 'Can style the home, navbar, menus and footer. NOT automatically '
                . 'scoped to home: use explicit homepage selectors for home-only rules. '
                . 'Do not use bare & at stylesheet root or remote imports.',
            'homepagefullwidth' => 'Use full available homepage width in learnlingo '
                . 'or html mode; does not control the native moodle homepage.',
            'footermode' => 'moodle: traditional four-column footer with optional '
                . 'credits; html: custom footerhtml on every page. Independent '
                . 'of homepagemode. Inactive footer fields retain their values.',
            'footerhtml' => 'INNER HTML inserted into the existing page-footer '
                . 'element and learnlingo-footer-custom-html wrapper on every page '
                . 'ONLY when footermode=html. Do not add another <footer>, '
                . '<html>, <head>, <body>, <style> or scripts; place CSS in footerscss.',
            'footerscss' => 'SCSS active ONLY with footermode=html and automatically '
                . 'nested under #page-footer.learnlingo-site-footer.learnlingo-footer-html-mode. '
                . 'Use & { ... } for the footer root and relative descendant selectors '
                . 'for its children; do not wrap another #page-footer or use body/html '
                . 'selectors. Affects the custom footer on every page, not the rest of the site.',
            'footer_background_color' => 'Background color for the traditional '
                . 'moodle footer; for a custom HTML footer use footerscss.',
            'footer_title_1' => 'Heading for traditional Moodle footer column 1; '
                . 'hidden and not rendered when footermode=html.',
            'footer_html_1' => 'HTML content for traditional Moodle footer column 1; '
                . 'hidden and not rendered when footermode=html.',
            'footer_title_2' => 'Heading for traditional Moodle footer column 2; '
                . 'hidden and not rendered when footermode=html.',
            'footer_html_2' => 'HTML content for traditional Moodle footer column 2; '
                . 'hidden and not rendered when footermode=html.',
            'footer_title_3' => 'Heading for traditional Moodle footer column 3; '
                . 'hidden and not rendered when footermode=html.',
            'footer_html_3' => 'HTML content for traditional Moodle footer column 3; '
                . 'hidden and not rendered when footermode=html.',
            'footer_title_4' => 'Heading for traditional Moodle footer column 4; '
                . 'hidden and not rendered when footermode=html.',
            'footer_html_4' => 'HTML content for traditional Moodle footer column 4; '
                . 'hidden and not rendered when footermode=html.',
            'footer_show_copywriter' => 'Show built-in credit line in traditional '
                . 'moodle footer only; not rendered when footermode=html.',
            'showdashboardhero' => 'Display the introductory dashboard hero.',
            'showcoursehero' => 'Display the course header/hero.',
            'courseheroeyebrow' => 'Short caption above the course hero heading.',
            'showcourseherosummary' => 'Show course summary in the course hero.',
            'showcourseheroprogress' => 'Show course progress in the course hero.',
            'showpersonalxp' => 'Show the Personal XP feature.',
            'scsspre' => 'Global pre-SCSS compiled BEFORE Boost/Bootstrap; use '
                . 'for Sass variables and compile-time overrides.',
            'scss' => 'Global SCSS compiled AFTER Boost on all pages, regardless '
                . 'of homepage or footer modes. homepagescss is also global.',
        ];
        $properties = [];
        foreach (branding_exchange::definitions() as $name => [$type, $default]) {
            $property = [
                'type' => $type === 'bool' ? 'boolean' : 'string',
                'description' => $descriptions[$name] ?? $name . ' (' . $type . ')',
            ];
            if ($type === 'color') {
                $property['pattern'] = '^#[A-Fa-f0-9]{6}$';
            } else if ($type === 'homepage') {
                $property['enum'] = ['learnlingo', 'moodle', 'html'];
            } else if ($type === 'footermode') {
                $property['enum'] = ['moodle', 'html'];
            }
            $properties[$name] = $property;
        }
        $image = [
            'type' => 'object',
            'properties' => [
                'image_base64' => ['type' => 'string', 'description' => 'Base64 PNG or JPEG (5 MiB maximum).'],
                'image_url' => ['type' => 'string', 'description' => 'Public HTTPS URL of a PNG or JPEG image.'],
                'remove' => ['type' => 'boolean', 'description' => 'Set true to remove the uploaded image.'],
            ],
            'additionalProperties' => false,
        ];
        return $this->object_schema([
            'settings' => [
                'type' => 'object',
                'description' => 'Only the settings to change. Other values are preserved, '
                    . 'including footer fields hidden by the currently selected mode.',
                'properties' => (object)$properties,
                'additionalProperties' => false,
            ],
            'images' => [
                'type' => 'object',
                'description' => 'Upload, replace or remove site branding images. Provide expected_hash. '
                    . 'Only the supplied file areas change; logo and favicon are independent.',
                'properties' => [
                    'logo' => $image,
                    'favicon' => $image,
                ],
                'additionalProperties' => false,
            ],
            'expected_hash' => [
                'type' => 'string',
                'description' => 'state_hash from learnlingo_get_settings for concurrent-change protection.',
            ],
        ]);
    }

    /**
     * Resolve the security context.
     *
     * @param array $arguments Input arguments.
     * @return context Result.
     */
    public function resolve_context(array $arguments): context {
        return context_system::instance();
    }

    /**
     * Indicate whether previews are supported.
     *
     * @return bool Result.
     */
    public function supports_dry_run(): bool {
        return true;
    }

    /**
     * Preview the requested changes.
     *
     * @param array $arguments Input arguments.
     * @param authenticated_identity $identity Connected user.
     * @return array Result.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        $prepared = settings_service::prepare($arguments);
        $settings = [];
        foreach ($prepared['settings'] as $name => $value) {
            $old = $prepared['before']['settings'][$name];
            $settings[$name] = [
                'type' => gettype($value),
                'bytes_before' => is_string($old) ? strlen($old) : null,
                'bytes_after' => is_string($value) ? strlen($value) : null,
            ];
        }
        $images = [];
        foreach ($prepared['images'] as $name => $image) {
            $images[$name] = [
                'action' => $image['remove'] ? 'remove' : 'replace',
                'mimetype' => $image['mime'] ?? null,
                'bytes' => isset($image['bytes']) ? strlen($image['bytes']) : 0,
            ];
        }
        return [
            'changed_settings' => array_keys($prepared['settings']),
            'settings_summary' => $settings,
            'images_summary' => $images,
            'requires_confirmation' => true,
        ];
    }

    /**
     * Execute the validated operation.
     *
     * @param array $arguments Input arguments.
     * @param authenticated_identity $identity Connected user.
     * @return array Result.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        $prepared = settings_service::prepare($arguments);
        settings_service::apply($prepared, $identity->userid);
        return [
            'success' => true,
            'changed_settings' => array_keys($prepared['settings']),
            'changed_images' => array_keys($prepared['images']),
            'state_hash' => settings_service::snapshot()['state_hash'],
        ];
    }
}
