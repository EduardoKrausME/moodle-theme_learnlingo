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
 * Process login-only assets from the LearnLingo onboarding assistant.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use context_system;
use invalid_parameter_exception;
use theme_config;
use Throwable;

/**
 * Store the login logo and background in their existing Moodle file areas.
 */
final class setup_login_images {
    /** Maximum dimensions of the logo displayed on the login form. */
    public const LOGO_WIDTH = 1200;
    /** Maximum logo height in pixels. */
    public const LOGO_HEIGHT = 400;
    /** Maximum login background width in pixels. */
    public const BACKGROUND_WIDTH = 1920;
    /** Maximum login background height in pixels. */
    public const BACKGROUND_HEIGHT = 1080;
    /** Maximum decoded source pixels. */
    private const MAX_PIXELS = 24000000;

    /**
     * Validate optional HTTP uploads without trusting their filenames or MIME.
     *
     * @param array $files PHP uploaded file array.
     * @return array Indexed by loginlogo/loginbackground.
     */
    public static function validate(array $files): array {
        $result = [];
        foreach (['loginlogo', 'loginbackground'] as $name) {
            if (!isset($files[$name])) {
                continue;
            }
            $file = $files[$name];
            $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null)
                || !is_uploaded_file($file['tmp_name'])
                || !is_file($file['tmp_name']) || filesize($file['tmp_name']) <= 0) {
                throw new invalid_parameter_exception(get_string('setupimageuploaderror', 'theme_learnlingo'));
            }
            if (!extension_loaded('gd')) {
                throw new invalid_parameter_exception(get_string('setupimagegderror', 'theme_learnlingo'));
            }
            $metadata = @getimagesize($file['tmp_name']);
            if (!$metadata || !self::fits_memory((int)$metadata[0], (int)$metadata[1])) {
                throw new invalid_parameter_exception(get_string('setupimagememoryerror', 'theme_learnlingo'));
            }
            $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
            $type = (int)$metadata[2];
            $supported = ($type === IMAGETYPE_PNG && $extension === 'png')
                || ($type === IMAGETYPE_JPEG && in_array($extension, ['jpg', 'jpeg'], true))
                || ($type === IMAGETYPE_WEBP && $extension === 'webp'
                    && function_exists('imagecreatefromwebp'));
            if (!$supported) {
                throw new invalid_parameter_exception(get_string('setupimageformaterror', 'theme_learnlingo'));
            }
            $result[$name] = ['tmp_name' => $file['tmp_name']];
        }
        return $result;
    }

    /**
     * Avoid compressed-image decompression bombs and memory exhaustion.
     *
     * @param int $width Image width.
     * @param int $height Image height.
     * @return bool
     */
    private static function fits_memory(int $width, int $height): bool {
        $pixels = $width * $height;
        if ($width <= 0 || $height <= 0 || $pixels > self::MAX_PIXELS) {
            return false;
        }
        $limit = trim((string)ini_get('memory_limit'));
        if ($limit === '' || $limit === '-1') {
            return true;
        }
        $number = (float)$limit;
        $unit = strtolower(substr($limit, -1));
        if ($unit === 'g') {
            $number *= 1024 ** 3;
        } else {
            if ($unit === 'm') {
                $number *= 1024 ** 2;
            } else {
                if ($unit === 'k') {
                    $number *= 1024;
                }
            }
        }
        return memory_get_usage(true) + $pixels * 7
            + (self::BACKGROUND_WIDTH * self::BACKGROUND_HEIGHT * 5) + 20 * 1024 ** 2 < $number;
    }

    /**
     * Normalize the source image to a safe display size using PHP GD.
     *
     * @param string $field loginlogo/loginbackground.
     * @param string $source Path to the uploaded image.
     * @return array Temporary file record.
     */
    private static function normalize(string $field, string $source): array {
        global $CFG;

        $metadata = @getimagesize($source);
        if (!$metadata || !self::fits_memory((int)$metadata[0], (int)$metadata[1])) {
            throw new invalid_parameter_exception(get_string('setupimagememoryerror', 'theme_learnlingo'));
        }
        $type = (int)$metadata[2];
        $input = match ($type) {
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp')
                ? @imagecreatefromwebp($source) : false,
            default => false,
        };
        if (!$input) {
            throw new invalid_parameter_exception(get_string('setupimageformaterror', 'theme_learnlingo'));
        }
        $output = false;
        $path = false;
        try {
            $width = imagesx($input);
            $height = imagesy($input);
            $maxwidth = $field === 'loginlogo' ? self::LOGO_WIDTH : self::BACKGROUND_WIDTH;
            $maxheight = $field === 'loginlogo' ? self::LOGO_HEIGHT : self::BACKGROUND_HEIGHT;
            // Do not upscale, crop, or change the source aspect ratio.
            $scale = min(1.0, $maxwidth / $width, $maxheight / $height);
            $newwidth = max(1, (int)round($width * $scale));
            $newheight = max(1, (int)round($height * $scale));
            $output = imagecreatetruecolor($newwidth, $newheight);
            if (!$output) {
                throw new invalid_parameter_exception(get_string('setupimagememoryerror', 'theme_learnlingo'));
            }
            $savepng = $field === 'loginlogo' && $type !== IMAGETYPE_JPEG;
            if ($savepng) {
                imagealphablending($output, false);
                imagesavealpha($output, true);
                imagefill($output, 0, 0, imagecolorallocatealpha($output, 0, 0, 0, 127));
            } else {
                // White background prevents dark/black artifacts from alpha
                // when saving a login background as optimized JPEG.
                imagefill($output, 0, 0, imagecolorallocate($output, 255, 255, 255));
            }
            if (!imagecopyresampled($output, $input, 0, 0, 0, 0,
                $newwidth, $newheight, $width, $height)) {
                throw new invalid_parameter_exception(get_string('setupimageformaterror', 'theme_learnlingo'));
            }
            $path = @tempnam($CFG->tempdir, 'll-login-');
            if ($path === false) {
                throw new invalid_parameter_exception(get_string('setupimageuploaderror', 'theme_learnlingo'));
            }
            $saved = $savepng ? imagepng($output, $path, 6) : imagejpeg($output, $path, 86);
            if (!$saved) {
                throw new invalid_parameter_exception(get_string('setupimageuploaderror', 'theme_learnlingo'));
            }
            return [
                'path' => $path,
                'extension' => $savepng ? 'png' : 'jpg',
                'mimetype' => $savepng ? 'image/png' : 'image/jpeg',
            ];
        } catch (Throwable $error) {
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
     * Normalize all images before replacing any existing Moodle asset.
     *
     * @param array $uploads Validated uploads.
     * @return void
     */
    public static function save(array $uploads): void {
        global $USER;
        $prepared = [];
        try {
            foreach ($uploads as $field => $file) {
                if (!in_array($field, ['loginlogo', 'loginbackground'], true)) {
                    continue;
                }
                $prepared[$field] = self::normalize($field, (string)$file['tmp_name']);
            }

            $context = context_system::instance();
            $fs = get_file_storage();
            foreach ($prepared as $field => $image) {
                $component = $field === 'loginlogo' ? 'theme_learnlingo' : 'theme_boost';
                $filearea = $field === 'loginlogo' ? 'loginlogo' : 'loginbackgroundimage';
                $filename = 'learnlingo-' . $filearea . '-' .
                    substr(hash_file('sha256', $image['path']), 0, 16) . '.' . $image['extension'];
                $record = [
                    'contextid' => $context->id,
                    'component' => $component,
                    'filearea' => $filearea,
                    'itemid' => 0,
                    'filepath' => '/',
                    'filename' => $filename,
                    'mimetype' => $image['mimetype'],
                    'userid' => $USER->id,
                ];
                if (!$fs->file_exists($context->id, $component, $filearea, 0, '/', $filename)) {
                    $fs->create_file_from_pathname($record, $image['path']);
                }
                set_config($filearea, '/' . $filename, $component);
                foreach ($fs->get_area_files($context->id, $component, $filearea, 0, 'id', false) as $existing) {
                    if ($existing->get_filepath() !== '/' || $existing->get_filename() !== $filename) {
                        $existing->delete();
                    }
                }
            }
        } finally {
            foreach ($prepared as $image) {
                if (is_file($image['path'])) {
                    @unlink($image['path']);
                }
            }
        }
    }

    /**
     * Return a revisioned URL for the existing login image.
     *
     * @param string $field loginlogo/loginbackground.
     * @return string
     */
    public static function current_url(string $field): string {
        if (!in_array($field, ['loginlogo', 'loginbackground'], true)) {
            return '';
        }
        $theme = $field === 'loginlogo' ? 'learnlingo' : 'boost';
        $filearea = $field === 'loginlogo' ? 'loginlogo' : 'loginbackgroundimage';
        return (string)(theme_config::load($theme)->setting_file_url($filearea, $filearea) ?: '');
    }
}
