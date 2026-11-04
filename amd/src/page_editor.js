// This file is part of Moodle - https://moodle.org/
// @module theme_learnlingo/page_editor
// @copyright 2026 Eduardo Kraus
// @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define(['theme_learnlingo/code_settings'], function(CodeSettings) {
    'use strict';

    // Reuse the exact editor/highlighter already used for Homepage HTML/SCSS.
    // The original moodleform textarea remains the only submitted form field.
    const enhance = (name, language, translations) => {
        const textarea = document.getElementById('id_' + name);
        if (!textarea || textarea.closest('[data-ll-code-editor]')) {
            return;
        }
        const root = document.createElement('div');
        root.className = 'll-code-editor';
        root.dataset.llCodeEditor = '';
        root.dataset.language = language;
        root.dataset.oneLine = translations.oneLine;
        root.dataset.manyLines = translations.manyLines;
        root.innerHTML = [
            '<div class="ll-code-editor__toolbar">',
            '<span class="ll-code-editor__language"></span>',
            '<span class="ll-code-editor__lines" data-code-lines></span>',
            '<button class="ll-code-editor__expand" type="button" data-code-expand aria-pressed="false">',
            '<i class="fa fa-expand" aria-hidden="true"></i>',
            '<span data-code-expand-label></span>',
            '</button></div>',
            '<div class="ll-code-editor__workspace">',
            '<div class="ll-code-editor__gutter" data-code-gutter aria-hidden="true"></div>',
            '<div class="ll-code-editor__area">',
            '<pre class="ll-code-editor__highlight" data-code-highlight aria-hidden="true"><code></code></pre>',
            '</div></div>',
            '<div class="ll-code-editor__footer"><span data-code-hint></span>',
            '<span data-code-position></span></div>'
        ].join('');
        root.querySelector('.ll-code-editor__language').textContent = language.toUpperCase();
        root.querySelector('[data-code-hint]').textContent = translations.hint;
        const expand = root.querySelector('[data-code-expand]');
        expand.dataset.expandLabel = translations.expand;
        expand.dataset.collapseLabel = translations.collapse;
        expand.setAttribute('aria-label', translations.expand);
        root.querySelector('[data-code-expand-label]').textContent = translations.expand;
        textarea.parentNode.replaceChild(root, textarea);
        textarea.classList.add('ll-code-editor__input');
        textarea.setAttribute('dir', 'ltr');
        textarea.setAttribute('spellcheck', 'false');
        textarea.setAttribute('autocomplete', 'off');
        root.querySelector('.ll-code-editor__area').appendChild(textarea);
    };

    return {
        init: (translations) => {
            enhance('content', 'html', translations);
            enhance('scss', 'scss', translations);
            CodeSettings.init();
        }
    };
});
