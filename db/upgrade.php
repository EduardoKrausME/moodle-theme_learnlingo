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
 * LearnLingo theme upgrades.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Copy the previous theme-specific logo into Moodle's shared site logo area.
 * Existing core_admin logos always take precedence.
 *
 * @return void
 */
function theme_learnlingo_migrate_logo_to_core(): void {
    if (trim((string)get_config('core_admin', 'logo')) !== '') {
        return;
    }

    $context = context_system::instance();
    $fs = get_file_storage();

    // An uploaded core logo may exist without a matching configuration entry.
    $corefiles = $fs->get_area_files($context->id, 'core_admin', 'logo', 0, 'id', false);
    if ($corefiles) {
        $file = reset($corefiles);
        set_config('logo', $file->get_filepath() . $file->get_filename(), 'core_admin');
        return;
    }

    $legacy = trim((string)get_config('theme_learnlingo', 'logo'));
    if ($legacy === '') {
        return;
    }

    $relativepath = ltrim($legacy, '/');
    $file = $fs->get_file_by_hash(sha1("/{$context->id}/theme_learnlingo/logo/0/{$relativepath}"));
    if (!$file || $file->is_directory()) {
        return;
    }

    $copy = $fs->create_file_from_storedfile([
        'contextid' => $context->id,
        'component' => 'core_admin',
        'filearea' => 'logo',
        'itemid' => 0,
        'filepath' => $file->get_filepath(),
        'filename' => $file->get_filename(),
    ], $file);
    set_config('logo', $copy->get_filepath() . $copy->get_filename(), 'core_admin');
}

/**
 * Upgrade LearnLingo.
 *
 * @param int $oldversion Previously installed version.
 * @return bool
 */
function xmldb_theme_learnlingo_upgrade(int $oldversion): bool {
    if ($oldversion < 2026103000) {
        theme_learnlingo_migrate_logo_to_core();
        upgrade_plugin_savepoint(true, 2026103000, 'theme', 'learnlingo');
    }

    return true;
}
