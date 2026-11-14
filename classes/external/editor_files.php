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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * AJAX services for LearnLingo editor assets.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * List and delete images used by the LearnLingo visual editor.
 *
 * @package theme_learnlingo
 */
final class editor_files extends external_api {
    /**
     * No arguments are required to list the site's editor images.
     *
     * @return external_function_parameters
     */
    public static function list_files_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * List assets stored by the theme in the system context.
     *
     * @return array
     */
    public static function list_files(): array {
        self::validate_parameters(self::list_files_parameters(), []);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        return \theme_learnlingo\editor_assets::list_files();
    }

    /**
     * Return the image data expected by GrapesJS.
     *
     * @return external_multiple_structure
     */
    public static function list_files_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Stored file id'),
            'name' => new external_value(PARAM_TEXT, 'File name'),
            'type' => new external_value(PARAM_ALPHA, 'Editor asset type'),
            'src' => new external_value(PARAM_URL, 'Pluginfile URL'),
            'size' => new external_value(PARAM_INT, 'File size in bytes'),
            'delete' => new external_value(PARAM_BOOL, 'Whether deletion is available'),
        ]));
    }

    /**
     * Parameters for deleting a stored editor image.
     *
     * @return external_function_parameters
     */
    public static function delete_file_parameters(): external_function_parameters {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'Stored file id'),
        ]);
    }

    /**
     * Delete one image only when it belongs to the editor's system file area.
     *
     * @param int $id Stored file id
     * @return array
     */
    public static function delete_file(int $id): array {
        ['id' => $id] = self::validate_parameters(self::delete_file_parameters(), ['id' => $id]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $file = $id > 0 ? get_file_storage()->get_file_by_id($id) : false;
        if (!$file || $file->get_contextid() !== $context->id ||
                $file->get_component() !== 'theme_learnlingo' || $file->get_filearea() !== 'editor_assets' ||
                $file->is_directory()) {
            throw new \invalid_parameter_exception('Image does not belong to the LearnLingo editor');
        }

        $file->delete();
        return ['success' => true];
    }

    /**
     * Status returned to the editor.
     *
     * @return external_single_structure
     */
    public static function delete_file_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Deletion succeeded'),
        ]);
    }
}
