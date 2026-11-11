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
 * Export a ChatGPT-ready prompt and import the returned strict JSON.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

use theme_learnlingo\branding_exchange;

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$url = new moodle_url('/theme/learnlingo/admin/branding_json.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('jsonpagetitle', 'theme_learnlingo'));
$PAGE->set_heading(get_string('jsonpagetitle', 'theme_learnlingo'));

$error = '';
$importjson = '';
$report = '';
$reportclass = '';
$issues = [];

// PHP can discard the entire POST body when post_max_size is exceeded.
// Explain the real server limit instead of silently losing the import.
$postmax = trim((string)ini_get('post_max_size'));
$postmaxbytes = 0;
if (preg_match('/^(\d+(?:\.\d+)?)\s*([KMG]?)B?$/i', $postmax, $postmaxmatches)) {
    $units = ['' => 1, 'K' => 1024, 'M' => 1048576, 'G' => 1073741824];
    $postmaxbytes = (int)((float)$postmaxmatches[1] * $units[strtoupper($postmaxmatches[2])]);
}
$contentlength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $postmaxbytes > 0 &&
    $contentlength > $postmaxbytes) {
    $error = get_string('jsonerrorposttoolarge', 'theme_learnlingo', (object)[
        'received' => $contentlength,
        'limit' => $postmaxbytes,
        'setting' => $postmax,
    ]);
} else if (optional_param('action', '', PARAM_ALPHA) === 'import') {
    require_sesskey();
    $importjson = optional_param('importjson', '', PARAM_RAW);
    try {
        // Upload is optional. When supplied, it takes precedence over pasted text.
        $upload = $_FILES['importfile'] ?? null;
        if (is_array($upload) && (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploaderror = (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploaderror !== UPLOAD_ERR_OK) {
                $errornames = [
                    UPLOAD_ERR_INI_SIZE => 'upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'partial',
                    UPLOAD_ERR_NO_TMP_DIR => 'temporary_directory',
                    UPLOAD_ERR_CANT_WRITE => 'disk_write',
                    UPLOAD_ERR_EXTENSION => 'extension',
                ];
                throw new moodle_exception('jsonerrorupload', 'theme_learnlingo', '',
                    $errornames[$uploaderror] ?? (string)$uploaderror);
            }
            $filename = (string)($upload['name'] ?? '');
            if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'json') {
                throw new moodle_exception('jsonerrorfiletype', 'theme_learnlingo');
            }
            $tmpname = (string)($upload['tmp_name'] ?? '');
            if (!is_uploaded_file($tmpname)) {
                throw new moodle_exception('jsonerrorupload', 'theme_learnlingo', '', 'invalid_upload');
            }
            $contents = file_get_contents($tmpname);
            if ($contents === false) {
                throw new moodle_exception('jsonerrorupload', 'theme_learnlingo', '', 'read_failed');
            }
            $importjson = $contents;
        }
        if (trim($importjson) === '') {
            throw new moodle_exception('jsonerrorempty', 'theme_learnlingo');
        }
        // Save valid fields; report every rejected setting without changing it.
        $validation = branding_exchange::validate_import($importjson);
        $result = branding_exchange::apply_import($validation['valid']);
        $issues = $validation['issues'];
        $report = get_string('jsonimportreport', 'theme_learnlingo', (object)[
            'updated' => $result['updated'],
            'unchanged' => $result['unchanged'],
            'rejected' => count($issues),
        ]);
        $reportclass = $issues ? 'alert-warning' : 'alert-success';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$export = branding_exchange::export();
$prompt = branding_exchange::prompt($export);
$exportjson = branding_exchange::json($export);

$PAGE->requires->css(new moodle_url('/theme/learnlingo/styles/branding_json.css'));
$PAGE->requires->js_call_amd('theme_learnlingo/branding_json', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_learnlingo/branding_json', [
    'actionurl' => $url->out(false),
    'sesskey' => sesskey(),
    'saved' => optional_param('saved', 0, PARAM_BOOL),
    'error' => $error,
    'report' => $report,
    'reportclass' => $reportclass,
    'hasissues' => !empty($issues),
    'issues' => $issues,
    'prompt' => $prompt,
    'importjson' => $importjson,
    'exportjson' => $exportjson,
]);
echo $OUTPUT->footer();
