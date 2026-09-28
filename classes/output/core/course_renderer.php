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
 * Course renderer overrides.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\output\core;

use core_completion\progress;
use core_course_category;
use core_course_list_element;
use core_course_renderer;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Replaces the standard Moodle front page with the PulsoMed experience.
 */
class course_renderer extends core_course_renderer {

    /**
     * Render the site front page.
     *
     * The standard front page is kept while editing so administrators retain
     * access to configured news, categories, activities and course lists.
     *
     * @return string
     */
    public function frontpage() {
        if ($this->page->user_is_editing() || !$this->customfrontpageenabled()) {
            return parent::frontpage();
        }

        global $USER;

        list($courses, $isenrolledlist) = $this->getfrontpagecourses();
        $showwelcome = isloggedin() && !isguestuser();
        $coursecards = [];
        $tone = 1;

        foreach ($courses as $course) {
            $coursecards[] = $this->exportcourse($course, $tone);
            $tone = $tone === 3 ? 1 : $tone + 1;
        }

        $featuredcourse = null;
        if (!empty($coursecards)) {
            $featuredcourse = array_shift($coursecards);
        }

        $firstname = $showwelcome && !empty($USER->firstname)
            ? $USER->firstname
            : '';

        $courseslandingurl = isloggedin() && !isguestuser()
            ? new moodle_url('/my/courses.php')
            : new moodle_url('/course/index.php');

        $data = [
            'showwelcome' => $showwelcome,
            'dashboardeyebrow' => get_string('dashboardeyebrow', 'theme_learnlingo'),
            'dashboardtitle' => $showwelcome
                ? get_string('dashboardtitle', 'theme_learnlingo', $firstname)
                : '',
            'dashboarddescription' => get_string('dashboarddescription', 'theme_learnlingo'),
            'dashboardcta' => get_string('dashboardcta', 'theme_learnlingo'),
            'goaltitle' => get_string('goaltitle', 'theme_learnlingo'),
            'goaltime' => get_string('goaltime', 'theme_learnlingo'),
            'goalsubtitle' => get_string('goalsubtitle', 'theme_learnlingo'),
            'goalcta' => get_string('goalcta', 'theme_learnlingo'),
            'courseseyebrow' => get_string('frontpagecourseseyebrow', 'theme_learnlingo'),
            'coursestitle' => $isenrolledlist
                ? get_string('frontpagemycoursestitle', 'theme_learnlingo')
                : get_string('frontpageavailablecoursestitle', 'theme_learnlingo'),
            'allcoursestext' => get_string('frontpageallcourses', 'theme_learnlingo'),
            'allcoursesurl' => $courseslandingurl->out(false),
            'mycoursesurl' => $courseslandingurl->out(false),
            'featuredlabel' => get_string('frontpagecontinue', 'theme_learnlingo'),
            'featuredaction' => get_string('frontpagefeaturedaction', 'theme_learnlingo'),
            'courseaction' => get_string('frontpagecourseaction', 'theme_learnlingo'),
            'emptytitle' => get_string('frontpageemptytitle', 'theme_learnlingo'),
            'emptydescription' => get_string('frontpageemptydescription', 'theme_learnlingo'),
            'featuredcourse' => $featuredcourse,
            'hasfeaturedcourse' => !empty($featuredcourse),
            'courses' => array_values($coursecards),
            'hascourses' => !empty($coursecards),
            'showempty' => empty($featuredcourse) && empty($coursecards),
        ];

        return $this->render_from_template('theme_learnlingo/frontpage', $data);
    }

    /**
     * Check whether the custom home is enabled.
     *
     * @return bool
     */
    protected function customfrontpageenabled(): bool {
        return !isset($this->page->theme->settings->showdashboardhero)
            || !empty($this->page->theme->settings->showdashboardhero);
    }

    /**
     * Get enrolled courses or fall back to available courses.
     *
     * @return array Two items: courses and whether these are enrolled courses.
     */
    protected function getfrontpagecourses(): array {
        if (isloggedin() && !isguestuser()) {
            $fields = 'id, fullname, shortname, summary, summaryformat, startdate, enddate, visible, category, sortorder';
            $courses = enrol_get_my_courses($fields, 'sortorder ASC', 6);
            if (!empty($courses)) {
                return [array_values($courses), true];
            }
        }

        $options = [
            'recursive' => true,
            'limit' => 6,
            'summary' => true,
            'coursecontacts' => true,
            'customfields' => true,
        ];
        $courses = core_course_category::top()->get_courses($options);

        return [array_values($courses), false];
    }

    /**
     * Export a course for the front page template.
     *
     * @param stdClass|core_course_list_element $course Course record or list element.
     * @param int $tone Visual fallback tone.
     * @return array
     */
    protected function exportcourse($course, int $tone): array {
        global $USER;

        $element = $course instanceof core_course_list_element
            ? $course
            : new core_course_list_element($course);

        $progress = null;
        if (isloggedin() && !isguestuser() && class_exists('\\core_completion\\progress')) {
            $progress = progress::get_course_progress_percentage($course, $USER->id);
        }

        if ($progress !== null) {
            $progress = max(0, min(100, (int) round($progress)));
        }

        $categoryname = '';
        if (!empty($course->category)) {
            $category = core_course_category::get($course->category, IGNORE_MISSING);
            if ($category) {
                $categoryname = $category->get_formatted_name();
            }
        }
        if ($categoryname === '') {
            $categoryname = format_string($course->shortname);
        }

        $imageurl = $this->getcourseimageurl($element);

        return [
            'id' => $course->id,
            'fullname' => $element->get_formatted_name(),
            'shortname' => format_string($course->shortname),
            'categoryname' => $categoryname,
            'url' => new moodle_url('/course/view.php', ['id' => $course->id]),
            'imageurl' => $imageurl,
            'hasimage' => $imageurl !== '',
            'toneclass' => 'pulsomed-course-tone-' . $tone,
            'progress' => $progress === null ? 0 : $progress,
            'hasprogress' => $progress !== null,
            'progresslabel' => $progress === null
                ? ''
                : get_string('frontpageprogress', 'theme_learnlingo', $progress),
        ];
    }

    /**
     * Return the first valid course overview image URL.
     *
     * @param core_course_list_element $course Course list element.
     * @return string
     */
    protected function getcourseimageurl(core_course_list_element $course): string {
        foreach ($course->get_course_overviewfiles() as $file) {
            if (!$file->is_valid_image()) {
                continue;
            }

            return moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                "",
                "",
                $file->get_filename(),
                false
            )->out(false);
        }

        return '';
    }
}
