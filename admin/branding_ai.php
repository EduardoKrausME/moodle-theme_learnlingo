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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License.
//
// AI-assisted institutional identity suggestions.

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/theme/learnlingo/lib.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$url = new moodle_url('/theme/learnlingo/admin/branding_ai.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('aibranding', 'theme_learnlingo'));
$PAGE->set_heading(get_string('aibranding', 'theme_learnlingo'));

$fields = [
    'brandcolor', 'accentcolor', 'canvascolor', 'textcolor',
    'dashboardeyebrowtext', 'dashboarddescriptiontext',
    'loginpaneltitletext', 'loginpaneldescriptiontext', 'loginpaneltaglinetext',
];
$colorfields = array_slice($fields, 0, 4);
$action = optional_param('action', '', PARAM_ALPHA);
$description = optional_param('description', '', PARAM_TEXT);
$error = '';

if ($action !== '') {
    require_sesskey();
}

if ($action === 'generate') {
    if (!class_exists('\\local_ai_bridge\\api')) {
        $error = get_string('aibridgemissing', 'theme_learnlingo');
    } else if (trim($description) === '') {
        $error = get_string('aidescriptionrequired', 'theme_learnlingo');
    } else {
        try {
            // The bridge purpose 'theme-branding' must be configured by the administrator.
            // Only plain text proposals are requested; image generation is not supported here.
            $prompt = 'Propose a Moodle institutional visual identity from this description: ' .
                $description . "\n" .
                'Respond with ONLY a JSON object containing these keys: ' . implode(', ', $fields) . '. ' .
                'The first four keys must contain #RRGGBB hex colours. ' .
                'All other keys must contain short, natural Brazilian Portuguese copy. ' .
                'Avoid hardcoded product, provider and client names that are not in the description.';
            $response = \\local_ai_bridge\\api::generate('theme-branding', $prompt);
            $raw = trim((string)$response->text);
            $raw = preg_replace('/^\x60\x60\x60(?:json)?\s*|\s*\x60\x60\x60$/i', '', $raw);
            $data = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                throw new moodle_exception('invaliddata');
            }
            $proposal = [];
            foreach ($fields as $field) {
                if (!isset($data[$field]) || !is_string($data[$field])) {
                    throw new moodle_exception('invaliddata');
                }
                $value = trim($data[$field]);
                if (in_array($field, $colorfields, true)) {
                    if (!preg_match('/^#[0-9a-fA-F]{6}$/D', $value)) {
                        throw new moodle_exception('invaliddata');
                    }
                } else {
                    $value = clean_param($value, PARAM_TEXT);
                    if ($value === '' || core_text::strlen($value) > 350) {
                        throw new moodle_exception('invaliddata');
                    }
                }
                $proposal[$field] = $value;
            }
            $SESSION->learnlingobrandproposal = $proposal;
        } catch (Throwable $e) {
            unset($SESSION->learnlingobrandproposal);
            $error = get_string('aigenerationfailed', 'theme_learnlingo') . ': ' . $e->getMessage();
        }
    }
}
if ($action === 'apply') {
    if (empty($SESSION->learnlingobrandproposal) || !is_array($SESSION->learnlingobrandproposal)) {
        $error = get_string('ainoproposal', 'theme_learnlingo');
    } else {
        foreach ($fields as $field) {
            if (isset($SESSION->learnlingobrandproposal[$field])) {
                set_config($field, $SESSION->learnlingobrandproposal[$field], 'theme_learnlingo');
            }
        }
        unset($SESSION->learnlingobrandproposal);
        theme_reset_all_caches();
        redirect(new moodle_url('/admin/settings.php', ['section' => 'themesettinglearnlingo']),
            get_string('aiapplied', 'theme_learnlingo'));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('aibranding', 'theme_learnlingo'));
echo html_writer::tag('p', get_string('aibrandingdesc', 'theme_learnlingo'));
if ($error !== '') {
    echo $OUTPUT->notification(s($error), 'error');
}
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'generate']);
echo html_writer::tag('label', get_string('aidescription', 'theme_learnlingo'),
    ['for' => 'branding-description']);
echo html_writer::tag('textarea', s($description), [
    'id' => 'branding-description', 'name' => 'description', 'rows' => 5,
    'class' => 'form-control mb-3', 'required' => 'required', 'maxlength' => 2000,
]);
echo html_writer::empty_tag('input', [
    'type' => 'submit', 'class' => 'btn btn-primary',
    'value' => get_string('aigenerate', 'theme_learnlingo'),
]);
echo html_writer::end_tag('form');

if (!empty($SESSION->learnlingobrandproposal)) {
    echo $OUTPUT->heading(get_string('aipreview', 'theme_learnlingo'), 3);
    echo html_writer::start_tag('dl');
    foreach ($fields as $field) {
        echo html_writer::tag('dt', s(get_string($field, 'theme_learnlingo')));
        echo html_writer::tag('dd', s($SESSION->learnlingobrandproposal[$field]));
    }
    echo html_writer::end_tag('dl');
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'apply']);
    echo html_writer::empty_tag('input', [
        'type' => 'submit', 'class' => 'btn btn-success',
        'value' => get_string('aiapply', 'theme_learnlingo'),
    ]);
    echo html_writer::end_tag('form');
}
echo $OUTPUT->footer();
