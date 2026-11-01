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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Site-wide logo and favicon uploads from LearnLingo's setup wizard.
 *
 * Both settings intentionally use Moodle's existing core_admin file areas,
 * not a second theme-specific copy of the site's images.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use context_system;
use invalid_parameter_exception;
use moodle_url;

/**
 * Validate and store small, image-only site identity files.
 */
final class setup_images {
    /**
     * Validate all submitted files before modifying existing site files.
     *
     * @param array $files PHP uploads, usually $_FILES.
     * @return array Validated uploads indexed by logo and favicon.
     */
    public static function validate(array $files): array {
        $result = [];
        foreach (['logo', 'favicon'] as $field) {
            if (!isset($files[$field])) {
                continue;
            }
            $file = $files[$field];
            $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null)
                    || !is_uploaded_file($file['tmp_name'])) {
                throw new invalid_parameter_exception(get_string('setupimageuploaderror', 'theme_learnlingo'));
            }

            $bytes = (int)($file['size'] ?? 0);
            $limit = $field === 'logo' ? 3 * 1024 * 1024 : 1024 * 1024;
            if ($bytes <= 0 || $bytes > $limit) {
                throw new invalid_parameter_exception(get_string('setupimagesizeerror', 'theme_learnlingo'));
            }

            $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
            $image = @getimagesize($file['tmp_name']);
            $type = is_array($image) ? ($image[2] ?? 0) : 0;
            $mimetype = '';
            if ($type === IMAGETYPE_PNG && $extension === 'png') {
                $mimetype = 'image/png';
            } else if ($type === IMAGETYPE_JPEG && in_array($extension, ['jpg', 'jpeg'], true)) {
                $mimetype = 'image/jpeg';
            } else if ($field === 'favicon' && $extension === 'ico' && self::valid_ico($file['tmp_name'])) {
                $mimetype = 'image/vnd.microsoft.icon';
            }
            if (!$mimetype) {
                throw new invalid_parameter_exception(get_string('setupimageformaterror', 'theme_learnlingo'));
            }
            // Browser-facing files receive canonical names, never supplied names.
            $result[$field] = [
                'tmp_name' => $file['tmp_name'],
                'extension' => $extension === 'jpeg' ? 'jpg' : $extension,
                'mimetype' => $mimetype,
            ];
        }
        return $result;
    }

    /**
     * Verify the basic ICO directory structure rather than trusting a MIME header.
     *
     * @param string $pathname Temporary file.
     * @return bool
     */
    private static function valid_ico(string $pathname): bool {
        $fp = @fopen($pathname, 'rb');
        if (!$fp) {
            return false;
        }
        $header = fread($fp, 22);
        fclose($fp);
        if (strlen($header) < 22 || substr($header, 0, 4) !== "\x00\x00\x01\x00") {
            return false;
        }
        $count = unpack('v', substr($header, 4, 2))[1];
        $length = unpack('V', substr($header, 14, 4))[1];
        $offset = unpack('V', substr($header, 18, 4))[1];
        return $count >= 1 && $count <= 100 && $length > 0
            && $offset >= 6 + ($count * 16)
            && $offset + $length <= filesize($pathname);
    }

    /**
     * Save previously validated files and update the same core settings
     * used by Moodle's Appearance > Logos page.
     *
     * @param array $uploads Return value of validate().
     * @return void
     */
    public static function save(array $uploads): void {
        global $USER;

        $context = context_system::instance();
        $fs = get_file_storage();
        foreach ($uploads as $field => $upload) {
            if (!in_array($field, ['logo', 'favicon'], true)) {
                continue;
            }
            $filename = 'learnlingo-' . $field . '-' .
                substr(hash_file('sha256', $upload['tmp_name']), 0, 16) . '.' . $upload['extension'];

            $record = [
                'contextid' => $context->id,
                'component' => 'core_admin',
                'filearea' => $field,
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $filename,
                'mimetype' => $upload['mimetype'],
                'userid' => $USER->id,
            ];
            if (!$fs->file_exists($context->id, 'core_admin', $field, 0, '/', $filename)) {
                $fs->create_file_from_pathname($record, $upload['tmp_name']);
            }
            set_config($field, '/' . $filename, 'core_admin');

            // Keep the newly selected asset and remove superseded site images.
            foreach ($fs->get_area_files($context->id, 'core_admin', $field, 0, 'id', false) as $existing) {
                if ($existing->get_filepath() !== '/' || $existing->get_filename() !== $filename) {
                    $existing->delete();
                }
            }
        }
    }

    /**
     * Return the actual uploaded site asset for display in the wizard.
     *
     * @param string $field logo or favicon.
     * @return string Browser URL or empty string when no file is available.
     */
    public static function current_url(string $field): string {
        if (!in_array($field, ['logo', 'favicon'], true)) {
            return '';
        }
        $relative = ltrim((string)get_config('core_admin', $field), '/');
        if ($relative === '') {
            return '';
        }
        $contextid = context_system::instance()->id;
        $hash = sha1("/{$contextid}/core_admin/{$field}/0/{$relative}");
        $file = get_file_storage()->get_file_by_hash($hash);
        if (!$file || $file->is_directory()) {
            return '';
        }
        return moodle_url::make_pluginfile_url(
            $contextid, 'core_admin', $field, 0, $file->get_filepath(), $file->get_filename()
        )->out(false);
    }
}
