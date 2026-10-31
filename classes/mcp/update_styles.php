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
 * MCP WRITE tool for LearnLingo custom HTML and SCSS.
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
 * Atomic partial writes for homepage, footer and global HTML/SCSS fields.
 *
 * Reuses settings_service to keep validation and concurrency behavior in one place.
 */
final class update_styles extends base_tool {
    /** Explicit names exposed in the MCP schema, in display order. */
    private const FIELDS = [
        'homepagemode' => 'Homepage layout: moodle for native Moodle, learnlingo for the theme home, '
            . 'html for a custom home. Independent of footermode.',
        'homepagehtml' => 'HTML above course cards in learnlingo mode or the whole home content in '
            . 'html mode. Hidden in moodle mode. No scripts or additional html/body wrappers.',
        'homepagescss' => 'Full SCSS loaded globally on every page, regardless of homepagemode. '
            . 'This includes menus, navbar and footer; use specific homepage selectors for '
            . 'home-only rules. Can be cleared with an empty string after migrating CSS '
            . 'to scss in the same operation.',
        'footermode' => 'moodle uses the traditional columns and credits; html renders '
            . 'footerhtml on all pages. Independent of homepagemode. Existing settings '
            . 'for inactive mode are preserved.',
        'footerhtml' => 'Inner HTML of the existing #page-footer wrapper when footermode=html. '
            . 'Do not add a second footer tag, scripts or style tags. '
            . 'Put footer CSS in footerscss.',
        'footerscss' => 'SCSS enabled only for the HTML footer, automatically scoped to '
            . '#page-footer.learnlingo-site-footer.learnlingo-footer-html-mode. '
            . 'Use & for the footer root and relative selectors for descendants. '
            . 'Use an empty string to clear.',
        'scsspre' => 'Global SCSS inserted before Boost/Bootstrap for Sass variables '
            . 'and compile-time settings.',
        'scss' => 'Global SCSS inserted after Boost/Bootstrap and applied to every page '
            . 'in all homepage and footer modes.',
    ];

    /** @return string Tool identifier. */
    public function get_name(): string {
        return 'learnlingo_update_styles';
    }

    /** @return string Tool title. */
    public function get_title(): string {
        return 'Update LearnLingo HTML and SCSS';
    }

    /** @return string Description available to MCP clients. */
    public function get_description(): string {
        return 'Atomically update any subset of LearnLingo homepagehtml, homepagescss, '
            . 'homepagemode, footerhtml, footerscss, footermode, scsspre and scss. '
            . 'homepagescss is compiled globally on every page; footerscss applies '
            . 'only inside the custom HTML footer when footermode=html; scss is global. '
            . 'Move CSS from homepagescss to scss and clear homepagescss in ONE confirmed '
            . 'write to avoid duplication. Other fields remain unchanged. '
            . 'Read learnlingo_get_settings first; expected_hash is REQUIRED to '
            . 'protect against concurrent changes. Always preview and obtain '
            . 'explicit confirmation before applying.';
    }

    /** @return array JSON schema. */
    public function get_input_schema(): array {
        $definitions = branding_exchange::definitions();
        $properties = [];
        foreach (self::FIELDS as $name => $description) {
            $type = $definitions[$name][0];
            $field = [
                'type' => $type === 'bool' ? 'boolean' : 'string',
                'description' => $description,
            ];
            if ($type === 'homepage') {
                $field['enum'] = ['moodle', 'learnlingo', 'html'];
            } else if ($type === 'footermode') {
                $field['enum'] = ['moodle', 'html'];
            }
            $properties[$name] = $field;
        }
        return $this->object_schema([
            'settings' => [
                'type' => 'object',
                'description' => 'Only the HTML, SCSS and mode fields to change. '
                    . 'Send homepagescss and scss together to migrate rules atomically.',
                'properties' => (object)$properties,
                'additionalProperties' => false,
            ],
            'expected_hash' => [
                'type' => 'string',
                'description' => 'Required state_hash from learnlingo_get_settings. '
                    . 'A changed hash rejects this write; read and preview again.',
            ],
        ]);
    }

    /** @return string Required administrator capability. */
    public function get_required_capability(): string {
        return 'moodle/site:config';
    }

    /**
     * @param array $arguments Tool input.
     * @return context Security context.
     */
    public function resolve_context(array $arguments): context {
        return context_system::instance();
    }

    /** @return bool Supports confirmation previews. */
    public function supports_dry_run(): bool {
        return true;
    }

    /**
     * Validate the restricted patch and its concurrency token.
     *
     * @param array $arguments Tool input.
     * @return array Prepared state.
     */
    private static function prepare(array $arguments): array {
        if (!isset($arguments['expected_hash']) || !is_string($arguments['expected_hash']) ||
                !preg_match('/^[a-f0-9]{64}$/D', $arguments['expected_hash'])) {
            throw new api_exception('expected_hash_required', 400,
                'Read learnlingo_get_settings and supply its state_hash before editing.');
        }
        $values = $arguments['settings'] ?? null;
        if (!is_array($values) || !$values) {
            throw new api_exception('no_changes', 400, 'Provide at least one HTML or SCSS setting.');
        }
        foreach (array_keys($values) as $name) {
            if (!array_key_exists($name, self::FIELDS)) {
                throw new api_exception('unknown_setting', 400, 'Field not editable by this tool: ' . $name);
            }
        }
        // Do not permit image writes through this dedicated content/styles tool.
        if (array_key_exists('images', $arguments)) {
            throw new api_exception('invalid_images', 400, 'Images cannot be changed with this tool.');
        }
        return settings_service::prepare($arguments);
    }

    /**
     * Return metadata only; never echo entire HTML or SCSS payloads.
     *
     * @param array $arguments Tool input.
     * @param authenticated_identity $identity Connected administrator.
     * @return array Preview.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        $prepared = self::prepare($arguments);
        $summary = [];
        foreach ($prepared['settings'] as $name => $value) {
            $old = $prepared['before']['settings'][$name];
            $summary[$name] = [
                'bytes_before' => is_string($old) ? strlen($old) : null,
                'bytes_after' => is_string($value) ? strlen($value) : null,
                'value_before' => in_array($name, ['homepagemode', 'footermode'], true) ? $old : null,
                'value_after' => in_array($name, ['homepagemode', 'footermode'], true) ? $value : null,
            ];
        }
        return [
            'changed_settings' => array_keys($prepared['settings']),
            'settings_summary' => $summary,
            'requires_confirmation' => true,
        ];
    }

    /**
     * Validate again and apply all provided fields as one transaction.
     *
     * @param array $arguments Tool input.
     * @param authenticated_identity $identity Connected administrator.
     * @return array Result.
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
