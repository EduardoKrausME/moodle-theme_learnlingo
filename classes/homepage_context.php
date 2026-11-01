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
 * Server-side Mustache data for the administrator-authored homepagehtml field only.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use core_cache\cache;
use core_completion\progress;
use core_course_category;
use core_course_list_element;
use moodle_url;

/**
 * Build a strictly controlled template context, never exposing Moodle globals.
 */
final class homepage_context {
    /**
     * Render only the configured homepagehtml field, not native theme templates.
     *
     * All strings in the context are escaped before rendering, including for
     * triple-brace or ampersand Mustache tags. No Moodle helpers, lambdas or
     * partial loaders are exposed to custom administrator HTML.
     *
     * @param string $html Configured custom home HTML.
     * @return string Rendered markup.
     */
    public static function render(string $html): string {
        if (trim($html) === '') {
            return $html;
        }

        $engine = new \Mustache\Engine([
            // The context is already escaped so triple braces are safe too.
            'escape' => static function($value): string {
                return (string)$value;
            },
        ]);
        return $engine->render($html, self::data());
    }

    /**
     * Obtain the variables available only to homepagehtml.
     *
     * @return array Context with guest-safe public course data and live own-user data.
     */
    public static function data(): array {
        global $CFG, $SITE, $USER;

        $loggedin = isloggedin() && !isguestuser();
        $publiccourses = self::public_courses($loggedin);
        $mycourses = [];
        $cohorts = [];
        $user = false;

        if ($loggedin) {
            $user = [
                'id' => (int)$USER->id,
                'firstname' => self::safe($USER->firstname),
                'lastname' => self::safe($USER->lastname),
                'fullname' => self::safe(fullname($USER)),
                'email' => self::safe($USER->email),
                'profileurl' => self::safe((new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false)),
                'lang' => self::safe(current_language()),
            ];

            require_once($CFG->libdir . '/enrollib.php');
            $fields = 'id,fullname,shortname,summary,summaryformat,startdate,enddate,visible,category,sortorder';
            foreach (enrol_get_my_courses($fields, 'sortorder ASC') as $course) {
                $mycourses[] = self::course_data($course, true, (int)$USER->id);
            }

            require_once($CFG->dirroot . '/cohort/lib.php');
            foreach (cohort_get_user_cohorts((int)$USER->id) as $cohort) {
                $cohorts[] = [
                    'id' => (int)$cohort->id,
                    'name' => self::safe($cohort->name),
                    'idnumber' => self::safe($cohort->idnumber ?? ''),
                ];
            }
        }

        return [
            'isloggedin' => $loggedin,
            'isguest' => !$loggedin,
            'user' => $user,
            'site' => [
                'fullname' => self::safe($SITE->fullname),
                'shortname' => self::safe($SITE->shortname),
                'url' => self::safe($CFG->wwwroot),
            ],
            'loginurl' => self::safe((new moodle_url('/login/index.php'))->out(false)),
            'dashboardurl' => self::safe((new moodle_url('/my/'))->out(false)),
            'mycoursesurl' => self::safe((new moodle_url('/my/courses.php'))->out(false)),
            'coursesurl' => self::safe((new moodle_url('/course/index.php'))->out(false)),
            'courses' => $publiccourses,
            'hascourses' => !empty($publiccourses),
            'coursecount' => count($publiccourses),
            'mycourses' => $mycourses,
            'hasmycourses' => !empty($mycourses),
            'mycoursecount' => count($mycourses),
            'cohorts' => $cohorts,
            'hascohorts' => !empty($cohorts),
        ];
    }

    /**
     * Describe the stable homepagehtml context for the MCP reader.
     *
     * @return array Public field documentation, not current users' personal data.
     */
    public static function schema(): array {
        return [
            'field' => 'homepagehtml',
            'rendered_by' => 'PHP Mustache on each homepage request',
            'fields' => [
                'isloggedin, isguest' => 'Booleans. Guest visitors never receive user, mycourses or cohorts data.',
                'site' => 'fullname, shortname, url',
                'user' => 'Only for the logged-in visitor: id, firstname, lastname, fullname, email, profileurl, lang',
                'courses' => 'Visible catalogue courses; guest selection follows homepagepubliccourses, empty means all.',
                'mycourses' => 'Only the visitor own enrolments, with progress. Never returned to guests.',
                'cohorts' => 'Only visible cohorts of the current logged-in user: id, name, idnumber.',
                'hascourses, hasmycourses, hascohorts' => 'Boolean list guards.',
                'coursecount, mycoursecount' => 'List sizes.',
                'loginurl, dashboardurl, mycoursesurl, coursesurl' => 'Internal navigation URLs.',
                'course item' => 'id, fullname, shortname, summary, categoryname, url, imageurl, hasimage, '
                    . 'startdate, enddate, progress, hasprogress, completed, enrolled',
            ],
            'examples' => [
                '{{#isloggedin}}Olá {{user.firstname}}{{/isloggedin}}',
                '{{^isloggedin}}<a href="{{loginurl}}">Entrar</a>{{/isloggedin}}',
                '{{#courses}}<a href="{{url}}">{{fullname}}</a>{{/courses}}',
                '{{#mycourses}}<a href="{{url}}">{{fullname}} ({{progress}}%)</a>{{/mycourses}}',
                '{{#cohorts}}<span>{{name}}</span>{{/cohorts}}',
            ],
            'security' => 'All context strings are HTML-escaped, including in triple-brace tags. '
                . 'Progress and user data are evaluated per request; rendered HTML is never shared in cache.',
            'cache' => 'Guest public catalogue uses MUC application cache (TTL 300 seconds), '
                . 'invalidated by course/category change events and setting updates.',
        ];
    }

