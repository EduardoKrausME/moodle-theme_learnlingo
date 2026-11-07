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
 * Reorder custom pages and categories from the theme settings tree.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

use theme_learnlingo\pages;

require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();

$type = required_param('type', PARAM_ALPHA);
$id = required_param('id', PARAM_INT);
$direction = required_param('direction', PARAM_ALPHA);
if (!pages::ready()) {
    throw new moodle_exception('custompagesneedupgrade', 'theme_learnlingo');
}
pages::move($type, $id, $direction);

$url = new moodle_url('/admin/settings.php', ['section' => 'themesettinglearnlingo']);
$url->set_anchor('theme_learnlingo_pages');
redirect($url);
