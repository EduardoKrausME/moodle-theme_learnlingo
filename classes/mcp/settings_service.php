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
 * Validated settings and logo operations for the LearnLingo MCP extension.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_learnlingo\mcp;

use context_system;
use local_mcp\course\file_url_normalizer;
use local_mcp\course\image_service;
use local_mcp\exception\api_exception;
use stored_file;
use theme_learnlingo\branding_exchange;

/**
 * Use the same validation contract as the LearnLingo JSON importer.
 */
final class settings_service {
    /** Uploaded branding file areas and their Moodle File API components. */
    private const FILEAREAS = [
        'logo' => 'core_admin',
        'favicon' => 'core_admin',
        'loginlogo' => 'theme_learnlingo',
    ];

    /**
     * Read all portable settings and existing uploaded image metadata.
     *
     * @param bool $includeimages Whether to attach small raster logos as MCP image blocks.
     * @return array Snapshot and state_hash.
     */
    public static function snapshot(bool $includeimages = false): array {
        global $OUTPUT;
        $data = branding_exchange::export();
        $images = [];
        $content = [];
        $hashimages = [];
        foreach (self::FILEAREAS as $area => $component) {
            $files = self::files($area);
            $image = $files ? reset($files) : null;
            if ($image) {
                if ($area === 'logo') {
                    $imageurl = $OUTPUT->get_logo_url();
                } else if ($area === 'loginlogo') {
                    // Use the theme revision in the URL, as admin_setting_configstoredfile does.
                    $imageurl = \theme_config::load('learnlingo')->setting_file_url('loginlogo', 'loginlogo');
                } else {
                    $imageurl = \moodle_url::make_pluginfile_url(
                        $image->get_contextid(), 'core_admin', 'favicon', 0,
                        $image->get_filepath(), $image->get_filename(), false
                    );
                }
                $metadata = [
                    'filename' => $image->get_filename(),
                    'mimetype' => $image->get_mimetype(),
                    'filesize' => $image->get_filesize(),
                    'contenthash' => $image->get_contenthash(),
                    'url' => $imageurl ? (string)$imageurl : null,
                ];
                // File API metadata can outlive the physical blob; do not fail the
                // entire MCP export if a logo is temporarily or permanently unreadable.
                if ($includeimages && $image->get_filesize() <= image_service::MAX_INLINE_BYTES &&
                        in_array($image->get_mimetype(), ['image/png', 'image/jpeg', 'image/webp'], true) &&
                        get_file_storage()->get_file_system()->is_file_readable_remotely_by_storedfile($image)) {
                    $content[] = [
                        'type' => 'image',
                        'mimeType' => $image->get_mimetype(),
                        'data' => base64_encode($image->get_content()),
                    ];
                }
                // Account for unexpected multiple files in the area when checking concurrent writes.
                $hashimages[$area] = array_map(
                    static fn($file): array => [$file->get_filename(), $file->get_contenthash()],
                    $files
                );
            } else {
                $metadata = null;
                $hashimages[$area] = [];
            }
            $images[$area] = ['uploaded' => $metadata];
        }
        $data['images'] = $images;
        $data['state_hash'] = hash('sha256', json_encode(
            [$data['settings'], $hashimages],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ));
        if ($content) {
            $data['_mcp_content'] = $content;
        }
        return $data;
    }

