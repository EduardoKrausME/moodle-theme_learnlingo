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
 * Shared image storage for the visual editor and the MCP integration.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use context_system;
use invalid_parameter_exception;
use moodle_url;
use stored_file;

/**
 * Keep image validation, storage and metadata identical across transports.
 */
final class editor_assets {
    /** The same maximum size used by _editor/actions.php. */
    public const MAX_BYTES = 10 * 1024 * 1024;

    /** Image file extensions must match the MIME determined from the image bytes. */
    private const TYPES = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    /**
     * Validate an image without writing any file.
     *
     * @param string $filename Client-supplied filename.
     * @param string $bytes Complete binary image.
     * @return array Filename, mimetype, size, width, height.
     */
    public static function validate(string $filename, string $bytes): array {
        if ($filename === '' || strlen($filename) > 255 ||
                strpbrk($filename, '/\\') !== false) {
            throw new invalid_parameter_exception('Invalid image filename');
        }
        $filename = clean_filename($filename);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $size = strlen($bytes);
        if ($size === 0 || $size > self::MAX_BYTES) {
            throw new invalid_parameter_exception('Image upload failed or exceeded 10 MiB');
        }
        $imageinfo = @getimagesizefromstring($bytes);
        if (!isset(self::TYPES[$extension]) || !$imageinfo ||
                ($imageinfo['mime'] ?? '') !== self::TYPES[$extension]) {
            throw new invalid_parameter_exception('Only PNG, JPEG, GIF and WebP images are accepted');
        }
        return [
            'filename' => $filename,
            'mimetype' => $imageinfo['mime'],
            'size' => $size,
            'width' => (int)$imageinfo[0],
            'height' => (int)$imageinfo[1],
        ];
    }

    /**
     * Store the image in the exact public file area used by GrapesJS.
     *
     * @param string $filename Original filename.
     * @param string $bytes Binary image.
     * @param int $userid Authenticated Moodle user id.
     * @return array File metadata in the visual-editor format.
     */
    public static function save(string $filename, string $bytes, int $userid): array {
        $validated = self::validate($filename, $bytes);
        $context = context_system::instance();
        require_capability('moodle/site:config', $context);
        $fs = get_file_storage();

        // Unique itemids permit repeated names without overwriting another image.
        $itemid = 0;
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = random_int(1, 2147483647);
            if (!$fs->file_exists($context->id, 'theme_learnlingo', 'editor_assets',
                    $candidate, '/', $validated['filename'])) {
                $itemid = $candidate;
                break;
            }
        }
        if (!$itemid) {
            throw new invalid_parameter_exception('Unable to allocate an image identifier');
        }

        $file = $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'theme_learnlingo',
            'filearea' => 'editor_assets',
            'itemid' => $itemid,
            'filepath' => '/',
            'filename' => $validated['filename'],
            'userid' => $userid,
            'mimetype' => $validated['mimetype'],
        ], $bytes);
        return self::metadata($file);
    }

    /**
     * List editor assets. The storage area is public but its inventory is admin-only.
     *
     * @return array File metadata, newest first.
     */
    public static function list_files(): array {
        $context = context_system::instance();
        require_capability('moodle/site:config', $context);
        $files = get_file_storage()->get_area_files($context->id, 'theme_learnlingo',
            'editor_assets', false, 'timecreated DESC', false);
        return array_values(array_map([self::class, 'metadata'], $files));
    }

    /**
     * Return the shape expected by GrapesJS and existing AJAX services.
     *
     * @param stored_file $file Stored image.
     * @return array Image metadata.
     */
    public static function metadata(stored_file $file): array {
        $url = moodle_url::make_pluginfile_url(
            $file->get_contextid(), $file->get_component(), $file->get_filearea(),
            $file->get_itemid(), $file->get_filepath(), $file->get_filename()
        );
        return [
            'id' => $file->get_id(),
            'name' => $file->get_filename(),
            'type' => 'image',
            'src' => $url->out(false),
            'size' => $file->get_filesize(),
            'delete' => true,
        ];
    }
}
