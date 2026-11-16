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
 * Public LearnLingo identity for the app builder (no authenticated data).
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Only publicly visible site branding is returned; login is intentionally not required.
// @codingStandardsIgnoreLine
require_once(__DIR__ . '/../../config.php');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
        || get_config('core', 'theme') !== 'learnlingo') {
    http_response_code(404);
    echo json_encode(['error' => 'LearnLingo is not the active site theme']);
    exit;
}

echo json_encode(\theme_learnlingo\mobile_branding::export(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
