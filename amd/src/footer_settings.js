// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @module theme_learnlingo/footer_settings

/**
 * Toggle the mode-specific footer fields, preserving the configured values.
 */
export const init = () => {
    const select = document.getElementById('id_s_theme_learnlingo_footermode');
    if (!select) {
        return;
    }
    const setVisible = (name, visible) => {
        const input = document.getElementById('id_s_theme_learnlingo_' + name);
        const wrapper = document.getElementById('admin-' + name) ||
            input?.closest('.form-item, .fitem, .form-group');
        if (wrapper) {
            wrapper.classList.toggle('d-none', !visible);
            wrapper.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }
    };
    const refresh = () => {
        const isHtml = select.value === 'html';
        setVisible('footerhtml', isHtml);
        setVisible('footer_background_color', !isHtml);
        for (let index = 1; index <= 4; index++) {
            setVisible('footer_title_' + index, !isHtml);
            setVisible('footer_html_' + index, !isHtml);
            const heading = document.getElementById('admin-theme_learnlingo_footer_heading_' + index) ||
                document.getElementById('admin-footer_heading_' + index);
            if (heading) {
                heading.classList.toggle('d-none', isHtml);
                heading.setAttribute('aria-hidden', isHtml ? 'true' : 'false');
            }
        }
        setVisible('footer_show_copywriter', !isHtml);
    };
    select.addEventListener('change', refresh);
    refresh();
};
