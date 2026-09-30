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
 * Admin settings for the LearnLingo theme.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $setting = new admin_setting_configcolourpicker(
        'theme_learnlingo/brandcolor',
        get_string('brandcolor', 'theme_learnlingo'),
        get_string('brandcolor_desc', 'theme_learnlingo'),
        '#ef3340'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $setting = new admin_setting_configcolourpicker(
        'theme_learnlingo/accentcolor',
        get_string('accentcolor', 'theme_learnlingo'),
        get_string('accentcolor_desc', 'theme_learnlingo'),
        '#ff6670'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $setting = new admin_setting_configcheckbox(
        'theme_learnlingo/showdashboardhero',
        get_string('showdashboardhero', 'theme_learnlingo'),
        get_string('showdashboardhero_desc', 'theme_learnlingo'),
        1
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $settings->add(new admin_setting_heading(
        'theme_learnlingo/courseheroheading',
        get_string('courseheroheading', 'theme_learnlingo'),
        get_string('courseheroheading_desc', 'theme_learnlingo')
    ));

    $setting = new admin_setting_configcheckbox(
        'theme_learnlingo/showcoursehero',
        get_string('showcoursehero', 'theme_learnlingo'),
        get_string('showcoursehero_desc', 'theme_learnlingo'),
        1
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'theme_learnlingo/courseheroeyebrow',
        get_string('courseheroeyebrow', 'theme_learnlingo'),
        get_string('courseheroeyebrow_desc', 'theme_learnlingo'),
        get_string('courseheroeyebrowdefault', 'theme_learnlingo'),
        PARAM_TEXT
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $setting = new admin_setting_configcheckbox(
        'theme_learnlingo/showcourseherosummary',
        get_string('showcourseherosummary', 'theme_learnlingo'),
        get_string('showcourseherosummary_desc', 'theme_learnlingo'),
        1
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $setting = new admin_setting_configcheckbox(
        'theme_learnlingo/showcourseheroprogress',
        get_string('showcourseheroprogress', 'theme_learnlingo'),
        get_string('showcourseheroprogress_desc', 'theme_learnlingo'),
        1
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $setting = new admin_setting_configtextarea(
        'theme_learnlingo/scsspre',
        get_string('scsspre', 'theme_learnlingo'),
        get_string('scsspre_desc', 'theme_learnlingo'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $setting = new admin_setting_configtextarea(
        'theme_learnlingo/scss',
        get_string('scss', 'theme_learnlingo'),
        get_string('scss_desc', 'theme_learnlingo'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);
}
