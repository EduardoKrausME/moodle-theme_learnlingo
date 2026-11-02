// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Progressive, dependency-free HTML and SCSS syntax highlighting.
 *
 * Keeps the actual textarea in the form. The highlighted layer is visual only
 * and is built using textContent, so editing HTML cannot execute that HTML.
 *
 * @module     theme_learnlingo/code_settings
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    'use strict';

    const addText = (parent, value) => {
        if (value) {
            parent.appendChild(document.createTextNode(value));
        }
    };

    const addToken = (parent, value, type) => {
        const span = document.createElement('span');
        span.className = 'll-code-token--' + type;
        span.textContent = value;
        parent.appendChild(span);
    };

    /**
     * Highlight attributes and tag structure without evaluating source HTML.
     *
     * @param {Node} node Tag output container.
     * @param {String} tag Tag source.
     */
    const highlightTag = (node, tag) => {
        const marker = /^<(\/?)((?:[\w:-]+))/.exec(tag);
        if (!marker) {
            addToken(node, tag, 'tag');
            return;
        }

        addToken(node, '<' + marker[1], 'punctuation');
        addToken(node, marker[2], 'tag');
        const tail = tag.slice(marker[0].length);
        const parts = /(\b[\w:-]+(?=\s*=))|("(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*')|([=<>/])/g;
        let offset = 0;
        let match;
        while ((match = parts.exec(tail)) !== null) {
            addText(node, tail.slice(offset, match.index));
            addToken(node, match[0], match[1] ? 'attribute' : (match[2] ? 'string' : 'punctuation'));
            offset = parts.lastIndex;
        }
        addText(node, tail.slice(offset));
    };

    /**
     * HTML lexer, intentionally presentation-only and resilient to unfinished tags.
     *
     * @param {Node} node Output container.
     * @param {String} source Raw HTML.
     */
    const highlightHtml = (node, source) => {
        const tokens = /<!--[\s\S]*?-->|<!DOCTYPE[^>]*>|<\/?[A-Za-z][^>]*>|&(?:#[xX][\da-fA-F]+|#\d+|[a-zA-Z][\w-]*);/gi;
        let offset = 0;
        let match;
        while ((match = tokens.exec(source)) !== null) {
            addText(node, source.slice(offset, match.index));
            const token = match[0];
            if (token.startsWith('<!--')) {
                addToken(node, token, 'comment');
            } else if (token.startsWith('<!')) {
                addToken(node, token, 'keyword');
            } else if (token.startsWith('<')) {
                highlightTag(node, token);
            } else {
                addToken(node, token, 'entity');
            }
            offset = tokens.lastIndex;
        }
        addText(node, source.slice(offset));
    };

    /**
     * SCSS/CSS lexer for comments, directives, variables, rules, values and colours.
     *
     * @param {Node} node Output container.
     * @param {String} source Raw SCSS.
     */
    const highlightScss = (node, source) => {
        const tokens = /\/\*[\s\S]*?\*\/|\/\/[^\r\n]*|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|@[\w-]+|\$[\w-]+|#[\da-fA-F]{3,8}\b|\b[\w-]+(?=\s*:)|\b\d+(?:\.\d+)?(?:px|rem|em|vh|vw|vmin|vmax|%|s|ms|deg)?\b|[{}:;(),]/g;
        let offset = 0;
        let match;
        while ((match = tokens.exec(source)) !== null) {
            addText(node, source.slice(offset, match.index));
            const token = match[0];
            let type;
            if (token.startsWith('/*') || token.startsWith('//')) {
                type = 'comment';
            } else if (token.startsWith('"') || token.startsWith("'")) {
                type = 'string';
            } else if (token.startsWith('@')) {
                type = 'keyword';
            } else if (token.startsWith('$')) {
                type = 'variable';
            } else if (token.startsWith('#')) {
                type = 'colour';
            } else if (/^\d/.test(token)) {
                type = 'number';
            } else if (/^[{}:;(),]$/.test(token)) {
                type = 'punctuation';
            } else {
                type = 'property';
            }
            addToken(node, token, type);
            offset = tokens.lastIndex;
        }
        addText(node, source.slice(offset));
    };

    const initialise = (root) => {
        if (root.dataset.codeInitialised === '1') {
            return;
        }

        const input = root.querySelector('.ll-code-editor__input');
        const display = root.querySelector('[data-code-highlight]');
        const code = display && display.querySelector('code');
        const gutter = root.querySelector('[data-code-gutter]');
        const lines = root.querySelector('[data-code-lines]');
        const position = root.querySelector('[data-code-position]');
        const expand = root.querySelector('[data-code-expand]');
        const expandLabel = root.querySelector('[data-code-expand-label]');
        if (!input || !code || !gutter || !lines || !position || !expand) {
            return;
        }
        root.dataset.codeInitialised = '1';

        const syncScroll = () => {
            display.scrollTop = input.scrollTop;
            display.scrollLeft = input.scrollLeft;
            gutter.scrollTop = input.scrollTop;
        };

        const updatePosition = () => {
            const prefix = input.value.slice(0, input.selectionStart);
            const row = prefix.split('\n');
            position.textContent = row.length + ':' + (row[row.length - 1].length + 1);
        };

        const refresh = () => {
            const value = input.value.replace(/\r\n?/g, '\n');
            // Avoid generating hundreds of thousands of DOM nodes on huge documents.
            code.replaceChildren();
            if (value.length > 250000) {
                addText(code, value + '\n ');
            } else if (root.dataset.language === 'scss') {
                highlightScss(code, value + '\n ');
            } else {
                highlightHtml(code, value + '\n ');
            }
            const total = value.split('\n').length;
            lines.textContent = total + (total === 1 ? ' line' : ' lines');
            const numbers = document.createDocumentFragment();
            for (let i = 1; i <= Math.min(total, 20000); i++) {
                const number = document.createElement('span');
                number.textContent = String(i);
                numbers.appendChild(number);
            }
            gutter.replaceChildren(numbers);
            syncScroll();
            updatePosition();
        };

        const insertIndent = (event) => {
            if (event.key !== 'Tab' || event.ctrlKey || event.metaKey || event.altKey || input.disabled) {
                return;
            }
            event.preventDefault();
            const start = input.selectionStart;
            const end = input.selectionEnd;
            const indentation = '    ';
            if (event.shiftKey) {
                const before = input.value.slice(0, start);
                const match = /(?:^|\n)( {1,4}|\t)$/.exec(before);
                if (match) {
                    const length = match[1].length;
                    input.setRangeText('', start - length, start, 'start');
                }
            } else {
                input.setRangeText(indentation, start, end, 'end');
            }
            input.dispatchEvent(new Event('input', {bubbles: true}));
        };

        input.addEventListener('input', refresh);
        input.addEventListener('scroll', syncScroll, {passive: true});
        input.addEventListener('click', updatePosition);
        input.addEventListener('keyup', updatePosition);
        input.addEventListener('select', updatePosition);
        input.addEventListener('keydown', insertIndent);

        const toggleExpanded = (enabled) => {
            root.classList.toggle('ll-code-editor--expanded', enabled);
            expand.setAttribute('aria-pressed', enabled ? 'true' : 'false');
            expandLabel.textContent = enabled ? expand.dataset.collapseLabel : expand.dataset.expandLabel;
            input.focus();
            syncScroll();
        };
        expand.addEventListener('click', () => {
            toggleExpanded(!root.classList.contains('ll-code-editor--expanded'));
        });
        root.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && root.classList.contains('ll-code-editor--expanded')) {
                toggleExpanded(false);
                event.preventDefault();
            }
        });

        try {
            refresh();
            root.classList.add('ll-code-editor--ready');
        } catch (error) {
            // Never make the original textarea unreadable if highlighting fails.
            root.classList.remove('ll-code-editor--ready');
        }
    };

    return {
        init: () => document.querySelectorAll('[data-ll-code-editor]').forEach(initialise)
    };
});
