<?php
// This file is part of Moodle - http://moodle.org/
// @package theme_learnlingo
// @copyright 2026 Eduardo Kraus
// @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

require_once(__DIR__ . '/../../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !\theme_learnlingo\pages::ready()) {
    throw new moodle_exception('invalidrequest');
}
$deletepage = optional_param('deletepage', 0, PARAM_INT);
$deletecategory = optional_param('deletecategory', 0, PARAM_INT);
if ($deletepage > 0) {
    $DB->delete_records('theme_learnlingo_pages', ['id' => $deletepage]);
} else if ($deletecategory > 0) {
    // Never cascade-delete pages. Move them first, resolving any slug collisions.
    if ($DB->record_exists('theme_learnlingo_pages', ['categoryid' => $deletecategory])) {
        throw new moodle_exception('custompagescategorynotempty', 'theme_learnlingo');
    }
    $DB->delete_records('theme_learnlingo_pagecats', ['id' => $deletecategory]);
} else {
    throw new moodle_exception('invalidrequest');
}
theme_reset_all_caches();
redirect(new moodle_url('/admin/settings.php', ['section' => 'themesettinglearnlingo']));
