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
    $settings->add(new admin_setting_heading(
        'theme_learnlingo/aibrandingheading',
        get_string('aibranding', 'theme_learnlingo'),
        html_writer::link(
            new moodle_url('/theme/learnlingo/admin/branding_ai.php'),
            get_string('aibrandingopen', 'theme_learnlingo')
        )
    ));

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

    foreach (['logo', 'loginlogo'] as $area) {
        $setting = new admin_setting_configstoredfile(
            'theme_learnlingo/' . $area,
            get_string($area, 'theme_learnlingo'),
            get_string($area . '_desc', 'theme_learnlingo'),
            $area,
            0,
            ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg', '.webp']]
        );
        $setting->set_updatedcallback('theme_reset_all_caches');
        $settings->add($setting);
    }

    foreach (['canvascolor' => '#f5f6f8', 'textcolor' => '#252a37'] as $name => $default) {
        $setting = new admin_setting_configcolourpicker(
            'theme_learnlingo/' . $name,
            get_string($name, 'theme_learnlingo'),
            get_string($name . '_desc', 'theme_learnlingo'),
            $default
        );
        $setting->set_updatedcallback('theme_reset_all_caches');
        $settings->add($setting);
    }

    foreach ([
        'dashboardeyebrowtext' => '',
        'dashboarddescriptiontext' => '',
        'loginpaneltitletext' => '',
        'loginpaneldescriptiontext' => '',
        'loginpaneltaglinetext' => '',
    ] as $name => $default) {
        $setting = new admin_setting_configtext(
            'theme_learnlingo/' . $name,
            get_string($name, 'theme_learnlingo'),
            get_string($name . '_desc', 'theme_learnlingo'),
            $default,
            PARAM_TEXT
        );
        $setting->set_updatedcallback('theme_reset_all_caches');
        $settings->add($setting);
    }

    // The existing LearnLingo landing page remains the default for existing sites.
    $settings->add(new admin_setting_heading(
        'theme_learnlingo/homepageheading',
        get_string('homepageheading', 'theme_learnlingo'),
        get_string('homepageheading_desc', 'theme_learnlingo')
    ));

    $setting = new admin_setting_configselect(
        'theme_learnlingo/homepagemode',
        get_string('homepagemode', 'theme_learnlingo'),
        get_string('homepagemode_desc', 'theme_learnlingo'),
        'learnlingo',
        [
            'learnlingo' => get_string('homepagemodelearnlingo', 'theme_learnlingo'),
            'moodle' => get_string('homepagemodemoodle', 'theme_learnlingo'),
            'html' => get_string('homepagemodehtml', 'theme_learnlingo'),
        ]
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $setting = new admin_setting_confightmleditor(
        'theme_learnlingo/homepagehtml',
        get_string('homepagehtml', 'theme_learnlingo'),
        get_string('homepagehtml_desc', 'theme_learnlingo'),
        '',
        PARAM_RAW,
        '90',
        '18'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $setting = new admin_setting_configcheckbox(
        'theme_learnlingo/homepagefullwidth',
        get_string('homepagefullwidth', 'theme_learnlingo'),
        get_string('homepagefullwidth_desc', 'theme_learnlingo'),
        1
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

    $setting = new admin_setting_configcheckbox(
        'theme_learnlingo/showpersonalxp',
        get_string('showpersonalxp', 'theme_learnlingo'),
        get_string('showpersonalxp_desc', 'theme_learnlingo'),
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
