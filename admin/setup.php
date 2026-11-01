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
 * Visual first-run configuration for LearnLingo.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$url = new moodle_url('/theme/learnlingo/admin/setup.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('setuptitle', 'theme_learnlingo'));
$PAGE->set_heading(get_string('setuptitle', 'theme_learnlingo'));

// Only predefined, validated colours can be saved by this page. Custom
// institution palettes remain available in the regular colour settings.
$palettes = [
    'classic' => ['#58cc02', '#8be24a', '#f7faf5', '#263238', '#314755'],
    'ocean' => ['#087eaa', '#29c2d2', '#f1f9fc', '#193c50', '#163f54'],
    'violet' => ['#7450c8', '#bb8df2', '#f8f5ff', '#302347', '#3b3157'],
    'sunset' => ['#d45b42', '#f0b05a', '#fff9f4', '#412d32', '#633b38'],
    'forest' => ['#167758', '#69c79a', '#f2faf5', '#193d35', '#214a3c'],
    'graphite' => ['#3d5d78', '#e6b85a', '#f4f6fa', '#243344', '#253344'],
];

// Keep an explicit 'preserve' choice so existing sites never lose custom colours.
$paletteitems = [[
    'value' => 'preserve',
    'label' => get_string('setuppalettepreserve', 'theme_learnlingo'),
    'description' => get_string('setuppalettepreservedesc', 'theme_learnlingo'),
    'selected' => true,
    'preserve' => true,
]];
foreach ($palettes as $key => $colours) {
    $paletteitems[] = [
        'value' => $key,
        'label' => get_string('setuppalette' . $key, 'theme_learnlingo'),
        'description' => get_string('setuppalettedesc' . $key, 'theme_learnlingo'),
        'selected' => false,
        'preserve' => false,
    ];
}

$currentmode = (string)(get_config('theme_learnlingo', 'homepagemode') ?: 'learnlingo');
$currentfooter = (string)(get_config('theme_learnlingo', 'footermode') ?: 'moodle');
$themeactive = ($CFG->theme ?? '') === 'learnlingo';
$adminurl = new moodle_url('/' . ($CFG->admin ?? 'admin') . '/index.php');

// The site can be configured in several visits. A completed step unlocks the
// next step; returning to earlier steps does not discard subsequent choices.
$pending = (string)get_config('theme_learnlingo', 'setup_pending') === '1';
$completed = max(0, min(3, (int)get_config('theme_learnlingo', 'setup_completed_step')));
$allowed = $pending ? min(3, $completed + 1) : 3;
$step = max(1, min(3, optional_param('step', 1, PARAM_INT)));
if ($step > $allowed) {
    redirect(new moodle_url($url, ['step' => $allowed]));
}

$action = optional_param('action', '', PARAM_ALPHA);
if ($action !== '') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new moodle_exception('invalidrequest', 'error');
    }
    require_sesskey();

    if ($action === 'skip') {
        set_config('setup_pending', 0, 'theme_learnlingo');
        redirect($adminurl);
    }
    if ($action === 'activate') {
        // Changing the site's active theme always requires an explicit POST.
        if (!$themeactive) {
            set_config('theme', 'learnlingo');
            theme_reset_all_caches();
        }
        redirect(new moodle_url($url, ['activated' => 1]));
    }
    if ($action !== 'next' || !$themeactive) {
        throw new moodle_exception('invalidrequest', 'error');
    }

    $submitted = required_param('step', PARAM_INT);
    if ($submitted < 1 || $submitted > 3 || $submitted > $allowed) {
        throw new invalid_parameter_exception('Invalid or locked LearnLingo setup step');
    }

    switch ($submitted) {
        case 1:
            $palette = required_param('palette', PARAM_ALPHANUMEXT);
            if ($palette !== 'preserve' && !array_key_exists($palette, $palettes)) {
                throw new invalid_parameter_exception('Invalid LearnLingo colour palette');
            }
            // Validate uploads and normalize with GD before applying the palette.
            // A decoding/memory failure must leave the previously selected
            // colours and all existing site images untouched.
            $uploads = \theme_learnlingo\setup_images::validate($_FILES ?? []);
            \theme_learnlingo\setup_images::save($uploads);
            if ($palette !== 'preserve') {
                $fields = ['brandcolor', 'accentcolor', 'canvascolor', 'textcolor', 'footer_background_color'];
                foreach ($fields as $index => $field) {
                    set_config($field, $palettes[$palette][$index], 'theme_learnlingo');
                }
            }
            set_config('setup_palette', $palette, 'theme_learnlingo');
            theme_reset_all_caches();
            break;

        case 2:
            // HTML mode is preserved until the administrator selects a new mode.
            $mode = optional_param('homepagemode', '', PARAM_ALPHA);
            if (!in_array($mode, ['', 'learnlingo', 'moodle'], true)) {
                throw new invalid_parameter_exception('Invalid LearnLingo homepage mode');
            }
            if ($mode !== '') {
                set_config('homepagemode', $mode, 'theme_learnlingo');
                theme_reset_all_caches();
            }
            break;

        case 3:
            $footer = required_param('footermode', PARAM_ALPHA);
            if (!in_array($footer, ['moodle', 'html'], true)) {
                throw new invalid_parameter_exception('Invalid LearnLingo footer mode');
            }
            set_config('footermode', $footer, 'theme_learnlingo');
            foreach (['homepagefullwidth', 'showdashboardhero', 'showcoursehero'] as $field) {
                set_config($field, optional_param($field, 0, PARAM_BOOL), 'theme_learnlingo');
            }
            theme_reset_all_caches();
            // ChatGPT, Claude and Moodle MCP are optional within Home and Footer.
            set_config('setup_pending', 0, 'theme_learnlingo');
            break;
    }

    set_config('setup_completed_step', max($completed, $submitted), 'theme_learnlingo');
    if ($submitted === 3) {
        redirect(new moodle_url($url, ['step' => 3, 'done' => 1]));
    }
    redirect(new moodle_url($url, ['step' => $submitted + 1, 'saved' => 1]));
}

