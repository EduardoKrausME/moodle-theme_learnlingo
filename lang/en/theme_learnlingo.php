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
 * English language strings.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['accentcolor'] = 'Accent colour';
$string['accentcolor_desc'] = 'Secondary colour used in gradients and decorative elements.';
$string['aiapplied'] = 'Institution branding preferences saved.';
$string['aiapply'] = 'Apply these settings';
$string['aibranding'] = 'AI-assisted branding';
$string['aibrandingdesc'] = 'Describe the institution and generate a reviewable proposal. Requires an enabled local_ai_bridge with the theme-branding purpose and a route for the current administrator. AI never modifies settings without approval.';
$string['aibrandingopen'] = 'Open the AI branding assistant';
$string['aibridgemissing'] = 'The AI Bridge plugin is not installed or enabled.';
$string['aidescription'] = 'Institution, audience and desired visual style';
$string['aidescriptionrequired'] = 'Describe the institution before generating.';
$string['aigenerate'] = 'Generate suggestion';
$string['aigenerationfailed'] = 'Unable to generate a valid suggestion';
$string['ainoproposal'] = 'Generate a proposal before applying it.';
$string['aipreview'] = 'Review the proposed values';
$string['brandcolor'] = 'Primary colour';
$string['brandcolor_desc'] = 'Main colour used for buttons, links and highlights.';
$string['brandingsettings'] = 'Branding and colours';
$string['canvascolor'] = 'Page background colour';
$string['canvascolor_desc'] = 'Main background colour of the pages.';
$string['choosereadme'] = 'LearnLingo is a Boost child theme designed for playful, gamified learning.';
$string['configtitle'] = 'LearnLingo';
$string['courseheroeyebrow'] = 'Course header label';
$string['courseheroeyebrow_desc'] = 'Short label displayed above the course name in the playful course header.';
$string['courseheroeyebrowdefault'] = 'Keep moving';
$string['courseheroheading'] = 'Course header';
$string['courseheroheading_desc'] = 'The course header uses the native Moodle course name, summary and Course image. Teachers can change the image in Course settings > Description > Course image.';
$string['courseheroprogress'] = 'Your progress';
$string['courseheroprogressvalue'] = '{$a}% complete';
$string['coursesettings'] = 'Course pages';
$string['coursexpincourse'] = 'XP in this course';
$string['coursexplevel'] = 'Current level';
$string['coursexpreward'] = '+{$a} XP';
$string['dashboardcta'] = 'Keep learning';
$string['dashboarddescription'] = 'Build your skills one lesson at a time, follow your progress and take on your next learning challenge.';
$string['dashboarddescriptiontext'] = 'Welcome description';
$string['dashboarddescriptiontext_desc'] = 'Institution-specific welcome description.';
$string['dashboardeyebrow'] = 'Learn at your own pace';
$string['dashboardeyebrowtext'] = 'Welcome panel label';
$string['dashboardeyebrowtext_desc'] = 'Replaces the small welcome label when filled.';
$string['dashboardsettings'] = 'Dashboard';
$string['dashboardtitle'] = 'Hello, {$a}! Ready for the next step?';
$string['favicon'] = 'Favicon';
$string['favicon_desc'] = 'The favicon is displayed next to the page title in the browser tab. A Moodle favicon is displayed if a custom favicon is not provided.';
$string['frontpageallcourses'] = 'View all';
$string['frontpageavailablecoursestitle'] = 'Courses to start learning';
$string['frontpagecontinue'] = 'Continue where you left off';
$string['frontpagecourseaction'] = 'Open course';
$string['frontpagecourseseyebrow'] = 'Your next mission';
$string['frontpageemptydescription'] = 'Explore the catalogue and choose the next step in your learning path.';
$string['frontpageemptytitle'] = 'Your next course is waiting';
$string['frontpagefeaturedaction'] = 'Continue course';
$string['frontpageguestname'] = 'Student';
$string['frontpagemycoursestitle'] = 'Courses to keep you moving forward';
$string['frontpageprogress'] = '{$a}% complete';
$string['frontpagexpbadge'] = '{$a->xp} XP · {$a->level}';
$string['goalcta'] = 'View my courses';
$string['goalsubtitle'] = 'A small step today keeps your progress moving.';
$string['goaltime'] = '15 min';
$string['goaltitle'] = 'Today’s goal';
$string['homepagefullwidth'] = 'Full-width HTML homepage';
$string['homepagefullwidth_desc'] = 'Remove the content width restriction on the HTML or LearnLingo homepage.';
$string['homepageheading'] = 'Site homepage';
$string['homepageheading_desc'] = 'Choose how the public site homepage is rendered. The dashboard and course pages are independent.';
$string['homepagehtml'] = 'Custom homepage HTML';
$string['homepagehtml_desc'] = 'HTML shown in Custom HTML mode or above the LearnLingo course cards. Use the visual editor or HTML source for content and Homepage SCSS for its styles.';
$string['homepagemode'] = 'Homepage layout';
$string['homepagemode_desc'] = 'LearnLingo retains the existing home, Moodle uses the native course and activity lists, and HTML displays the editor content. An empty HTML home automatically falls back to Moodle.';
$string['homepagemodehtml'] = 'Custom HTML home';
$string['homepagemodelearnlingo'] = 'LearnLingo home (current layout)';
$string['homepagemodemoodle'] = 'Native Moodle home';
$string['homepagesettings'] = 'Site homepage';
$string['jsoncopy'] = 'Copy prompt';
$string['jsonerrorfield'] = 'Invalid JSON field: {$a}. Verify type, size and safety rules.';
$string['jsonerroridentity'] = 'The schema version, plugin identifier or repository does not match LearnLingo.';
$string['jsonerrorinvalid'] = 'Invalid JSON. Remove Markdown fences and text outside the JSON object.';
$string['jsonerrorlarge'] = 'The JSON exceeds the import size limit.';
$string['jsonerrorsettings'] = 'The JSON settings do not contain exactly the same keys as the original export.';
$string['jsonerrorstructure'] = 'The JSON root structure does not match LearnLingo\'s export.';
$string['jsonfilesnote'] = 'The JSON includes colors, copy, HTML homepage, SCSS and feature switches. The shared Moodle site logo and the login logo remain in Moodle file storage and are not embedded in the JSON.';
$string['jsonimportbutton'] = 'Import settings';
$string['jsonimportdesc'] = 'After the conversation, paste only the JSON content, without Markdown fences. All keys and values are validated before any configuration is saved.';
$string['jsonimportlabel'] = 'Final JSON to import';
$string['jsonimportsuccess'] = 'Settings imported successfully. Theme caches have been reset.';
$string['jsonimporttitle'] = '2. Import the final JSON';
$string['jsonimportwarning'] = 'Importing replaces the current theme colors, text, SCSS, HTML and SVG settings. Review generated HTML and SCSS carefully. Separately uploaded image files are not deleted.';
$string['jsonopen'] = 'Export a ChatGPT prompt and import JSON settings';
$string['jsonpageintro'] = 'Copy the full prompt into a ChatGPT conversation and answer the questions about your institution. Then paste the final JSON in the import field below.';
$string['jsonpromptdesc'] = 'The prompt includes the current configuration, plugin repository and instructions to ask questions before returning the updated JSON.';
$string['jsonpromptlabel'] = 'Full prompt with current JSON';
$string['jsonprompttitle'] = '1. Copy the prompt into ChatGPT';
$string['jsonrawdesc'] = 'Copy of the exported data, useful for reference or backup before importing a new configuration.';
$string['jsonrawtitle'] = 'Current JSON (without instructions)';
$string['loginbenefitone'] = 'Practical learning for your routine';
$string['loginbenefitthree'] = 'A clear view of your progress';
$string['loginbenefittwo'] = 'Your courses and learning paths in one place';
$string['logindescription'] = 'Sign in to continue your learning path.';
$string['loginenterpassword'] = 'Enter your password';
$string['loginforgotpassword'] = 'Forgot your password?';
$string['loginpaneldescription'] = 'Short learning challenges, clear goals and progress you can follow every day.';
$string['loginpaneldescriptiontext'] = 'Login panel description';
$string['loginpaneldescriptiontext_desc'] = 'Description on the left side of the login page.';
$string['loginpaneltagline'] = 'Learn. Practice. Evolve.';
$string['loginpaneltaglinetext'] = 'Login panel tagline';
$string['loginpaneltaglinetext_desc'] = 'Short tagline at the bottom of the login panel.';
$string['loginpaneltitle'] = 'A little learning every day takes you further.';
$string['loginpaneltitletext'] = 'Login panel heading';
$string['loginpaneltitletext_desc'] = 'Main heading on the left side of the login page.';
$string['loginsettings'] = 'Login';
$string['loginstartsignup'] = 'Create a new account';
$string['loginwelcomeback'] = 'Welcome back!';
$string['logo'] = 'Header logo';
$string['logo_desc'] = 'Shared Moodle site logo displayed in the navigation bar. The same image is configured under Appearance > Logos.';
$string['mcp_banner_badge'] = 'AI INTEGRATION';
$string['mcp_banner_benefit_control'] = 'Confirmation required for write actions';
$string['mcp_banner_benefit_discovery'] = 'Automatic tool discovery';
$string['mcp_banner_benefit_security'] = 'Moodle permissions enforced';
$string['mcp_banner_cta'] = 'Explore Moodle MCP';
$string['mcp_banner_description'] = 'Install local_mcp to manage the LearnLingo homepage and theme settings from ChatGPT, with Moodle permissions enforced.';
$string['mcp_banner_dismiss'] = 'Do not show this again';
$string['mcp_banner_title'] = 'Bring LearnLingo into ChatGPT';
$string['pluginname'] = 'LearnLingo';
$string['privacy:metadata'] = 'The LearnLingo theme does not store personal data.';
$string['scss'] = 'Additional SCSS';
$string['scss_desc'] = 'Global SCSS applied site-wide after the theme styles. Use the separate homepage and HTML footer fields for styles limited to those areas.';
$string['scsspre'] = 'Initial SCSS';
$string['scsspre_desc'] = 'SCSS inserted before the parent theme styles. Use it mainly for variables.';
$string['scsssettings'] = 'Advanced SCSS';
$string['showcoursehero'] = 'Show course header';
$string['showcoursehero_desc'] = 'Displays the LearnLingo course header on the main course page.';
$string['showcourseheroprogress'] = 'Show course progress';
$string['showcourseheroprogress_desc'] = 'Shows the current user course completion percentage when Moodle completion tracking is available.';
$string['showcourseherosummary'] = 'Show course summary';
$string['showcourseherosummary_desc'] = 'Shows a shortened version of the native Moodle course summary in the course header.';
$string['showdashboardhero'] = 'Show welcome panel';
$string['showdashboardhero_desc'] = 'Displays the learning welcome panel on the site home and user dashboard.';
$string['showfooter'] = 'Show footer';
$string['showpersonalxp'] = 'Show Personal XP';
$string['showpersonalxp_desc'] = 'Displays Personal XP in the LearnLingo course experience when local_personalxp is enabled. The plugin remains installed even when this display is disabled.';
$string['textcolor'] = 'Text colour';
$string['textcolor_desc'] = 'Main text and heading colour.';

