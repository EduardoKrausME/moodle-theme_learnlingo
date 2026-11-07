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
 * Save visual-editor content and manage its image assets.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/editor-lib.php');

use theme_learnlingo\pages;

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);
require_sesskey();

$action = required_param('action', PARAM_RAW_TRIMMED);
$target = theme_learnlingo_editor_target(required_param('target', PARAM_ALPHA));
$id = optional_param('id', 0, PARAM_INT);
$component = 'theme_learnlingo';
$filearea = 'editor_assets';

if ($action === 'page-save') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new invalid_parameter_exception('Editor saving requires POST');
    }
    // Both inputs are source code. Do not pass HTML or SCSS through PARAM_TEXT.
    $html = required_param('html', PARAM_RAW);
    $scss = required_param('scss', PARAM_RAW);
    if ($target === 'page') {
        if (trim($html) === '') {
            throw new invalid_parameter_exception('Custom page HTML cannot be empty');
        }
        $page = $DB->get_record('theme_learnlingo_pages', ['id' => $id], '*', MUST_EXIST);
        $page->content = $html;
        $page->scss = $scss;
        // The current schema uses content/scss, and save_page validates and
        // preserves all menu, access, publication and categorisation metadata.
        pages::save_page($page);
    } else {
        // Match the constraints used by admin_setting_configcode for these fields.
        if (preg_match('~@(at-root|import|use|forward|extend|keyframes|font-face|charset|namespace|property)\b~i',
                $scss)) {
            throw new invalid_parameter_exception(get_string('jsonvalidatescssdirective', 'theme_learnlingo'));
        }
        set_config($target . 'html', $html, $component);
        set_config($target === 'homepage' ? 'homepagescss' : 'footerscss', $scss, $component);
        theme_reset_all_caches();
    }
    $data = theme_learnlingo_editor_data($target, $id);
    redirect($data['returnurl'], get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action === 'file-list') {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, $component, $filearea, false, 'timecreated DESC', false);
    $items = [];
    foreach ($files as $file) {
        $items[] = [
            'id' => $file->get_id(),
            'name' => $file->get_filename(),
            'type' => 'image',
            'src' => theme_learnlingo_editor_file_url($file),
            'size' => $file->get_filesize(),
            'delete' => true,
        ];
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($items);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new invalid_parameter_exception('Editor file changes require POST');
}

if ($action === 'file-upload') {
    if (empty($_FILES['files']) || !is_uploaded_file($_FILES['files']['tmp_name'])) {
        throw new invalid_parameter_exception('No uploaded image');
    }
    $upload = $_FILES['files'];
    if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 10 * 1024 * 1024) {
        throw new invalid_parameter_exception('Image upload failed or exceeded 10 MiB');
    }
    $extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
    $types = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];
    $imageinfo = @getimagesize($upload['tmp_name']);
    if (!isset($types[$extension]) || !$imageinfo || $imageinfo['mime'] !== $types[$extension]) {
        throw new invalid_parameter_exception('Only PNG, JPEG, GIF and WebP images are accepted');
    }
    $fs = get_file_storage();
    // Store each upload under its own item id so equal filenames never collide.
    $file = $fs->create_file_from_pathname([
        'contextid' => $context->id,
        'component' => $component,
        'filearea' => $filearea,
        'itemid' => random_int(1, 2147483647),
        'filepath' => '/',
        'filename' => clean_filename($upload['name']),
        'userid' => $USER->id,
    ], $upload['tmp_name']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([[
        'id' => $file->get_id(),
        'name' => $file->get_filename(),
        'type' => 'image',
        'src' => theme_learnlingo_editor_file_url($file),
        'size' => $file->get_filesize(),
        'delete' => true,
    ]]);
    exit;
}

if ($action === 'file-delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $fileid = (int)($data['id'] ?? 0);
    $file = $fileid ? get_file_storage()->get_file_by_id($fileid) : false;
    if (!$file || $file->get_contextid() !== $context->id ||
            $file->get_component() !== $component || $file->get_filearea() !== $filearea) {
        throw new invalid_parameter_exception('Image does not belong to the LearnLingo editor');
    }
    $file->delete();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true]);
    exit;
}

throw new invalid_parameter_exception('Invalid LearnLingo editor action');
