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
 * Visual first-run configuration for LearnLingo.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use theme_learnlingo\branding_exchange;
use theme_learnlingo\dark_mode;
use theme_learnlingo\setup_images;
use theme_learnlingo\setup_login_images;
use theme_learnlingo\setup_steps;

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
$dashboardenabled = setup_steps::dashboard_enabled($CFG);
$mycoursesenabled = setup_steps::mycourses_enabled($CFG);
$stepcount = setup_steps::count($CFG);
$studentstep = setup_steps::student_enabled($CFG);
$loginstep = setup_steps::login_step($CFG);
$darkstep = setup_steps::dark_step($CFG);
$completed = max(0, min($stepcount, (int)get_config('theme_learnlingo', 'setup_completed_step')));
// The last step disappears when local_boost_dark is installed. If the
// administrator had already saved Login, do not leave the welcome sentinel.
if ($pending && $darkstep === null && $completed >= $stepcount) {
    if ((string)get_config('theme_learnlingo', 'homepagehtml') === 'config') {
        set_config('homepagehtml', '', 'theme_learnlingo');
    }
    set_config('setup_pending', 0, 'theme_learnlingo');
    theme_reset_all_caches();
    $pending = false;
}
$allowed = $pending ? min($stepcount, $completed + 1) : $stepcount;
$step = max(1, min($stepcount, optional_param('step', 1, PARAM_INT)));
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
        // Declining onboarding must also disarm the homepage sentinel.
        if ((string)get_config('theme_learnlingo', 'homepagehtml') === 'config') {
            set_config('homepagehtml', '', 'theme_learnlingo');
            theme_reset_all_caches();
        }
        set_config('setup_pending', 0, 'theme_learnlingo');
        redirect(new moodle_url('/'));
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
    if ($submitted < 1 || $submitted > $stepcount || $submitted > $allowed) {
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
            $uploads = setup_images::validate($_FILES ?? []);
            setup_images::save($uploads);
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
            // An existing HTML homepage is managed in the full theme settings
            // or by authorized integrations, never replaced by this wizard.
            $mode = $currentmode === 'html' ? '' : optional_param('homepagemode', '', PARAM_ALPHA);
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
            foreach (['homepagefullwidth', 'showcoursehero'] as $field) {
                set_config($field, optional_param($field, 0, PARAM_BOOL), 'theme_learnlingo');
            }
            theme_reset_all_caches();
            break;

        case 4:
            if (!$studentstep) {
                // With Dashboard and My courses both disabled, login is step 4.
                // Fall through to case 5, which processes the same form.
                break;
            }
            // These are the actual Moodle-wide navigation settings, not a
            // LearnLingo-only presentation toggle. Do not allow both off.
            $newdashboard = (bool)optional_param('enabledashboard', 0, PARAM_BOOL);
            $newmycourses = (bool)optional_param('enablemycourses', 0, PARAM_BOOL);
            if (!setup_steps::valid_selection($newdashboard, $newmycourses)) {
                throw new invalid_parameter_exception(get_string('setupstudentchooseone', 'theme_learnlingo'));
            }
            set_config('enabledashboard', (int)$newdashboard);
            set_config('enablemycourses', (int)$newmycourses);

            // Do not leave the default post-login landing page pointing
            // to a page that was just disabled through this wizard.
            $defaulthomepage = (int)($CFG->defaulthomepage ?? HOMEPAGE_SITE);
            $validhome = setup_steps::default_homepage(
                $defaulthomepage, $newdashboard, $newmycourses);
            if ($validhome !== $defaulthomepage) {
                set_config('defaulthomepage', $validhome);
            }

            set_config('showwelcome', optional_param('showwelcome', 0, PARAM_BOOL), 'theme_learnlingo');
            // Persist the control even when the dashboard is newly enabled.
            set_config('showdashboardhero',
                optional_param('showdashboardhero', 0, PARAM_BOOL), 'theme_learnlingo');
            foreach (['dashboardeyebrowtext', 'dashboarddescriptiontext'] as $field) {
                set_config($field, optional_param($field, '', PARAM_TEXT), 'theme_learnlingo');
            }
            theme_reset_all_caches();
            break;

        case 5:
            // Step 5 is the login page when the optional student step exists.
            // The same handler is also used for login on step 4.
            break;
    }

    if ($submitted === $loginstep) {
        // Process login assets before changing the text fields. A decoding
        // failure must not leave half-updated branding in the database.
        $images = setup_login_images::validate($_FILES ?? []);
        setup_login_images::save($images);
        foreach (['loginpaneltitletext', 'loginpaneldescriptiontext', 'loginpaneltaglinetext'] as $field) {
            set_config($field, optional_param($field, '', PARAM_TEXT), 'theme_learnlingo');
        }
        theme_reset_all_caches();
    }

    // The last enabled step must clear the one-time homepage sentinel.
    if ($submitted === $stepcount) {
        // Once the final step is saved, remove the sentinel, otherwise the
        // homepage would keep offering the wizard. Preserve real custom HTML.
        if ((string)get_config('theme_learnlingo', 'homepagehtml') === 'config') {
            set_config('homepagehtml', '', 'theme_learnlingo');
        }
        set_config('setup_pending', 0, 'theme_learnlingo');
        theme_reset_all_caches();
    }

    set_config('setup_completed_step', max($completed, $submitted), 'theme_learnlingo');
    if ($submitted === $stepcount) {
        redirect(new moodle_url($url, ['step' => $stepcount, 'done' => 1]));
    }
    redirect(new moodle_url($url, ['step' => $submitted + 1, 'saved' => 1]));
}

