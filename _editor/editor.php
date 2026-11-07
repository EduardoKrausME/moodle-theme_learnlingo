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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * GrapesJS Studio editor for existing homepage, footer and custom pages.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/editor-lib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$target = theme_learnlingo_editor_target(required_param('target', PARAM_ALPHA));
$id = optional_param('id', 0, PARAM_INT);
$data = theme_learnlingo_editor_data($target, $id);
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/theme/learnlingo/_editor/editor.php',
    ['target' => $target, 'id' => $id]));
$PAGE->set_title(get_string('visualeditoropen', 'theme_learnlingo'));

$initial = json_encode([
    'html' => $data['html'],
    'scss' => $data['scss'],
    'title' => $data['title'],
    'urls' => [
        'list' => theme_learnlingo_editor_action_url('file-list', $target, $id)->out(false),
        'upload' => theme_learnlingo_editor_action_url('file-upload', $target, $id)->out(false),
        'delete' => theme_learnlingo_editor_action_url('file-delete', $target, $id)->out(false),
    ],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT |
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
?>
<!DOCTYPE html>
<html lang="<?php echo s(current_language()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo s($data['title']); ?> - LearnLingo</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/grapes.css">
    <script src="js/build/index.min.js"></script>
    <script src="js/build/tableComponent.min.js"></script>
    <script src="js/build/iconifyComponent.min.js"></script>
    <script src="js/build/flexComponent.min.js"></script>
    <script src="js/build/canvasEmptyState.min.js"></script>
    <script src="js/build/layoutSidebarButtons.min.js"></script>
    <script src="js/build/prosemirror.min.js"></script>
    <style>
        body { margin: 0; background: #17191d; color: #f5f6f8; }
        .ll-visual-bar { box-sizing: border-box; display: flex; align-items: center; gap: 12px;
            padding: 8px 14px; min-height: 58px; background: #21252b; color: #f7f8fa;
            border-bottom: 1px solid #414750; font: 14px/1.4 system-ui, sans-serif; }
        .ll-visual-bar strong { margin-right: auto; overflow: hidden; white-space: nowrap;
            text-overflow: ellipsis; max-width: 35vw; }
        .ll-visual-bar button, .ll-visual-bar a { display: inline-flex; align-items: center;
            justify-content: center; text-decoration: none; padding: 8px 12px; border-radius: 5px;
            border: 1px solid #697485; background: #2f353d; color: white; cursor: pointer;
            font: inherit; }
        .ll-visual-bar button[aria-pressed="true"] { background: #475871; border-color: #91b9ef; }
        .ll-visual-bar #ll-save { background: #25734c; border-color: #2a9f68; }
        .ll-visual-bar button:disabled { opacity: .55; cursor: wait; }
        #ll-message { display: none; position: fixed; z-index: 110000; top: 65px; right: 14px;
            background: #5e2424; color: #fff; padding: 12px 16px; max-width: 520px;
            box-shadow: 0 4px 20px #0009; font: 14px/1.5 system-ui, sans-serif; }
        #ll-studio { height: calc(100dvh - 58px); }
        #ll-code { height: calc(100dvh - 58px); box-sizing: border-box; padding: 16px;
            display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 16px; }
        #ll-code[hidden], #ll-studio[hidden] { display: none; }
        .ll-code-column { display: flex; flex-direction: column; min-height: 0; gap: 8px; }
        .ll-code-column label { font: 600 14px system-ui, sans-serif; }
        .ll-code-column textarea { box-sizing: border-box; flex: 1; width: 100%; min-height: 0;
            resize: none; background: #11151b; color: #e9ecf1; border: 1px solid #64748b;
            padding: 15px; font: 13px/1.55 ui-monospace, SFMono-Regular, Consolas, monospace;
            tab-size: 4; white-space: pre; }
        .ll-code-note { margin: 0; color: #cbd5e1; font: 12px/1.5 system-ui, sans-serif; }
        @media (max-width: 760px) {
            .ll-visual-bar { flex-wrap: wrap; }
            .ll-visual-bar strong { max-width: none; flex-basis: 100%; }
            #ll-code { grid-template-columns: 1fr; height: auto; min-height: calc(100dvh - 58px); }
            .ll-code-column textarea { min-height: 40vh; }
        }
    </style>
</head>
<body>
    <header class="ll-visual-bar">
        <strong><?php echo s($data['title']); ?></strong>
        <button type="button" id="ll-visual" aria-pressed="false">
            <?php echo get_string('visualeditormode', 'theme_learnlingo'); ?>
        </button>
        <button type="button" id="ll-source" aria-pressed="false">
            <?php echo get_string('visualeditorsource', 'theme_learnlingo'); ?>
        </button>
        <a href="<?php echo s($data['returnurl']->out(false)); ?>">
            <?php echo get_string('cancel'); ?>
        </a>
        <button type="button" id="ll-save"><?php echo get_string('save'); ?></button>
    </header>
    <div id="ll-message" role="alert"></div>
    <form id="ll-save-form" method="post"
            action="<?php echo s(theme_learnlingo_editor_action_url('page-save', $target, $id)->out(false)); ?>">
        <textarea id="ll-submit-html" name="html" hidden></textarea>
        <textarea id="ll-submit-scss" name="scss" hidden></textarea>
    </form>
    <div id="ll-studio" hidden></div>
    <section id="ll-code" hidden>
        <div class="ll-code-column">
            <label for="ll-html">HTML / Mustache</label>
            <textarea id="ll-html" spellcheck="false" dir="ltr"></textarea>
            <p class="ll-code-note"><?php echo get_string('visualeditormustache', 'theme_learnlingo'); ?></p>
        </div>
        <div class="ll-code-column">
            <label for="ll-scss">SCSS / CSS</label>
            <textarea id="ll-scss" spellcheck="false" dir="ltr"></textarea>
        </div>
    </section>
    <script>
    (function() {
        'use strict';
        const initial = <?php echo $initial; ?>;
        const htmlInput = document.getElementById('ll-html');
        const scssInput = document.getElementById('ll-scss');
        const visual = document.getElementById('ll-studio');
        const code = document.getElementById('ll-code');
        const saveButton = document.getElementById('ll-save');
        const visualButton = document.getElementById('ll-visual');
        const sourceButton = document.getElementById('ll-source');
        const message = document.getElementById('ll-message');
        const startMarker = '/* LearnLingo visual CSS: begin */';
        const endMarker = '/* LearnLingo visual CSS: end */';
        let editor = null;
        let mode = 'source';
        let studioSource = '';

        htmlInput.value = initial.html;
        scssInput.value = initial.scss;

        const showError = (error) => {
            message.textContent = error instanceof Error ? error.message : String(error);
            message.style.display = 'block';
        };
        const clearError = () => { message.style.display = 'none'; };
        const requestJson = async (url, options) => {
            const response = await fetch(url, options);
            if (!response.ok) {
                throw new Error('HTTP ' + response.status + ': ' + await response.text());
            }
            return response.json();
        };
        const tags = (text) => text.match(/\{\{\{[\s\S]*?\}\}\}|\{\{[\s\S]*?\}\}/g) || [];
        const managedCss = (source) => {
            const start = source.indexOf(startMarker);
            const end = source.indexOf(endMarker, start + startMarker.length);
            return start < 0 || end < 0 ? source :
                source.slice(0, start).trimEnd() + source.slice(end + endMarker.length);
        };

        const createStudio = async () => {
            if (editor) {
                return editor;
            }
            if (!window.GrapesJs || !window.GrapesJsPlugins_prosemirror) {
                throw new Error('GrapesJS Studio assets could not be loaded');
            }
            studioSource = htmlInput.value;
            // SCSS is never parsed into GrapesJS rules, which would destroy
            // Sass nesting, variables and Moodle's Mustache expressions.
            window.GrapesJsCSS = "@import url('css/bootstrap.css');\n" + scssInput.value;
            const instance = GrapesJs.createEditor({
                root: '#ll-studio',
                theme: 'dark',
                project: {
                    type: 'web',
                    default: {
                        pages: [{name: initial.title, component: studioSource}]
                    }
                },
                assets: {
                    storageType: 'self',
                    onLoad: () => requestJson(initial.urls.list),
                    onUpload: async ({files}) => {
                        const result = [];
                        for (const file of files) {
                            const body = new FormData();
                            body.append('files', file);
                            const uploaded = await requestJson(initial.urls.upload,
                                {method: 'POST', body: body});
                            result.push(...uploaded);
                        }
                        return result;
                    },
                    onDelete: async ({assets}) => {
                        for (const asset of assets) {
                            if (!asset.attributes.delete) {
                                throw new Error('This image cannot be deleted');
                            }
                            await requestJson(initial.urls.delete, {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({id: asset.attributes.id})
                            });
                        }
                    }
                },
                storage: {
                    type: 'self',
                    onSave: async ({editor: activeEditor}) => {
                        await applyVisual(activeEditor);
                        submit();
                    },
                    onLoad: async () => [],
                    autosaveChanges: 1000000,
                    autosaveIntervalMs: 3600000
                },
                plugins: [
                    GrapesJsPlugins_prosemirror.init({}),
                    GrapesJsPlugins_layoutSidebarButtons.init({}),
                    GrapesJsPlugins_tableComponent.init({}),
                    GrapesJsPlugins_iconifyComponent.init({}),
                    GrapesJsPlugins_flexComponent.init({}),
                    GrapesJsPlugins_canvasEmptyState.init({})
                ]
            });
            editor = await Promise.resolve(instance);
            return editor;
        };

        const applyVisual = async (activeEditor) => {
            if (!activeEditor || typeof activeEditor.runCommand !== 'function') {
                throw new Error('The visual editor has not finished loading');
            }
            const files = await activeEditor.runCommand('studio:projectFiles');
            if (!Array.isArray(files)) {
                throw new Error('The visual editor did not return project files');
            }
            const htmlFile = files.find((file) => file.mimeType === 'text/html');
            if (!htmlFile || typeof htmlFile.content !== 'string') {
                throw new Error('The visual editor did not return HTML');
            }
            let html = htmlFile.content;
            const body = /<body\b[^>]*>([\s\S]*?)<\/body>/i.exec(html);
            if (body) {
                html = body[1].trim();
            }
            // The SDK includes a generated font stylesheet that does not
            // belong to the original Moodle HTML.
            html = html.replace(/<style\b[^>]*data-project-fonts[^>]*>[\s\S]*?<\/style>/gi, '');
            if (JSON.stringify(tags(studioSource)) !== JSON.stringify(tags(html))) {
                throw new Error(<?php echo json_encode(get_string('visualeditortokenerror', 'theme_learnlingo'),
                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
            }
            htmlInput.value = html;
            studioSource = html;
            const cssFile = files.find((file) => file.mimeType === 'text/css');
            const generated = typeof cssFile?.content === 'string' ? cssFile.content.trim() : '';
            const original = managedCss(scssInput.value).trimEnd();
            // Keep authored SCSS intact; visual-only rules are a separate,
            // replaceable section rather than a conversion of Sass to CSS.
            scssInput.value = generated
                ? original + '\n\n' + startMarker + '\n' + generated + '\n' + endMarker
                : scssInput.value;
        };

        const submit = () => {
            document.getElementById('ll-submit-html').value = htmlInput.value;
            document.getElementById('ll-submit-scss').value = scssInput.value;
            document.getElementById('ll-save-form').submit();
        };

        const setMode = async (next) => {
            clearError();
            if (next === 'visual') {
                if (editor && htmlInput.value !== studioSource) {
                    showError(<?php echo json_encode(get_string('visualeditorreload', 'theme_learnlingo')); ?>);
                    return;
                }
                if (!editor && tags(htmlInput.value).length &&
                        !window.confirm(<?php echo json_encode(get_string('visualeditorwarning',
                            'theme_learnlingo')); ?>)) {
                    return;
                }
                // Studio needs a visible root to measure the canvas on its
                // first render; initialising into a hidden node yields zero size.
                visual.hidden = false;
                code.hidden = true;
                try {
                    await createStudio();
                } catch (error) {
                    visual.hidden = true;
                    code.hidden = false;
                    throw error;
                }
            } else if (mode === 'visual' && editor) {
                await applyVisual(editor);
            }
            mode = next;
            visual.hidden = next !== 'visual';
            code.hidden = next !== 'source';
            visualButton.setAttribute('aria-pressed', String(next === 'visual'));
            sourceButton.setAttribute('aria-pressed', String(next === 'source'));
        };
        visualButton.addEventListener('click', () => setMode('visual').catch(showError));
        sourceButton.addEventListener('click', () => setMode('source').catch(showError));
        saveButton.addEventListener('click', async () => {
            clearError();
            saveButton.disabled = true;
            try {
                if (mode === 'visual') {
                    await applyVisual(editor);
                }
                submit();
            } catch (error) {
                showError(error);
                saveButton.disabled = false;
            }
        });

        // Mustache and Sass remain in source mode by default to avoid an
        // accidental destructive round-trip through a visual CSS/HTML parser.
        const dynamic = tags(initial.html).length ||
            /(?:^|\n)\s*(?:\$[\w-]+\s*:|@(?:mixin|include|function|use|forward|import)\b)/m.test(initial.scss);
        setMode(dynamic ? 'source' : 'visual').catch(showError);
    })();
    </script>
</body>
</html>
