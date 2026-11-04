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

use theme_learnlingo\form\category_form;
use theme_learnlingo\pages;

require_login();
require_capability('moodle/site:config', context_system::instance());
$id = optional_param('id', 0, PARAM_INT);
$url = new moodle_url('/theme/learnlingo/admin/category.php', $id ? ['id' => $id] : []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('custompageseditcategory', 'theme_learnlingo'));
$PAGE->set_heading(get_string('custompageseditcategory', 'theme_learnlingo'));
if (!pages::ready()) {
    throw new moodle_exception('custompagesneedupgrade', 'theme_learnlingo');
}
$record = $id ? $DB->get_record('theme_learnlingo_pagecats', ['id' => $id], '*', MUST_EXIST) : null;
$form = new category_form($url);
if ($record) {
    $form->set_data($record);
}
if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/settings.php', ['section' => 'themesettinglearnlingo']));
}
if ($data = $form->get_data()) {
    $data->id = $id;
    pages::save_category($data);
    redirect(new moodle_url('/admin/settings.php', ['section' => 'themesettinglearnlingo']),
        get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
