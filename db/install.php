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
 * Installation helpers for LearnLingo.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Seed the theme logos on a fresh installation.
 *
 * @return void
 * @throws coding_exception
 * @throws dml_exception
 * @throws file_exception
 * @throws stored_file_creation_exception
 */
function xmldb_theme_learnlingo_install(): void {
    global $CFG;

    $source = "{$CFG->dirroot}/theme/learnlingo/pix/default-logo.png";
    if (file_exists($source)) {
        $context = context_system::instance();
        $fs = get_file_storage();

        // Preserve a configured logo if its file is still available.
        $configured = trim((string)get_config("theme_learnlingo", "logo"));
        if ($configured !== "") {
            $relativepath = ltrim($configured, "/");
            $existing = $fs->get_file_by_hash(sha1("/{$context->id}/theme_learnlingo/logo/0/{$relativepath}"));
            if (!($existing && !$existing->is_directory())) {
                $files = $fs->get_area_files($context->id, "theme_learnlingo", "logo", 0, "id", false);
                if ($files) {
                    // Recover a pre-existing upload whose configuration value is empty.
                    $file = reset($files);
                } else {
                    $file = $fs->create_file_from_pathname([
                        "contextid" => $context->id,
                        "component" => "theme_learnlingo",
                        "filearea" => "logo",
                        "itemid" => 0,
                        "filepath" => "/",
                        "filename" => "default-logo.png",
                        "mimetype" => "image/png",
                        "userid" => 0,
                    ], $source);
                }

                set_config("logo", $file->get_filepath() . $file->get_filename(), "theme_learnlingo");
            }
        }
    }
}