    /**
     * Explicit admin selection. An empty selection always means all visible courses.
     *
     * @return int[]
     */
    public static function selected_ids(): array {
        $raw = (string)(get_config('theme_learnlingo', 'homepagepubliccourses') ?: '');
        $ids = array_map('intval', array_filter(explode(',', $raw), 'strlen'));
        return array_values(array_unique(array_filter($ids, static fn(int $id): bool => $id > SITEID)));
    }

    /**
     * Public catalogue. Cache is ONLY shared between anonymous visitors.
     *
     * @param bool $loggedin Whether the current user is authenticated.
     * @return array
     */
    private static function public_courses(bool $loggedin): array {
        $ids = self::selected_ids();
        $cachekey = 'guest_' . sha1(current_language() . ':' . implode(',', $ids));

        if (!$loggedin) {
            $cache = cache::make('theme_learnlingo', 'publiccourses');
            $cached = $cache->get($cachekey);
            if ($cached !== false) {
                return $cached;
            }
        }

        $options = [
            'recursive' => true,
            'limit' => 0,
            'summary' => true,
        ];
        $courses = core_course_category::top()->get_courses($options);
        $selected = $ids ? array_fill_keys($ids, true) : [];
        $result = [];
        foreach ($courses as $course) {
            // Administrators can see hidden courses; never expose them as public courses.
            if (empty($course->visible) || ($ids && !isset($selected[(int)$course->id]))) {
                continue;
            }
            $category = core_course_category::get((int)$course->category, IGNORE_MISSING);
            if (!$category || !$category->is_uservisible()) {
                continue;
            }
            $result[] = self::course_data($course, false);
        }

        if (!$loggedin) {
            $cache->set($cachekey, $result);
        }
        return $result;
    }

    /**
     * Export only a safe subset of course fields to HTML templates.
     *
     * @param object $course Course object or course list element.
     * @param bool $enrolled Whether the course comes from the user's enrolments.
     * @param int $userid Current user's ID, only for enrolled courses.
     * @return array
     */
    private static function course_data($course, bool $enrolled, int $userid = 0): array {
        $element = $course instanceof core_course_list_element
            ? $course : new core_course_list_element($course);
        $category = core_course_category::get((int)$course->category, IGNORE_MISSING);
        $categoryname = $category ? $category->get_formatted_name() : '';
        $imageurl = '';

        foreach ($element->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                $imageurl = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    null,
                    $file->get_filepath(),
                    $file->get_filename(),
                    false
                )->out(false);
                break;
            }
        }

        $percentage = null;
        if ($enrolled && $userid > 0) {
            $percentage = progress::get_course_progress_percentage($course, $userid);
        }
        $progress = $percentage === null ? 0 : max(0, min(100, (int)round($percentage)));
        $summary = $course->summary ?? '';
        $summary = trim(strip_tags((string)$summary));
        $summary = shorten_text(html_entity_decode($summary, ENT_QUOTES | ENT_HTML5, 'UTF-8'), 220);

        return [
            'id' => (int)$course->id,
            'fullname' => self::safe($element->get_formatted_name()),
            'shortname' => self::safe($course->shortname),
            'summary' => self::safe($summary),
            'categoryname' => self::safe($categoryname),
            'url' => self::safe((new moodle_url('/course/view.php', ['id' => $course->id]))->out(false)),
            'imageurl' => self::safe($imageurl),
            'hasimage' => $imageurl !== '',
            'startdate' => (int)($course->startdate ?? 0),
            'enddate' => (int)($course->enddate ?? 0),
            'progress' => $progress,
            'hasprogress' => $percentage !== null,
            'completed' => $percentage !== null && $progress === 100,
            'enrolled' => $enrolled,
        ];
    }

    /**
     * Escape even values placed in raw Mustache tags by the administrator.
     *
     * @param mixed $value Text or URL.
     * @return string HTML-escaped text.
     */
    private static function safe($value): string {
        return s((string)$value);
    }

    /**
     * Purge ONLY the public course catalogue cache.
     *
     * @return void
     */
    public static function purge_public_cache(): void {
        cache::make('theme_learnlingo', 'publiccourses')->purge();
    }
}
