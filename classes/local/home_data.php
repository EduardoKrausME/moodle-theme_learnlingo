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
 * Read-only homepage data, with session-aware access control.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\local;

use cache;
use context_course;
use core_completion\progress;
use core_course_category;
use core_course_list_element;
use moodle_url;

/**
 * Homepage data provider used by the native frontpage and same-origin JSON API.
 *
 * Only global course-view counts are cached in a shared MUC cache. User profile,
 * enrolments, cohort membership and progress are always resolved in the active
 * Moodle session, never placed in a shared cache.
 */
final class home_data {
    /** Max courses in one JSON response. */
    private const MAX_PAGE_SIZE = 48;

    /** How far back popularity counts are calculated, in seconds. */
    private const POPULAR_WINDOW = 30 * DAYSECS;

    /**
     * Whether the visitor is an authenticated (non-guest) user.
     *
     * @return bool
     */
    public static function logged_in(): bool {
        return isloggedin() && !isguestuser();
    }

    /**
     * Public course selector from the homepage settings.
     *
     * An empty selection means all publicly listed courses, in Moodle's
     * configured sort order. Moodle's category API enforces visibility.
     *
     * @return array
     */
    public static function public_courses(): array {
        $courses = core_course_category::top()->get_courses([
            'recursive' => true,
            'summary' => false,
            'coursecontacts' => false,
            'customfields' => false,
        ]);

        $selected = self::selected_public_course_ids();
        if ($selected) {
            $courses = array_filter($courses, static function($course) use ($selected): bool {
                return isset($selected[(int)$course->id]);
            });
        }

        $courses = array_values($courses);
        usort($courses, static function($left, $right): int {
            return ((int)$left->sortorder <=> (int)$right->sortorder)
                ?: ((int)$left->id <=> (int)$right->id);
        });
        return $courses;
    }

    /**
     * Return the selected IDs as a lookup map.
     *
     * @return array
     */
    public static function selected_public_course_ids(): array {
        $config = (string)(get_config('theme_learnlingo', 'publiccourses') ?: '');
        $ids = [];
        foreach (explode(',', $config) as $value) {
            $id = filter_var(trim($value), FILTER_VALIDATE_INT, ['options' => ['min_range' => 2]]);
            if ($id !== false) {
                $ids[(int)$id] = true;
            }
        }
        return $ids;
    }

    /**
     * Available public course options for Moodle's admin multiselect.
     *
     * @return array Course ID => display name.
     */
    public static function public_course_options(): array {
        $options = [];
        foreach (self::public_courses_unfiltered() as $course) {
            $options[$course->id] = $course->get_formatted_name()
                . ' (' . format_string($course->shortname) . ')';
        }
        return $options;
    }

    /**
     * Get available courses without applying the administrator's shortlist.
     *
     * @return array
     */
    private static function public_courses_unfiltered(): array {
        return array_values(core_course_category::top()->get_courses([
            'recursive' => true,
            'summary' => false,
            'coursecontacts' => false,
            'customfields' => false,
        ]));
    }

    /**
     * Current user's enrolled courses, or the public catalogue if none.
     *
     * @return array [courses, whether it is an enrolled list].
     */
    public static function homepage_courses(): array {
        if (self::logged_in()) {
            $enrolled = enrol_get_my_courses(
                'id, fullname, shortname, summary, summaryformat, startdate, enddate, visible, category, sortorder',
                'sortorder ASC'
            );
            if ($enrolled) {
                return [array_values($enrolled), true];
            }
        }
        return [self::public_courses(), false];
    }

    /**
     * Export the current user's own standard profile fields.
     *
     * Never accepts a user ID argument, and never exposes other users' data.
     *
     * @return array|null
     */
    public static function user(): ?array {
        global $USER;
        if (!self::logged_in()) {
            return null;
        }
        return [
            'id' => (int)$USER->id,
            'firstname' => (string)$USER->firstname,
            'lastname' => (string)$USER->lastname,
            'fullname' => fullname($USER),
            'email' => (string)$USER->email,
            'city' => (string)($USER->city ?? ''),
            'country' => (string)($USER->country ?? ''),
            'institution' => (string)($USER->institution ?? ''),
            'department' => (string)($USER->department ?? ''),
        ];
    }

    /**
     * Return only cohorts belonging to the current user.
     *
     * @return array
     */
    public static function cohorts(): array {
        global $CFG, $USER;
        if (!self::logged_in()) {
            return [];
        }
        require_once($CFG->dirroot . '/cohort/lib.php');
        $result = [];
        foreach (cohort_get_user_cohorts($USER->id) as $cohort) {
            $result[] = [
                'id' => (int)$cohort->id,
                'name' => format_string($cohort->name),
                'idnumber' => (string)$cohort->idnumber,
            ];
        }
        return $result;
    }

