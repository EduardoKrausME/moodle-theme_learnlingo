// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @module theme_learnlingo/homepage_settings

/**
 * Toggle settings for the selected homepage mode, without clearing values.
 */
export const init = () => {
    const select = document.getElementById('id_s_theme_learnlingo_homepagemode');
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
        const isLearnlingo = select.value === 'learnlingo';
        const isHtml = select.value === 'html';
        setVisible('homepagehtml', isLearnlingo || isHtml);
        setVisible('homepagescss', isLearnlingo || isHtml);
        setVisible('homepagefullwidth', isLearnlingo || isHtml);
    };
    select.addEventListener('change', refresh);
    refresh();
};
