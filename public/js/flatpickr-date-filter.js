/**
 * Flatpickr Date Filter Utility
 * Supports three modes: after, interval, up_to
 */

// Date formatting helper
function fmt(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${dd}`;
}

// Parse YYYY-MM-DD format
function parseYMD(s) {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || '');
    if (!m) return null;
    const d = new Date(+m[1], +m[2] - 1, +m[3]);
    return (d.getFullYear() == +m[1] && d.getMonth() == +m[2] - 1 && d.getDate() == +m[3]) ? d : null;
}

// Initialize Flatpickr for a date filter field
function initDateFilterPicker(inputElement, fieldId, filterObj) {
    if (!filterObj) {
        window.filters[fieldId] = { mode: 'interval', start_date: null, end_date: null };
    }

    let mode = filterObj.mode || 'interval';
    let isUpdatingDates = false;

    function formatDisplay(selectedDates) {
        if (mode === 'after') {
            if (selectedDates.length >= 1) return fmt(selectedDates[0]);
            return '';
        }
        if (mode === 'up_to') {
            if (selectedDates.length >= 1) return fmt(selectedDates[selectedDates.length - 1]);
            return '';
        }
        // interval - show range format for 2 dates
        if (selectedDates.length >= 2) return `${fmt(selectedDates[0])} to ${fmt(selectedDates[1])}`;
        if (selectedDates.length === 1) return fmt(selectedDates[0]);
        return '';
    }

    function setFromTypedValue(v, fp) {
        const parts = v.split(/\s+to\s+/i);
        if (parts.length === 2) {
            const s = parseYMD(parts[0]), e = parseYMD(parts[1]);
            if (s && e) {
                isUpdatingDates = true;
                fp.setDate([s, e], false);
                isUpdatingDates = false;
                inputElement.value = `${fmt(s)} to ${fmt(e)}`;
                updateFilterState(fieldId, fp.selectedDates, 'interval');
                return;
            }
        }
        const d = parseYMD(v);
        if (d) {
            isUpdatingDates = true;
            if (mode === 'after' || mode === 'up_to') {
                fp.setDate([d], false);
                inputElement.value = fmt(d);
            } else {
                // For interval mode, only set single date - user will click again for second date
                fp.setDate([d], false);
                inputElement.value = fmt(d);
            }
            isUpdatingDates = false;
            updateFilterState(fieldId, fp.selectedDates, mode);
        }
    }

    const fp = flatpickr(inputElement, {
        mode: 'range',
        dateFormat: 'Y-m-d',
        allowInput: true,
        clickOpens: true,
        closeOnSelect: false,
        onReady: function (selectedDates, dateStr, instance) {
            const cal = instance.calendarContainer;

            // Create mode selector wrapper INSIDE the calendar
            const modeWrapper = document.createElement('div');
            modeWrapper.className = 'fp-mode-wrapper';
            modeWrapper.innerHTML = `
                <select id="fpMode_${fieldId}" class="fp-mode-select" aria-label="Date selection mode">
                    <option value="interval">Select date interval</option>
                    <option value="after">AFTER</option>
                    <option value="up_to">DATE UP TO</option>
                </select>
            `;

            // Insert at the very beginning of the calendar (before all content)
            cal.insertBefore(modeWrapper, cal.firstChild);

            const sel = modeWrapper.querySelector(`#fpMode_${fieldId}`);
            sel.value = mode;

            sel.addEventListener('change', (e) => {
                const newMode = e.target.value;
                mode = newMode;
                window.filters[fieldId].mode = mode;

                // Clear selection when mode changes
                isUpdatingDates = true;
                instance.setDate([], false);
                isUpdatingDates = false;
                inputElement.value = '';
                updateFilterState(fieldId, [], mode);
            });
        },
        onOpen: function (selectedDates, dateStr, instance) {
            const sel = instance.calendarContainer.querySelector(`#fpMode_${fieldId}`);
            if (sel) sel.value = mode;
        },
        onChange: function (selectedDates, dateStr, instance) {
            // Skip if this is a recursive update
            if (isUpdatingDates) return;

            // For interval mode, allow 2 dates. For after/up_to, enforce single date.
            if (mode === 'after') {
                if (selectedDates.length > 1) {
                    // Keep only the first date
                    isUpdatingDates = true;
                    instance.setDate([selectedDates[0]], false);
                    isUpdatingDates = false;
                }
                inputElement.value = formatDisplay(instance.selectedDates);
                updateFilterState(fieldId, instance.selectedDates, mode);
            } else if (mode === 'up_to') {
                if (selectedDates.length > 1) {
                    // Keep only the last date
                    isUpdatingDates = true;
                    instance.setDate([selectedDates[selectedDates.length - 1]], false);
                    isUpdatingDates = false;
                }
                inputElement.value = formatDisplay(instance.selectedDates);
                updateFilterState(fieldId, instance.selectedDates, mode);
            } else {
                // interval mode - allow up to 2 dates
                if (selectedDates.length <= 2) {
                    inputElement.value = formatDisplay(instance.selectedDates);
                    updateFilterState(fieldId, instance.selectedDates, mode);
                }
            }
        },
        onClose: function (selectedDates) {
            inputElement.value = formatDisplay(selectedDates);
        }
    });

    // Typing support
    inputElement.addEventListener('change', () => {
        const value = inputElement.value.trim();
        if (value) {
            setFromTypedValue(value, fp);
        }
    });

    // Restore initial values if they exist
    if (filterObj.start_date || filterObj.end_date) {
        const dates = [];
        if (filterObj.start_date) dates.push(parseYMD(filterObj.start_date));
        if (filterObj.end_date) dates.push(parseYMD(filterObj.end_date));
        if (dates.length > 0) {
            isUpdatingDates = true;
            fp.setDate(dates, false);
            isUpdatingDates = false;
            inputElement.value = formatDisplay(dates);
        }
    }

    return fp;
}

// Update filter state in window.filters
function updateFilterState(fieldId, selectedDates, mode) {
    if (!window.filters[fieldId]) {
        window.filters[fieldId] = {};
    }

    if (mode === 'after') {
        window.filters[fieldId].mode = 'after';
        // For AFTER mode: store only start_date, end_date is null
        window.filters[fieldId].start_date = selectedDates.length ? fmt(selectedDates[0]) : null;
        window.filters[fieldId].end_date = null;
        console.log('AFTER filter set - Start Date:', window.filters[fieldId].start_date, 'Selected Dates:', selectedDates.map(d => fmt(d)));
    } else if (mode === 'up_to') {
        window.filters[fieldId].mode = 'up_to';
        // For UP_TO mode: store only end_date, start_date is null
        window.filters[fieldId].start_date = null;
        window.filters[fieldId].end_date = selectedDates.length ? fmt(selectedDates[selectedDates.length - 1]) : null;
        console.log('UP_TO filter set - End Date:', window.filters[fieldId].end_date, 'Selected Dates:', selectedDates.map(d => fmt(d)));
    } else {
        // interval
        window.filters[fieldId].mode = 'interval';
        // For INTERVAL mode: store both dates
        window.filters[fieldId].start_date = selectedDates.length >= 1 ? fmt(selectedDates[0]) : null;
        window.filters[fieldId].end_date = selectedDates.length >= 2 ? fmt(selectedDates[1]) : null;
        console.log('INTERVAL filter set - Start:', window.filters[fieldId].start_date, 'End:', window.filters[fieldId].end_date);
    }

    console.log('Filter State Updated for field:', fieldId, window.filters[fieldId]);

    // Trigger table update if renderTable function exists
    if (typeof window.renderTable === 'function') {
        window.renderTable();
    }
}