    /**
     * Serialize a course without exposing raw HTML or private user progress.
     *
     * @param object $course Course DB record or list element.
     * @param bool $isenrolled True only for this user's enrolled courses.
     * @return array
     */
    private static function course_data($course, bool $isenrolled = false): array {
        global $USER;
        $element = $course instanceof core_course_list_element
            ? $course : new core_course_list_element($course);
        $completion = null;
        if ($isenrolled && self::logged_in()) {
            $value = progress::get_course_progress_percentage($course, $USER->id);
            if ($value !== null) {
                $completion = max(0, min(100, (int)round($value)));
            }
        }
        $image = '';
        foreach ($element->get_course_overviewfiles() as $file) {
            if (!$file->is_valid_image()) {
                continue;
            }
            $image = moodle_url::make_pluginfile_url(
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
        $categoryname = '';
        if (!empty($course->category)) {
            $category = core_course_category::get($course->category, IGNORE_MISSING);
            if ($category) {
                $categoryname = $category->get_formatted_name();
            }
        }
        return [
            'id' => (int)$course->id,
            'fullname' => $element->get_formatted_name(),
            'shortname' => format_string($course->shortname),
            'category' => $categoryname,
            'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            'imageurl' => $image,
            'enrolled' => $isenrolled,
            'progress' => $completion,
        ];
    }

    /**
     * Paginated course cards for the API.
     *
     * @param int $limit Page length, 1 to 48.
     * @param int $offset Zero-based offset.
     * @param bool $public Force public course catalogue.
     * @return array
     */
    public static function courses(int $limit = 12, int $offset = 0, bool $public = false): array {
        $limit = max(1, min(self::MAX_PAGE_SIZE, $limit));
        $offset = max(0, $offset);
        if ($public) {
            $all = self::public_courses();
            $enrolled = false;
        } else {
            [$all, $enrolled] = self::homepage_courses();
        }
        $items = [];
        foreach (array_slice($all, $offset, $limit) as $course) {
            $items[] = self::course_data($course, $enrolled);
        }
        return [
            'total' => count($all),
            'limit' => $limit,
            'offset' => $offset,
            'enrolledlist' => $enrolled,
            'items' => $items,
        ];
    }

    /**
     * Most viewed accessible courses in the last 30 days.
     *
     * The cache contains only aggregate IDs and view counts, not personal
     * information. Visibility is checked fresh for every requesting user.
     *
     * @param int $limit Maximum returned courses.
     * @return array
     */
    public static function popular_courses(int $limit = 6): array {
        global $DB;
        $limit = max(1, min(12, $limit));
        $cache = cache::make('theme_learnlingo', 'popularcourses');
        $ranking = $cache->get('last30days');

        if ($ranking === false) {
            $ranking = [];
            if ($DB->get_manager()->table_exists('logstore_standard_log')) {
                $sql = "SELECT courseid, COUNT(id) AS views
                          FROM {logstore_standard_log}
                         WHERE eventname = :eventname
                           AND timecreated >= :since
                           AND courseid > :siteid
                      GROUP BY courseid
                      ORDER BY views DESC, courseid ASC";
                $rows = $DB->get_records_sql($sql, [
                    'eventname' => '\\core\\event\\course_viewed',
                    'since' => time() - self::POPULAR_WINDOW,
                    'siteid' => SITEID,
                ], 0, 100);
                foreach ($rows as $row) {
                    $ranking[(int)$row->courseid] = (int)$row->views;
                }
            }
            $cache->set('last30days', $ranking);
        }

        $result = [];
        foreach ($ranking as $courseid => $views) {
            $course = $DB->get_record('course', ['id' => (int)$courseid], '*', IGNORE_MISSING);
            if (!$course || !$course->visible ||
                    !core_course_category::can_view_course_info($course)) {
                continue;
            }
            // Inaccessible categories must not leak names through this aggregate.
            if (!core_course_category::get($course->category, IGNORE_MISSING)) {
                continue;
            }
            $item = self::course_data($course);
            $item['views'] = (int)$views;
            $result[] = $item;
            if (count($result) >= $limit) {
                break;
            }
        }
        return $result;
    }

    /**
     * Produce a JSON response section. Every section includes login state.
     *
     * @param string $section Section requested.
     * @param int $limit Page size.
     * @param int $offset Zero-based offset.
     * @return array
     */
    public static function response(string $section, int $limit, int $offset): array {
        $response = ['loggedin' => self::logged_in()];
        if ($section === 'all' || $section === 'user') {
            $response['user'] = self::user();
        }
        if ($section === 'all' || $section === 'courses') {
            $response['courses'] = self::courses($limit, $offset);
        }
        if ($section === 'all' || $section === 'publiccourses') {
            $response['publiccourses'] = self::courses($limit, $offset, true);
        }
        if ($section === 'all' || $section === 'cohorts') {
            $response['cohorts'] = self::cohorts();
        }
        if ($section === 'all' || $section === 'popular') {
            $response['popular'] = self::popular_courses();
        }
        return $response;
    }
}