    /**
     * Validate a partial settings patch and optional logo operations before writing.
     *
     * @param array $arguments MCP tool arguments.
     * @return array Normalized write plan.
     */
    public static function prepare(array $arguments): array {
        $before = self::snapshot();
        if (array_key_exists('expected_hash', $arguments) &&
                !hash_equals($before['state_hash'], (string)$arguments['expected_hash'])) {
            throw new api_exception('settings_changed', 409,
                'The LearnLingo settings changed. Read them again before updating.');
        }

        $data = branding_exchange::export();
        $updates = [];
        $input = $arguments['settings'] ?? [];
        if (!is_array($input)) {
            throw new api_exception('invalid_settings', 400);
        }
        $definitions = branding_exchange::definitions();
        foreach ($input as $name => $value) {
            if (!array_key_exists($name, $definitions)) {
                throw new api_exception('unknown_setting', 400, 'Unknown LearnLingo setting: ' . $name);
            }
            if ($definitions[$name][0] === 'html' && is_string($value)) {
                $value = file_url_normalizer::normalize($value);
            }
            $data['settings'][$name] = $value;
        }
        // Validate the complete resulting document, not just the changed fields.
        $validated = branding_exchange::parse(branding_exchange::json($data));
        foreach ($input as $name => $unused) {
            if ($before['settings'][$name] !== $validated['settings'][$name]) {
                $updates[$name] = $validated['settings'][$name];
            }
        }

        $images = [];
        $requested = $arguments['images'] ?? [];
        if (!is_array($requested)) {
            throw new api_exception('invalid_images', 400);
        }
        if ($requested && !isset($arguments['expected_hash'])) {
            throw new api_exception('expected_hash_required', 400,
                'Read LearnLingo settings and provide expected_hash before replacing branding images.');
        }
        foreach ($requested as $name => $source) {
            if (!array_key_exists($name, self::FILEAREAS) || !is_array($source)) {
                throw new api_exception('invalid_image_area', 400);
            }
            foreach (array_keys($source) as $key) {
                if (!in_array($key, ['remove', 'image_base64', 'image_url'], true)) {
                    throw new api_exception('invalid_image_field', 400);
                }
            }
            if (array_key_exists('remove', $source) && !is_bool($source['remove'])) {
                throw new api_exception('invalid_image_action', 400);
            }
            if (!empty($source['remove'])) {
                if (count($source) !== 1) {
                    throw new api_exception('invalid_image_action', 400,
                        'Removing an image cannot also provide an image source.');
                }
                $images[$name] = ['remove' => true];
                continue;
            }
            [$bytes, $mime, $ext] = image_service::decode($source);
            $allowedmimes = $name === 'loginlogo'
                ? ['image/png', 'image/jpeg', 'image/webp']
                : ['image/png', 'image/jpeg'];
            if (!in_array($mime, $allowedmimes, true)) {
                throw new api_exception('invalid_brand_image_type', 400,
                    $name === 'loginlogo'
                        ? 'The login logo accepts PNG, JPEG or WebP images through MCP.'
                        : 'The Moodle site logo and favicon accept PNG or JPEG files.');
            }
            $images[$name] = ['remove' => false, 'bytes' => $bytes, 'mime' => $mime, 'extension' => $ext];
        }
        if (!$updates && !$images) {
            throw new api_exception('no_changes', 400, 'Specify a changed setting or an image operation.');
        }
        return ['before' => $before, 'settings' => $updates, 'images' => $images];
    }

    /**
     * Apply the validated patch. Unrelated settings and file areas are untouched.
     *
     * @param array $prepared Validated write plan.
     * @param int $userid Connected user's Moodle ID.
     * @return void
     */
    public static function apply(array $prepared, int $userid): void {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        foreach ($prepared['settings'] as $name => $value) {
            set_config($name, is_bool($value) ? (int)$value : $value, 'theme_learnlingo');
        }
        $context = context_system::instance();
        foreach ($prepared['images'] as $area => $source) {
            $component = self::FILEAREAS[$area];
            foreach (self::files($area) as $file) {
                $file->delete();
            }
            if ($source['remove']) {
                set_config($area, '', $component);
            } else {
                $file = get_file_storage()->create_file_from_string([
                    'contextid' => $context->id,
                    'component' => $component,
                    'filearea' => $area,
                    'itemid' => 0,
                    'filepath' => '/',
                    'filename' => $area . '-' . bin2hex(random_bytes(6)) . '.' . $source['extension'],
                    'userid' => $userid,
                    'mimetype' => $source['mime'],
                    'license' => 'allrightsreserved',
                ], $source['bytes']);
                // The storedfile setting holds the path, not merely a presence flag.
                set_config($area, $file->get_filepath() . $file->get_filename(), $component);
            }
        }
        $transaction->allow_commit();
        theme_reset_all_caches();
    }

    /**
     * List all real files (not the virtual "." directory) in a logo area.
     *
     * @param string $area Moodle filearea.
     * @return stored_file[]
     */
    private static function files(string $area): array {
        $context = context_system::instance();
        $component = self::FILEAREAS[$area];
        return array_values(get_file_storage()->get_area_files(
            $context->id, $component, $area, 0, 'id DESC', false
        ));
    }
}
