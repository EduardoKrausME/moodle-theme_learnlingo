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
 * Normalize the institution's logo and favicon with PHP GD before storage.
 *
 * The output size is controlled by pixel dimensions rather than an arbitrary
 * upload size. PHP's upload_max_filesize/post_max_size still protect transport;
 * source pixel count and available memory protect image decompression.
 */
final class setup_images {
    /** Maximum logo dimensions, preserving the original aspect ratio. */
    public const LOGO_MAX_WIDTH = 1200;
    /** Maximum logo height. */
    public const LOGO_MAX_HEIGHT = 400;
    /** Square favicon canvas, in pixels. */
    public const FAVICON_SIZE = 256;
    /** Guard against decompression bombs, independent of compressed file size. */
    private const MAX_SOURCE_PIXELS = 24000000;

    /**
     * Check uploaded files before changing the palette or site images.
     *
     * @param array $files $_FILES.
     * @return array Validated image sources, indexed by logo and favicon.
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
            if (!is_file($file['tmp_name']) || filesize($file['tmp_name']) <= 0) {
                throw new invalid_parameter_exception(get_string('setupimageformaterror', 'theme_learnlingo'));
            }

            $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
            if ($field === 'favicon' && $extension === 'ico') {
                // GD cannot decode multi-resolution ICO containers. A verified
                // ICO already contains pre-sized icons, so keep it unchanged.
                if (!self::valid_ico($file['tmp_name'])) {
                    throw new invalid_parameter_exception(get_string('setupimageformaterror', 'theme_learnlingo'));
                }
                $result[$field] = [
                    'tmp_name' => $file['tmp_name'],
                    'extension' => 'ico',
                    'mimetype' => 'image/vnd.microsoft.icon',
                ];
                continue;
            }

            if (!extension_loaded('gd')) {
                throw new invalid_parameter_exception(get_string('setupimagegderror', 'theme_learnlingo'));
            }
            $info = @getimagesize($file['tmp_name']);
            if (!$info || (int)$info[0] < 1 || (int)$info[1] < 1) {
                throw new invalid_parameter_exception(get_string('setupimageformaterror', 'theme_learnlingo'));
            }

            $type = (int)$info[2];
            $valid = ($type === IMAGETYPE_PNG && $extension === 'png')
                || ($type === IMAGETYPE_JPEG && in_array($extension, ['jpg', 'jpeg'], true))
                || ($type === IMAGETYPE_WEBP && $extension === 'webp'
                    && function_exists('imagecreatefromwebp'));
            if (!$valid) {
                throw new invalid_parameter_exception(get_string('setupimageformaterror', 'theme_learnlingo'));
            }
            if (!self::has_capacity((int)$info[0], (int)$info[1])) {
                throw new invalid_parameter_exception(get_string('setupimagememoryerror', 'theme_learnlingo'));
            }

            $result[$field] = [
                'tmp_name' => $file['tmp_name'],
                'type' => $type,
                'width' => (int)$info[0],
                'height' => (int)$info[1],
            ];
        }
        return $result;
    }

    /**
     * Ensure the decoder has enough room before allocating a source bitmap.
     *
     * @param int $width Width in pixels.
     * @param int $height Height in pixels.
     * @return bool
     */
    private static function has_capacity(int $width, int $height): bool {
        $pixels = $width * $height;
        if ($width <= 0 || $height <= 0 || $pixels > self::MAX_SOURCE_PIXELS) {
            return false;
        }

        $limit = trim((string)ini_get('memory_limit'));
        if ($limit === '-1' || $limit === '') {
            return true;
        }
        $number = (float)$limit;
        $unit = strtolower(substr($limit, -1));
        if ($unit === 'g') {
            $number *= 1024 * 1024 * 1024;
        } else if ($unit === 'm') {
            $number *= 1024 * 1024;
        } else if ($unit === 'k') {
            $number *= 1024;
        }

        // Decoder memory + destination bitmap + native GD overhead + margin.
        $required = $pixels * 6 + (self::FAVICON_SIZE ** 2 * 4) + 16 * 1024 * 1024;
        return memory_get_usage(true) + $required < $number;
    }

    /**
     * Validate the directory of a multi-size ICO. GD does not resize ICO.
     *
     * @param string $pathname Uploaded ICO.
     * @return bool
     */
    private static function valid_ico(string $pathname): bool {
        $size = @filesize($pathname);
        if ($size === false || $size < 22) {
            return false;
        }
        $fp = @fopen($pathname, 'rb');
        if (!$fp) {
            return false;
        }
        try {
            $header = fread($fp, 6);
            if (strlen($header) !== 6 || substr($header, 0, 4) !== "\x00\x00\x01\x00") {
                return false;
            }
            $count = unpack('v', substr($header, 4, 2))[1];
            if ($count < 1 || $count > 256 || $size < 6 + $count * 16) {
                return false;
            }
            $directory = fread($fp, $count * 16);
            if (strlen($directory) !== $count * 16) {
                return false;
            }
            for ($i = 0; $i < $count; $i++) {
                $entry = substr($directory, $i * 16, 16);
                $length = unpack('V', substr($entry, 8, 4))[1];
                $offset = unpack('V', substr($entry, 12, 4))[1];
                if ($length === 0 || $offset < 6 + $count * 16 || $offset + $length > $size) {
                    return false;
                }
            }
            return true;
        } finally {
            fclose($fp);
        }
    }

