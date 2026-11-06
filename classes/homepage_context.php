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
use Mustache\Engine;
use Mustache_Engine;
use Throwable;

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

        // Moodle 5.x uses Mustache\\Engine; older releases use Mustache_Engine.
        // Reuse the Moodle-registered autoloader, as in local_kopere_bi.
        if (!class_exists(Mustache_Engine::class)) {
            if (!class_exists(Engine::class)) {
                debugging('LearnLingo homepage Mustache engine is not available.', DEBUG_DEVELOPER);
                return $html;
            }
            class_alias(Engine::class, 'Mustache_Engine');
        }

        try {
            $engine = new Mustache_Engine([
                // The context is already escaped so triple braces are safe too.
                'escape' => static function ($value): string {
                    return (string)$value;
                },
                // Compiled Mustache templates are keyed by source and theme revision.
                'cache' => make_localcache_directory('mustache/learnlingo-home/' . theme_get_revision()),
            ]);
            return $engine->render($html, self::data());
        } catch (Throwable $exception) {
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
        // Only anonymous visitors use the configured course limit and pagination.
        $guestpagination = !$loggedin ? self::paginate_guest_courses(
            $publiccourses,
            optional_param('llcoursepage', 1, PARAM_INT),
            self::guest_courses_per_page()
        ) : null;
        if ($guestpagination !== null) {
            $publiccourses = $guestpagination['courses'];
            unset($guestpagination['courses']);
        }
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
            // Pagination metadata exists only in the visitor context, not student/course lists.
            'coursepagination' => $guestpagination ?: false,
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
                'COMPLETE SITE DESIGN CHECKLIST: when asked to create or redesign a homepage, '
                . 'treat homepage content, site navigation, footer, shared logo and favicon '
                . 'as one visual delivery. Read learnlingo_get_homepage AND '
                . 'learnlingo_get_settings (include_images when needed) before planning. '
                . 'Inspect existing branding and footer, propose matching changes and '
                . 'request separate explicit MCP previews/approvals before applying them. '
                . 'Do not report a complete redesign while the footer/logo/favicon are '
                . 'still default or inconsistent. For a small isolated edit, preserve '
                . 'the existing branding/footer unless the administrator requests otherwise.',
                'MENU AND NAVIGATION: put all shared navbar, menu, header and navigation '
                . 'styles in the global scss field (learnlingo_update_styles or '
                . 'learnlingo_update_settings), never in homepagescss. homepagescss '
                . 'technically compiles globally but is reserved for homepage-only '
                . 'selectors. If older menu rules are in homepagescss, move ONLY those '
                . 'rules into scss and remove their duplicates in the same confirmed '
                . 'learnlingo_update_styles write; preserve unrelated home SCSS.',
                'FOOTER: read footermode, footerhtml and footerscss; configure the custom '
                . 'HTML footer to match the new home when a full redesign requires it, '
                . 'with at least 20px horizontal padding on desktop and mobile and '
                . '[data-bs-theme="dark"] support. Footer styles belong in footerscss, '
                . 'not homepagescss; do not add a second footer element. Use '
                . 'learnlingo_update_footer or learnlingo_update_settings with approval.',
                'BRANDING: inspect images.logo and images.favicon through '
                . 'learnlingo_get_settings. Prefer a PNG logo with real alpha transparency '
                . '(no white, colored or opaque rectangle behind the artwork). '
                . 'CRITICAL: the logo artwork must be tightly cropped to its visible '
                . 'content; NEVER leave blank, transparent or white padding between '
                . 'the logo artwork and the image canvas edges. Trim all four sides '
                . 'to the visible artwork bounding box before uploading, without '
                . 'distorting proportions or cutting off visible details. Image '
                . 'padding is NOT the same as navbar CSS spacing: review both separately. '
                . 'Prefer a horizontal shared site logo and a separate square favicon, '
                . 'which has its own dimensions and may have a deliberate background. '
                . 'Preview the actual cropped image and request explicit approval '
                . 'before learnlingo_update_settings images.logo/images.favicon. '
                . 'Preserve the independent loginlogo unless requested.',
                'PUBLICATION: after each approved homepage/SCSS/footer/branding update, '
                . 'verify the saved fields using learnlingo_get_homepage and/or '
                . 'learnlingo_get_settings, and check the returned theme_revision and '
                . 'theme_cache_purged status. Theme CSS caches are purged automatically '
                . 'on execution, without opening the admin settings page. Report any '
                . 'missing or unapproved footer/logo/favicon work instead of silently '
                . 'calling the redesign finished.',
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
                'For logged-out visitors only, homepagepubliccourses / MCP publiccourses '
                . 'selects visible course IDs in the catalogue. Empty selection '
                . 'means all visible accessible courses in Moodle sort order. '
                . 'This never makes hidden courses public or enrolls users.',
                'Respect homepagepubliccoursesperpage / MCP publiccoursesperpage (1-12, default 4) '
                . 'for anonymous visitors ONLY. {{#courses}} renders at most that many '
                . 'courses on the current page. It does not limit logged-in users or '
                . 'their {{#mycourses}} list. Do NOT hardcode a fixed number of cards '
                . 'or truncate the loop manually.',
                'Use {{coursepagination.coursecolumns}} for responsive grids: it equals '
                . 'the ACTUAL number of cards on the current guest page, not the '
                . 'requested page size. Example: 4 configured and 3 available gives '
                . '3 columns; 3 configured and 4 available gives pages of 3 and 1. '
                . 'Never invent or repeat a fourth course to fill an empty slot. '
                . 'Use responsive CSS and consider narrower/mobile layouts.',
                'Use {{#coursepagination.hascoursepages}} to show navigation, loop '
                . '{{#coursepagination.coursepages}} for page links (number, url, '
                . 'iscurrent), and optionally use hasnextcoursepage/nextcoursepageurl '
                . 'and haspreviouscoursepage/previouscoursepageurl. These values '
                . 'only exist for anonymous visitors. The llcoursepage query '
                . 'parameter is validated and clamped to available pages.',
                'Mustache interpolates values and conditions; it cannot perform '
                . 'database queries, JavaScript, PHP, or Moodle helper calls. '
                . 'Do not generate executable scripts, partials or lambdas.',
                'Keep semantic responsive HTML in homepagehtml and CSS/SCSS in '
                . 'homepagescss for homepage-only selectors; place shared menu, '
                . 'navigation and header styles in the global scss field. '
                . 'homepagescss is technically compiled on every page, so scope '
                . 'its rules to the homepage root.',
                'MANDATORY DARK MODE SUPPORT: every custom Mustache homepage must '
                . 'look correct in both light and dark appearances. The optional '
                . 'local_boost_dark plugin sets data-bs-theme="dark" on <html>. '
                . 'Use [data-bs-theme="dark"] .your-home selectors in homepagescss '
                . 'for dark backgrounds, surfaces, text, borders, cards, links, '
                . 'buttons and focus states. Match selector specificity to your '
                . 'light rules, and preserve readable contrast in both modes. '
                . 'Scope home styles to a unique homepage root so they do not '
                . 'change other Moodle pages.',
                'Prefer var(--learnlingo-home-canvas), var(--learnlingo-home-surface), '
                . 'var(--learnlingo-home-ink), var(--learnlingo-home-muted) and '
                . 'var(--learnlingo-home-border) in custom homepage CSS. These '
                . 'variables inherit the theme palette and update automatically '
                . 'when the HTML data-bs-theme attribute changes. Hard-coded '
                . 'light backgrounds in custom sections must have explicit dark '
                . 'overrides; the theme cannot infer arbitrary branded colors.',
                'Dark appearance is client-side, not a Mustache data field: do '
                . 'NOT invent {{isdark}}, do not render separate cached dark '
                . 'HTML and do not require JavaScript or a page reload to switch '
                . 'appearance. Do not force the user dark-mode preference or '
                . 'assume the optional plugin is installed or enabled.',
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
                'coursecount, mycoursecount' => 'coursecount is the number of currently displayed '
                    . 'course items (one guest page); mycoursecount is the signed-in user own enrollment count.',
                'coursepagination' => 'Guest-only object; false when logged in. coursesperpage '
                    . '(1-12), coursecount (actual page items), coursetotal (all visible '
                    . 'and selected courses), coursecolumns (actual page columns, '
                    . 'not slots), coursepage (current), coursepagecount (number of pages), '
                    . 'hascoursepages, coursepages (array of number, url and iscurrent), '
                    . 'haspreviouscoursepage, hasnextcoursepage, previouscoursepageurl '
                    . 'and nextcoursepageurl. When 4 are configured but 3 exist, '
                    . 'coursecolumns=3 and no placeholder course is invented.',
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
                'Guest responsive grid: {{#isguest}}<div class="cards" '
                . 'style="--guest-columns: {{coursepagination.coursecolumns}}">'
                . '{{#courses}}<article><a href="{{url}}">{{fullname}}</a></article>'
                . '{{/courses}}</div>{{/isguest}}. CSS: .cards { display:grid; '
                . 'grid-template-columns:repeat(var(--guest-columns), minmax(0,1fr)); } '
                . 'Use @media rules for mobile.',
                'Guest pagination: {{#coursepagination.hascoursepages}}<nav>'
                . '{{#coursepagination.coursepages}}<a href="{{url}}">'
                . '{{number}}</a>{{/coursepagination.coursepages}}'
                . '</nav>{{/coursepagination.hascoursepages}}',
                'Empty course list: {{^hascourses}}<p>No courses available.</p>{{/hascourses}}',
                'Dark-aware custom home: homepagehtml uses <section class="example-home">'
                . '{{#courses}}<article class="example-card"><a href="{{url}}">'
                . '{{fullname}}</a></article>{{/courses}}</section>. '
                . 'homepagescss uses .example-home {background:var(--learnlingo-home-canvas);'
                . 'color:var(--learnlingo-home-ink)} '
                . '.example-home .example-card {background:var(--learnlingo-home-surface);'
                . 'border:1px solid var(--learnlingo-home-border)} '
                . '[data-bs-theme="dark"] .example-home {--example-accent:#F5BD8D} '
                . 'for additional branded dark overrides, including link and '
                . 'button colors. Do not put these CSS rules inside homepagehtml.',
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
     * Configured guest page size, with a safe default for older installations.
     *
     * @return int Page size between 1 and 12.
     */
    public static function guest_courses_per_page(): int {
        $configured = (int)get_config('theme_learnlingo', 'homepagepubliccoursesperpage');
        return $configured >= 1 && $configured <= 12 ? $configured : 4;
    }

    /**
     * Paginate only the anonymous catalogue, after the visibility/selection filters.
     *
     * Keep the complete catalogue in MUC; never cache the rendered HTML or a
     * specific page, as every page is just a slice of the same public data.
     *
     * @param array $courses Visible public courses in Moodle sort order.
     * @param int $requestedpage Page from the homepage URL.
     * @param int $perpage Administrator-configured number of cards.
     * @return array Page metadata and the current page's courses.
     */
    public static function paginate_guest_courses(array $courses, int $requestedpage, int $perpage): array {
        $perpage = max(1, min(12, $perpage));
        $total = count($courses);
        $pages = max(1, (int)ceil($total / $perpage));
        $page = max(1, min($pages, $requestedpage));
        $items = array_slice($courses, ($page - 1) * $perpage, $perpage);
        $previous = $page > 1;
        $next = $page < $pages;

        // Use an isolated homepage query parameter, not Moodle course indexes.
        $pageurl = static function (int $number): string {
            $args = $number > 1 ? ['llcoursepage' => $number] : [];
            return self::safe((new moodle_url('/', $args))->out(false));
        };
        $links = [];
        if ($pages > 1) {
            for ($number = 1; $number <= $pages; $number++) {
                $links[] = [
                    'number' => $number,
                    'url' => $pageurl($number),
                    'iscurrent' => $number === $page,
                ];
            }
        }

        return [
            'courses' => $items,
            'coursecount' => count($items),
            'coursetotal' => $total,
            'coursesperpage' => $perpage,
            // A 4-column design with only 3 real courses can use 3 columns.
            'coursecolumns' => min($perpage, count($items)),
            'coursepage' => $page,
            'coursepagecount' => $pages,
            'hascoursepages' => $pages > 1,
            'coursepages' => $links,
            'haspreviouscoursepage' => $previous,
            'hasnextcoursepage' => $next,
            'previouscoursepageurl' => $previous ? $pageurl($page - 1) : '',
            'nextcoursepageurl' => $next ? $pageurl($page + 1) : '',
        ];
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
