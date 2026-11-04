<?php
// This file is part of Moodle - http://moodle.org/
// @package theme_learnlingo
// @copyright 2026 Eduardo Kraus
// @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

require_once(__DIR__ . '/../../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
$id = required_param('id', PARAM_INT);
$page = $DB->get_record('theme_learnlingo_pages', ['id' => $id], '*', MUST_EXIST);
$category = $page->categoryid ?
    $DB->get_record('theme_learnlingo_pagecats', ['id' => $page->categoryid], '*', MUST_EXIST) : null;
\theme_learnlingo\pages::render($page, $category, true);
