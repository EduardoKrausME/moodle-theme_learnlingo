<?php
// This file is part of Moodle - http://moodle.org/
// @package theme_learnlingo
// @copyright 2026 Eduardo Kraus
// @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

require_once(__DIR__ . '/../../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

if (!\theme_learnlingo\pages::ready()) {
    throw new moodle_exception('custompagesneedupgrade', 'theme_learnlingo');
}
$deletepage = optional_param('deletepage', 0, PARAM_INT);
$deletecategory = optional_param('deletecategory', 0, PARAM_INT);
if (($deletepage > 0) === ($deletecategory > 0)) {
    throw new moodle_exception('invalidrequest');
}

$table = $deletepage > 0 ? 'theme_learnlingo_pages' : 'theme_learnlingo_pagecats';
$recordid = $deletepage ?: $deletecategory;
$record = $DB->get_record($table, ['id' => $recordid], '*', MUST_EXIST);

$adminurl = new moodle_url('/admin/settings.php', ['section' => 'themesettinglearnlingo']);
$params = $deletepage > 0 ? ['deletepage' => $deletepage] : ['deletecategory' => $deletecategory];
$url = new moodle_url('/theme/learnlingo/admin/manage_pages.php', $params);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('custompagesconfirmdelete', 'theme_learnlingo'));
$PAGE->set_heading(get_string('custompagesconfirmdelete', 'theme_learnlingo'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    if (optional_param('confirm', 0, PARAM_INT) !== 1) {
        throw new moodle_exception('invalidrequest');
    }
    if ($deletepage > 0) {
        $DB->delete_records('theme_learnlingo_pages', ['id' => $deletepage]);
    } else {
        if ($DB->record_exists('theme_learnlingo_pages', ['categoryid' => $deletecategory])) {
            throw new moodle_exception('custompagescategorynotempty', 'theme_learnlingo');
        }
        $DB->delete_records('theme_learnlingo_pagecats', ['id' => $deletecategory]);
    }
    theme_reset_all_caches();
    redirect($adminurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
if ($deletecategory > 0 && $DB->record_exists('theme_learnlingo_pages', ['categoryid' => $deletecategory])) {
    echo $OUTPUT->notification(get_string('custompagescategorynotempty', 'theme_learnlingo'),
        'notifyproblem');
    echo $OUTPUT->single_button($adminurl, get_string('back'), 'get');
} else {
    $confirmurl = new moodle_url('/theme/learnlingo/admin/manage_pages.php',
        $params + ['confirm' => 1]);
    echo $OUTPUT->confirm(get_string('custompagesconfirmdelete', 'theme_learnlingo') .
        ' ' . s($record->title ?? $record->name), $confirmurl, $adminurl);
}
echo $OUTPUT->footer();
