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

use theme_learnlingo\dark_mode;
use theme_learnlingo\mcp_banner;

defined('MOODLE_INTERNAL') || die;

global $PAGE, $OUTPUT, $ADMIN, $CFG, $DB;

// The admin tree must expose the settings page even when fulltree is false.
$settings->hidden = false;

if ($ADMIN->fulltree) {
    // Boost's native tabs keep the same Moodle settings URL and setting names.
    $settings = new theme_boost_admin_settingspage_tabs(
        "themesettinglearnlingo",
        get_string("configtitle", "theme_learnlingo")
    );
    $settings->hidden = false;

    $page = new admin_settingpage(
        "theme_learnlingo_branding",
        get_string("brandingsettings", "theme_learnlingo")
    );

    if (mcp_banner::should_show()) {
        $page->add(new admin_setting_heading(
            "theme_learnlingo/mcp_banner",
            "",
            $OUTPUT->render_from_template("theme_learnlingo/mcp_banner", [
                "mcp_banner_dismiss_url" => new moodle_url("/theme/learnlingo/dismiss-mcp-banner.php"),
            ])
        ));
    }

    // Accessible after installation as well as during first-run onboarding.
    $page->add(new admin_setting_heading(
        'theme_learnlingo/visualsetuplink',
        '',
        $OUTPUT->render_from_template('theme_learnlingo/branding_admin_link', [
            'description' => get_string('setupsettingsdesc', 'theme_learnlingo'),
            'url' => (new moodle_url('/theme/learnlingo/admin/setup.php'))->out(false),
            'label' => get_string('setupsettingslink', 'theme_learnlingo'),
        ])
    ));

    $colors = [
        "brandcolor" => "#58cc02",
        "accentcolor" => "#8be24a",
        "canvascolor" => "#f7faf5",
        "textcolor" => "#263238",
    ];
    foreach ($colors as $name => $default) {
        $setting = new \theme_learnlingo\admin_setting_configcolourmodern(
            "theme_learnlingo/{$name}",
            get_string($name, "theme_learnlingo"),
            get_string("{$name}_desc", "theme_learnlingo"),
            $default
        );
        $setting->set_updatedcallback("theme_reset_all_caches");
        $page->add($setting);
    }

    // Initialise the modern colour editor on both the branding and footer tabs.
    $PAGE->requires->js_call_amd("theme_learnlingo/colour_settings", "init");
    $PAGE->requires->js_call_amd("theme_learnlingo/storedfile_settings", "init");

    // Use the Moodle-wide site logo, shared with core's Appearance > Logos page.
    $setting = new \theme_learnlingo\admin_setting_configstoredfilemodern(
        "core_admin/logo",
        get_string("logo", "theme_learnlingo"),
        get_string("logo_desc", "theme_learnlingo"),
        "logo",
        0,
        ["maxfiles" => 1, "accepted_types" => [".jpg", ".png"]]
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    // Favicon file setting.
    $setting = new \theme_learnlingo\admin_setting_configstoredfilemodern(
        "core_admin/favicon",
        get_string("favicon", "theme_learnlingo"),
        get_string("favicon_desc", "theme_learnlingo"),
        "favicon", 0,
        ["maxfiles" => 1, "accepted_types" => ["image"]]
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);


    $settings->add($page);

    $page = new admin_settingpage(
        "theme_learnlingo_homepage",
        get_string("homepagesettings", "theme_learnlingo")
    );

    $setting = new admin_setting_configselect(
        "theme_learnlingo/homepagemode",
        get_string("homepagemode", "theme_learnlingo"),
        get_string("homepagemode_desc", "theme_learnlingo"),
        "learnlingo",
        [
            "learnlingo" => get_string("homepagemodelearnlingo", "theme_learnlingo"),
            "moodle" => get_string("homepagemodemoodle", "theme_learnlingo"),
            "html" => get_string("homepagemodehtml", "theme_learnlingo"),
        ]
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $setting = new admin_setting_configtextarea(
        "theme_learnlingo/homepagehtml",
        get_string("homepagehtml", "theme_learnlingo"),
        get_string("homepagehtml_desc", "theme_learnlingo"),
        "config",
        PARAM_RAW,
        "90",
        "18"
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $setting = new admin_setting_configtextarea(
        "theme_learnlingo/homepagescss",
        get_string("homepagescss", "theme_learnlingo"),
        get_string("homepagescss_desc", "theme_learnlingo"),
        "",
        PARAM_RAW,
        "90",
        "16"
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $setting = new admin_setting_configcheckbox(
        "theme_learnlingo/homepagefullwidth",
        get_string("homepagefullwidth", "theme_learnlingo"),
        get_string("homepagefullwidth_desc", "theme_learnlingo"),
        1
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    // No selection means all public, visible courses in Moodle's course sort order.
    $publiccourseoptions = [];
    foreach ($DB->get_records('course', ['visible' => 1], 'sortorder ASC', 'id,fullname') as $publiccourse) {
        if ((int)$publiccourse->id === SITEID) {
            continue;
        }
        $publiccourseoptions[$publiccourse->id] = format_string($publiccourse->fullname);
    }
    $setting = new admin_setting_configmulticheckbox(
        "theme_learnlingo/homepagepubliccourses",
        get_string("homepagepubliccourses", "theme_learnlingo"),
        get_string("homepagepubliccourses_desc", "theme_learnlingo"),
        "",
        $publiccourseoptions
    );
    $setting->set_updatedcallback("theme_learnlingo_homepage_cache_reset");
    $page->add($setting);

    // Limit ONLY the guest-facing {{#courses}} list; student lists are never paginated.
    $perpageoptions = [];
    foreach (range(1, 12) as $count) {
        $perpageoptions[$count] = (string)$count;
    }
    $setting = new admin_setting_configselect(
        "theme_learnlingo/homepagepubliccoursesperpage",
        get_string("homepagepubliccoursesperpage", "theme_learnlingo"),
        get_string("homepagepubliccoursesperpage_desc", "theme_learnlingo"),
        "4",
        $perpageoptions
    );
    $setting->set_updatedcallback("theme_learnlingo_homepage_cache_reset");
    $page->add($setting);

    $PAGE->requires->js_call_amd("theme_learnlingo/homepage_settings", "init");
    $settings->add($page);

    $page = new admin_settingpage(
        "theme_learnlingo_footer",
        get_string("footersettings", "theme_learnlingo")
    );

    // Default mode preserves the previously configured footer.
    $setting = new admin_setting_configselect(
        "theme_learnlingo/footermode",
        get_string("footermode", "theme_learnlingo"),
        get_string("footermode_desc", "theme_learnlingo"),
        "moodle",
        [
            "moodle" => get_string("footermodemoodle", "theme_learnlingo"),
            "html" => get_string("footermodehtml", "theme_learnlingo"),
        ]
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $setting = new admin_setting_configtextarea(
        "theme_learnlingo/footerhtml",
        get_string("footerhtml", "theme_learnlingo"),
        get_string("footerhtml_desc", "theme_learnlingo"),
        ""
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $setting = new admin_setting_configtextarea(
        "theme_learnlingo/footerscss",
        get_string("footerscss", "theme_learnlingo"),
        get_string("footerscss_desc", "theme_learnlingo"),
        "",
        PARAM_RAW,
        "90",
        "16"
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $PAGE->requires->js_call_amd("theme_learnlingo/footer_settings", "init");

    $setting = new \theme_learnlingo\admin_setting_configcolourmodern(
        "theme_learnlingo/footer_background_color",
        get_string("footer_background_color", "theme_learnlingo"),
        get_string("footer_background_color_desc", "theme_learnlingo"),
        "#314755"
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    for ($i = 1; $i <= 4; $i++) {
        $page->add(new admin_setting_heading(
            "theme_learnlingo_footer_heading_{$i}",
            get_string("footer_heading", "theme_learnlingo", $i),
            ""
        ));
        $setting = new admin_setting_configtext(
            "theme_learnlingo/footer_title_{$i}",
            get_string("footer_title", "theme_learnlingo", $i),
            get_string("footer_title_desc", "theme_learnlingo", $i),
            "",
            PARAM_TEXT
        );
        $setting->set_updatedcallback("theme_reset_all_caches");
        $page->add($setting);
        $setting = new admin_setting_confightmleditor(
            "theme_learnlingo/footer_html_{$i}",
            get_string("footer_html", "theme_learnlingo", $i),
            get_string("footer_html_desc", "theme_learnlingo", $i),
            ""
        );
        $setting->set_updatedcallback("theme_reset_all_caches");
        $page->add($setting);
    }

    $setting = new admin_setting_configcheckbox(
        "theme_learnlingo/footer_show_copywriter",
        get_string("footer_show_copywriter", "theme_learnlingo"),
        get_string("footer_show_copywriter_desc", "theme_learnlingo"),
        1
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);
    $settings->add($page);

    $page = new admin_settingpage(
        "theme_learnlingo_dashboard",
        get_string("dashboardsettings", "theme_learnlingo")
    );

    $names = [
        "dashboardeyebrowtext",
        "dashboarddescriptiontext",
    ];
    foreach ($names as $name) {
        $setting = new admin_setting_configtext(
            "theme_learnlingo/{$name}",
            get_string($name, "theme_learnlingo"),
            get_string("{$name}_desc", "theme_learnlingo"),
            "", PARAM_TEXT
        );
        $setting->set_updatedcallback("theme_reset_all_caches");
        $page->add($setting);
    }

    $setting = new admin_setting_configcheckbox(
        "theme_learnlingo/showdashboardhero",
        get_string("showdashboardhero", "theme_learnlingo"),
        get_string("showdashboardhero_desc", "theme_learnlingo"),
        1
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $setting = new admin_setting_configcheckbox(
        "theme_learnlingo/showwelcome",
        get_string("showwelcome", "theme_learnlingo"),
        get_string("showwelcome_desc", "theme_learnlingo"),
        1
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $settings->add($page);

    $page = new admin_settingpage(
        "theme_learnlingo_course",
        get_string("coursesettings", "theme_learnlingo")
    );

    $setting = new admin_setting_configcheckbox(
        "theme_learnlingo/showcoursehero",
        get_string("showcoursehero", "theme_learnlingo"),
        get_string("showcoursehero_desc", "theme_learnlingo"),
        1
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $setting = new admin_setting_configtext(
        "theme_learnlingo/courseheroeyebrow",
        get_string("courseheroeyebrow", "theme_learnlingo"),
        get_string("courseheroeyebrow_desc", "theme_learnlingo"),
        get_string("courseheroeyebrowdefault", "theme_learnlingo"),
        PARAM_TEXT
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $setting = new admin_setting_configcheckbox(
        "theme_learnlingo/showcourseherosummary",
        get_string("showcourseherosummary", "theme_learnlingo"),
        get_string("showcourseherosummary_desc", "theme_learnlingo"),
        1
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $setting = new admin_setting_configcheckbox(
        "theme_learnlingo/showcourseheroprogress",
        get_string("showcourseheroprogress", "theme_learnlingo"),
        get_string("showcourseheroprogress_desc", "theme_learnlingo"),
        1
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $setting = new admin_setting_configcheckbox(
        "theme_learnlingo/showpersonalxp",
        get_string("showpersonalxp", "theme_learnlingo"),
        get_string("showpersonalxp_desc", "theme_learnlingo"),
        1
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $settings->add($page);

    $page = new admin_settingpage(
        "theme_learnlingo_login",
        get_string("loginsettings", "theme_learnlingo")
    );

    // Login Background image setting.
    $name = "theme_boost/loginbackgroundimage";
    $title = get_string("loginbackgroundimage", "theme_boost");
    $description = get_string("loginbackgroundimage_desc", "theme_boost");
    $setting = new \theme_learnlingo\admin_setting_configstoredfilemodern($name, $title, $description, "loginbackgroundimage");
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    // Optional logo for the login form, independent of the Moodle-wide site logo.
    $setting = new \theme_learnlingo\admin_setting_configstoredfilemodern(
        "theme_learnlingo/loginlogo",
        get_string("loginlogo", "theme_learnlingo"),
        get_string("loginlogo_desc", "theme_learnlingo"),
        "loginlogo",
        0,
        ["maxfiles" => 1, "accepted_types" => [".png", ".jpg", ".jpeg", ".webp", ".svg"]]
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $names = [
        "loginpaneltitletext",
        "loginpaneldescriptiontext",
        "loginpaneltaglinetext",
    ];
    foreach ($names as $name) {
        $setting = new admin_setting_configtext(
            "theme_learnlingo/{$name}",
            get_string($name, "theme_learnlingo"),
            get_string("{$name}_desc", "theme_learnlingo"),
            "", PARAM_TEXT
        );
        $setting->set_updatedcallback("theme_reset_all_caches");
        $page->add($setting);
    }

    $settings->add($page);

    // Integrate the optional Dark Mode plugin without making it a dependency.
    if (!dark_mode::installed()) {
        $page = new admin_settingpage(
            "theme_learnlingo_darkmode",
            get_string('darkmodetab', 'theme_learnlingo')
        );
        $page->add(new admin_setting_heading(
            'theme_learnlingo/darkmodeintegration',
            '',
            $OUTPUT->render_from_template('theme_learnlingo/darkmode_settings', [
                'installed' => dark_mode::installed(),
                'enabled' => dark_mode::enabled(),
                'codeavailable' => dark_mode::code_available(),
                'installurl' => dark_mode::install_url()->out(false),
                'sesskey' => sesskey(),
            ])
        ));
        $settings->add($page);
    }

    $page = new admin_settingpage(
        "theme_learnlingo_advanced",
        get_string("scsssettings", "theme_learnlingo")
    );

    $setting = new admin_setting_configtextarea(
        "theme_learnlingo/scsspre",
        get_string("scsspre", "theme_learnlingo"),
        get_string("scsspre_desc", "theme_learnlingo"),
        "",
        PARAM_RAW
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $setting = new admin_setting_configtextarea(
        "theme_learnlingo/scss",
        get_string("scss", "theme_learnlingo"),
        get_string("scss_desc", "theme_learnlingo"),
        "",
        PARAM_RAW
    );
    $setting->set_updatedcallback("theme_reset_all_caches");
    $page->add($setting);

    $settings->add($page);

    $page = new admin_settingpage(
        "theme_learnlingo_ai",
        get_string("aibranding", "theme_learnlingo")
    );

    if (file_exists("{$CFG->dirroot}/local/ai_bridge/version.php")) {
        // These assistants use their own POST forms; keep them outside settings.php.
        $page->add(new admin_setting_heading(
            "theme_learnlingo/aibrandinglink",
            "",
            $OUTPUT->render_from_template("theme_learnlingo/branding_admin_link", [
                "description" => get_string("aibrandingdesc", "theme_learnlingo"),
                "url" => (new moodle_url("/theme/learnlingo/admin/branding_ai.php"))->out(false),
                "label" => get_string("aibrandingopen", "theme_learnlingo"),
            ])
        ));
    }

    $page->add(new admin_setting_heading(
        "theme_learnlingo/jsonbrandinglink",
        "",
        $OUTPUT->render_from_template("theme_learnlingo/branding_admin_link", [
            "description" => get_string("jsonpageintro", "theme_learnlingo"),
            "url" => (new moodle_url("/theme/learnlingo/admin/branding_json.php"))->out(false),
            "label" => get_string("jsonopen", "theme_learnlingo"),
        ])
    ));

    $settings->add($page);

}
