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
 * MCP READ tool for LearnLingo homepage settings.
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
 * Return the full homepage HTML and styling for editing.
 */
final class get_homepage implements tool_interface {
    public function get_name(): string {
        return 'learnlingo_get_homepage';
    }

    public function get_title(): string {
        return 'Read LearnLingo homepage';
    }

    public function get_description(): string {
        return 'Read the LearnLingo theme homepage mode, custom HTML, full-width '
            . 'setting and custom SCSS. Returns a state_hash used to avoid overwriting '
            . 'concurrent changes with learnlingo_update_homepage.';
    }

    public function get_input_schema(): array {
        return [
            'type' => 'object',
            'properties' => (object)[],
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
        return home_service::snapshot();
    }
}
