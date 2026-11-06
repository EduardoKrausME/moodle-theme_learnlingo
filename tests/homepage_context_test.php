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
 * Mustache homepage security, public course selection and cache regression tests.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use context_course;
use core\event\course_created;
use core_cache\cache;

/**
 * Class homepage_context_test
 *
 * @covers \theme_learnlingo\homepage_context
 */
final class homepage_context_test extends advanced_testcase {
    /**
     * Anonymous visitors never receive personal fields.
     */
    public function test_guest_context_has_no_user_enrolments_or_cohorts(): void {
        $this->resetAfterTest();
        $this->setGuestUser();
        $data = homepage_context::data();

        $this->assertFalse($data['isloggedin']);
        $this->assertTrue($data['isguest']);
        $this->assertFalse($data['user']);
        $this->assertSame([], $data['mycourses']);
        $this->assertSame([], $data['cohorts']);
        $this->assertFalse($data['hasmycourses']);

        $html = homepage_context::render(
            '{{^isloggedin}}<h1>{{site.shortname}}</h1>{{/isloggedin}}'
            . '{{#isloggedin}}<div>PRIVATE</div>{{/isloggedin}}'
        );
        $this->assertStringContainsString('<h1>', $html);
        $this->assertStringNotContainsString('PRIVATE', $html);
    }

    /**
     * Empty means every public course; selection must not reveal hidden courses.
     */
    public function test_public_selection_limits_guest_courses_and_empty_means_all(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $first = $this->getDataGenerator()->create_course(['fullname' => 'First Public', 'visible' => 1]);
        $second = $this->getDataGenerator()->create_course(['fullname' => 'Second Public', 'visible' => 1]);
        $hidden = $this->getDataGenerator()->create_course(['fullname' => 'Hidden Course', 'visible' => 0]);
        set_config('homepagepubliccourses', $second->id . ',' . $hidden->id, 'theme_learnlingo');
        homepage_context::purge_public_cache();

        $this->setGuestUser();
        $data = homepage_context::data();
        $this->assertSame([(int)$second->id], array_column($data['courses'], 'id'));
        $this->assertSame(1, $data['coursecount']);
        $this->assertSame('Second Public', html_entity_decode($data['courses'][0]['fullname']));

        set_config('homepagepubliccourses', '', 'theme_learnlingo');
        homepage_context::purge_public_cache();
        $data = homepage_context::data();
        $ids = array_column($data['courses'], 'id');
        $this->assertContains((int)$first->id, $ids);
        $this->assertContains((int)$second->id, $ids);
        $this->assertNotContains((int)$hidden->id, $ids);
    }

    /**
     * Student-only fields must not survive into the next visitor's context.
     */
    public function test_logged_in_course_context_is_per_user_and_not_cached_publicly(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['fullname' => 'My Enrolment']);
        $user = $this->getDataGenerator()->create_user(['firstname' => 'Alice']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->setUser($user);
        $data = homepage_context::data();
        $this->assertTrue($data['isloggedin']);
        $this->assertSame('Alice', $data['user']['firstname']);
        $this->assertContains((int)$course->id, array_column($data['mycourses'], 'id'));

        $this->setGuestUser();
        $guest = homepage_context::data();
        $this->assertFalse($guest['user']);
        $this->assertSame([], $guest['mycourses']);
    }

    /**
     * Even unescaped Mustache syntax cannot inject HTML from profile data.
     */
    public function test_profile_values_remain_escaped_in_triple_braces(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $user->firstname = '<img src=x onerror=alert(1)>';
        $this->setUser($user);
        $html = homepage_context::render('{{{user.firstname}}}');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }

    /**
     * Public cache is both writable and explicitly purged.
     */
    public function test_public_cache_can_be_invalidated(): void {
        $this->resetAfterTest();
        $cache = cache::make('theme_learnlingo', 'publiccourses');
        $cache->set('test_key', [['id' => 91]]);
        $this->assertNotFalse($cache->get('test_key'));
        homepage_context::purge_public_cache();
        $this->assertFalse($cache->get('test_key'));
    }

    /**
     * A core course-created event has a registered post-commit observer that clears MUC.
     *
     * PHPUnit keeps its database transaction open until teardown, so Moodle
     * defers external observers ('internal' => false) rather than dispatching
     * them synchronously. Test the observer registration and callback directly.
     */
    public function test_course_created_event_purges_public_cache(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $CFG;

        $observers = [];
        require($CFG->dirroot . '/theme/learnlingo/db/events.php');
        $matching = array_values(array_filter($observers, static function(array $definition): bool {
            return $definition['eventname'] === '\\core\\event\\course_created';
        }));
        $this->assertCount(1, $matching);
        $this->assertSame('\\theme_learnlingo\\observer::public_courses_changed', $matching[0]['callback']);
        $this->assertFalse($matching[0]['internal']);

        $course = $this->getDataGenerator()->create_course(['fullname' => 'New catalogue course']);
        $cache = cache::make('theme_learnlingo', 'publiccourses');
        $cache->set('test_event_key', [['id' => 99]]);
        $this->assertNotFalse($cache->get('test_event_key'));

        $event = course_created::create([
            'objectid' => $course->id,
            'context' => context_course::instance($course->id),
            'other' => ['fullname' => $course->fullname],
        ]);
        observer::public_courses_changed($event);
        $this->assertFalse($cache->get('test_event_key'));
    }