// Configurable footer adapted from theme_eadtraining.
$string['footerhtml'] = 'Custom footer HTML';
$string['footerhtml_desc'] = 'Replaces the existing footer on every page in HTML mode. HTML is cleaned before rendering.';
$string['footersettings'] = 'Footer';
$string['footer_background_color'] = 'Footer background colour';
$string['footer_background_color_desc'] = 'Background colour for the footer. Text contrast is calculated automatically.';
$string['footer_heading'] = 'Footer column {$a}';
$string['footer_title'] = 'Title for column {$a}';
$string['footer_title_desc'] = 'Optional heading for footer column {$a}.';
$string['footer_html'] = 'Content for column {$a}';
$string['footer_html_desc'] = 'Rich HTML content for column {$a}. Empty columns are hidden.';
$string['footer_show_copywriter'] = 'Display developer and Moodle credits';
$string['footer_show_copywriter_desc'] = 'Show or hide credits at the bottom of the footer.';
$string['footer_copywriter'] = 'Theme by';
$string['footermode'] = 'Footer layout';
$string['footermode_desc'] = 'Moodle keeps the current configurable columns; HTML replaces the footer content.';
$string['footermodemoodle'] = 'Current footer (Moodle)';
$string['footermodehtml'] = 'Custom HTML footer';

$string['homepagescss'] = 'Homepage SCSS';
$string['homepagescss_desc'] = 'Styles included site-wide, regardless of homepage mode, so they can also style the shared navigation bar and footer. Use full CSS/SCSS selectors such as :root, body, nav or #page-footer; no homepage selector is added automatically.';
$string['footerscss'] = 'Footer SCSS';
$string['footerscss_desc'] = 'Styles used only when the footer is in HTML mode. Supports full CSS selectors such as #page-footer (automatically scoped to the HTML footer), as well as nested SCSS and relative selectors.';
