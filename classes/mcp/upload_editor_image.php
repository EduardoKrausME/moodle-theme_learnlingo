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
 * MCP write tool for LearnLingo editor image uploads.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\mcp;

use context;
use context_system;
use local_mcp\course\image_service;
use local_mcp\exception\api_exception;
use local_mcp\security\authenticated_identity;
use local_mcp\write\tool\base_tool;
use theme_learnlingo\editor_assets;

/**
 * Upload one image with a confirmation preview, preserving page/HTML settings.
 */
final class upload_editor_image extends base_tool {
    /**
     * @return string MCP tool name.
     */
    public function get_name(): string {
        return 'learnlingo_upload_editor_image';
    }

    /**
     * @return string Human-readable title.
     */
    public function get_title(): string {
        return 'Upload an image to the LearnLingo visual editor';
    }

    /**
     * @return string Tool instructions.
     */
    public function get_description(): string {
        return 'Upload an image to the same theme_learnlingo/editor_assets area used by '
            . 'the visual editor file-upload action. The image becomes publicly accessible '
            . 'through pluginfile.php for reuse in homepagehtml, footerhtml and custom pages. '
            . 'Supply a filename whose extension matches the real image bytes, plus exactly '
            . 'one source: image_base64 (PNG/JPEG/GIF/WebP up to 10 MiB, optionally a data URI) '
            . 'or image_url (public HTTPS PNG/JPEG/WebP, up to 5 MiB using the safe local_mcp '
            . 'image decoder). Preview before approval; the response does not echo Base64 '
            . 'or modify existing HTML/SCSS, theme settings, branding or uploaded images. '
            . 'After confirmation, use the returned file.src URL in HTML or use '
            . 'learnlingo_get_editor_images to inspect the uploaded file.';
    }

    /**
     * @return array Strict MCP argument schema.
     */
    public function get_input_schema(): array {
        return $this->object_schema([
            'filename' => ['type' => 'string', 'maxLength' => 255,
                'description' => 'Filename with matching .png, .jpg, .jpeg, .gif or .webp extension.'],
            'image_base64' => ['type' => 'string',
                'description' => 'Base64 image bytes or image data URI; maximum 10 MiB decoded.'],
            'image_url' => ['type' => 'string',
                'description' => 'Public HTTPS PNG/JPEG/WebP URL; max 5 MiB. Alternative to image_base64.'],
            'expected_sha256' => ['type' => 'string', 'pattern' => '^[a-f0-9]{64}$',
                'description' => 'Optional SHA-256 from the preview; reject a changed image source.'],
        ], ['filename']);
    }

    /**
     * @return string Moodle capability required for a public asset write.
     */
    public function get_required_capability(): string {
        return 'moodle/site:config';
    }

    /**
     * @param array $arguments Tool inputs.
     * @return context System context.
     */
    public function resolve_context(array $arguments): context {
        return context_system::instance();
    }

    /**
     * @return bool A preview must never create files.
     */
    public function supports_dry_run(): bool {
        return true;
    }

    /**
     * Decode and validate input without writing to Moodle File API.
     *
     * @param array $arguments MCP request fields.
     * @return array Validated metadata and image bytes.
     */
    private static function prepare(array $arguments): array {
        foreach (array_keys($arguments) as $name) {
            if (!in_array($name, ['filename', 'image_base64', 'image_url', 'expected_sha256'], true)) {
                throw new api_exception('invalid_image_field', 400, 'Unknown editor upload field.');
            }
        }
        if (!isset($arguments['filename']) || !is_string($arguments['filename'])) {
            throw new api_exception('invalid_filename', 400, 'Provide the image filename.');
        }
        $hasbase64 = array_key_exists('image_base64', $arguments);
        $hasurl = array_key_exists('image_url', $arguments);
        if ($hasbase64 === $hasurl) {
            throw new api_exception('image_source_required', 400,
                'Supply exactly one of image_base64 or image_url.');
        }

        if ($hasbase64) {
            if (!is_string($arguments['image_base64'])) {
                throw new api_exception('invalid_image_base64', 400);
            }
            $encoded = $arguments['image_base64'];
            if (preg_match('~^data:image/(?:png|jpe?g|gif|webp);base64,~i', $encoded, $match)) {
                $encoded = substr($encoded, strlen($match[0]));
            }
            if (strlen($encoded) > (int)ceil(editor_assets::MAX_BYTES * 4 / 3) + 8) {
                throw new api_exception('image_too_large', 400, 'Editor images are limited to 10 MiB.');
            }
            $bytes = base64_decode($encoded, true);
            if ($bytes === false) {
                throw new api_exception('invalid_image_base64', 400);
            }
        } else {
            if (!is_string($arguments['image_url'])) {
                throw new api_exception('invalid_image_url', 400);
            }
            // Reuse local_mcp's HTTPS, redirect and private-host restrictions.
            // That decoder intentionally limits URL-sourced images to 5 MiB.
            [$bytes] = image_service::decode(['image_url' => $arguments['image_url']]);
        }

        $metadata = editor_assets::validate($arguments['filename'], $bytes);
        $sha256 = hash('sha256', $bytes);
        if (array_key_exists('expected_sha256', $arguments) &&
                (!is_string($arguments['expected_sha256']) ||
                !preg_match('/^[a-f0-9]{64}$/D', $arguments['expected_sha256']) ||
                !hash_equals($sha256, $arguments['expected_sha256']))) {
            throw new api_exception('image_changed', 409,
                'The image differs from the preview. Review the upload again.');
        }
        return ['bytes' => $bytes, 'metadata' => $metadata, 'sha256' => $sha256];
    }

    /**
     * @param array $arguments Image upload fields.
     * @param authenticated_identity $identity Connected administrator.
     * @return array Non-writing preview.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        $prepared = self::prepare($arguments);
        return [
            'filename' => $prepared['metadata']['filename'],
            'mimetype' => $prepared['metadata']['mimetype'],
            'bytes' => $prepared['metadata']['size'],
            'width' => $prepared['metadata']['width'],
            'height' => $prepared['metadata']['height'],
            'sha256' => $prepared['sha256'],
            'destination' => 'theme_learnlingo/editor_assets',
            'public_url' => true,
            'requires_confirmation' => true,
        ];
    }

    /**
     * @param array $arguments Approved image upload.
     * @param authenticated_identity $identity Connected administrator.
     * @return array Newly stored editor asset and ready-to-use URL.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        $prepared = self::prepare($arguments);
        $file = editor_assets::save($prepared['metadata']['filename'],
            $prepared['bytes'], (int)$identity->userid);
        return [
            'success' => true,
            'file' => $file,
            'url' => $file['src'],
            'html' => '<img src="' . htmlspecialchars($file['src'], ENT_QUOTES, 'UTF-8') . '" alt="">',
            'sha256' => $prepared['sha256'],
        ];
    }
}
