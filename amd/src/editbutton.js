// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Adds the bulk value button to the native quiz question-editing toolbar.
 *
 * @module     local_quizbulkmarks/editbutton
 * @copyright  2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const BUTTON_ID = 'local-quizbulkmarks-edit-button';
const TOOLBAR_SELECTOR = '.mod_quiz-edit-action-buttons';

/**
 * Try to add the button to Moodle's native question editing toolbar.
 *
 * @param {String} pluginUrl Plugin page URL.
 * @param {String} buttonLabel Visible button label.
 * @param {String} ariaLabel Accessible label.
 * @returns {Boolean} True when the button exists or was inserted.
 */
const addButton = (pluginUrl, buttonLabel, ariaLabel) => {
    if (document.getElementById(BUTTON_ID)) {
        return true;
    }

    const toolbar = document.querySelector(TOOLBAR_SELECTOR);
    if (!toolbar) {
        return false;
    }

    const button = document.createElement('a');
    button.id = BUTTON_ID;
    button.href = pluginUrl;
    button.className = 'btn btn-secondary ms-1';
    button.textContent = buttonLabel;
    button.setAttribute('aria-label', ariaLabel);
    button.dataset.localQuizbulkmarks = '1';

    toolbar.appendChild(button);
    return true;
};

/**
 * Initialise the integration.
 *
 * The toolbar is server-rendered in standard Moodle. A short MutationObserver
 * is retained as a fallback for themes which render or move controls later.
 *
 * @param {String} pluginUrl Plugin page URL.
 * @param {String} buttonLabel Visible button label.
 * @param {String} ariaLabel Accessible label.
 */
export const init = (pluginUrl, buttonLabel, ariaLabel) => {
    const run = () => {
        if (addButton(pluginUrl, buttonLabel, ariaLabel)) {
            return;
        }

        const observer = new MutationObserver(() => {
            if (addButton(pluginUrl, buttonLabel, ariaLabel)) {
                observer.disconnect();
            }
        });

        observer.observe(document.body, {childList: true, subtree: true});
        window.setTimeout(() => observer.disconnect(), 5000);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, {once: true});
    } else {
        run();
    }
};
