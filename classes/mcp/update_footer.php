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
 * MCP WRITE tool for the complete LearnLingo footer configuration.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\mcp;

use context;
use context_system;
use local_mcp\exception\api_exception;
use local_mcp\security\authenticated_identity;
use local_mcp\write\tool\base_tool;
use theme_learnlingo\branding_exchange;

/**
 * Update the thirteen footer fields without modifying unrelated settings.
 *
 * The settings_service retains validation, concurrency protection and database
 * transaction handling, while local_mcp handles explicit write confirmation.
 */
final class update_footer extends base_tool {
    /**
     * User-facing descriptions of every footer setting.
     */
    private const FIELDS = [
        'footermode' => 'moodle: show the traditional footer with up to four columns '
            . 'and optional credits; html: show footerhtml. Independent of homepagemode. '
            . 'Switching modes preserves the inactive mode configuration.',
        'footerhtml' => 'Custom INNER HTML shown on every page only with footermode=html. '
            . 'The theme already renders <footer id="page-footer"> and '
            . '.learnlingo-footer-custom-html. Do not add another footer element, '
            . 'style tags or scripts. Place footer styles in footerscss.',
        'footerscss' => 'SCSS used only when footermode=html. The theme scopes it under '
            . '#page-footer.learnlingo-site-footer.learnlingo-footer-html-mode. '
            . 'Use & { ... } for the footer itself and relative descendant selectors '
            . 'for its content; avoid body/html selectors and remote imports.',
        'footer_background_color' => 'Background color (#RRGGBB) of the traditional '
            . 'moodle footer. For the HTML footer set colors in footerscss.',
        'footer_title_1' => 'Title of column 1 in the traditional moodle footer. '
            . 'Not rendered in html mode, but its stored value is preserved.',
        'footer_html_1' => 'HTML content for column 1 of the traditional moodle footer. '
            . 'Not rendered in html mode, but its stored value is preserved.',
        'footer_title_2' => 'Title of column 2 in the traditional moodle footer. '
            . 'Not rendered in html mode, but its stored value is preserved.',
        'footer_html_2' => 'HTML content for column 2 of the traditional moodle footer. '
            . 'Not rendered in html mode, but its stored value is preserved.',
        'footer_title_3' => 'Title of column 3 in the traditional moodle footer. '
            . 'Not rendered in html mode, but its stored value is preserved.',
        'footer_html_3' => 'HTML content for column 3 of the traditional moodle footer. '
            . 'Not rendered in html mode, but its stored value is preserved.',
        'footer_title_4' => 'Title of column 4 in the traditional moodle footer. '
            . 'Not rendered in html mode, but its stored value is preserved.',
        'footer_html_4' => 'HTML content for column 4 of the traditional moodle footer. '
            . 'Not rendered in html mode, but its stored value is preserved.',
        'footer_show_copywriter' => 'Boolean controlling the built-in footer credit '
            . 'line when footermode=moodle. Not rendered in html mode.',
    ];

    /**
     * MCP tool name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'learnlingo_update_footer';
    }

    /**
     * Human-readable title.
     *
     * @return string
     */
    public function get_title(): string {
        return 'Update LearnLingo footer';
    }

    /**
     * Tool instructions for MCP clients.
     *
     * @return string
     */
    public function get_description(): string {
        return 'Update any subset of the thirteen LearnLingo footer settings: '
            . 'footermode, footerhtml, footerscss, footer_background_color, '
            . 'footer_title_1..4, footer_html_1..4 and footer_show_copywriter. '
            . 'The html footer uses footerhtml and footerscss; the moodle footer '
            . 'uses four optional columns, background color and credits. '
            . 'Footer mode is independent of homepage mode. Switching modes '
            . 'never clears inactive fields. Read learnlingo_get_settings first '
            . 'and pass its state_hash as required expected_hash. Submit only '
            . 'the fields to change. Preview and obtain explicit confirmation '
            . 'before writing. All thirteen fields use the existing validator.';
    }

