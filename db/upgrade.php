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
 * LearnLingo theme upgrades.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade LearnLingo.
 *
 * @param int $oldversion Previously installed version.
 * @return bool
 */
function xmldb_theme_learnlingo_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026110300) {
        $cats = new xmldb_table('theme_learnlingo_pagecats');
        $cats->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $cats->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $cats->add_field('slug', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $cats->add_field('description', XMLDB_TYPE_TEXT);
        $cats->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $cats->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $cats->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $cats->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $cats->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $cats->add_index('sluguniq', XMLDB_INDEX_UNIQUE, ['slug']);
        $cats->add_index('sortorderidx', XMLDB_INDEX_NOTUNIQUE, ['sortorder']);
        if (!$dbman->table_exists($cats)) {
            $dbman->create_table($cats);
        }

        $pages = new xmldb_table('theme_learnlingo_pages');
        $pages->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $pages->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $pages->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $pages->add_field('slug', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $pages->add_field('content', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL);
        $pages->add_field('scss', XMLDB_TYPE_TEXT, 'big');
        $pages->add_field('description', XMLDB_TYPE_TEXT);
        $pages->add_field('metadescription', XMLDB_TYPE_TEXT);
        $pages->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $pages->add_field('accesslevel', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $pages->add_field('fullwidth', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $pages->add_field('showinmenu', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $pages->add_field('menutitle', XMLDB_TYPE_CHAR, '255');
        $pages->add_field('menuorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $pages->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $pages->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $pages->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $pages->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $pages->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $pages->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $pages->add_index('categorysluguniq', XMLDB_INDEX_UNIQUE, ['categoryid', 'slug']);
        $pages->add_index('statusidx', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $pages->add_index('categoryidx', XMLDB_INDEX_NOTUNIQUE, ['categoryid']);
        $pages->add_index('sortorderidx', XMLDB_INDEX_NOTUNIQUE, ['sortorder']);
        if (!$dbman->table_exists($pages)) {
            $dbman->create_table($pages);
        }

        upgrade_plugin_savepoint(true, 2026110300, 'theme', 'learnlingo');
    }

    if ($oldversion < 2026112001) {
        $table = new xmldb_table("theme_learnlingo_accesslog");
        $table->add_field("id", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field("userid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_field("courseid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_field("cmid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
        $table->add_field("action", XMLDB_TYPE_CHAR, "40", null, XMLDB_NOTNULL);
        $table->add_field("item", XMLDB_TYPE_CHAR, "100");
        $table->add_field("status", XMLDB_TYPE_CHAR, "20");
        $table->add_field("activeitems", XMLDB_TYPE_TEXT);
        $table->add_field("statejson", XMLDB_TYPE_TEXT);
        $table->add_field("pageurl", XMLDB_TYPE_TEXT);
        $table->add_field("timecreated", XMLDB_TYPE_INTEGER, "20", null, XMLDB_NOTNULL, null, "0");
        $table->add_key("primary", XMLDB_KEY_PRIMARY, ["id"]);
        $table->add_index("userid", XMLDB_INDEX_NOTUNIQUE, ["userid"]);
        $table->add_index("action", XMLDB_INDEX_NOTUNIQUE, ["action"]);
        $table->add_index("timecreated", XMLDB_INDEX_NOTUNIQUE, ["timecreated"]);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026112001, "theme", "learnlingo");
    }

    return true;
}
