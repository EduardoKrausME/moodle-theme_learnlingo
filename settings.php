<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

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
