<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * LearnLingo theme upgrades.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Upgrade LearnLingo.
 *
 * @param int $oldversion Previously installed version.
 * @return bool
 */
function xmldb_theme_learnlingo_upgrade(int $oldversion): bool {
    if ($oldversion < 2026101001) {
        $fs = get_file_storage();
        $context = context_system::instance();
        foreach (['logo' => 'logosvg', 'loginlogo' => 'loginlogosvg'] as $area => $legacy) {
            $svg = (string) get_config('theme_learnlingo', $legacy);
            if ($svg !== '' && \theme_learnlingo\svg_logo::is_valid($svg)) {
                $files = $fs->get_area_files($context->id, 'theme_learnlingo', $area, 0, 'id', false);
                foreach ($files as $file) {
                    $file->delete();
                }
                $fs->create_file_from_string([
                    'contextid' => $context->id,
                    'component' => 'theme_learnlingo',
                    'filearea' => $area,
                    'itemid' => 0,
                    'filepath' => '/',
                    'filename' => $area . '.svg',
                    'mimetype' => 'image/svg+xml',
                    'userid' => 0,
                ], $svg);
                unset_config($legacy, 'theme_learnlingo');
            }
        }
        upgrade_plugin_savepoint(true, 2026101001, 'theme', 'learnlingo');
    }
    return true;
}
