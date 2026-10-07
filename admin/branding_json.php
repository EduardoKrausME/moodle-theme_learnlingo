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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU GPL for details.

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
if (optional_param('action', '', PARAM_ALPHA) === 'import') {
    require_sesskey();
    $importjson = optional_param('importjson', '', PARAM_RAW);
    try {
        // Parse and validate every field, including SCSS, HTML and SVG, before
        // changing any Moodle config record.
        $configuration = branding_exchange::parse($importjson);
        branding_exchange::apply($configuration);
        redirect(new moodle_url($url, ['saved' => 1]));
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

$export = branding_exchange::export();
$prompt = branding_exchange::prompt($export);
$exportjson = branding_exchange::json($export);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('jsonpagetitle', 'theme_learnlingo'));
if (optional_param('saved', 0, PARAM_BOOL)) {
    echo $OUTPUT->notification(get_string('jsonimportsuccess', 'theme_learnlingo'), 'success');
}
echo html_writer::tag('p', get_string('jsonpageintro', 'theme_learnlingo'));
echo html_writer::tag('p', get_string('jsonfilesnote', 'theme_learnlingo'), ['class' => 'text-muted']);

echo $OUTPUT->heading(get_string('jsonprompttitle', 'theme_learnlingo'), 3);
echo html_writer::tag('p', get_string('jsonpromptdesc', 'theme_learnlingo'));
echo html_writer::tag('label', get_string('jsonpromptlabel', 'theme_learnlingo'),
    ['for' => 'learnlingo-brand-prompt', 'class' => 'form-label']);
echo html_writer::tag('textarea', s($prompt), [
    'id' => 'learnlingo-brand-prompt',
    'class' => 'form-control mb-2 font-monospace',
    'rows' => '23',
    'readonly' => 'readonly',
    'spellcheck' => 'false',
]);
echo html_writer::tag('button', get_string('jsoncopy', 'theme_learnlingo'), [
    'type' => 'button',
    'id' => 'learnlingo-copy-prompt',
    'class' => 'btn btn-secondary mb-4',
]);

echo $OUTPUT->heading(get_string('jsonimporttitle', 'theme_learnlingo'), 3);
echo html_writer::tag('p', get_string('jsonimportdesc', 'theme_learnlingo'));
if ($error !== '') {
    echo $OUTPUT->notification(s($error), 'error');
}
echo html_writer::start_tag('form', ['action' => $url->out(false), 'method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'import']);
echo html_writer::tag('label', get_string('jsonimportlabel', 'theme_learnlingo'), [
    'for' => 'learnlingo-brand-json',
    'class' => 'form-label',
]);
echo html_writer::tag('textarea', s($importjson), [
    'id' => 'learnlingo-brand-json',
    'name' => 'importjson',
    'class' => 'form-control mb-3 font-monospace',
    'rows' => '20',
    'required' => 'required',
    'spellcheck' => 'false',
]);
echo html_writer::tag('p', get_string('jsonimportwarning', 'theme_learnlingo'),
    ['class' => 'text-muted']);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('jsonimportbutton', 'theme_learnlingo'),
    'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');

// The raw JSON is also available without the prompt for backups/diffing.
echo $OUTPUT->heading(get_string('jsonrawtitle', 'theme_learnlingo'), 3, 'mt-5');
echo html_writer::tag('p', get_string('jsonrawdesc', 'theme_learnlingo'));
echo html_writer::tag('textarea', s($exportjson), [
    'id' => 'learnlingo-brand-raw',
    'class' => 'form-control font-monospace',
    'rows' => '10',
    'readonly' => 'readonly',
    'spellcheck' => 'false',
]);

$PAGE->requires->js_init_code(<<<'JAVASCRIPT'
(function() {
    const btn = document.getElementById('learnlingo-copy-prompt');
    const area = document.getElementById('learnlingo-brand-prompt');
    if (!btn || !area) { return; }
    btn.addEventListener('click', async function() {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(area.value);
            } else {
                area.focus();
                area.select();
                document.execCommand('copy');
            }
        } catch (err) {
            area.focus();
            area.select();
        }
    });
})();
JAVASCRIPT
);
echo $OUTPUT->footer();