    /**
     * The real anonymous homepage context must use the configured page size.
     */
    public function test_guest_homepage_context_uses_configured_page_size(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('homepagepubliccoursesperpage', '3', 'theme_learnlingo');
        for ($i = 1; $i <= 5; $i++) {
            $this->getDataGenerator()->create_course(['fullname' => 'Guest Course ' . $i]);
        }
        homepage_context::purge_public_cache();
        $this->setGuestUser();

        $data = homepage_context::data();
        $this->assertCount(3, $data['courses']);
        $this->assertSame(3, $data['coursecount']);
        $this->assertSame(5, $data['coursepagination']['coursetotal']);
        $this->assertSame(3, $data['coursepagination']['coursesperpage']);
        $this->assertSame(2, $data['coursepagination']['coursepagecount']);
    }

    /**
     * Four public courses with a three-card layout must create two pages.
     */
    public function test_guest_pages_limit_courses_and_offer_navigation(): void {
        $courses = [
            ['id' => 11], ['id' => 12], ['id' => 13], ['id' => 14],
        ];
        $first = homepage_context::paginate_guest_courses($courses, 1, 3);
        $this->assertSame([11, 12, 13], array_column($first['courses'], 'id'));
        $this->assertSame(3, $first['coursecount']);
        $this->assertSame(4, $first['coursetotal']);
        $this->assertSame(3, $first['coursesperpage']);
        $this->assertSame(3, $first['coursecolumns']);
        $this->assertSame(2, $first['coursepagecount']);
        $this->assertTrue($first['hasnextcoursepage']);
        $this->assertFalse($first['haspreviouscoursepage']);
        $this->assertCount(2, $first['coursepages']);
        $this->assertStringContainsString('llcoursepage=2', $first['nextcoursepageurl']);

        $second = homepage_context::paginate_guest_courses($courses, 2, 3);
        $this->assertSame([14], array_column($second['courses'], 'id'));
        $this->assertSame(1, $second['coursecolumns']);
        $this->assertFalse($second['hasnextcoursepage']);
        $this->assertTrue($second['haspreviouscoursepage']);
        $this->assertSame(1, $second['coursepages'][0]['number']);
        $this->assertTrue($second['coursepages'][1]['iscurrent']);
    }

    /**
     * Never synthesize a fourth course to fill a visual four-card layout.
     */
    public function test_fewer_visible_courses_reduce_real_column_count(): void {
        $courses = [['id' => 1], ['id' => 2], ['id' => 3]];
        $page = homepage_context::paginate_guest_courses($courses, 1, 4);
        $this->assertSame(4, $page['coursesperpage']);
        $this->assertSame(3, $page['coursecolumns']);
        $this->assertSame(3, $page['coursecount']);
        $this->assertFalse($page['hascoursepages']);
        $this->assertCount(3, $page['courses']);
        $this->assertSame(1, homepage_context::paginate_guest_courses($courses, -9, 4)['coursepage']);
        $this->assertSame(1, homepage_context::paginate_guest_courses($courses, 900, 4)['coursepage']);
    }

    /**
     * The public visitor limit cannot truncate authenticated course enrollments.
     */
    public function test_guest_limit_does_not_affect_enrolled_courses(): void {
        $this->resetAfterTest();
        set_config('homepagepubliccoursesperpage', '1', 'theme_learnlingo');
        $user = $this->getDataGenerator()->create_user();
        $one = $this->getDataGenerator()->create_course();
        $two = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $one->id);
        $this->getDataGenerator()->enrol_user($user->id, $two->id);
        $this->setUser($user);
        $data = homepage_context::data();
        $this->assertFalse($data['coursepagination']);
        $this->assertSame(2, $data['mycoursecount']);
        $this->assertSame(2, count($data['mycourses']));
        $this->assertSame(1, homepage_context::guest_courses_per_page());
    }

    /**
     * MCP's context contract must document all supported dynamic lists.
     */
    public function test_contract_is_limited_to_homepagehtml(): void {
        $schema = homepage_context::schema();
        $this->assertSame('homepagehtml', $schema['field']);
        $this->assertArrayHasKey('mycourses', $schema['fields']);
        $this->assertArrayHasKey('cohorts', $schema['fields']);
        $this->assertArrayHasKey('courses', $schema['fields']);
        $this->assertArrayHasKey('coursepagination', $schema['fields']);
        $this->assertStringContainsString('ONLY', implode(' ', $schema['instructions']));
        $this->assertStringContainsString('coursecolumns', implode(' ', $schema['instructions']));
        $this->assertStringContainsString('tightly cropped', implode(' ', $schema['instructions']));
        $this->assertStringContainsString('NEVER leave blank', implode(' ', $schema['instructions']));
        $this->assertStringContainsString('MENU AND NAVIGATION', implode(' ', $schema['instructions']));
        $this->assertStringContainsString('BRANDING', implode(' ', $schema['instructions']));
    }
}
