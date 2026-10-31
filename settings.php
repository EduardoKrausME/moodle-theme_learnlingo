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

use theme_learnlingo\mcp_banner;

defined('MOODLE_INTERNAL') || die;

global $PAGE, $OUTPUT, $ADMIN, $CFG;

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

    $colors = [
        "brandcolor" => "#58cc02",
        "accentcolor" => "#8be24a",
        "canvascolor" => "#f7faf5",
        "textcolor" => "#263238"
    ];
    foreach ($colors as $name => $default) {
        $setting = new admin_setting_configcolourpicker(
            "theme_learnlingo/{$name}",
            get_string($name, "theme_learnlingo"),
            get_string("{$name}_desc", "theme_learnlingo"),
            $default
        );
        $setting->set_updatedcallback("theme_reset_all_caches");
        $page->add($setting);
    }

    // Use the Moodle-wide site logo, shared with core's Appearance > Logos page.
    $setting = new admin_setting_configstoredfile(
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
    $setting = new admin_setting_configstoredfile(
        "core_admin/favicon",
        get_string("favicon", "theme_learnlingo"),
        get_string("favicon_desc", "theme_learnlingo"),
        "favicon", 0,
        ["maxfiles" => 1, "accepted_types" => [".jpg", ".jpeg", ".png"]]
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
        "",
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

    $setting = new admin_setting_confightmleditor(
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

    $setting = new admin_setting_configcolourpicker(
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
    $setting = new admin_setting_configstoredfile($name, $title, $description, "loginbackgroundimage");
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
            html_writer::tag("p", get_string("aibrandingdesc", "theme_learnlingo")) .
            html_writer::link(
                new moodle_url("/theme/learnlingo/admin/branding_ai.php"),
                get_string("aibrandingopen", "theme_learnlingo")
            )
        ));
    }

    $page->add(new admin_setting_heading(
        "theme_learnlingo/jsonbrandinglink",
        "",
        html_writer::tag("p", get_string("jsonpageintro", "theme_learnlingo")) .
        html_writer::link(
            new moodle_url("/theme/learnlingo/admin/branding_json.php"),
            get_string("jsonopen", "theme_learnlingo")
        )
    ));

    $settings->add($page);

}
