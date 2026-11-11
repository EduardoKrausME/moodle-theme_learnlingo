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
 * Administrative page for LearnLingo custom pages.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

use core\output\notification;
use theme_learnlingo\form\page_form;
use theme_learnlingo\pages;

require_login();
require_capability('moodle/site:config', context_system::instance());
$id = optional_param('id', 0, PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$url = new moodle_url('/theme/learnlingo/admin/page.php', $id ? ['id' => $id] : []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('custompageseditpage', 'theme_learnlingo'));
$PAGE->set_heading(get_string('custompageseditpage', 'theme_learnlingo'));

if (!pages::ready()) {
    throw new moodle_exception('custompagesneedupgrade', 'theme_learnlingo');
}
$record = $id ? $DB->get_record('theme_learnlingo_pages', ['id' => $id], '*', MUST_EXIST) : null;
$form = new page_form($url, ['categories' => pages::categories(), 'pageid' => $id]);
if ($record) {
    $form->set_data($record);
} else {
    $form->set_data((object)['categoryid' => $categoryid]);
}
// The page tree lives on the dedicated Pages settings tab.
$pagesurl = new moodle_url('/admin/settings.php', ['section' => 'theme_learnlingo_pages']);
if ($form->is_cancelled()) {
    redirect($pagesurl);
}
if ($data = $form->get_data()) {
    $data->id = $id;
    $savedid = pages::save_page($data);
    redirect(new moodle_url('/theme/learnlingo/admin/page.php', ['id' => $savedid]),
        get_string('changessaved'), null, notification::NOTIFY_SUCCESS);
}

$PAGE->requires->js_call_amd('theme_learnlingo/page_editor', 'init', [[
    'oneLine' => get_string('codeeditorline', 'theme_learnlingo'),
    'manyLines' => get_string('codeeditorlines', 'theme_learnlingo'),
    'hint' => get_string('codeeditorhint', 'theme_learnlingo'),
    'expand' => get_string('codeeditorfullscreen', 'theme_learnlingo'),
    'collapse' => get_string('codeeditorcollapse', 'theme_learnlingo'),
]]);
echo $OUTPUT->header();
if ($id > 0) {
    // Keep a direct route back to the complete page tree while editing.
    echo html_writer::div(
        html_writer::link($pagesurl, get_string('custompagesbacktolist', 'theme_learnlingo'),
            ['class' => 'btn btn-outline-secondary']),
        'mb-3'
    );
}
$form->display();
echo $OUTPUT->footer();
