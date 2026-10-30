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
        return 'Update any subset of LearnLingo settings, including scsspre and scss, colors, '
            . 'login/dashboard/course options, homepage HTML and uploaded PNG/JPEG site logos or PNG/JPEG/WebP login logos. '
            . 'Only specified settings are changed. Requires confirmation; read learnlingo_get_settings '
            . 'first and pass its state_hash in expected_hash to avoid concurrent overwrites. '
            . 'The site logo uses core_admin.';
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
        $properties = [];
        foreach (branding_exchange::definitions() as $name => [$type, $default]) {
            $property = [
                'type' => $type === 'bool' ? 'boolean' : 'string',
                'description' => $name . ' (' . $type . ')',
            ];
            if ($type === 'color') {
                $property['pattern'] = '^#[A-Fa-f0-9]{6}$';
            } else if ($type === 'homepage') {
                $property['enum'] = ['learnlingo', 'moodle', 'html'];
            }
            $properties[$name] = $property;
        }
        $image = [
            'type' => 'object',
            'properties' => [
                'image_base64' => ['type' => 'string', 'description' => 'Base64 PNG, JPEG or WebP (5 MiB maximum).'],
                'image_url' => ['type' => 'string', 'description' => 'Public HTTPS URL of a PNG, JPEG or WebP image.'],
                'remove' => ['type' => 'boolean', 'description' => 'Set true to remove the uploaded image.'],
            ],
            'additionalProperties' => false,
        ];
        return $this->object_schema([
            'settings' => [
                'type' => 'object',
                'description' => 'Only the settings that need to change. Other settings are preserved.',
                'properties' => (object)$properties,
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
