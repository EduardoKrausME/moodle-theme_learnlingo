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
 * Canonical, reusable menu structure for MCP clients.
 *
 * The structure describes existing Moodle markup; it does not replace templates
 * or constrain which menu items are available to a given user.
 */
final class menu_guide {
    /**
     * Return the complete navbar coverage reference for AI-generated SCSS.
     *
     * @return array
     */
    public static function schema(): array {
        return [
            'instructions' => [
                'MANDATORY: before creating, editing or explaining navbar/menu SCSS, review the entire structure and cover '
                    . 'its shared selectors, guest-only login and authenticated-only controls. Do not style only the '
                    . 'primary navigation links or only the anonymous menu.',
                'Keep a single minimal SCSS hierarchy rooted at .navbar.fixed-top. Reuse Moodle/Bootstrap classes; '
                    . 'do not rebuild core/moremenu, core/user_menu or popovers and do not use unstable generated IDs.',
                'Navbar, shared menu and header styling MUST go in the global scss setting, NOT homepagescss. '
                    . 'Preserve unrelated SCSS, avoid duplicate/conflicting rules and use a preview/confirmation for writes.',
                'Account for default, hover, focus-visible, active, dropdown-open, unread-count, mobile and '
                    . 'light/dark states without interfering with notification, message, user-menu or edit-switch behaviour.',
                'Use theme-aware color variables and verify [data-bs-theme="dark"] contrast. A fixed white navbar '
                    . 'background can defeat dark mode, especially if an existing !important rule overrides the new CSS.',
                'Prefer a transparent PNG logo cropped to the artwork bounds, with NO built-in blank, transparent '
                    . 'or white padding; any desired spacing belongs to CSS, not the image canvas.',
                'Some nodes are optional: guest login appears only when logged out; notifications, messages, '
                    . 'profile dropdown and edit switch appear only when available to authenticated users. '
                    . 'Handle missing nodes without separate copies of the full stylesheet.',
            ],
            'structure' => <<<'STRUCTURE'
.navbar.fixed-top
|-- .container-fluid
|-- .navbar-toggler
|   `-- .navbar-toggler-icon
|-- .navbar-brand.learnlingo-navbar-brand
|   |-- .logo
|   `-- .learnlingo-wordmark (when no logo)
|-- .primary-navigation
|   `-- .moremenu
|       `-- .more-nav.navbar-nav
|           |-- .nav-item > .nav-link
|           |-- .nav-link.active
|           `-- .dropdownmoremenu > .dropdown-menu (More)
`-- #usernavigation
    |-- .popover-region-notifications [logged in]
    |   |-- .popover-region-toggle
    |   |-- .count-container
    |   `-- .popover-region-container
    |       |-- .popover-region-header-container
    |       |-- .popover-region-content-container
    |       `-- .popover-region-footer-container
    |-- .popover-region-messages [logged in]
    |   |-- .popover-region-toggle
    |   `-- .count-container
    |-- .kraus-layout-dark [both]
    |   |-- .dark-mode
    |   |-- .light-mode
    |   `-- svg
    |-- .divider
    |-- .usermenu-container [both]
    |   `-- .usermenu
    |       |-- .login a [guest]
    |       `-- .dropdown [logged in]
    |           |-- .dropdown-toggle / #user-menu-toggle
    |           `-- .dropdown-menu / #user-action-menu
    |               `-- .dropdown-item
    `-- .editmode-switch-form [when permitted]
        `-- .mds-switch-label / .mds-switch-control
STRUCTURE,
            'scss_outline' => <<<'SCSS'
.navbar.fixed-top {
    .navbar-toggler { /* mobile toggle */ }
    .navbar-toggler-icon { /* mobile icon */ }
    .navbar-brand {
        .logo { /* image dimensions, never image-canvas padding */ }
        .learnlingo-wordmark { /* text fallback */ }
    }
    .primary-navigation .moremenu {
        .nav-link { /* default, hover and focus-visible */ }
        .nav-link.active { /* selected page */ }
        .dropdown-menu { /* More menu */ }
    }
    #usernavigation {
        .popover-region-notifications,
        .popover-region-messages {
            .popover-region-toggle { /* notification/message icon */ }
            .count-container { /* unread badge */ }
        }
        .popover-region-container { /* notification panel */ }
        .kraus-layout-dark {
            .dark-mode, .light-mode { /* light/dark control */ }
            svg { /* icon sizing */ }
        }
        .divider { /* optional separator */ }
        .usermenu-container .usermenu {
            .login a { /* guest sign-in */ }
            .dropdown-toggle { /* logged-in avatar */ }
            .dropdown-menu .dropdown-item { /* profile, preferences, logout */ }
        }
        .editmode-switch-form { /* editing control; keep Moodle functionality */ }
    }
}
SCSS,
        ];
    }
}