// This is a standalone static CSS file, not a Moodle theme CSS build. Give
// its URL a revision so browsers/CDNs do not keep an earlier stepper layout
// even after the administrator purges Moodle caches.
$setupcss = dirname(__DIR__) . '/styles/setup.css';
$PAGE->requires->css(new moodle_url('/theme/learnlingo/styles/setup.css', [
    'v' => (int)filemtime($setupcss),
]));

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
for ($number = 1; $number <= $stepcount; $number++) {
    $steps[] = [
        'number' => $number,
        'label' => get_string('setupsteplabel' . ($number === $darkstep ? '6' :
                ($number === $loginstep ? '5' : (string)$number)),
            'theme_learnlingo'),
        'current' => $step === $number,
        'done' => $number <= $completed && $number !== $step,
        'available' => $number <= $allowed,
        'url' => (new moodle_url($url, ['step' => $number]))->out(false),
    ];
}

if ($step >= 2 && $step <= $loginstep) {
    $PAGE->requires->js_call_amd('theme_learnlingo/branding_json', 'init');
}

$done = $step === $stepcount && optional_param('done', 0, PARAM_BOOL);

// The copy/paste prompt is scoped to this step, but retains the validated
// complete JSON schema and all existing values for lossless re-import.
$task = $step === 2
    ? get_string('setupaihomeprompt', 'theme_learnlingo')
    : ($step === 3 ? get_string('setupaifooterprompt', 'theme_learnlingo')
        : ($step === $loginstep ? get_string('setupailoginprompt', 'theme_learnlingo')
            : get_string('setupaidashboardprompt', 'theme_learnlingo')));
