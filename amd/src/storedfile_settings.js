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
 * Improve Moodle's native admin filemanager with an image preview and upload surface.
 * Core YUI still handles the upload, file restrictions, draft files, and saving.
 *
 * @module     theme_learnlingo/storedfile_settings
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    'use strict';

    /**
     * Choose a contrasting background from the actual visible image pixels.
     * Ignore near-transparent pixels (essential for white transparent logos).
     *
     * @param {HTMLImageElement} image
     * @param {HTMLElement} canvasContainer
     */
    const selectBackground = (image, canvasContainer) => {
        if (!image.complete || !image.naturalWidth) {
            return;
        }

        try {
            const canvas = document.createElement('canvas');
            canvas.width = 64;
            canvas.height = 64;
            const context = canvas.getContext('2d', {willReadFrequently: true});
            if (!context) {
                return;
            }
            // Contain rather than crop so wide logos retain their important pixels.
            const scale = Math.min(canvas.width / image.naturalWidth, canvas.height / image.naturalHeight);
            const width = image.naturalWidth * scale;
            const height = image.naturalHeight * scale;
            context.drawImage(image, (canvas.width - width) / 2, (canvas.height - height) / 2, width, height);

            const pixels = context.getImageData(0, 0, canvas.width, canvas.height).data;
            let brightness = 0;
            let visible = 0;
            for (let index = 0; index < pixels.length; index += 4) {
                const alpha = pixels[index + 3] / 255;
                if (alpha < 0.12) {
                    continue;
                }
                // The weighted RGB luminance is perceptually closer to the actual contrast.
                const luminance = 0.2126 * pixels[index] +
                    0.7152 * pixels[index + 1] + 0.0722 * pixels[index + 2];
                brightness += luminance * alpha;
                visible += alpha;
            }

            canvasContainer.dataset.background = visible && brightness / visible >= 148 ? 'dark' : 'light';
        } catch (error) {
            // Cross-origin or unsupported images keep the safe two-tone background.
            canvasContainer.dataset.background = 'neutral';
        }
    };

    /**
     * Initialise one independent stored-file field.
     *
     * @param {HTMLElement} root
     */
    const setup = (root) => {
        if (root.dataset.initialised === '1') {
            return;
        }
        root.dataset.initialised = '1';

        const manager = root.querySelector('.ll-storedfile__manager');
        const zone = root.querySelector('[data-file-dropzone]');
        const button = root.querySelector('[data-file-choose]');
        const fileInput = root.querySelector('[data-file-input]');
        const image = root.querySelector('[data-file-image]');
        const empty = root.querySelector('[data-file-empty]');
        const canvas = root.querySelector('[data-file-canvas]');
        const name = root.querySelector('[data-file-name]');
        const state = root.querySelector('[data-file-state]');
        const currentlabel = root.dataset.currentLabel;
        const emptylabel = root.dataset.emptyLabel;
        const newlabel = root.dataset.newLabel;
        const noimagelabel = root.dataset.noImageLabel;
        const existingFilename = image.getAttribute('alt') || '';
        const template = root.dataset.draftPattern;
        let filename = existingFilename;
        let objectUrl = null;

        const releaseObjectUrl = () => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }
        };

        const showPreview = (url, file, temporary) => {
            if (url) {
                if (!temporary) {
                    releaseObjectUrl();
                }
                image.hidden = false;
                empty.hidden = true;
                filename = file;
                image.alt = file;
                image.onload = () => selectBackground(image, canvas);
                image.onerror = () => {
                    canvas.dataset.background = 'neutral';
                };
                canvas.dataset.background = 'neutral';
                image.src = url;
                if (image.complete && image.naturalWidth) {
                    selectBackground(image, canvas);
                }
                name.textContent = file;
                // Saved image vs new draft image.
                if (file !== existingFilename || temporary) {
                    state.textContent = newlabel;
                } else {
                    state.textContent = currentlabel;
                }
            } else {
                releaseObjectUrl();
                filename = '';
                image.hidden = true;
                image.removeAttribute('src');
                empty.hidden = false;
                canvas.dataset.background = 'neutral';
                name.textContent = noimagelabel;
                state.textContent = emptylabel;
            }
        };

        image.addEventListener('load', () => selectBackground(image, canvas));
        if (image.complete && image.naturalWidth) {
            selectBackground(image, canvas);
        }

        const pickFromMoodle = () => {
            const add = manager.querySelector('.fp-btn-add a');
            if (add) {
                add.click();
            }
        };

        // Reuse the DnD upload handler already installed by Moodle on this container.
        // A synthetic FileList from a local picker is handled by the same upload
        // restrictions, progress notifications and draft-area refresh as real drops.
        const uploadWithMoodle = (file) => {
            const destination = manager.querySelector('.filemanager-container') ||
                manager.querySelector('[id^="filemanager-"]');
            if (!destination || !document.body.classList.contains('dndsupported') ||
                    typeof DataTransfer === 'undefined' || typeof DragEvent === 'undefined') {
                return false;
            }
            try {
                const transfer = new DataTransfer();
                transfer.items.add(file);
                const event = new DragEvent('drop', {
                    bubbles: true,
                    cancelable: true,
                    dataTransfer: transfer
                });
                return !destination.dispatchEvent(event);
            } catch (error) {
                return false;
            }
        };

        button.addEventListener('click', () => {
            if (document.body.classList.contains('dndsupported') &&
                    typeof DataTransfer !== 'undefined' && typeof DragEvent !== 'undefined') {
                fileInput.click();
            } else {
                pickFromMoodle();
            }
        });

        fileInput.addEventListener('change', () => {
            const file = fileInput.files && fileInput.files[0];
            fileInput.value = '';
            if (!file) {
                return;
            }
            if (!uploadWithMoodle(file)) {
                pickFromMoodle();
                return;
            }
            releaseObjectUrl();
            objectUrl = URL.createObjectURL(file);
            showPreview(objectUrl, file.name, true);
        });

        // Install our dropzone inside the native YUI drag-and-drop target.
        // The browser's actual drop event bubbles into Moodle's original listener.
        const mountZone = () => {
            const destination = manager.querySelector('.filemanager-container') ||
                manager.querySelector('[id^="filemanager-"]');
            if (destination && zone.parentElement !== destination) {
                destination.prepend(zone);
            }
            zone.hidden = !destination;
        };
        mountZone();

        // Moodle re-renders the file list after uploads/deletions. Keep the
        // full-size preview in sync with that draft area without saving here.
        const refresh = () => {
            const entries = manager.querySelectorAll('.fp-file:not(.fp-folder)');
            let active = null;
            entries.forEach((entry) => {
                const element = entry.querySelector('.fp-filename');
                if (element && element.textContent.trim()) {
                    active = element.textContent.trim();
                }
            });
            if (active && (active !== filename || objectUrl)) {
                const url = template.replace('__FILENAME__', encodeURIComponent(active));
                showPreview(url, active, false);
            } else if (!active && filename && manager.querySelector('.fm-empty-container')) {
                showPreview('', '', false);
            }
        };

        const observer = new MutationObserver((mutations) => {
            if (mutations.some((mutation) =>
                mutation.target.closest && mutation.target.closest('.fp-content')
            )) {
                refresh();
            }
        });
        observer.observe(manager, {childList: true, subtree: true});
    };

    return {
        init: () => {
            document.querySelectorAll('[data-ll-storedfile]').forEach(setup);
        }
    };
});
