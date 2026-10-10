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
 * MCP LearnLingo homepage settings operations.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\mcp;

use local_mcp\exception\api_exception;
use theme_learnlingo\branding_exchange;

/**
 * Validate and apply only the home-related theme settings.
 */
final class home_service {
    /** Map public MCP input names to the theme's configuration keys. */
    private const FIELDS = [
        'mode' => 'homepagemode',
        'html' => 'homepagehtml',
        'fullwidth' => 'homepagefullwidth',
        'scss' => 'scss',
    ];

    /**
     * Return the current state and a concurrency-checking hash.
     *
     * @return array
     */
    public static function snapshot(): array {
        $settings = branding_exchange::export()['settings'];
        $home = [];
        foreach (self::FIELDS as $public => $setting) {
            $home[$public] = $settings[$setting];
        }
        $home['state_hash'] = hash('sha256', json_encode($home,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $home;
    }

    /**
     * Validate requested changes against the theme's existing JSON contract.
     * No data is modified here.
     *
     * @param array $arguments MCP tool inputs.
     * @return array Current and proposed home state.
     */
    public static function prepare(array $arguments): array {
        $before = self::snapshot();
        if (isset($arguments['expected_hash']) &&
                !hash_equals($before['state_hash'], (string)$arguments['expected_hash'])) {
            throw new api_exception('homepage_changed', 409,
                'The LearnLingo homepage changed. Read it again before updating.');
        }

        $data = branding_exchange::export();
        $changes = [];
        foreach (self::FIELDS as $public => $setting) {
            if (!array_key_exists($public, $arguments)) {
                continue;
            }
            $data['settings'][$setting] = $arguments[$public];
            $changes[] = $setting;
        }
        // Supplying new HTML without a mode activates that HTML as the homepage.
        if (array_key_exists('html', $arguments) && !array_key_exists('mode', $arguments)) {
            $data['settings']['homepagemode'] = 'html';
            $changes[] = 'homepagemode';
        }
        if (!$changes) {
            throw new api_exception('no_changes', 400,
                'Provide at least one homepage field to update.');
        }

        // Reuse the theme's full HTML/SCSS safety checks and field restrictions.
        $data = branding_exchange::parse(branding_exchange::json($data));
        $changedsettings = [];
        foreach (array_unique($changes) as $setting) {
            $changedsettings[$setting] = $data['settings'][$setting];
        }
        $after = $before;
        foreach (self::FIELDS as $public => $setting) {
            $after[$public] = $data['settings'][$setting];
        }
        unset($after['state_hash']);
        return [
            'before' => $before,
            'after' => $after,
            'settings' => $changedsettings,
        ];
    }

    /**
     * Persist the validated changes and clear theme caches.
     *
     * @param array $prepared Result of prepare().
     * @return void
     */
    public static function apply(array $prepared): void {
        foreach ($prepared['settings'] as $name => $value) {
            set_config($name, is_bool($value) ? (int)$value : $value, 'theme_learnlingo');
        }
        theme_reset_all_caches();
    }
}