    /**
     * MCP input schema.
     *
     * @return array
     */
    public function get_input_schema(): array {
        $definitions = branding_exchange::definitions();
        $properties = [];
        foreach (self::FIELDS as $name => $description) {
            $type = $definitions[$name][0];
            $property = [
                'type' => $type === 'bool' ? 'boolean' : 'string',
                'description' => $description,
            ];
            if ($type === 'footermode') {
                $property['enum'] = ['moodle', 'html'];
            } else {
                if ($type === 'color') {
                    $property['pattern'] = '^#[A-Fa-f0-9]{6}$';
                }
            }
            $properties[$name] = $property;
        }
        return $this->object_schema([
            'settings' => [
                'type' => 'object',
                'description' => 'Only the footer fields to modify. '
                    . 'Other settings and inactive footer configuration remain unchanged.',
                'properties' => (object)$properties,
                'additionalProperties' => false,
            ],
            'expected_hash' => [
                'type' => 'string',
                'description' => 'Required state_hash returned by learnlingo_get_settings. '
                    . 'A stale hash rejects the update before any changes are saved.',
            ],
        ]);
    }

    /**
     * Moodle capability needed to update the theme.
     *
     * @return string
     */
    public function get_required_capability(): string {
        return 'moodle/site:config';
    }

    /**
     * Resolve the tool security context.
     *
     * @param array $arguments Tool input.
     * @return context Context for permission checks.
     */
    public function resolve_context(array $arguments): context {
        return context_system::instance();
    }

    /**
     * Whether the write supports a preview.
     *
     * @return bool
     */
    public function supports_dry_run(): bool {
        return true;
    }

    /**
     * Check the allowlist and concurrency hash before using the existing service.
     *
     * @param array $arguments MCP arguments.
     * @return array Prepared changes.
     */
    private static function prepare(array $arguments): array {
        if (!isset($arguments['expected_hash']) || !is_string($arguments['expected_hash']) ||
            !preg_match('/^[a-f0-9]{64}$/D', $arguments['expected_hash'])) {
            throw new api_exception('expected_hash_required', 400,
                'Read learnlingo_get_settings and supply its state_hash before updating the footer.');
        }
        $settings = $arguments['settings'] ?? null;
        if (!is_array($settings) || !$settings) {
            throw new api_exception('no_changes', 400, 'Provide at least one footer setting to update.');
        }
        foreach (array_keys($settings) as $name) {
            if (!array_key_exists($name, self::FIELDS)) {
                throw new api_exception('unknown_setting', 400, 'Not a footer setting: ' . $name);
            }
        }
        return settings_service::prepare($arguments);
    }

    /**
     * Show only metadata for long HTML/SCSS values.
     *
     * @param array $arguments Requested changes.
     * @param authenticated_identity $identity Authenticated Moodle user.
     * @return array Preview metadata.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        $prepared = self::prepare($arguments);
        $summary = [];
        foreach ($prepared['settings'] as $name => $value) {
            $before = $prepared['before']['settings'][$name];
            $summary[$name] = [
                'bytes_before' => is_string($before) ? strlen($before) : null,
                'bytes_after' => is_string($value) ? strlen($value) : null,
            ];
            if ($name === 'footermode' || $name === 'footer_show_copywriter' ||
                $name === 'footer_background_color') {
                $summary[$name]['value_before'] = $before;
                $summary[$name]['value_after'] = $value;
            }
        }
        return [
            'changed_settings' => array_keys($prepared['settings']),
            'settings_summary' => $summary,
            'requires_confirmation' => true,
        ];
    }

    /**
     * Revalidate and write only the requested footer fields.
     *
     * @param array $arguments Requested changes.
     * @param authenticated_identity $identity Authenticated Moodle user.
     * @return array Write result.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        $prepared = self::prepare($arguments);
        settings_service::apply($prepared, $identity->userid);
        return [
            'success' => true,
            'changed_settings' => array_keys($prepared['settings']),
            'state_hash' => settings_service::snapshot()['state_hash'],
        ];
    }
}
