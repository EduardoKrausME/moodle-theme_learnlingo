# LearnLingo

LearnLingo is a learning-focused Moodle theme with a playful visual identity, customizable homepage, course presentation, dashboard, login screen and footer. It gives institutions more control over how learners experience a Moodle site without changing how Moodle courses, activities or grades work.

The theme is inspired by approachable language-learning interfaces, with emphasis on clear calls to action and visible learning progress.

## What can administrators customize?

### Site identity

Administrators can adjust the theme's colors, site logo, favicon and other visual settings to match the institution. Branding choices apply across the experience rather than requiring teachers to style individual course pages.

### Homepage

LearnLingo supports different homepage presentations, including its own learning-focused homepage, Moodle's standard front page and custom HTML content. Custom styling can be applied where a more tailored institutional homepage is needed.

### Dashboard and courses

The learner dashboard and course pages have configurable visual elements, including introductory areas, course information and progress presentation. Administrators can choose which optional elements appear instead of filling every screen with repeated information.

### Login and footer

The login experience can use its own branding and imagery. The footer can retain Moodle's standard presentation or use custom institutional content, with configurable appearance and sections.

## Guided site configuration

LearnLingo includes visual configuration options to help administrators make related branding and homepage decisions in one place. This is particularly useful when a Moodle site needs a coherent public homepage, course appearance and login screen rather than a collection of independent customizations.

The theme also provides an option to export and import branding settings as JSON, making it easier to reproduce a reviewed visual configuration on another site.

## Customize LearnLingo with ChatGPT or Claude

LearnLingo can optionally connect to ChatGPT or Claude through the Moodle MCP plugin (`local_mcp`). This allows an administrator to describe the changes they want in ordinary language, such as adjusting homepage content, changing colors or refining theme settings.

The assistant can inspect supported current settings before proposing changes. Updates require the permissions and explicit confirmation configured for the Moodle connection, helping administrators review what will change before publication.

This integration is optional: LearnLingo works normally without ChatGPT, Claude or the Moodle MCP plugin.

## Good practices

A learning-focused visual design still needs accessible colors, readable text, responsive layouts and clear navigation. Review the actual learner experience on desktop and mobile, especially when using custom HTML or styling.

For institutions with several course types, verify the dashboard, a representative course page, the login screen and the homepage after major branding changes.

**Compatibility:** Moodle 4.5 or newer.

**License:** GNU GPL v3 or later.

## First-run visual setup

New installations persist the theme settings with `set_config()` and
initialize **`theme_learnlingo/homepagehtml = config`** as a reserved
onboarding marker. The regular Moodle installation and admin notification
pages are left alone: **there is no redirect from `admin/index.php`**.

When a logged-in site administrator visits the actual site frontpage
(`/index.php`, including Moodle installations under a subdirectory), the
`core\hook\after_config` listener checks for the exact stored sentinel
and opens the wizard. If LearnLingo is not the current site theme, it asks
before switching; merely installing the plugin does not activate it.
Guests and ordinary learners see the regular frontpage. During the initial
setup, the homepage renderer never renders the literal word `config`.

Finishing the final step or explicitly selecting **Not now** clears the
sentinel to an empty string (only if it is still exactly `config`), and
disarms the one-time prompt. Genuine custom HTML is never cleared by
these actions. Existing installations with their own homepage HTML are
unaffected by an update; the sentinel is only seeded for new installs.
The wizard can always be reopened manually from the theme settings.

The assistant consists of **four or five independently saved steps**:

1. **Colours, logo and favicon**: choose one of six palettes or preserve the
   institution's current colours. Optionally upload a PNG, JPEG or WebP logo
   or a PNG, JPEG, WebP or ICO favicon. With **PHP GD**, raster logos are
   resampled to fit within **1200 × 400 px** (no cropping, no upscaling),
   retaining transparency for PNG/WebP; raster favicons are centered on a
   transparent **256 × 256 PNG** canvas. Multi-resolution ICO files are
   validated and retained unchanged because GD cannot decode native ICO.
   There are no artificial 3 MiB/1 MiB image limits in the wizard: the
   server's PHP upload limits still apply, along with pixel-count and memory
   checks to avoid decompression-bomb exhaustion. A missing GD extension
   produces an explicit validation error for raster uploads.
   Image processing finishes before saving colours, and all intermediate
   temporary files are removed. Assets are saved in the system context's
   **core_admin/logo** and **core_admin/favicon** File API areas, matching
   the existing site logo settings; no theme-specific copies are created.
   Existing images are kept unless a new file is submitted.
