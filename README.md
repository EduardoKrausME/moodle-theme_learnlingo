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

A fresh installation stores the theme's portable defaults with `set_config()`
using `theme_learnlingo\\branding_exchange::definitions()` (the same source
of truth as the JSON/MCP tools). Existing values and uploaded file settings are
not overwritten.

After Moodle finishes its normal installation/upgrade steps, the next regular
GET to `/admin/index.php` by a site administrator opens
`/theme/learnlingo/admin/setup.php`. The one-time redirection is registered
through Moodle's `core\\hook\\after_config` in `db/hooks.php`, with guards for
installation, upgrades, CLI, PHPUnit, Behat and maintenance. Selecting
**Not now** stops the onboarding redirect. Upgrading an existing installation
does not unexpectedly enable the wizard.

**Activation is always a separate, explicit decision:** if the active site
theme is not `learnlingo`, the setup first asks whether to activate it.
It never changes Moodle's global `theme` setting merely by visiting the page.
Declining leaves the existing theme untouched. A site whose `config.php`
forces another theme sees an explanation rather than an unexplained redirect
loop.

After activation, administrators see **two large illustrated homepage
choices**: LearnLingo cards/hero or Moodle's native course layout. The third
`homepagemode=html` mode remains available in advanced settings; if an
existing HTML homepage is active, leaving the two radio buttons unselected
preserves its mode and its content. Six predefined palettes, current-colour
preservation, footer selection and component switches are also available.

The lower sections present two independent ChatGPT workflows:

1. **Moodle MCP:** optional installation of `local_mcp`, tool discovery,
   READ/WRITE scope, `moodle/site:config` capability and explicit write
   confirmation.
2. **Copy/paste without MCP:** the `branding_exchange::prompt()` exporter
   embeds the current theme JSON into a ChatGPT conversation prompt. An existing
   AMD clipboard helper copies it; the user pastes the generated JSON into
   `/theme/learnlingo/admin/branding_json.php`, which validates it before
   applying changes. Logos and other uploaded images remain separate.

All page content is localized in PT-BR/EN and the screen uses scoped responsive
CSS. Installing or selecting LearnLingo does not auto-apply a colour preset;
saving the wizard changes only the explicitly selected options. To use the new
hook immediately on an already-installed development site without increasing
`version.php`, purge Moodle's caches.

Run the installation regression tests on a Moodle site with PHPUnit configured:

```bash
vendor/bin/phpunit --testsuite theme_learnlingo_testsuite
```
