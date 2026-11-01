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

// Installing a theme is not the same as selecting it as the site's theme.
// Do not silently change the active site theme when LearnLingo was installed.
$themeactive = ($CFG->theme ?? '') === 'learnlingo';
$adminurl = new moodle_url('/' . ($CFG->admin ?? 'admin') . '/index.php');

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
        // Only the explicit activation button can change the site's theme.
        if (!$themeactive) {
            set_config('theme', 'learnlingo');
            theme_reset_all_caches();
        }
        redirect(new moodle_url($url, ['activated' => 1]));
    }
    if ($action !== 'save' || !$themeactive) {
        throw new moodle_exception('invalidrequest', 'error');
    }

    $palette = required_param('palette', PARAM_ALPHANUMEXT);
    // A site using the custom HTML homepage may keep that mode unchanged.
    // The wizard offers exactly two illustrated choices without deleting HTML.
    $mode = optional_param('homepagemode', '', PARAM_ALPHA);
    $footermode = required_param('footermode', PARAM_ALPHA);
    if (($palette !== 'preserve' && !array_key_exists($palette, $palettes))
            || !in_array($mode, ['', 'learnlingo', 'moodle'], true)
            || !in_array($footermode, ['moodle', 'html'], true)) {
        throw new invalid_parameter_exception('Unknown LearnLingo colour palette or layout');
    }

    if ($palette !== 'preserve') {
        $fields = ['brandcolor', 'accentcolor', 'canvascolor', 'textcolor', 'footer_background_color'];
        foreach ($fields as $index => $field) {
            set_config($field, $palettes[$palette][$index], 'theme_learnlingo');
        }
    }
    // Keep existing HTML homepage mode when neither illustrated option was selected.
    if ($mode !== '') {
        set_config('homepagemode', $mode, 'theme_learnlingo');
    }
    // Do not touch HTML, SCSS, uploaded images, texts or other unselected fields.
    set_config('footermode', $footermode, 'theme_learnlingo');
    foreach (['homepagefullwidth', 'showdashboardhero', 'showcoursehero'] as $field) {
        set_config($field, optional_param($field, 0, PARAM_BOOL), 'theme_learnlingo');
    }
    set_config('setup_pending', 0, 'theme_learnlingo');
    theme_reset_all_caches();
    redirect(new moodle_url($url, ['saved' => 1]));
}

$PAGE->requires->css(new moodle_url('/theme/learnlingo/styles/setup.css'));

if (!$themeactive) {
    // This page is intentionally the first step when a different theme is active.
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

$PAGE->requires->js_call_amd('theme_learnlingo/branding_json', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_learnlingo/setup', [
    'actionurl' => $url->out(false),
    'sesskey' => sesskey(),
    'saved' => optional_param('saved', 0, PARAM_BOOL),
    'palettes' => $paletteitems,
    'layouts' => $layouts,
    'footers' => $footers,
    'fullwidth' => (bool)(get_config('theme_learnlingo', 'homepagefullwidth') ?? true),
    'dashboardhero' => (bool)(get_config('theme_learnlingo', 'showdashboardhero') ?? true),
    'coursehero' => (bool)(get_config('theme_learnlingo', 'showcoursehero') ?? true),
    'mcpinstalled' => get_config('local_mcp', 'version') !== false,
    'mcpurl' => 'https://github.com/EduardoKrausME/moodle-local_mcp',
    'chatgpturl' => 'https://chatgpt.com/',
    'jsonurl' => (new moodle_url('/theme/learnlingo/admin/branding_json.php'))->out(false),
    'prompt' => \theme_learnlingo\\branding_exchange::prompt(
        \theme_learnlingo\\branding_exchange::export()),
    'customhtmlmode' => $currentmode === 'html',
    'advancedurl' => (new moodle_url('/admin/settings.php',
        ['section' => 'theme_learnlingo_branding']))->out(false),
    'homeurl' => (new moodle_url('/'))->out(false),
]);
echo $OUTPUT->footer();