$PAGE->requires->css(new moodle_url('/theme/learnlingo/styles/setup.css'));

if (!$themeactive) {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('theme_learnlingo/setup_activate', [
        'actionurl' => $url->out(false),
        'sesskey' => sesskey(),
        'currenttheme' => format_string($CFG->theme ?? 'boost'),
        'activationfailed' => optional_param('activated', 0, PARAM_BOOL),
    ]);
    echo $OUTPUT->footer();
    return;
}

// Re-select the palette used by this wizard, not an arbitrary default.
$palettechoice = (string)(get_config('theme_learnlingo', 'setup_palette') ?: 'preserve');
foreach ($paletteitems as &$item) {
    $item['selected'] = $item['value'] === $palettechoice;
}
unset($item);

$layouts = [];
foreach (['learnlingo', 'moodle'] as $mode) {
    $layouts[] = [
        'value' => $mode,
        'label' => get_string('setuplayout' . $mode, 'theme_learnlingo'),
        'description' => get_string('setuplayoutdesc' . $mode, 'theme_learnlingo'),
        'selected' => $currentmode === $mode,
        'islearnlingo' => $mode === 'learnlingo',
        'ismoodle' => $mode === 'moodle',
    ];
}
$footers = [];
foreach (['moodle', 'html'] as $mode) {
    $footers[] = [
        'value' => $mode,
        'label' => get_string('setupfooter' . $mode, 'theme_learnlingo'),
        'description' => get_string('setupfooterdesc' . $mode, 'theme_learnlingo'),
        'selected' => $currentfooter === $mode,
    ];
}

$steps = [];
for ($number = 1; $number <= 3; $number++) {
    $steps[] = [
        'number' => $number,
        'label' => get_string('setupsteplabel' . $number, 'theme_learnlingo'),
        'current' => $step === $number,
        'done' => $number <= $completed && $number !== $step,
        'available' => $number <= $allowed,
        'url' => (new moodle_url($url, ['step' => $number]))->out(false),
    ];
}

if (in_array($step, [2, 3], true)) {
    $PAGE->requires->js_call_amd('theme_learnlingo/branding_json', 'init');
}

$done = $step === 3 && optional_param('done', 0, PARAM_BOOL);

// The copy/paste prompt is scoped to this step, but retains the validated
// complete JSON schema and all existing values for lossless re-import.
$task = $step === 2
    ? get_string('setupaihomeprompt', 'theme_learnlingo')
    : get_string('setupaifooterprompt', 'theme_learnlingo');
$prompt = in_array($step, [2, 3], true) && !$done
    ? $task . "\n\n" .
        \theme_learnlingo\branding_exchange::prompt(\theme_learnlingo\branding_exchange::export())
    : '';
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_learnlingo/setup', [
    'actionurl' => $url->out(false),
    'sesskey' => sesskey(),
    'step' => $step,
    'steps' => $steps,
    'progress' => (int)round($step * 100 / 3),
    'stepone' => $step === 1 && !$done,
    'steptwo' => $step === 2 && !$done,
    'stepthree' => $step === 3 && !$done,
    'laststep' => $step === 3,
    'done' => $done,
    'saved' => optional_param('saved', 0, PARAM_BOOL),
    'previousurl' => $step > 1 ? (new moodle_url($url, ['step' => $step - 1]))->out(false) : '',
    'hasprevious' => $step > 1,
    'palettes' => $paletteitems,
    'layouts' => $layouts,
    'footers' => $footers,
    'fullwidth' => (bool)get_config('theme_learnlingo', 'homepagefullwidth'),
    'dashboardhero' => (bool)get_config('theme_learnlingo', 'showdashboardhero'),
    'coursehero' => (bool)get_config('theme_learnlingo', 'showcoursehero'),
    'mcpinstalled' => get_config('local_mcp', 'version') !== false,
    'mcpurl' => 'https://github.com/EduardoKrausME/moodle-local_mcp',
    'chatgpturl' => 'https://chatgpt.com/',
    'claudeurl' => 'https://claude.ai/',
    'jsonurl' => (new moodle_url('/theme/learnlingo/admin/branding_json.php'))->out(false),
    'prompt' => $prompt,
    'logoimageurl' => \theme_learnlingo\setup_images::current_url('logo'),
    'faviconimageurl' => \theme_learnlingo\setup_images::current_url('favicon'),
    'customhtmlmode' => $currentmode === 'html',
    'advancedurl' => (new moodle_url('/admin/settings.php',
        ['section' => 'theme_learnlingo_branding']))->out(false),
    'homeurl' => (new moodle_url('/'))->out(false),
]);
echo $OUTPUT->footer();