    /**
     * Resample a validated raster with GD, retaining transparency for PNG/WebP.
     * Favicon images are centered on a transparent square without cropping.
     *
     * @param string $field logo or favicon.
     * @param array $upload Validated file.
     * @return array Normalized temporary file record.
     */
    private static function normalize(string $field, array $upload): array {
        global $CFG;

        if (($upload['extension'] ?? '') === 'ico') {
            return $upload;
        }

        $source = (string)$upload['tmp_name'];
        // Recheck metadata before decoding even if the caller supplied values.
        $info = @getimagesize($source);
        if (!$info || !self::has_capacity((int)$info[0], (int)$info[1])) {
            throw new invalid_parameter_exception(get_string('setupimagememoryerror', 'theme_learnlingo'));
        }
        $type = (int)$info[2];
        if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new invalid_parameter_exception(get_string('setupimageformaterror', 'theme_learnlingo'));
        }

        if ($type === IMAGETYPE_JPEG) {
            $input = @imagecreatefromjpeg($source);
        } else if ($type === IMAGETYPE_PNG) {
            $input = @imagecreatefrompng($source);
        } else {
            $input = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false;
        }
        if (!$input) {
            throw new invalid_parameter_exception(get_string('setupimageformaterror', 'theme_learnlingo'));
        }

        $output = null;
        $path = false;
        try {
            $width = imagesx($input);
            $height = imagesy($input);
            if ($field === 'logo') {
                $scale = min(1.0, self::LOGO_MAX_WIDTH / $width, self::LOGO_MAX_HEIGHT / $height);
                $destwidth = max(1, (int)round($width * $scale));
                $destheight = max(1, (int)round($height * $scale));
                $canvaswidth = $destwidth;
                $canvasheight = $destheight;
                $destx = 0;
                $desty = 0;
            } else {
                $scale = min(self::FAVICON_SIZE / $width, self::FAVICON_SIZE / $height);
                $destwidth = max(1, (int)round($width * $scale));
                $destheight = max(1, (int)round($height * $scale));
                $canvaswidth = self::FAVICON_SIZE;
                $canvasheight = self::FAVICON_SIZE;
                $destx = (int)floor(($canvaswidth - $destwidth) / 2);
                $desty = (int)floor(($canvasheight - $destheight) / 2);
            }

            $output = imagecreatetruecolor($canvaswidth, $canvasheight);
            if (!$output) {
                throw new invalid_parameter_exception(get_string('setupimagememoryerror', 'theme_learnlingo'));
            }
            $png = $field === 'favicon' || $type !== IMAGETYPE_JPEG;
            if ($png) {
                imagealphablending($output, false);
                imagesavealpha($output, true);
                $clear = imagecolorallocatealpha($output, 0, 0, 0, 127);
                imagefill($output, 0, 0, $clear);
            }
            if (!imagecopyresampled($output, $input, $destx, $desty, 0, 0,
                    $destwidth, $destheight, $width, $height)) {
                throw new invalid_parameter_exception(get_string('setupimageformaterror', 'theme_learnlingo'));
            }

            $path = @tempnam($CFG->tempdir, 'learnlingo-img-');
            if ($path === false) {
                throw new invalid_parameter_exception(get_string('setupimageuploaderror', 'theme_learnlingo'));
            }
            $ok = $png ? imagepng($output, $path, 6) : imagejpeg($output, $path, 86);
            if (!$ok) {
                throw new invalid_parameter_exception(get_string('setupimageuploaderror', 'theme_learnlingo'));
            }
            return [
                'tmp_name' => $path,
                'extension' => $png ? 'png' : 'jpg',
                'mimetype' => $png ? 'image/png' : 'image/jpeg',
                'generated' => true,
            ];
        } catch (\Throwable $error) {
            if ($path !== false && is_file($path)) {
                @unlink($path);
            }
            throw $error;
        } finally {
            imagedestroy($input);
            if ($output) {
                imagedestroy($output);
            }
        }
    }

    /**
     * Re-encode all raster uploads before changing any stored image.
     *
     * @param array $uploads Validated source files (or fixtures in tests).
     * @return void
     */
    public static function save(array $uploads): void {
        global $USER;

        $prepared = [];
        try {
            foreach ($uploads as $field => $upload) {
                if (!in_array($field, ['logo', 'favicon'], true)) {
                    continue;
                }
                $prepared[$field] = self::normalize($field, $upload);
            }

            $context = context_system::instance();
            $fs = get_file_storage();
            foreach ($prepared as $field => $upload) {
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

                // Old file content is removed only after the new one is stored.
                foreach ($fs->get_area_files($context->id, 'core_admin', $field, 0, 'id', false) as $existing) {
                    if ($existing->get_filepath() !== '/' || $existing->get_filename() !== $filename) {
                        $existing->delete();
                    }
                }
            }
        } finally {
            foreach ($prepared as $upload) {
                if (!empty($upload['generated']) && is_file($upload['tmp_name'])) {
                    @unlink($upload['tmp_name']);
                }
            }
        }
    }

    /**
     * Get the current site image preview through Moodle's File API.
     *
     * @param string $field logo or favicon.
     * @return string File URL or empty string.
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
