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
 * A drawer based layout for the boost theme.
 *
 * @package   theme_learnlingo
 * @copyright 2021 Bas Brands
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\navigation\output\more_menu;
use core\output\select_menu;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

// Course hero is only rendered on the course main page. Activity pages keep the native header.
$showcoursehero = !isset($PAGE->theme->settings->showcoursehero)
    || !empty($PAGE->theme->settings->showcoursehero);
$coursehero = $showcoursehero
    && !empty($PAGE->course)
    && (int)$PAGE->course->id !== SITEID
    && strpos((string)$PAGE->pagetype, 'course-view-') === 0;
if ($coursehero) {
    $extraclasses[] = 'learnlingo-has-course-hero';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

// Body attributes are built after the homepage layout mode is resolved.
$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $selectmenu = new select_menu(
            'tertiarynavigation',
            $overflowdata->urls,
            $overflowdata->selected,
        );
        $selectmenu->set_label($overflowdata->label, $overflowdata->labelattributes);
        $overflow = $selectmenu->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$coursefullname = (!empty($PAGE->course) && !empty($PAGE->course->fullname)) ? format_string(
    $PAGE->course->fullname,
    true,
    ['context' => context_course::instance($PAGE->course->id), 'escape' => false],
) : '';
$courseurl = !empty($PAGE->course) ? new moodle_url('/course/view.php', ['id' => $PAGE->course->id]) : null;

$showdashboardhero = !isset($PAGE->theme->settings->showdashboardhero)
    || !empty($PAGE->theme->settings->showdashboardhero);
$dashboardhero = $PAGE->pagetype === 'my-index'
    && isloggedin()
    && !isguestuser()
    && !$PAGE->user_is_editing()
    && $showdashboardhero;
$homepagemode = theme_learnlingo_homepage_mode($PAGE->theme);
$ishtmlhomepage = $PAGE->pagetype === 'site-index'
    && !$PAGE->user_is_editing()
    && $homepagemode === 'html'
    && theme_learnlingo_homepage_html($PAGE->theme) !== '';
$customfrontpage = $PAGE->pagetype === 'site-index'
    && !$PAGE->user_is_editing()
    && ($ishtmlhomepage || $homepagemode === 'learnlingo');
if ($ishtmlhomepage && (!isset($PAGE->theme->settings->homepagefullwidth)
        || !empty($PAGE->theme->settings->homepagefullwidth))) {
    $extraclasses[] = 'learnlingo-html-home-fullwidth';
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$coursesurl = new moodle_url('/my/courses.php');
$dashboardtitle = $dashboardhero
    ? get_string('dashboardtitle', 'theme_learnlingo', $USER->firstname)
    : '';

$courseherosummary = '';
$courseheroimage = '';
$courseprogressavailable = false;
$courseprogress = 0;
$courseprogresslabel = '';
$courseheroeyebrow = get_string('courseheroeyebrowdefault', 'theme_learnlingo');
$personalxp = false;
$personalxpactivityreward = 0;

if ($coursehero) {
    $coursecontext = context_course::instance($PAGE->course->id);

    if (!empty($PAGE->theme->settings->courseheroeyebrow)) {
        $courseheroeyebrow = format_string($PAGE->theme->settings->courseheroeyebrow, true, [
            'context' => $coursecontext,
        ]);
    }

    $showcourseherosummary = !isset($PAGE->theme->settings->showcourseherosummary)
        || !empty($PAGE->theme->settings->showcourseherosummary);
    if ($showcourseherosummary && !empty($PAGE->course->summary)) {
        $formattedsummary = format_text($PAGE->course->summary, $PAGE->course->summaryformat, [
            'context' => $coursecontext,
            'para' => false,
        ]);
        $plaincoursesummary = trim(preg_replace('/\\s+/', ' ', strip_tags($formattedsummary)));
        $courseherosummary = shorten_text($plaincoursesummary, 220);
    }

    $courselist = new core_course_list_element($PAGE->course);
    foreach ($courselist->get_course_overviewfiles() as $overviewfile) {
        if (!$overviewfile->is_valid_image()) {
            continue;
        }
        $courseheroimage = moodle_url::make_pluginfile_url(
            $overviewfile->get_contextid(),
            $overviewfile->get_component(),
            $overviewfile->get_filearea(),
            $overviewfile->get_itemid(),
            $overviewfile->get_filepath(),
            $overviewfile->get_filename(),
            false,
        )->out(false);
        break;
    }

    $showcourseheroprogress = !isset($PAGE->theme->settings->showcourseheroprogress)
        || !empty($PAGE->theme->settings->showcourseheroprogress);
    if ($showcourseheroprogress && isloggedin() && !isguestuser()) {
        $progress = \core_completion\progress::get_course_progress_percentage($PAGE->course, $USER->id);
        if ($progress !== null) {
            $courseprogress = max(0, min(100, (int)round($progress)));
            $courseprogressavailable = true;
            $courseprogresslabel = get_string('courseheroprogressvalue', 'theme_learnlingo', $courseprogress);
        }
    }

    $showpersonalxp = !isset($PAGE->theme->settings->showpersonalxp)
        || !empty($PAGE->theme->settings->showpersonalxp);
    if (
        $showpersonalxp
        && class_exists('\\local_personalxp\\service\\xp_manager')
        && isloggedin()
        && !isguestuser()
        && has_capability('local/personalxp:view', $coursecontext)
    ) {
        $xpstate = \local_personalxp\service\xp_manager::get_course_display_state(
            (int) $USER->id,
            (int) $PAGE->course->id
        );

        if ($xpstate['enabled']) {
            $personalxpactivityreward = (int) $xpstate['activitycompletionxp'];
            $personalxp = [
                'totalxp' => (int) $xpstate['totalxp'],
                'currentlevel' => (string) $xpstate['currentlevel'],
                'nextlevel' => $xpstate['nextlevel'],
                'hasnextlevel' => $xpstate['nextlevel'] !== null,
                'levelprogress' => (int) $xpstate['levelprogress'],
                'xptonext' => (int) $xpstate['xptonext'],
                'nextlabel' => $xpstate['nextlevel'] === null
                    ? get_string('highestlevel', 'local_personalxp')
                    : get_string('nextlevel', 'local_personalxp', (object) [
                        'xp' => (int) $xpstate['xptonext'],
                        'level' => (string) $xpstate['nextlevel'],
                    ]),
                'url' => (new moodle_url('/local/personalxp/index.php', [
                    'courseid' => $PAGE->course->id,
                ]))->out(false),
            ];
        }
    }
}

$templatecontext = [
    'brandlogo' => theme_learnlingo_logo_url($PAGE->theme, 'logo'),
    'hasbrandlogo' => (bool) theme_learnlingo_logo_url($PAGE->theme, 'logo'),
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'coursefullname' => $coursefullname,
    'courseurl' => $courseurl ? $courseurl->out(false) : null,
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $customfrontpage ? false : ($secondarynavigation ?: false),
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
    'dashboardhero' => $dashboardhero,
    'coursehero' => $coursehero,
    'courseheroeyebrow' => $courseheroeyebrow,
    'courseherosummary' => $courseherosummary,
    'courseheroimage' => $courseheroimage,
    'courseprogressavailable' => $courseprogressavailable,
    'courseprogress' => $courseprogress,
    'courseprogresslabel' => $courseprogresslabel,
    'courseprogressheading' => get_string('courseheroprogress', 'theme_learnlingo'),
    'coursestats' => $courseprogressavailable || !empty($personalxp),
    'personalxp' => $personalxp,
    'personalxpactivityreward' => $personalxpactivityreward,
    'personalxpactivityrewardlabel' => $personalxpactivityreward > 0
        ? get_string('coursexpreward', 'theme_learnlingo', $personalxpactivityreward)
        : '',
    'personalxpincourselabel' => get_string('coursexpincourse', 'theme_learnlingo'),
    'personalxplevellabel' => get_string('coursexplevel', 'theme_learnlingo'),
    'showfullheader' => !$dashboardhero && !$customfrontpage,
    'dashboardeyebrow' => theme_learnlingo_brand_text('dashboardeyebrowtext', 'dashboardeyebrow'),
    'dashboardtitle' => $dashboardtitle,
    'dashboarddescription' => theme_learnlingo_brand_text('dashboarddescriptiontext', 'dashboarddescription'),
    'dashboardcta' => get_string('dashboardcta', 'theme_learnlingo'),
    'goaltitle' => get_string('goaltitle', 'theme_learnlingo'),
    'goaltime' => get_string('goaltime', 'theme_learnlingo'),
    'goalsubtitle' => get_string('goalsubtitle', 'theme_learnlingo'),
    'goalcta' => get_string('goalcta', 'theme_learnlingo'),
    'coursesurl' => $coursesurl->out(false),
];

echo $OUTPUT->render_from_template('theme_learnlingo/drawers', $templatecontext);
