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

### Integração MCP — configurações do tema

Com o `local_mcp` instalado, o LearnLingo registra automaticamente quatro ferramentas: `learnlingo_get_homepage` e `learnlingo_update_homepage` para a página inicial, além de `learnlingo_get_settings` e `learnlingo_update_settings` para todas as configurações. A leitura completa inclui cores, textos, opções de cursos e painel, `scsspre`, `scss`, HTML da home e metadados das imagens `logo`

A atualização geral recebe somente os campos desejados em `settings` e, opcionalmente, imagens em `images` por Base64 ou URL HTTPS (PNG, JPEG ou WebP, máximo 5 MiB), ou `remove: true`. A leitura oferece `state_hash`, que deve ser enviado como `expected_hash` para evitar sobrescrita concorrente; a gravação exige permissão `moodle/site:config`, escopo MCP de escrita e confirmação explícita. O SCSS e demais campos usam a mesma validação da importação JSON, e as alterações invalidam o cache do tema. A logo principal é a logo global do Moodle (`core_admin/logo`, PNG ou JPEG). Na instalação nova, a logo global recebe `pix/default-logo.png` apenas quando ainda não existe uma imagem configurada; em atualizações, uma logo antiga do tema é copiada somente se não houver logo global. O MCP altera os arquivos e seus caminhos de configuração conjuntamente.

## First-run visual setup

New installs store theme settings defaults with `set_config()` and keep any
previously uploaded site assets. Moodle's `core\hook\after_config` hook
offers the first-run visual assistant to the site administrator after Moodle
finishes installing or upgrading. The wizard never activates LearnLingo without
an explicit confirmation if another site theme is still active.

The assistant consists of **three independently saved steps**:

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
3. **Footer and components**: choose the native or custom HTML footer, and
   configure the visual switches. The same MCP and ChatGPT/Claude workflows
   are offered for footer customization; there is no redundant fourth AI step.

Each step saves only the settings relevant to that step and automatically
opens the next. Returning to an earlier step doesn't reset subsequent settings.
Finishing the third step clears the one-time onboarding marker. The setup page
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
