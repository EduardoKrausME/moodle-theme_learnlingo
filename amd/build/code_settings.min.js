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


    // Mustache variables, block sections, partials, comments and Moodle helpers.
    const mustache = (node, value) => {
        const width = value.startsWith('{{{') ? 3 : 2;
        const contents = value.slice(width, -width);
        const parts = /^(\s*)([#/^>!&=])?([\s\S]*)$/.exec(contents);
        addToken(node, '{'.repeat(width), 'mustache-delimiter');
        addText(node, parts[1]);
        if (parts[2]) {
            addToken(node, parts[2], parts[2] === '!' ? 'comment' : 'mustache-section');
        }
        if (parts[2] === '!') {
            addToken(node, parts[3], 'comment');
        } else {
            const expression = /^(\s*[\w.:-]+)([\s\S]*)$/.exec(parts[3]);
            if (expression) {
                addToken(node, expression[1], /^\s*else\b/.test(contents) ? 'mustache-section' : 'mustache-variable');
                addText(node, expression[2]);
            } else {
                addToken(node, parts[3], 'mustache-variable');
            }
        }
        addToken(node, '}'.repeat(width), 'mustache-delimiter');
    };

    const withMustache = (node, value, type = '') => {
        const rx = /\{\{\{[\s\S]*?\}\}\}|\{\{[\s\S]*?\}\}/g;
        const plain = (part) => type ? addToken(node, part, type) : addText(node, part);
        let offset = 0;
        let found;
        while ((found = rx.exec(value)) !== null) {
            plain(value.slice(offset, found.index));
            mustache(node, found[0]);
            offset = rx.lastIndex;
        }
        plain(value.slice(offset));
    };

    const highlightTag = (node, value) => {
        const prefix = /^<(\/?)([a-z][\w:-]*)/i.exec(value);
        if (!prefix) {
            addText(node, value);
            return;
        }
        addToken(node, '<' + prefix[1], 'punctuation');
        addToken(node, prefix[2], 'tag');
        const rest = value.slice(prefix[0].length);
        const rx = /\{\{\{[\s\S]*?\}\}\}|\{\{[\s\S]*?\}\}|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|[@:#.\w-]+(?=\s*=)|[=<>/]/g;
        let offset = 0;
        let found;
        while ((found = rx.exec(rest)) !== null) {
            addText(node, rest.slice(offset, found.index));
            const part = found[0];
            if (part.startsWith('{{')) {
                mustache(node, part);
            } else if (/^['"]/.test(part)) {
                withMustache(node, part, 'string');
            } else {
                addToken(node, part, /^[=<>/]$/.test(part) ? 'punctuation' : 'attribute');
            }
            offset = rx.lastIndex;
        }
        addText(node, rest.slice(offset));
    };

    // Parse Sass selectors, variables, directives, interpolation and CSS values.
    const highlightScss = (node, source) => {
        const rx = /\/\*[\s\S]*?\*\/|url\((?:"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|[^)])*\)|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|\/\/[^\r\n]*|#\{[^}]*\}|@[a-z][\w-]*|\$[\w-]+|#[\da-fA-F]{3,8}\b|[.#%][a-zA-Z_-][\w-]*|&|:{1,2}[\w-]+|(?:--[\w-]+|[\w-]+)(?=\s*:)|[a-zA-Z_-][\w-]*(?=\s*\()|(?:\d*\.)?\d+(?:px|rem|em|vh|vw|vmin|vmax|fr|ch|pt|%|s|ms|deg|turn)?(?![\w-])|[{}:;(),\[\]]/g;
        let offset = 0;
        let found;
        while ((found = rx.exec(source)) !== null) {
            addText(node, source.slice(offset, found.index));
            const part = found[0];
            let type = 'property';
            if (/^\/[*/]/.test(part)) {
                type = 'comment';
            } else if (/^['"]/.test(part) || part.startsWith('url(')) {
                type = 'string';
            } else if (part.startsWith('#{')) {
                addToken(node, '#{', 'interpolation');
                const inner = part.slice(2, -1);
                const variables = /(\$[\w-]+)/g;
                let previous = 0;
                let variable;
                while ((variable = variables.exec(inner)) !== null) {
                    addText(node, inner.slice(previous, variable.index));
                    addToken(node, variable[0], 'variable');
                    previous = variables.lastIndex;
                }
                addText(node, inner.slice(previous));
                addToken(node, '}', 'interpolation');
                offset = rx.lastIndex;
                continue;
            } else if (part.startsWith('@')) {
                type = 'keyword';
            } else if (part.startsWith('$')) {
                type = 'variable';
            } else if (/^#[\da-fA-F]{3,8}$/i.test(part)) {
                type = /^\s*\{/.test(source.slice(rx.lastIndex)) ? 'selector' : 'colour';
            } else if (/^(?:\d|\.\d)/.test(part)) {
                type = 'number';
            } else if (/^[.#%&]/.test(part) || /^:{1,2}[\w-]+$/.test(part)) {
                type = 'selector';
            } else if (/^[{}:;(),\[\]]$/.test(part)) {
                type = 'punctuation';
            } else if (/^\s*\(/.test(source.slice(rx.lastIndex))) {
                type = 'function';
            }
            addToken(node, part, type);
            offset = rx.lastIndex;
        }
        addText(node, source.slice(offset));
    };

    const highlightHtml = (node, source) => {
        const rx = /<(script|style|pre|textarea|code)\b(?:[^>"']|"[^"]*"|'[^']*')*>[\s\S]*?<\/\1\s*>|<!--[\s\S]*?-->|<!DOCTYPE[^>]*>|<\/?[a-z][^>"']*(?:(?:"[^"]*"|'[^']*')[^>"']*)*>|\{\{\{[\s\S]*?\}\}\}|\{\{[\s\S]*?\}\}|&(?:#[xX][\da-fA-F]+|#\d+|[a-zA-Z][\w-]*);/gi;
        let offset = 0;
        let found;
        while ((found = rx.exec(source)) !== null) {
            withMustache(node, source.slice(offset, found.index));
            const part = found[0];
            const raw = /^<(script|style|pre|textarea|code)\b/i.exec(part);
            if (raw && /<\/(?:script|style|pre|textarea|code)\s*>$/i.test(part)) {
                const open = /^<(?:[^>"']|"[^"]*"|'[^']*')*>/.exec(part);
                const close = /<\/[a-z]+\s*>$/i.exec(part);
                highlightTag(node, open[0]);
                const content = part.slice(open[0].length, -close[0].length);
                if (raw[1].toLowerCase() === 'style') {
                    highlightScss(node, content);
                } else {
                    addText(node, content);
                }
                highlightTag(node, close[0]);
            } else if (part.startsWith('<!--')) {
                addToken(node, part, 'comment');
            } else if (part.startsWith('<!')) {
                addToken(node, part, 'keyword');
            } else if (part.startsWith('<')) {
                highlightTag(node, part);
            } else if (part.startsWith('{{')) {
                mustache(node, part);
            } else {
                addToken(node, part, 'entity');
            }
            offset = rx.lastIndex;
        }
        withMustache(node, source.slice(offset));
    };

    /**
     * Safely indent HTML block boundaries when settings open. In particular,
     * never alter inline text, quoted attributes or raw script/style/pre content.
     *
     * @param {String} source HTML source.
     * @returns {String} Indented source or the original when markup is ambiguous.
     */
    const formatHtmlOnOpen = (source) => {
        if (!source || source.length > 250000 || /<\?|<%|\{\{/.test(source)) {
            return source;
        }
        const blocks = new Set([
            'address', 'article', 'aside', 'blockquote', 'body', 'caption', 'dd',
            'details', 'dialog', 'div', 'dl', 'dt', 'fieldset', 'figcaption',
            'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'head', 'header', 'hgroup', 'html', 'li', 'main', 'menu', 'nav',
            'ol', 'option', 'optgroup', 'p', 'section', 'summary', 'table',
            'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'ul'
        ]);
        const voids = new Set([
            'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
            'link', 'meta', 'param', 'source', 'track', 'wbr'
        ]);
        const opaque = new Set([
            'code', 'iframe', 'math', 'noscript', 'pre', 'script', 'style',
            'svg', 'template', 'textarea', 'title', 'xmp'
        ]);
        const tokens = [];
        const tagStack = [];
        let index = 0;
        while (index < source.length) {
            if (source[index] !== '<') {
                const next = source.indexOf('<', index);
                const end = next < 0 ? source.length : next;
                tokens.push({kind: 'text', value: source.slice(index, end)});
                index = end;
                continue;
            }
            if (source.startsWith('<!--', index)) {
                const end = source.indexOf('-->', index + 4);
                if (end < 0) {
                    return source;
                }
                tokens.push({kind: 'tag', value: source.slice(index, end + 3), block: true});
                index = end + 3;
                continue;
            }
            let end = index + 1;
            let quote = '';
            while (end < source.length) {
                const char = source[end];
                if (quote) {
                    if (char === quote) {
                        quote = '';
                    }
                } else if (char === '"' || char === "'") {
                    quote = char;
                } else if (char === '>') {
                    break;
                }
                end++;
            }
            if (end === source.length) {
                return source;
            }
            const tag = source.slice(index, end + 1);
            const match = /^<(\/?)([a-z][\w:-]*)(?=[\s/>])/i.exec(tag);
            if (!match) {
                if (/^<!|^<\?/.test(tag)) {
                    tokens.push({kind: 'tag', value: tag, block: true});
                    index = end + 1;
                } else {
                    tokens.push({kind: 'text', value: '<'});
                    index++;
                }
                continue;
            }
            const name = match[2].toLowerCase();
            const closing = match[1] === '/';
            const selfClosing = voids.has(name) || /\/\s*>$/.test(tag);
            if (closing) {
                if (tagStack.pop() !== name) {
                    return source;
                }
            } else if (!selfClosing) {
                tagStack.push(name);
            }
            if (!closing && !selfClosing && opaque.has(name)) {
                const closingPattern = new RegExp('</' + name + '\\s*>', 'ig');
                closingPattern.lastIndex = end + 1;
                if (!closingPattern.exec(source)) {
                    return source;
                }
                tagStack.pop();
                tokens.push({
                    kind: 'tag',
                    value: source.slice(index, closingPattern.lastIndex),
                    block: true
                });
                index = closingPattern.lastIndex;
                continue;
            }
            tokens.push({
                kind: 'tag',
                value: tag,
                block: blocks.has(name),
                opening: !closing && !selfClosing,
                closing: closing,
                name: name
            });
            index = end + 1;
        }
        if (tagStack.length) {
            return source;
        }

        let result = '';
        let pending = '';
        let previous = null;
        let depth = 0;
        tokens.forEach((token) => {
            if (token.kind === 'text' && /^\s*$/.test(token.value)) {
                pending += token.value;
                return;
            }
            if (token.closing && token.block) {
                depth = Math.max(0, depth - 1);
            }
            const contiguousBlocks = previous && previous.kind === 'tag' &&
                previous.block && token.kind === 'tag' && token.block;
            const emptyPair = previous && previous.opening && token.closing &&
                previous.name === token.name;
            if (contiguousBlocks && !emptyPair) {
                result += '\n' + '    '.repeat(Math.min(depth, 80));
            } else {
                result += pending;
            }
            pending = '';
            result += token.value;
            if (token.opening && token.block) {
                depth++;
            }
            previous = token;
        });
        return result + pending;
    };


    // Indent existing rule lines without rewriting declarations, strings or Sass interpolation.
    const formatScssOnOpen = (source) => {
        if (!source || source.length > 250000 || !source.includes('\n') || /\{\{/.test(source)) {
            return source;
        }
        const lines = source.replace(/\r\n?/g, '\n').split('\n');
        let depth = 0;
        let comment = false;
        const formatted = [];
        for (const line of lines) {
            const text = line.trim();
            if (!text) {
                formatted.push('');
                continue;
            }
            if (comment) {
                formatted.push(line);
                if (line.includes('*/')) {
                    comment = false;
                }
                continue;
            }
            const indent = Math.max(0, depth - (text[0] === '}' ? 1 : 0));
            let quote = '';
            let nesting = 0;
            for (let i = 0; i < line.length; i++) {
                const char = line[i];
                if (quote) {
                    if (char === '\\') {
                        i++;
                    } else if (char === quote) {
                        quote = '';
                    }
                    continue;
                }
                if (line.startsWith('/*', i)) {
                    const end = line.indexOf('*/', i + 2);
                    if (end < 0) {
                        comment = true;
                        break;
                    }
                    i = end + 1;
                } else if (line.startsWith('//', i) && line[i - 1] !== ':') {
                    break;
                } else if (char === '"' || char === "'") {
                    quote = char;
                } else if (char === '(' || char === '[') {
                    nesting++;
                } else if (char === ')' || char === ']') {
                    nesting--;
                } else if (char === '#' && line[i + 1] === '{') {
                    const end = line.indexOf('}', i + 2);
                    if (end < 0) {
                        return source;
                    }
                    i = end;
                } else if (!nesting && char === '{') {
                    depth++;
                } else if (!nesting && char === '}') {
                    depth--;
                    if (depth < 0) {
                        return source;
                    }
                }
            }
            if (quote || nesting) {
                return source;
            }
            formatted.push('    '.repeat(Math.min(80, indent)) + text);
        }
        return depth === 0 && !comment ? formatted.join('\n') : source;
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

        // Format the editor's local value; Moodle config changes only on Save.
        if (!input.disabled && root.dataset.language === 'html') {
            input.value = formatHtmlOnOpen(input.value);
        } else if (!input.disabled && root.dataset.language === 'scss') {
            input.value = formatScssOnOpen(input.value);
        }

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
            lines.textContent = total + ' ' + (total === 1 ? root.dataset.oneLine : root.dataset.manyLines);
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
