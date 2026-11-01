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

        // Moodle 4.5 ships Mustache_Engine; newer releases use Mustache\Engine.
        if (!class_exists(\Mustache\Engine::class) && !class_exists(\Mustache_Engine::class)) {
            global $CFG;
            require_once($CFG->libdir . '/mustache/src/Mustache/Autoloader.php');
            \Mustache_Autoloader::register();
        }
        $engineclass = class_exists(\Mustache\Engine::class) ? \Mustache\Engine::class : \Mustache_Engine::class;
        $engine = new $engineclass([
            // The context is already escaped so triple braces are safe too.
            'escape' => static function($value): string {
                return (string)$value;
            },
            // Compiled Mustache templates are keyed by source and theme revision.
            'cache' => make_localcache_directory('mustache/learnlingo-home/' . theme_get_revision()),
        ]);
        try {
            return $engine->render($html, self::data());
        } catch (\Throwable $exception) {
            debugging('Invalid LearnLingo homepage Mustache: ' . $exception->getMessage(), DEBUG_DEVELOPER);
            // Preserve the legacy HTML instead of breaking the whole homepage.
            return $html;
        }
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
            'scope' => 'Mustache is evaluated exclusively inside homepagehtml. '
                . 'Native Moodle/parent theme Mustache templates, footerhtml and SCSS are unchanged.',
            'rendered_by' => 'Server-side PHP Mustache, for the current visitor on each homepage request.',
            'instructions' => [
                'When asked to design or edit the LearnLingo home, first call '
                    . 'learnlingo_get_homepage and read its mustache instructions, fields and examples. '
                    . 'Reuse the actual placeholders instead of hardcoding a particular user, '
                    . 'course name, image URL, enrollment or progress percentage.',
                'homepagehtml is rendered above built-in cards in learnlingo mode, replaces '
                    . 'homepage content in html mode and is unused in moodle mode. '
                    . 'Preserve the selected mode unless the administrator asks to change it.',
                'Use {{#isloggedin}}...{{/isloggedin}} for authenticated content and '
                    . '{{^isloggedin}}...{{/isloggedin}} for guest content. '
                    . 'The user object is available only to the signed-in visitor.',
                'Use {{#courses}}...{{/courses}} for visible catalogue courses, '
                    . '{{#mycourses}}...{{/mycourses}} for the signed-in visitor own '
                    . 'enrolled courses, and {{#cohorts}}...{{/cohorts}} for that '
                    . 'visitor own cohorts. Use the hascourses, hasmycourses, '
                    . 'hascohorts and hasimage flags for optional sections.',
                'Show progress using {{#hasprogress}} and {{progress}} inside '
                    . '{{#mycourses}}; if hasprogress is false do not claim a 0% '
                    . 'completion rate. The public courses list does not contain '
                    . 'personalized completion information.',
                'Render course images with {{#hasimage}}<img src="{{imageurl}}" alt="">'
                    . '{{/hasimage}} and links with {{url}}; let the server provide '
                    . 'course names, summaries and categories dynamically.',
                'For logged-out visitors, homepagepubliccourses / MCP publiccourses '
                    . 'selects visible course IDs in the catalogue. Empty selection '
                    . 'means all visible accessible courses in Moodle sort order. '
                    . 'This never makes hidden courses public or enrolls users.',
                'Mustache interpolates values and conditions; it cannot perform '
                    . 'database queries, JavaScript, PHP, or Moodle helper calls. '
                    . 'Do not generate executable scripts, partials or lambdas.',
                'Keep semantic responsive HTML in homepagehtml and CSS/SCSS in '
                    . 'homepagescss. Beware homepagescss is applied globally on every '
                    . 'page, so scope homepage-only rules with explicit selectors.',
                'Text and URL values are HTML-escaped in PHP, even inside triple-brace '
                    . 'tags. Prefer ordinary {{variable}} syntax for readability.',
                'Only the guest public catalogue uses a shared MUC cache with a '
                    . '300-second TTL, invalidated by course/category events and '
                    . 'homepage setting updates. The final rendered HTML, visitor '
                    . 'profile, course enrollments and progress are never placed '
                    . 'in this shared cache; do not introduce shared caching of '
                    . 'personalized homepage HTML.',
                'Preview writes and request explicit approval before updating '
                    . 'the homepage. Keep other theme settings and native templates unchanged.',
            ],
            'fields' => [
                'isloggedin, isguest' => 'Booleans. isguest is true for anonymous or guest sessions.',
                'site' => 'fullname, shortname, url; available to all visitors.',
                'user' => 'Own user only when signed in: id, firstname, lastname, fullname, email, profileurl, lang.',
                'courses' => 'Visible public catalogue courses. homepagepubliccourses controls selection; '
                    . 'empty selection means all visible accessible courses in Moodle sort order.',
                'mycourses' => 'Only the signed-in visitor own enrolled courses, including completion progress. '
                    . 'Empty for anonymous/guest users.',
                'cohorts' => 'Only the current signed-in visitor own cohorts: id, name, idnumber. '
                    . 'Empty for anonymous/guest users.',
                'hascourses, hasmycourses, hascohorts' => 'Boolean flags for optional sections.',
                'coursecount, mycoursecount' => 'Current counts of courses and enrolled courses.',
                'loginurl, dashboardurl, mycoursesurl, coursesurl' => 'Moodle navigation URLs.',
                'course item' => 'id, fullname, shortname, summary, categoryname, url, '
                    . 'imageurl, hasimage, startdate, enddate, progress, '
                    . 'hasprogress, completed, enrolled. startdate/enddate are Unix timestamps. '
                    . 'Progress is a 0-100 integer; hasprogress indicates whether the figure is known.',
            ],
            'examples' => [
                'Guest versus logged-in welcome: '
                    . '{{#isloggedin}}<h2>Hello, {{user.firstname}}!</h2>'
                    . '<a href="{{mycoursesurl}}">My courses</a>{{/isloggedin}}'
                    . '{{^isloggedin}}<a href="{{loginurl}}">Sign in</a>{{/isloggedin}}',
                'Public course cards: {{#courses}}<article>'
                    . '{{#hasimage}}<img src="{{imageurl}}" alt="">{{/hasimage}}'
                    . '<h3><a href="{{url}}">{{fullname}}</a></h3>'
                    . '<p>{{summary}}</p></article>{{/courses}}',
                'Own enrollments and completion: {{#isloggedin}}{{#hasmycourses}}'
                    . '{{#mycourses}}<a href="{{url}}">{{fullname}}</a>'
                    . '{{#hasprogress}}<progress value="{{progress}}" max="100">'
                    . '{{progress}}%</progress>{{/hasprogress}}{{/mycourses}}'
                    . '{{/hasmycourses}}{{/isloggedin}}',
                'Own cohorts: {{#isloggedin}}{{#cohorts}}'
                    . '<span>{{name}}</span>{{/cohorts}}{{/isloggedin}}',
                'Empty course list: {{^hascourses}}<p>No courses available.</p>{{/hascourses}}',
            ],
            'security' => 'All context strings are HTML-escaped, including in triple-brace tags. '
                . 'No data about other users is exposed; user, mycourses and cohorts are '
                . 'empty for guests. Individual progress and user data are read per request.',
            'cache' => 'Guest public catalogue uses a shared MUC application cache (TTL 300 seconds), '
                . 'invalidated by course/category change events and setting updates. '
                . 'No shared cache contains personalized or fully rendered homepage HTML.',
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
        return s(html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
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
