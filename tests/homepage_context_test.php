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
 * Mustache homepage security, public course selection and cache regression tests.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use core_cache\cache;

/**
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
     * A real core course event must invalidate the public MUC catalogue.
     */
    public function test_course_created_event_purges_public_cache(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $cache = cache::make('theme_learnlingo', 'publiccourses');
        $cache->set('test_event_key', [['id' => 99]]);
        $this->assertNotFalse($cache->get('test_event_key'));

        $this->getDataGenerator()->create_course(['fullname' => 'New catalogue course']);
        $this->assertFalse($cache->get('test_event_key'));
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
    }
}
