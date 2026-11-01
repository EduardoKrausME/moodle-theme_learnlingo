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
 * Home API isolation, visibility and cache tests.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use cache;
use theme_learnlingo\local\home_data;

/**
 * Verify visitor catalogue and per-user data are never confused.
 *
 * @covers \theme_learnlingo\local\home_data
 */
final class home_data_test extends advanced_testcase {
    /**
     * Empty selection means all visible courses; explicit selection limits it.
     */
    public function test_public_course_selection_and_visibility(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $first = $this->getDataGenerator()->create_course(['fullname' => 'First available course']);
        $second = $this->getDataGenerator()->create_course(['fullname' => 'Second available course']);
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);

        $options = home_data::public_course_options();
        $this->assertArrayHasKey($first->id, $options);
        $this->assertArrayHasKey($second->id, $options);
        $this->assertArrayNotHasKey($hidden->id, $options);

        set_config('publiccourses', '', 'theme_learnlingo');
        $allids = array_map(static function($course): int {
            return (int)$course->id;
        }, home_data::public_courses());
        $this->assertContains($first->id, $allids);
        $this->assertContains($second->id, $allids);
        $this->assertNotContains($hidden->id, $allids);

        set_config('publiccourses', (string)$second->id, 'theme_learnlingo');
        $selected = home_data::public_courses();
        $this->assertCount(1, $selected);
        $this->assertSame((int)$second->id, (int)$selected[0]->id);
    }

    /**
     * An anonymous request cannot read a student's profile, cohorts or progress.
     */
    public function test_guest_response_has_no_personal_information(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->getDataGenerator()->create_course(['fullname' => 'Public course']);
        $this->setGuestUser();

        $response = home_data::response('all', 12, 0);
        $this->assertFalse($response['loggedin']);
        $this->assertNull($response['user']);
        $this->assertSame([], $response['cohorts']);
        $this->assertGreaterThanOrEqual(1, $response['publiccourses']['total']);
        foreach ($response['courses']['items'] as $course) {
            $this->assertFalse($course['enrolled']);
            $this->assertNull($course['progress']);
        }
    }

    /**
     * Profile data, cohort membership and course progress are from this session.
     */
    public function test_logged_in_response_is_always_about_current_user(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $alice = $this->getDataGenerator()->create_user([
            'firstname' => 'Alice',
            'email' => 'alice@example.invalid',
        ]);
        $bob = $this->getDataGenerator()->create_user([
            'firstname' => 'Bob',
            'email' => 'bob@example.invalid',
        ]);
        $this->getDataGenerator()->enrol_user($alice->id, $course->id);
        $cohort = $this->getDataGenerator()->create_cohort(['name' => 'Alice cohort']);
        cohort_add_member($cohort->id, $alice->id);

        $this->setUser($alice);
        $a = home_data::response('all', 12, 0);
        $this->assertTrue($a['loggedin']);
        $this->assertSame('Alice', $a['user']['firstname']);
        $this->assertSame('alice@example.invalid', $a['user']['email']);
        $this->assertSame($cohort->id, $a['cohorts'][0]['id']);
        $this->assertTrue($a['courses']['enrolledlist']);
        $this->assertSame($course->id, $a['courses']['items'][0]['id']);
        $this->assertTrue($a['courses']['items'][0]['enrolled']);
        $this->assertArrayHasKey('progress', $a['courses']['items'][0]);

        $this->setUser($bob);
        $b = home_data::response('all', 12, 0);
        $this->assertSame('Bob', $b['user']['firstname']);
        $this->assertSame([], $b['cohorts']);
        $this->assertFalse($b['courses']['enrolledlist']);
        foreach ($b['courses']['items'] as $course) {
            $this->assertFalse($course['enrolled']);
            $this->assertNull($course['progress']);
        }
    }

    /**
     * Shared cached rankings never override the current viewer's visibility.
     */
    public function test_popular_ranking_cache_does_not_expose_hidden_courses(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $visible = $this->getDataGenerator()->create_course(['visible' => 1]);
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);
        cache::make('theme_learnlingo', 'popularcourses')->set('last30days', [
            $hidden->id => 30,
            $visible->id => 12,
        ]);

        $this->setGuestUser();
        $popular = home_data::popular_courses();
        $this->assertCount(1, $popular);
        $this->assertSame($visible->id, $popular[0]['id']);
        $this->assertSame(12, $popular[0]['views']);
    }
}
