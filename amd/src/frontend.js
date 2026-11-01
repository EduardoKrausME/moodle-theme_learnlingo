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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
//
// @module theme_learnlingo/frontend

/**
 * Declarative, read-only HTML homepage binding.
 *
 * Example:
 * <div data-learnlingo-loop="publiccourses" data-learnlingo-limit="4">
 *   <article><a href="{{url}}">{{fullname}}</a></article>
 * </div>
 *
 * Uses text nodes and URL-checked attributes, never an HTML string generated
 * from JSON. All personal fields come from the current Moodle session only.
 */
const SECTIONS = ['courses', 'publiccourses', 'popular', 'cohorts'];
const TOKENS = /\{\{\s*([a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*)\s*\}\}/gi;
const URL_ATTRIBUTES = ['href', 'src'];
const TEXT_ATTRIBUTES = ['title', 'alt', 'aria-label', 'aria-valuenow'];
const MAX_LIMIT = 48;
const FORBIDDEN = ['__proto__', 'constructor', 'prototype'];

/**
 * Read a JSON property path without invoking inherited properties.
 *
 * @param {Object} data Binding scope.
 * @param {String} path Dot-delimited field name.
 * @returns {*} Primitive value or undefined.
 */
const property = (data, path) => {
    let value = data;
    for (const name of path.split('.')) {
        if (FORBIDDEN.includes(name) || value === null || typeof value !== 'object' ||
                !Object.prototype.hasOwnProperty.call(value, name)) {
            return undefined;
        }
        value = value[name];
    }
    return value;
};

/**
 * Convert a primitive to escaped-by-the-DOM text.
 *
 * @param {*} value Value.
 * @returns {String} Text.
 */
const asText = (value) => value === null || value === undefined ||
    typeof value === 'object' ? '' : String(value);

/**
 * Interpolate a text node or allowed attribute.
 *
 * @param {String} template Text containing {{field}} placeholders.
 * @param {Object} data Binding scope.
 * @returns {String} Substituted text.
 */
const interpolate = (template, data) => template.replace(TOKENS,
    (token, field) => asText(property(data, field)));

/**
 * A URL passed through the API must not become a javascript: or data: URL.
 *
 * @param {String} value Candidate URL.
 * @returns {Boolean} Whether the URL has an allowed scheme.
 */
const allowedUrl = (value) => {
    if (!value || value.startsWith('//')) {
        return false;
    }
    try {
        const url = new URL(value, document.baseURI);
        return url.protocol === 'http:' || url.protocol === 'https:';
    } catch (error) {
        return false;
    }
};

/**
 * Evaluate supported conditionals. A progress of 0 is still valid progress.
 *
 * @param {String} expression Condition, optionally prefixed with !.
 * @param {Object} scope Binding scope.
 * @returns {Boolean} Whether the node should remain.
 */
const matches = (expression, scope) => {
    const inverted = expression.startsWith('!');
    const field = inverted ? expression.slice(1) : expression;
    const value = field === 'hasprogress'
        ? property(scope, 'progress') !== undefined && property(scope, 'progress') !== null
        : Boolean(property(scope, field));
    return inverted ? !value : value;
};

/**
 * Populate a static DOM fragment. Never assign user-controlled HTML.
 *
 * @param {Element|DocumentFragment} parent Static administrator-authored markup.
 * @param {Object} scope Personal/session data and current course/cohort.
 * @param {Boolean} skipLoops Avoid processing loop templates during global binding.
 */
const bind = (parent, scope, skipLoops = false) => {
    const blocked = (node) => skipLoops && node.parentElement &&
        node.parentElement.closest('[data-learnlingo-loop]');

    // Fill declarative text placeholders (also works without double braces).
    for (const element of parent.querySelectorAll('[data-learnlingo-text]')) {
        if (!blocked(element) && !(skipLoops && element.matches('[data-learnlingo-loop]'))) {
            element.textContent = asText(property(scope, element.dataset.learnlingoText));
        }
    }

    const walker = document.createTreeWalker(parent, NodeFilter.SHOW_TEXT);
    const texts = [];
    while (walker.nextNode()) {
        texts.push(walker.currentNode);
    }
    for (const node of texts) {
        if (!blocked(node)) {
            node.nodeValue = interpolate(node.nodeValue, scope);
        }
    }

    for (const element of parent.querySelectorAll('*')) {
        if (blocked(element) || (skipLoops && element.matches('[data-learnlingo-loop]'))) {
            continue;
        }
        for (const attribute of [...element.attributes]) {
            if (!attribute.value.includes('{{')) {
                continue;
            }
            const name = attribute.name.toLowerCase();
            if (!URL_ATTRIBUTES.includes(name) && !TEXT_ATTRIBUTES.includes(name)) {
                // Never interpolate into event handlers, inline CSS or unknown attributes.
                continue;
            }
            const value = interpolate(attribute.value, scope);
            if (URL_ATTRIBUTES.includes(name) && !allowedUrl(value)) {
                element.removeAttribute(name);
            } else {
                element.setAttribute(name, value);
            }
        }
        if (element.hasAttribute('data-learnlingo-progress')) {
            const value = property(scope, element.dataset.learnlingoProgress);
            const progress = Number(value);
            if (value !== null && value !== undefined && Number.isFinite(progress)) {
                const width = Math.min(100, Math.max(0, progress));
                element.style.width = width + '%';
                element.setAttribute('aria-valuenow', String(width));
            }
        }
    }

    // Work from descendants back toward parents so removal never skips siblings.
    const conditionals = [...parent.querySelectorAll('[data-learnlingo-if]')].reverse();
    for (const element of conditionals) {
        if (!blocked(element) && !(skipLoops && element.matches('[data-learnlingo-loop]')) &&
                !matches(element.dataset.learnlingoIf, scope)) {
            element.remove();
        }
    }
};

/**
 * Parse bounded integer settings supplied in HTML.
 *
 * @param {String} raw Attribute value.
 * @param {Number} fallback Default.
 * @param {Number} min Minimum.
 * @param {Number} max Maximum.
 * @returns {Number} Normalized value.
 */
const bounded = (raw, fallback, min, max) => {
    if (!/^\d+$/.test(raw || '')) {
        return fallback;
    }
    return Math.min(max, Math.max(min, Number(raw)));
};

/**
 * Hydrate one HTML home without installing script tags into the HTML setting.
 *
 * @param {HTMLElement} root Custom homepage wrapper.
 * @param {String} apiUrl Absolute Moodle API URL supplied by PHP.
 * @returns {Promise<void>}
 */
const hydrate = async(root, apiUrl) => {
    const loops = [...root.querySelectorAll('[data-learnlingo-loop]')].filter((element) =>
        !element.parentElement.closest('[data-learnlingo-loop]'));
    const templates = loops.map((element) => [...element.childNodes].map((node) => node.cloneNode(true)));
    const requests = new Map();
    const request = (section, limit = 12, offset = 0) => {
        const key = section + ':' + limit + ':' + offset;
        if (!requests.has(key)) {
            const url = new URL(apiUrl, document.baseURI);
            url.searchParams.set('section', section);
            url.searchParams.set('limit', String(limit));
            url.searchParams.set('offset', String(offset));
            requests.set(key, fetch(url.toString(), {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {'Accept': 'application/json'},
            }).then((response) => {
                if (!response.ok) {
                    throw new Error('LearnLingo home API: HTTP ' + response.status);
                }
                return response.json();
            }));
        }
        return requests.get(key);
    };

    // Hide unfinished bindings, so placeholders are not mistaken for real data.
    for (const loop of loops) {
        loop.hidden = true;
    }
    for (const element of root.querySelectorAll('[data-learnlingo-text]')) {
        if (!element.closest('[data-learnlingo-loop]')) {
            element.hidden = true;
        }
    }

    const session = await request('user');
    const globalScope = {loggedin: session.loggedin, user: session.user};

    await Promise.all(loops.map(async(loop, index) => {
        const section = loop.dataset.learnlingoLoop;
        if (!SECTIONS.includes(section)) {
            return;
        }
        const limit = bounded(loop.dataset.learnlingoLimit, 6, 1, MAX_LIMIT);
        const offset = bounded(loop.dataset.learnlingoOffset, 0, 0, 100000);
        const response = await request(section, limit, offset);
        const list = section === 'cohorts' || section === 'popular'
            ? (response[section] || []).slice(offset, offset + limit)
            : (response[section] && response[section].items) || [];

        const fragment = document.createDocumentFragment();
        for (const item of list) {
            const row = document.createDocumentFragment();
            for (const node of templates[index]) {
                row.appendChild(node.cloneNode(true));
            }
            bind(row, {...globalScope, ...item});
            fragment.appendChild(row);
        }
        loop.replaceChildren(fragment);
        loop.hidden = false;
        loop.dataset.learnlingoReady = 'true';
    }));

    // Finish scalar values and conditions outside loop templates.
    bind(root, globalScope, true);
    for (const element of root.querySelectorAll('[data-learnlingo-text]')) {
        if (!element.closest('[data-learnlingo-loop]')) {
            element.hidden = false;
        }
    }
};

/**
 * Called by Moodle on homepage render, only when custom home HTML is present.
 *
 * @param {String} apiUrl Fully qualified Moodle endpoint URL.
 * @returns {Promise<void>}
 */
export const init = async(apiUrl) => {
    const roots = document.querySelectorAll('.learnlingo-html-home, .learnlingo-home-intro');
    const pending = [];
    for (const root of roots) {
        if (root.dataset.learnlingoBound) {
            continue;
        }
        root.dataset.learnlingoBound = 'true';
        pending.push(hydrate(root, apiUrl).catch((error) => {
            // Never expose user data or stack traces in the visible homepage.
            window.console.warn('LearnLingo home bindings were not loaded', error);
        }));
    }
    await Promise.all(pending);
};
