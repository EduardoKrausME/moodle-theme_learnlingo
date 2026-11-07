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
 * Shared helpers for the LearnLingo visual editor.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Check the editor target before using it in queries or configuration names.
 *
 * @param string $target homepage, footer or page
 * @return string
 */
function theme_learnlingo_editor_target(string $target): string {
    if (!in_array($target, ['homepage', 'footer', 'page'], true)) {
        throw new invalid_parameter_exception('Invalid LearnLingo editor target');
    }
    return $target;
}

/**
 * Read existing source and its canonical settings/page return URL.
 *
 * The homepage sentinel "config" is never interpreted as editable HTML.
 * Custom-page metadata is intentionally not touched by the editor.
 *
 * @param string $target homepage, footer or page
 * @param int $id Custom page id
 * @return array
 */
function theme_learnlingo_editor_data(string $target, int $id = 0): array {
    global $DB;

    theme_learnlingo_editor_target($target);
    if ($target === 'page') {
        if ($id < 1) {
            throw new invalid_parameter_exception('Missing custom page id');
        }
        $page = $DB->get_record('theme_learnlingo_pages', ['id' => $id], '*', MUST_EXIST);
        return [
            'title' => $page->title,
            'html' => (string)$page->content,
            'scss' => (string)$page->scss,
            'returnurl' => new moodle_url('/theme/learnlingo/admin/page.php', ['id' => $id]),
        ];
    }

    $html = (string)get_config('theme_learnlingo', $target . 'html');
    return [
        'title' => get_string($target === 'homepage' ? 'homepagehtml' : 'footerhtml', 'theme_learnlingo'),
        'html' => $target === 'homepage' && $html === 'config' ? '' : $html,
        'scss' => (string)get_config('theme_learnlingo', $target === 'homepage' ? 'homepagescss' : 'footerscss'),
        'returnurl' => new moodle_url('/admin/settings.php', [
            'section' => $target === 'homepage' ? 'theme_learnlingo_homepage' : 'theme_learnlingo_footer',
        ]),
    ];
}

/**
 * Generate a sesskey-protected action URL with no user-supplied return URL.
 *
 * @param string $action Action identifier
 * @param string $target Target identifier
 * @param int $id Custom page id
 * @return moodle_url
 */
function theme_learnlingo_editor_action_url(string $action, string $target, int $id): moodle_url {
    return new moodle_url('/theme/learnlingo/_editor/actions.php', [
        'action' => $action,
        'target' => theme_learnlingo_editor_target($target),
        'id' => $id,
        'sesskey' => sesskey(),
    ]);
}

/**
 * Serve the stored image URI used by the visual editor.
 *
 * @param stored_file $file Stored image
 * @return string
 */
function theme_learnlingo_editor_file_url(stored_file $file): string {
    return moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename()
    )->out(false);
}
