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
 * Session-aware, read-only JSON API for the site homepage.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use theme_learnlingo\local\home_data;

$section = optional_param('section', 'all', PARAM_ALPHA);
$limit = optional_param('limit', 12, PARAM_INT);
$offset = optional_param('offset', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/theme/learnlingo/home_api.php'));
$PAGE->set_context(context_system::instance());

// The response can include the current user's profile, enrolments and progress.
// Never put it in a browser, CDN, reverse-proxy or shared HTTP cache.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store, no-cache, max-age=0, must-revalidate');
header('Pragma: no-cache');
header('Vary: Cookie');
header('X-Content-Type-Options: nosniff');

if (!in_array($section, ['all', 'user', 'courses', 'publiccourses', 'cohorts', 'popular'], true) ||
        $limit < 1 || $limit > 48 || $offset < 0 || $offset > 100000) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_parameters'], JSON_THROW_ON_ERROR);
    exit;
}

// No user ID can be supplied. Profile, cohorts, enrolments and progress always
// belong to the user authenticated by the Moodle session; guests receive none.
echo json_encode(home_data::response($section, $limit, $offset),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
