// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @module theme_learnlingo/footer_settings

/**
 * Toggle the mode-specific footer fields without clearing saved settings.
 *
 * Moodle renders admin_setting_heading as a bare h3.main, not as a setting
 * wrapper with an id. Find each heading relative to its first column field.
 */
export const init = () => {
    const select = document.getElementById('id_s_theme_learnlingo_footermode');
    if (!select) {
        return;
    }

    const getSettingRow = (name) => {
        const input = document.getElementById('id_s_theme_learnlingo_' + name);
        return document.getElementById('admin-' + name) ||
            document.getElementById('admin-theme_learnlingo_' + name) ||
            input?.closest('.form-item, .fitem, .form-group');
    };

    const setVisible = (element, visible) => {
        if (!element) {
            return;
        }
        element.classList.toggle('d-none', !visible);
        element.setAttribute('aria-hidden', visible ? 'false' : 'true');
    };

    const setColumnHeadingVisible = (index, visible) => {
        // core_admin/setting_heading.mustache emits h3.main with no id.
        const row = getSettingRow('footer_title_' + index);
        const previous = row?.previousElementSibling;
        if (previous?.matches('h3.main')) {
            setVisible(previous, visible);
        }
    };

    const refresh = () => {
        const isHtml = select.value === 'html';

        // HTML mode displays only the mode selector and the HTML/SCSS editors.
        setVisible(getSettingRow('footerhtml'), isHtml);
        setVisible(getSettingRow('footerscss'), isHtml);

        // Keep every existing Moodle-footer setting for when the mode changes back.
        setVisible(getSettingRow('footer_background_color'), !isHtml);
        for (let index = 1; index <= 4; index++) {
            setColumnHeadingVisible(index, !isHtml);
            setVisible(getSettingRow('footer_title_' + index), !isHtml);
            setVisible(getSettingRow('footer_html_' + index), !isHtml);
        }
        setVisible(getSettingRow('footer_show_copywriter'), !isHtml);
    };

    select.addEventListener('change', refresh);
    refresh();
};
