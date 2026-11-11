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
 * MCP instructions for styling the LearnLingo navbar.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\mcp;

/**
 * Canonical menu customization instructions and CSS variable allowlist for MCP clients.
 */
final class menu_guide {
    /**
     * Return the navbar CSS variable customization reference for AI-generated SCSS.
     *
     * @return array
     */
    public static function schema(): array {
        return [
            'instructions' => [
                'MANDATORY: for ordinary navbar/menu styling, change ONLY the CSS custom properties listed in '
                    . 'allowed_variables. Do not create selector-based styles for navbar components.',
                'Never generate or change .navbar.fixed-top, .nav-link, logo, toggler, dropdown, notification, '
                    . 'message, avatar, search or edit-switch CSS rules just to customize navbar appearance. '
                    . 'Use the existing theme variables instead of overriding its component selectors.',
                'Do NOT introduce height, min-height, max-height, padding, padding-inline, padding-block, '
                    . 'margin, width, gap, alignment or other structural/layout declarations for the navbar '
                    . 'or its descendants unless the user explicitly requests that exact change.',
                'Although --navbar-height is in allowed_variables, it is reserved exclusively for an explicit '
                    . 'navbar-height request. Do not emit or modify --navbar-height for colors, logos, '
                    . 'menu styling or other routine appearance changes.',
                'If the user explicitly requests a height, padding or other structural change, inspect the '
                    . 'existing theme rules and make only that requested exception. Never introduce '
                    . 'additional height/padding changes as a side effect of a visual redesign.',
                'Place the minimum requested CSS variable overrides in global scss, NOT homepagescss: '
                    . 'use :root for light mode and html[data-bs-theme="dark"] for dark mode when relevant. '
                    . 'Do not rewrite the theme SCSS source, duplicate unrelated variables or replace '
                    . 'the existing global stylesheet; preserve all unrelated rules.',
                'Check light/dark contrast and existing hover, focus, active and responsive behavior. '
                    . 'Keep Moodle navigation, notifications, messages, user dropdown, dark-mode control '
                    . 'and edit switch functional without changing their selectors or layout.',
                'Read current settings first, prepare a preview and obtain explicit confirmation before '
                    . 'writing the updated global scss. For an uploaded logo, prefer a transparent PNG '
                    . 'tightly cropped to the artwork; do not add image-canvas padding.',
            ],
            'allowed_variables' => [
                '--learnlingo-navbar-bg',
                '--learnlingo-navbar-text',
                '--learnlingo-navbar-border',
                '--learnlingo-navbar-divider',
                '--learnlingo-navbar-link',
                '--learnlingo-navbar-link-hover',
                '--learnlingo-navbar-link-hover-bg',
                '--learnlingo-navbar-link-active',
                '--learnlingo-navbar-link-active-ring',
                '--learnlingo-navbar-user-hover-bg',
                '--learnlingo-navbar-avatar-border',
                '--learnlingo-navbar-avatar-ring',
                '--learnlingo-navbar-search-bg',
                '--learnlingo-navbar-search-text',
                '--learnlingo-navbar-search-placeholder',
                '--learnlingo-navbar-brandmark-text',
                '--learnlingo-navbar-brandmark-start',
                '--learnlingo-navbar-brandmark-end',
                '--learnlingo-navbar-brandmark-shadow',
                '--learnlingo-navbar-wordmark-accent',
                '--navbar-height',
            ],
            'scss_outline' => <<<'SCSS'
// Example only: include ONLY the variable overrides explicitly requested.
// No .navbar selectors, height, padding or other structural declarations.
:root {
    --learnlingo-navbar-bg: rgba(255, 255, 255, .97);
    --learnlingo-navbar-link: #666A74;
    --learnlingo-navbar-link-hover: var(--bs-primary);
}
html[data-bs-theme="dark"] {
    --learnlingo-navbar-bg: rgba(30, 40, 52, .98);
    --learnlingo-navbar-link: var(--learnlingo-navbar-text);
    --learnlingo-navbar-link-hover: #A4E88B;
}
SCSS,
        ];
    }
}
