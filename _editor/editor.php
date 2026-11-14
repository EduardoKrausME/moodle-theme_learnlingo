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
 * GrapesJS Studio editor for existing homepage, footer and custom pages.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/editor-lib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$target = theme_learnlingo_editor_target(required_param('target', PARAM_ALPHA));
$id = optional_param('id', 0, PARAM_INT);
$data = theme_learnlingo_editor_data($target, $id);
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/theme/learnlingo/_editor/editor.php',
    ['target' => $target, 'id' => $id]));
$PAGE->set_title(get_string('visualeditoropen', 'theme_learnlingo'));

$initial = json_encode([
    'html' => $data['html'],
    'scss' => $data['scss'],
    'title' => $data['title'],
    'urls' => [
        'upload' => theme_learnlingo_editor_action_url('file-upload', $target, $id)->out(false),
    ],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT |
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

$esc = static function(string $value): string {
    return s($value);
};

$replacements = [
    '%%LL_LANG%%' => $esc(current_language()),
    '%%LL_MOODLE_HEAD%%' => $PAGE->requires->get_head_code($PAGE, $OUTPUT),
    '%%LL_MOODLE_BODY%%' => $PAGE->requires->get_top_of_body_code($OUTPUT),
    '%%LL_MOODLE_FOOT%%' => $PAGE->requires->get_end_code(),
    '%%LL_TITLE%%' => $esc($data['title']),
    '%%LL_VISUAL_LABEL%%' => $esc(get_string('visualeditormode', 'theme_learnlingo')),
    '%%LL_SOURCE_LABEL%%' => $esc(get_string('visualeditorsource', 'theme_learnlingo')),
    '%%LL_RETURN_URL%%' => $esc($data['returnurl']->out(false)),
    '%%LL_CANCEL%%' => $esc(get_string('cancel')),
    '%%LL_SAVE%%' => $esc(get_string('save')),
    '%%LL_SAVE_URL%%' => $esc(theme_learnlingo_editor_action_url('page-save', $target, $id)->out(false)),
    '%%LL_MUSTACHE_NOTE%%' => $esc(get_string('visualeditormustache', 'theme_learnlingo')),
    '%%LL_INITIAL_DATA%%' => $initial,
    '%%LL_EDITOR_ASSETS_MISSING%%' => json_encode(get_string('visualeditorassetsmissing', 'theme_learnlingo'),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
    '%%LL_EDITOR_ASSET_NOT_DELETABLE%%' => json_encode(get_string('visualeditorassetnotdeletable', 'theme_learnlingo'),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
    '%%LL_EDITOR_NOT_READY%%' => json_encode(get_string('visualeditornotready', 'theme_learnlingo'),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
    '%%LL_EDITOR_FILES_MISSING%%' => json_encode(get_string('visualeditorfilesmissing', 'theme_learnlingo'),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
    '%%LL_EDITOR_HTML_MISSING%%' => json_encode(get_string('visualeditorhtmlmissing', 'theme_learnlingo'),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
    '%%LL_TOKEN_ERROR%%' => json_encode(get_string('visualeditortokenerror', 'theme_learnlingo'),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
    '%%LL_RELOAD_WARNING%%' => json_encode(get_string('visualeditorreload', 'theme_learnlingo'),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
    '%%LL_VISUAL_WARNING%%' => json_encode(get_string('visualeditorwarning', 'theme_learnlingo'),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
];

header('Content-Type: text/html; charset=utf-8');
echo strtr(file_get_contents(__DIR__ . '/studio.html'), $replacements);
