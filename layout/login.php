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
 * A login page layout for the boost theme.
 *
 * @package   theme_learnlingo
 * @copyright 2016 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use theme_learnlingo\output\footer_renderer;

defined('MOODLE_INTERNAL') || die;

$bodyattributes = $OUTPUT->body_attributes();

// Left-panel instructions. Only set when the admin has defined custom instructions;
// the template falls back to the default welcome content when this is empty/null.
$leftinstructions = !empty($CFG->auth_instructions)
    ? format_text($CFG->auth_instructions, FORMAT_MOODLE, ['context' => context_system::instance()])
    : null;

$templatecontext = [
    'loginkicker' => theme_learnlingo_brand_text('dashboardeyebrowtext', 'dashboardeyebrow'),
    'logintitle' => theme_learnlingo_brand_text('loginpaneltitletext', 'loginpaneltitle'),
    'logindescription' => theme_learnlingo_brand_text('loginpaneldescriptiontext', 'loginpaneldescription'),
    'logintagline' => theme_learnlingo_brand_text('loginpaneltaglinetext', 'loginpaneltagline'),
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'leftinstructions' => $leftinstructions,
];

$templatecontext = array_merge($templatecontext, footer_renderer::mustache_data());

echo $OUTPUT->render_from_template('theme_learnlingo/login', $templatecontext);
