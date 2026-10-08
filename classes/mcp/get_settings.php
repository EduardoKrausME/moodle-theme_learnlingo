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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU GPL for details.

/**
 * MCP read access to all LearnLingo theme settings.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_learnlingo\mcp;

use context;
use context_system;
use local_mcp\read\tool_interface;
use local_mcp\security\authenticated_identity;

/**
 * Return all portable settings and the uploaded raster logo metadata.
 */
final class get_settings implements tool_interface {
    public function get_name(): string {
        return 'learnlingo_get_settings';
    }

    public function get_title(): string {
        return 'Read all LearnLingo settings';
    }

    public function get_description(): string {
        return 'Read all LearnLingo theme settings, including both SCSS fields, colors, SVG logos, '
            . 'homepage, login texts, course display options and uploaded raster logos. '
            . 'Returns a state_hash for learnlingo_update_settings. Set include_images to view uploaded logos.';
    }

    public function get_input_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'include_images' => [
                    'type' => 'boolean',
                    'description' => 'Attach uploaded PNG/JPEG/WebP logos as images when they are at most 2 MiB each.',
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    public function get_required_capability(): string {
        return 'moodle/site:config';
    }

    public function resolve_context(array $arguments): context {
        return context_system::instance();
    }

    public function execute(array $arguments, authenticated_identity $identity): array {
        return settings_service::snapshot(!empty($arguments['include_images']));
    }
}
