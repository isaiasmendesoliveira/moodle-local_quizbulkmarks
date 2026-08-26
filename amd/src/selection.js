// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Selection helpers, clickable question rows and projected sum preview.
 *
 * @module     local_quizbulkmarks/selection
 * @copyright  2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const getCheckboxes = () => Array.from(document.querySelectorAll('.quizbulkmarks-slot:not(:disabled)'));

const parseLocalizedNumber = (value) => {
    if (typeof value !== 'string') {
        return Number.NaN;
    }

    const cleaned = value.trim().replace(/\s/g, '').replace(',', '.');
    return Number.parseFloat(cleaned);
};

const updateRowState = (checkbox) => {
    const row = checkbox.closest('tr');
    if (row) {
        row.classList.toggle('table-active', checkbox.checked);
    }
};

const formatNumber = (value) => value.toLocaleString(undefined, {maximumFractionDigits: 5});

const formatProjectedNumber = (value) => value.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const refreshSummary = () => {
    const checkboxes = getCheckboxes();
    const selected = checkboxes.filter((checkbox) => checkbox.checked);
    const count = document.getElementById('quizbulkmarks-selected-count');
    const selectedTotal = document.getElementById('quizbulkmarks-selected-total');
    const projected = document.getElementById('quizbulkmarks-projected-sum');
    const currentSumNode = document.getElementById('quizbulkmarks-current-sum');
    const newValueNode = document.getElementById('quizbulkmarks-new-value');

    checkboxes.forEach(updateRowState);

    if (count) {
        count.textContent = selected.length.toString();
    }

    const selectedCurrent = selected.reduce(
        (sum, checkbox) => sum + Number.parseFloat(checkbox.dataset.currentMark || '0'),
        0,
    );

    if (selectedTotal) {
        selectedTotal.textContent = formatNumber(selectedCurrent);
    }

    if (!projected || !currentSumNode || !newValueNode) {
        return;
    }

    const currentSum = Number.parseFloat(currentSumNode.dataset.rawSum || '');
    const newValue = parseLocalizedNumber(newValueNode.value);

    if (Number.isNaN(currentSum) || Number.isNaN(newValue) || selected.length === 0) {
        projected.textContent = '—';
        return;
    }

    const result = currentSum - selectedCurrent + (selected.length * newValue);
    projected.textContent = formatProjectedNumber(result);
};

const toggleCheckbox = (checkbox) => {
    if (!checkbox || checkbox.disabled) {
        return;
    }

    checkbox.checked = !checkbox.checked;
    checkbox.dispatchEvent(new Event('change', {bubbles: true}));
};

const initialiseClickableRows = () => {
    document.querySelectorAll('.quizbulkmarks-clickable-row').forEach((row) => {
        const checkbox = document.getElementById(row.dataset.checkboxId || '');
        if (!checkbox) {
            return;
        }

        row.addEventListener('click', (event) => {
            if (event.target.closest('input, button, a, select, textarea')) {
                return;
            }
            toggleCheckbox(checkbox);
        });

        row.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            event.preventDefault();
            toggleCheckbox(checkbox);
        });
    });
};

export const init = () => {
    const allButton = document.getElementById('quizbulkmarks-select-all');
    const noneButton = document.getElementById('quizbulkmarks-select-none');
    const rangeButton = document.getElementById('quizbulkmarks-select-range');
    const fromInput = document.getElementById('quizbulkmarks-range-from');
    const toInput = document.getElementById('quizbulkmarks-range-to');
    const newValueInput = document.getElementById('quizbulkmarks-new-value');

    if (allButton) {
        allButton.addEventListener('click', () => {
            getCheckboxes().forEach((checkbox) => {
                checkbox.checked = true;
            });
            refreshSummary();
        });
    }

    if (noneButton) {
        noneButton.addEventListener('click', () => {
            getCheckboxes().forEach((checkbox) => {
                checkbox.checked = false;
            });
            refreshSummary();
        });
    }

    if (rangeButton && fromInput && toInput) {
        rangeButton.addEventListener('click', () => {
            const from = Number.parseInt(fromInput.value, 10);
            const to = Number.parseInt(toInput.value, 10);

            if (Number.isNaN(from) || Number.isNaN(to)) {
                return;
            }

            const lower = Math.min(from, to);
            const upper = Math.max(from, to);

            getCheckboxes().forEach((checkbox) => {
                const slotNumber = Number.parseInt(checkbox.dataset.slotNumber, 10);
                checkbox.checked = slotNumber >= lower && slotNumber <= upper;
            });
            refreshSummary();
        });
    }

    getCheckboxes().forEach((checkbox) => checkbox.addEventListener('change', refreshSummary));

    if (newValueInput) {
        newValueInput.addEventListener('input', refreshSummary);
    }

    initialiseClickableRows();
    refreshSummary();
};
