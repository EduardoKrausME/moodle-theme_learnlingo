<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Version information for the LearnLingo theme.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->component = 'theme_learnlingo';
$plugin->version = 2026072800;
$plugin->release = '1.2.2';
$plugin->requires = 2022112800;
$plugin->maturity = MATURITY_STABLE;

$plugin->dependencies = [
    'theme_boost' => 2022112800,
];
