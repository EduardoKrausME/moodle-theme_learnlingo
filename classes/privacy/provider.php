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
 * Privacy Subsystem implementation for theme_learnlingo.
 *
 * @package   theme_learnlingo
 * @copyright 2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @copyright based on work by 2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\privacy;

use context;
use context_system;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * The LearnLingo theme stores accessibility interaction records.
 *
 * @package theme_learnlingo
 * @copyright 2025 Eduardo Kraus {@link https://eduardokraus.com}
 * @copyright based on work by 2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /** Accessibility log table name. */
    const ACCESSIBILITY_LOG_TABLE = "theme_learnlingo_accesslog";

    /**
     * Returns metadata about this system.
     *
     * @param collection $items The initialised item collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items): collection {

        $items->add_database_table(self::ACCESSIBILITY_LOG_TABLE, [
            "userid" => "privacy:metadata:theme_learnlingo_accesslog:userid",
            "courseid" => "privacy:metadata:theme_learnlingo_accesslog:courseid",
            "cmid" => "privacy:metadata:theme_learnlingo_accesslog:cmid",
            "action" => "privacy:metadata:theme_learnlingo_accesslog:action",
            "item" => "privacy:metadata:theme_learnlingo_accesslog:item",
            "status" => "privacy:metadata:theme_learnlingo_accesslog:status",
            "activeitems" => "privacy:metadata:theme_learnlingo_accesslog:activeitems",
            "statejson" => "privacy:metadata:theme_learnlingo_accesslog:statejson",
            "pageurl" => "privacy:metadata:theme_learnlingo_accesslog:pageurl",
            "timecreated" => "privacy:metadata:theme_learnlingo_accesslog:timecreated",
        ], "privacy:metadata:theme_learnlingo_accesslog");

        return $items;
    }

    /**
     * Returns contexts containing data for the supplied user.
     *
     * @param int $userid The user id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                 WHERE ctx.contextlevel = :contextlevel
                   AND EXISTS (
                       SELECT 1
                         FROM {" . self::ACCESSIBILITY_LOG_TABLE . "} l
                        WHERE l.userid = :userid
                   )";
        $contextlist->add_from_sql($sql, [
            "contextlevel" => CONTEXT_SYSTEM,
            "userid" => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Exports user data for approved contexts.
     *
     * @param approved_contextlist $contextlist Approved context list.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!in_array(context_system::instance()->id, $contextlist->get_contextids(), true)) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $records = $DB->get_records(self::ACCESSIBILITY_LOG_TABLE, ["userid" => $userid], "timecreated ASC, id ASC");
        if (!$records) {
            return;
        }

        writer::with_context(context_system::instance())->export_data(
            [get_string("privacy:accessibilitylogpath", "theme_learnlingo")],
            (object) ["records" => array_values($records)]
        );
    }

    /**
     * Deletes all data for all users in the supplied context.
     *
     * @param context $context The context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $DB->delete_records(self::ACCESSIBILITY_LOG_TABLE);
    }

    /**
     * Deletes data for a user in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved context list.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (!in_array(context_system::instance()->id, $contextlist->get_contextids(), true)) {
            return;
        }

        $DB->delete_records(self::ACCESSIBILITY_LOG_TABLE, ["userid" => $contextlist->get_user()->id]);
    }

    /**
     * Adds users with data in the supplied context.
     *
     * @param userlist $userlist The userlist to add users to.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        if ($userlist->get_context()->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $userlist->add_from_sql("userid", "SELECT DISTINCT userid FROM {" . self::ACCESSIBILITY_LOG_TABLE . "}", []);
    }

    /**
     * Deletes user data for the supplied approved user list.
     *
     * @param approved_userlist $userlist Approved user list.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        if ($userlist->get_context()->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }

        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }

        list($sql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select(self::ACCESSIBILITY_LOG_TABLE, "userid {$sql}", $params);
    }

}
