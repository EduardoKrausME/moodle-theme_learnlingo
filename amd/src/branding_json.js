// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @module theme_learnlingo/branding_json

/**
 * Copy the exported branding prompt to the clipboard without inline JavaScript.
 */
export const init = () => {
    const button = document.getElementById('learnlingo-copy-prompt');
    const textarea = document.getElementById('learnlingo-brand-prompt');
    if (!button || !textarea) {
        return;
    }

    button.addEventListener('click', async() => {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(textarea.value);
                return;
            }
        } catch (error) {
            // Fall back to the original selection-based copy behavior.
        }

        textarea.focus();
        textarea.select();
        document.execCommand('copy');
    });
};
