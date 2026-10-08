# LearnLingo Moodle Theme

LearnLingo is a Moodle theme that customizes the homepage, dashboard,
course views and login experience. Its appearance can be configured from
the theme settings, or exported/imported through the JSON branding screen.

## Optional integration with Moodle MCP

When the `local_mcp` plugin is installed, LearnLingo automatically
registers its MCP tools via `classes/mcp/provider.php`. The integration
does not require adding callbacks to `lib.php`, installing a separate
MCP subplugin, or enabling a new theme setting.

The class implements `local_mcp\extension\read_provider_interface` and
`local_mcp\extension\write_provider_interface` and exposes:

- `learnlingo_get_homepage` (READ): retrieve homepage mode, raw HTML,
  full-width flag, custom SCSS and a `state_hash` for change detection.
- `learnlingo_update_homepage` (WRITE): update the homepage mode, HTML,
  full-width flag or custom SCSS with a preview and explicit confirmation.
  Supplying HTML without `mode` activates the custom HTML homepage.

Both tools require the Moodle capability `moodle/site:config` in the system
context in addition to the MCP token's READ or WRITE permission.
The WRITE tool uses the existing LearnLingo JSON validation to reject
unsafe HTML/SCSS, optionally checks `expected_hash` for concurrency and
resets the theme cache after saving. All unspecified theme settings are
preserved.

Example payload for `learnlingo_update_homepage`:

```json
{
  "mode": "html",
  "html": "<main class=\"landing\"><h1>LearnLingo</h1></main>",
  "fullwidth": true,
  "expected_hash": "value returned by learnlingo_get_homepage"
}
```

Without `local_mcp`, the theme works normally; these classes are loaded
only when requested by an installed MCP provider. After updating both
plugins, refresh the custom MCP connection in ChatGPT to discover the tools.
