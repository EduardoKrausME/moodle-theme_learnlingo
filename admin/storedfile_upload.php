<?php
// This file is part of Moodle - https://moodle.org/
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
 * Upload or delete a theme image directly through Moodle's File API.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$definitions = [
    'core_admin/logo' => [
        'area' => 'logo',
        'types' => ['.jpg', '.png'],
    ],
    'core_admin/favicon' => [
        'area' => 'favicon',
        'types' => ['image'],
    ],
    'theme_boost/loginbackgroundimage' => [
        'area' => 'loginbackgroundimage',
        'types' => ['.png', '.jpg', '.jpeg', '.webp'],
    ],
    'theme_learnlingo/loginlogo' => [
        'area' => 'loginlogo',
        'types' => ['.png', '.jpg', '.jpeg', '.webp', '.svg'],
    ],
];

$settingname = required_param('setting', PARAM_RAW_TRIMMED);
$action = required_param('action', PARAM_ALPHA);

if (!isset($definitions[$settingname]) || !in_array($action, ['upload', 'delete'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => get_string('invalidparameter')]);
    exit;
}

$definition = $definitions[$settingname];
$setting = new \theme_learnlingo\admin_setting_configstoredfilemodern(
    $settingname,
    '',
    '',
    $definition['area'],
    0,
    ['maxfiles' => 1, 'accepted_types' => $definition['types']]
);
$setting->set_updatedcallback('theme_reset_all_caches');

try {
    // Always create a private, fresh user draft. An interrupted upload cannot
    // overwrite the existing image or corrupt another form's draft contents.
    $draftid = file_get_unused_draft_itemid();
    $usercontext = context_user::instance($USER->id);
    $fs = get_file_storage();
    $filename = '';

    if ($action === 'upload') {
        if (empty($_FILES['image']) || !is_array($_FILES['image'])) {
            throw new moodle_exception('filemodernuploaderror', 'theme_learnlingo');
        }

        $upload = $_FILES['image'];
        if ($upload['error'] !== UPLOAD_ERR_OK ||
                !is_uploaded_file($upload['tmp_name']) ||
                !is_string($upload['name'])) {
            throw new moodle_exception('filemodernuploaderror', 'theme_learnlingo');
        }

        $filename = clean_param($upload['name'], PARAM_FILE);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            throw new moodle_exception('filemodernuploaderror', 'theme_learnlingo');
        }

        $filetypes = new \core_form\filetypes_util();
        $allowed = $filetypes->normalize_file_types($definition['types']);
        if (!$filetypes->is_allowed_file_type($filename, $allowed) ||
                strpos((string)mimeinfo('type', $filename), 'image/') !== 0) {
            throw new moodle_exception('filemoderntypeerror', 'theme_learnlingo');
        }

        $limit = get_user_max_upload_file_size(
            context_system::instance(),
            $CFG->maxbytes,
            -1
        );
        if ($upload['size'] <= 0 || ($limit > 0 && $upload['size'] > $limit)) {
            throw new moodle_exception('filemodernsizeerror', 'theme_learnlingo');
        }

        $record = [
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => $USER->id,
            'author' => fullname($USER),
            'license' => $CFG->sitedefaultlicense ?: 'allrightsreserved',
        ];
        $fs->create_file_from_pathname($record, $upload['tmp_name']);
    }

    // Reuse core's normal write path to persist the file in its correct
    // component/filearea and invoke the established cache-reset callback.
    $previous = $setting->get_setting();
    $result = $setting->write_setting($draftid);
    if ($result !== '') {
        throw new moodle_exception('filemodernuploaderror', 'theme_learnlingo');
    }
    $setting->post_write_settings($previous);

    $previewurl = $filename === '' ? '' :
        moodle_url::make_draftfile_url($draftid, '/', $filename)->out(false);
    echo json_encode([
        'success' => true,
        'draftid' => $draftid,
        'filename' => $filename,
        'previewurl' => $previewurl,
    ]);
} catch (Throwable $exception) {
    http_response_code(400);
    $message = $exception instanceof moodle_exception ?
        $exception->getMessage() : get_string('filemodernuploaderror', 'theme_learnlingo');
    echo json_encode(['success' => false, 'message' => $message]);
}
