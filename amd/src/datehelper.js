// This file is part of Moodle - http://moodle.org/
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

/**
 * AMD module to help editing course dates
 *
 * @module     report_editdates/datehelper
 * @copyright  2022 Te Wānanga o Aotearoa
 */

import * as ModalFactory from 'core/modal_factory';
import {get_string as getString} from 'core/str';

let datetimeSelectors = {};
let endDate = {};
const classEnd = "afterend";

/**
 * Initialiser function.
 */
export const init = () => {
    datetimeSelectors = document.querySelectorAll("div[data-fieldtype='date_time_selector']");
    let courseEnd = datetimeSelectors.item(1);
    endDate = getDate(courseEnd);

    // Event Listeners.
    courseEnd.addEventListener("change", endDateChanged);
    // Activity module date time selector events.
    for (const date of datetimeSelectors.entries()) {
        if (date[0] > 1) {
            date[1].addEventListener("change", modDateChanged);
        }
    }
    // Date picker event.
    M.form.dateselector.calendar.on('selectionChange', updateDates, false);
};

/**
 * A date has been changed using the date selector calendar.
 */
const updateDates = () => {
    let datepicker = M.form.dateselector.currentowner;
    if (null === datepicker) {
        return;
    }
    // Todo: is there a better way to ensure this runs after the selects have been updated?
    setTimeout(
        function() {
            let el = datepicker.calendarimage.getDOMNode();
            let datetimeSelector = el.closest("div[data-fieldtype='date_time_selector']");
            if (datetimeSelector === datetimeSelectors.item(1)) {
                endDateChanged();
            } else {
                checkEndDate(datetimeSelector);
            }
        },
        100
    );
};

/**
 * The end date of the course has been changed.
 * Check if any activity module dates are after this date.
 * @returns {*}
 */
const endDateChanged = () => {
    let datesPastEnd = 0;
    endDate = getDate(datetimeSelectors.item(1));
    for (const date of datetimeSelectors.entries()) {
        if (date[0] > 1) {
            let optional = date[1].querySelector("input[type='checkbox']");
            if (optional === null || optional.checked) {
                datesPastEnd += checkEndDate(date[1]);
            }
        }
    }

    if (datesPastEnd > 0) {
        return ModalFactory.create({
            type: ModalFactory.types.DEFAULT,
            title: getString('datesafterend_title', 'report_editdates'),
            body: getString('datesafterend_body', 'report_editdates')
        })
            .then(modal => {
                modal.show();
                return modal;
            });
    }
};

/**
 * An activity module date has been changed.
 * Check and warn if it is now after the course end date.
 * @param {Event} ev
 */
const modDateChanged = (ev) => {
    // Check against course end date.
    let container = ev.target.closest("div[data-fieldtype='date_time_selector']");

    checkEndDate(container);
};

/**
 * Get a Date object from the select DOM elements.
 * @param {HTMLElement} dateEl
 * @returns {Date}
 */
const getDate = (dateEl) => {
    let year = dateEl.querySelector(".fdate_time_selector > div:nth-child(3) select");
    let month = dateEl.querySelector(".fdate_time_selector > div:nth-child(2) select");
    let day = dateEl.querySelector(".fdate_time_selector > div:nth-child(1) select");
    let hour = dateEl.querySelector(".fdate_time_selector > div:nth-child(4) select");
    let minute = dateEl.querySelector(".fdate_time_selector > div:nth-child(5) select");
    return new Date(
        year.options[year.selectedIndex].value,
        month.options[month.selectedIndex].value - 1,
        day.options[day.selectedIndex].value,
        hour.options[hour.selectedIndex].value,
        minute.options[minute.selectedIndex].value,
    );
};

/**
 * Compare the date with the course end date. Toggle CSS class of element.
 * @param {HTMLElement} el
 * @returns {boolean}
 */
const checkEndDate = (el) => {
    let afterEndDate = false;
    if (getDate(el) > endDate) {
        el.parentNode.classList.add(classEnd);
        for (const input of el.querySelectorAll(".form-group").values()) {
            input.classList.add(classEnd);
        }
        afterEndDate = true;
    } else {
        el.parentNode.classList.remove(classEnd);
        for (const input of el.querySelectorAll(".form-group").values()) {
            input.classList.remove(classEnd);
        }
    }
    return afterEndDate;
};