$prompt = $step >= 2 && $step <= $loginstep && !$done
    ? $task . "\n\n" .
    branding_exchange::prompt(branding_exchange::export())
    : '';
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_learnlingo/setup', [
    'actionurl' => $url->out(false),
    'sesskey' => sesskey(),
    'step' => $step,
    'steps' => $steps,
    'stepdisplay' => get_string('setupwizardstepof', 'theme_learnlingo',
        (object)['current' => $step, 'total' => $stepcount]),
    'progress' => (int)round($step * 100 / $stepcount),
    'stepcount' => $stepcount,
    'foursteps' => $stepcount === 4,
    'fivesteps' => $stepcount === 5,
    'sixsteps' => $stepcount === 6,
    'threeonly' => $stepcount === 3,
    'stepone' => $step === 1 && !$done,
    'steptwo' => $step === 2 && !$done,
    'stepthree' => $step === 3 && !$done,
    'stepfour' => $studentstep && $step === 4 && !$done,
    'steplogin' => $step === $loginstep && !$done,
    'stepdark' => $darkstep !== null && $step === $darkstep && !$done,
    'darkinstallurl' => dark_mode::install_url()->out(false),
    'darkcodeavailable' => dark_mode::code_available(),
    'laststep' => $step === $stepcount,
    'done' => $done,
    'saved' => optional_param('saved', 0, PARAM_BOOL),
    'previousurl' => $step > 1 ? (new moodle_url($url, ['step' => $step - 1]))->out(false) : '',
    'hasprevious' => $step > 1,
    'palettes' => $paletteitems,
    'layouts' => $layouts,
    'footers' => $footers,
    'fullwidth' => (bool)get_config('theme_learnlingo', 'homepagefullwidth'),
    'dashboardhero' => (bool)get_config('theme_learnlingo', 'showdashboardhero'),
    'showwelcome' => (bool)(get_config('theme_learnlingo', 'showwelcome') !== false
        ? get_config('theme_learnlingo', 'showwelcome') : true),
    'dashboardenabled' => $dashboardenabled,
    'mycoursesenabled' => $mycoursesenabled,
    'dashboardeyebrowtext' => (string)get_config('theme_learnlingo', 'dashboardeyebrowtext'),
    'dashboarddescriptiontext' => (string)get_config('theme_learnlingo', 'dashboarddescriptiontext'),
    'dashboardurl' => (new moodle_url('/my/'))->out(false),
    'mycoursesurl' => (new moodle_url('/my/courses.php'))->out(false),
    'moodlenavigationurl' => (new moodle_url('/' . ($CFG->admin ?? 'admin') . '/settings.php',
        ['section' => 'navigation']))->out(false),
    'coursehero' => (bool)get_config('theme_learnlingo', 'showcoursehero'),
    'mcpinstalled' => get_config('local_mcp', 'version') !== false,
    'mcpurl' => 'https://github.com/EduardoKrausME/moodle-local_mcp',
    'chatgpturl' => 'https://chatgpt.com/',
    'claudeurl' => 'https://claude.ai/',
    'jsonurl' => (new moodle_url('/theme/learnlingo/admin/branding_json.php'))->out(false),
    'prompt' => $prompt,
    'loginpaneltitletext' => (string)get_config('theme_learnlingo', 'loginpaneltitletext'),
    'loginpaneldescriptiontext' => (string)get_config('theme_learnlingo', 'loginpaneldescriptiontext'),
    'loginpaneltaglinetext' => (string)get_config('theme_learnlingo', 'loginpaneltaglinetext'),
    'loginlogourl' => setup_login_images::current_url('loginlogo'),
    'loginbackgroundurl' => setup_login_images::current_url('loginbackground'),
    'loginpreviewurl' => (new moodle_url('/login/index.php'))->out(false),
    'logoimageurl' => setup_images::current_url('logo'),
    'faviconimageurl' => setup_images::current_url('favicon'),
    'customhtmlmode' => $currentmode === 'html',
    'existinghomepagehtml' => $currentmode === 'html'
        && !in_array(trim((string)get_config('theme_learnlingo', 'homepagehtml')), ['', 'config'], true),
    'htmlsettingsurl' => (new moodle_url('/' . ($CFG->admin ?? 'admin') . '/settings.php',
        ['section' => 'theme_learnlingo_homepage']))->out(false),
    'advancedurl' => (new moodle_url('/admin/settings.php',
        ['section' => 'theme_learnlingo_branding']))->out(false),
    'homeurl' => (new moodle_url('/'))->out(false),
]);
echo $OUTPUT->footer();