2. **Homepage**: visually compare the LearnLingo homepage and Moodle's
   native homepage. A custom HTML homepage can still be selected in advanced
   settings, and an existing HTML layout isn't silently discarded.
   The screen includes the optional Moodle MCP and a copy/paste JSON prompt
   for ChatGPT or Claude, focused on personalizing the homepage.
3. **Footer and components**: choose the native or custom HTML footer,
   configure course hero/full-width presentation, and use MCP, ChatGPT or
   Claude for optional footer customization.
4. **Dashboard and My courses** (only when either Moodle page is currently
   enabled): show both alternatives side by side, each with a selectable
   checkbox and an explanation of its purpose. The administrator may enable
   Dashboard, My courses, or both. A visible warning explains that keeping
   both may duplicate the enrolled course list, because Dashboard can contain
   Course overview and My courses provides that list directly. We recommend
   choosing one unless the Dashboard's calendar, timeline and additional
   blocks are needed. At least one must remain enabled. This writes Moodle's
   real global `enabledashboard` and `enablemycourses` settings; if the
   site's default login page is disabled, `defaulthomepage` switches to the
   remaining enabled page, without changing other default modes. The step also
   provides an independent `showwelcome` switch, the Dashboard hero toggle
   and welcome text customisation, including optional MCP/ChatGPT/Claude.

The student page step (number 4) is omitted when **both** Moodle personal
landing pages are disabled. Existing content, blocks and course enrolments
are never deleted by disabling a navigation page. A Moodle administrator
can reopen a disabled page from Site administration > Appearance > Navigation.

5. **Login screen** (always the **last** step, number **4** if the optional
   student step was omitted): preview the split-screen LearnLingo login,
   edit the welcome-panel heading, description and tagline, and optionally
   upload a separate login logo or a login background. Login artwork uses
   the same PHP GD normalization strategy as the site branding: the logo
   fits within **1200 × 400 px** with preserved PNG/WebP transparency;
   the login background fits within **1920 × 1080 px** without cropping,
   re-encoded as optimized JPEG. Files are saved to the existing Moodle
   **theme_learnlingo/loginlogo** and **theme_boost/loginbackgroundimage**
   areas. The global site logo and regular Moodle login/authentication
   settings remain unchanged. The preview is illustrative; check the real
   login screen in a private browser window to verify third-party identity
   providers, password recovery and mobile layout. An optional ChatGPT/Claude
   prompt or authorized MCP workflow can suggest institutional login copy.

When the optional student step is disabled, the Login screen is displayed
as **step 4 of 4**; otherwise it is **step 5 of 5**. Finishing the login step
clears the one-time marker, and any existing login assets remain intact when
their upload inputs are left empty.


Each step saves only the settings relevant to that step and automatically
opens the next. Returning to an earlier step doesn't reset subsequent settings.
Finishing the login step (fourth or fifth) clears the one-time onboarding marker. The setup page
remains available afterwards from the theme's advanced settings.

The copy/paste prompts reuse `branding_exchange::export()` and the existing
validated JSON importer at `/theme/learnlingo/admin/branding_json.php`.
Moodle MCP remains optional, and updates made through an authorized MCP
connection still require the existing explicit confirmation workflow.
Logo/favicon uploads are regular Moodle admin operations, not JSON strings.
Administrators should review site content before sharing a prompt externally.

Run the existing installation PHPUnit tests on a Moodle site with PHPUnit
configured:

```bash
vendor/bin/phpunit --testsuite theme_learnlingo_testsuite
```

This PR leaves `version.php` unchanged. On an already-installed development
site, purge Moodle's caches to ensure the new hooks and Mustache templates
are picked up.
