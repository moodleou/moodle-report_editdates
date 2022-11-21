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

const classOutOfRange = "outofrange";
const farawayDate = "December 31, 2099 23:59:59";
let datetimeSelectors = {};
let startDate = {};
let endDate = new Date(farawayDate);

/**
 * Initialiser function.
 */
export const init = () => {
    // Give our variables values.
    datetimeSelectors = document.querySelectorAll("div[data-fieldtype='date_time_selector']");
    let courseStart = datetimeSelectors.item(0);
    let courseEnd = datetimeSelectors.item(1);
    startDate = getDate(courseStart);
    let optional = courseEnd.querySelector("input[type='checkbox']");
    if (optional.checked) {
        endDate = getDate(courseEnd);
    }

    // Add some event Listeners.
    courseStart.addEventListener("change", startDateChanged);
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
            if (datetimeSelector === datetimeSelectors.item(0)) {
                startDateChanged();
            } else if (datetimeSelector === datetimeSelectors.item(1)) {
                endDateChanged();
            } else {
                checkDateRange(datetimeSelector);
            }
        },
        100
    );
};

/**
 * The start date of the course has been changed.
 * Adjust all other dates accordingly if desired.
 */
const startDateChanged = () => {
    startDate = adjustDates();
    checkAllDatesRange();
};

/**
 * The end date of the course has been changed.
 * Check if any activity module dates are after this date.
 */
const endDateChanged = () => {
    let endDateSelector = datetimeSelectors.item(1);
    let optional = endDateSelector.querySelector("input[type='checkbox']");
    if (optional.checked) {
        endDate = getDate(endDateSelector);
    } else {
        endDate = new Date(farawayDate);
    }
    checkAllDatesRange();
};

/**
 * An activity module date has been changed.
 * Check and warn if it is now after the course end date.
 * @param {Event} ev
 */
const modDateChanged = (ev) => {
    // Check against course end date.
    let container = ev.target.closest("div[data-fieldtype='date_time_selector']");

    checkDateRange(container);
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
 * Update the select DOM elements with a given Date object.
 * @param {HTMLElement} dateEl
 * @param {Date} date
 */
const setDate = (dateEl, date) => {
    let year = dateEl.querySelector(".fdate_time_selector > div:nth-child(3) select");
    let month = dateEl.querySelector(".fdate_time_selector > div:nth-child(2) select");
    let day = dateEl.querySelector(".fdate_time_selector > div:nth-child(1) select");
    let hour = dateEl.querySelector(".fdate_time_selector > div:nth-child(4) select");
    let minute = dateEl.querySelector(".fdate_time_selector > div:nth-child(5) select");
    year.querySelector("[value='" + date.getFullYear() + "']").selected = "selected";
    month.querySelector("[value='" + (date.getMonth() + 1) + "']").selected = "selected";
    day.querySelector("[value='" + date.getDate() + "']").selected = "selected";
    hour.querySelector("[value='" + date.getHours() + "']").selected = "selected";
    minute.querySelector("[value='" + date.getMinutes() + "']").selected = "selected";
};

/**
 * Check if the date change has caused any dates to be outside the course start and end.
 * @returns {boolean|*}
 */
const checkAllDatesRange = () => {
    let datesOutOfRange = 0;
    // Loop dates and if they are enabled then check the date range.
    for (const date of datetimeSelectors.entries()) {
        if (date[0] > 1) {
            let optional = date[1].querySelector("input[type='checkbox']");
            if (optional === null || optional.checked) {
                datesOutOfRange += checkDateRange(date[1]);
            }
        }
    }

    // Warn the person about it.
    if (datesOutOfRange > 0) {
        return ModalFactory.create({
            type: ModalFactory.types.DEFAULT,
            title: getString('datesoutofrange_title', 'report_editdates'),
            body: getString('datesoutofrange_body', 'report_editdates')
        })
            .then(modal => {
                modal.show();
                return modal;
            });
    }

    return false;
};

/**
 * Compare the date with the course end date. Toggle CSS class of element.
 * @param {HTMLElement} el
 * @returns {boolean}
 */
const checkDateRange = (el) => {
    let outOfRange = false;
    let modDate = getDate(el);
    if (modDate > endDate || modDate < startDate) {
        el.parentNode.classList.add(classOutOfRange);
        for (const input of el.querySelectorAll(".form-group").values()) {
            input.classList.add(classOutOfRange);
        }
        outOfRange = true;
    } else {
        el.parentNode.classList.remove(classOutOfRange);
        for (const input of el.querySelectorAll(".form-group").values()) {
            input.classList.remove(classOutOfRange);
        }
    }
    return outOfRange;
};

/**
 * Change the enabled dates by the amount the start date has been shifted.
 * @returns {Date}
 */
const adjustDates = () => {
    if (!document.getElementById("id_movealldates").checked) {
        return startDate;
    }
    // Determine the change.
    let newStart = getDate(datetimeSelectors.item(0));
    let dst1 = startDate.getTimezoneOffset() - newStart.getTimezoneOffset(); // Account for daylight savings shift.
    let change = newStart - startDate + (dst1 * 60000);

    // Update all enabled dates.
    for (const date of datetimeSelectors.entries()) {
        if (date[0] > 0) {
            let optional = date[1].querySelector("input[type='checkbox']");
            if (optional === null || optional.checked) {
                let currDate = getDate(date[1]);
                let newDate = new Date(currDate.getTime() + change);
                let dst2 = newDate.getTimezoneOffset() - currDate.getTimezoneOffset();
                newDate = new Date(newDate.getTime() + (dst2 * 60000));
                setDate(date[1], newDate);
            }
        }
    }

    // Update the stored value for the end date.
    let endDateSelector = datetimeSelectors.item(1);
    let optional = endDateSelector.querySelector("input[type='checkbox']");
    if (optional.checked) {
        endDate = getDate(endDateSelector);
    } else {
        endDate = new Date(farawayDate);
    }

    return newStart;
};
