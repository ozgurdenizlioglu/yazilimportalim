/**
 * Date Range Filter Helper
 * Adds date range filtering UI and logic to tables
 */

function initDateRangeFilter(options = {}) {
    const {
        tableId = 'dataTable',
        dateFields = [],  // Array of date field IDs to filter by
        onFilterChange = null
    } = options;

    if (!dateFields || dateFields.length === 0) {
        console.warn('No date fields specified for date range filter');
        return;
    }

    // Create filter container
    let filterContainer = document.getElementById(`${tableId}_dateFilterContainer`);
    if (!filterContainer) {
        const columnPanel = document.querySelector(`#${tableId}`)?.parentElement?.querySelector('.column-panel');
        if (columnPanel) {
            filterContainer = document.createElement('div');
            filterContainer.id = `${tableId}_dateFilterContainer`;
            filterContainer.className = 'date-filter-container card card-body py-2 mb-2';
            filterContainer.style.display = 'none';
            columnPanel.parentElement.insertBefore(filterContainer, columnPanel);
        }
    }

    if (!filterContainer) {
        console.warn('Could not create date filter container');
        return;
    }

    // Create filter UI
    filterContainer.innerHTML = `
        <div class="d-flex align-items-center justify-content-between gap-2">
            <strong>Tarih Aralığı Filtresi</strong>
            <button class="btn btn-sm btn-outline-secondary" id="${tableId}_clearDateFilter">Temizle</button>
        </div>
        <hr class="my-2">
        <div class="d-flex flex-wrap gap-2">
            <div>
                <label class="form-label small mb-1">Başlangıç Tarihi</label>
                <input type="date" id="${tableId}_dateStart" class="form-control form-control-sm" style="min-width: 150px;">
            </div>
            <div>
                <label class="form-label small mb-1">Bitiş Tarihi</label>
                <input type="date" id="${tableId}_dateEnd" class="form-control form-control-sm" style="min-width: 150px;">
            </div>
            <div style="display: flex; align-items: flex-end;">
                <button class="btn btn-sm btn-primary" id="${tableId}_applyDateFilter">Uygula</button>
            </div>
        </div>
    `;

    // Store date filter state globally
    window[`dateFilter_${tableId}`] = {
        startDate: null,
        endDate: null,
        dateFields: dateFields
    };

    // Get input elements
    const startInput = document.getElementById(`${tableId}_dateStart`);
    const endInput = document.getElementById(`${tableId}_dateEnd`);
    const applyBtn = document.getElementById(`${tableId}_applyDateFilter`);
    const clearBtn = document.getElementById(`${tableId}_clearDateFilter`);

    // Apply date filter
    function applyDateFilter() {
        const startDate = startInput.value ? new Date(startInput.value) : null;
        const endDate = endInput.value ? new Date(endInput.value) : null;

        if (startDate && endDate && startDate > endDate) {
            alert('Başlangıç tarihi bitiş tarihinden sonra olamaz!');
            return;
        }

        window[`dateFilter_${tableId}`].startDate = startDate;
        window[`dateFilter_${tableId}`].endDate = endDate;

        // Filter the data
        if (window.DATA && Array.isArray(window.DATA)) {
            const filtered = window.DATA.filter(record => {
                // Check if record matches date range
                for (let dateField of dateFields) {
                    const dateValue = record[dateField];
                    if (!dateValue) continue;

                    const recordDate = new Date(dateValue);

                    // Check start date
                    if (startDate && recordDate < startDate) {
                        continue;
                    }

                    // Check end date (set to end of day)
                    if (endDate) {
                        const endOfDay = new Date(endDate);
                        endOfDay.setHours(23, 59, 59, 999);
                        if (recordDate > endOfDay) {
                            continue;
                        }
                    }

                    // Record matches date filter
                    return true;
                }

                // If no date field matched, return false (filter out)
                return false;
            });

            // Store filtered data in a separate property or trigger callback
            if (onFilterChange) {
                onFilterChange(filtered);
            }

            // Trigger re-render if renderTable function exists in scope
            if (typeof renderTable !== 'undefined') {
                window.currentPage = 1;  // Reset to first page
                renderTable();
            }
        }
    }

    // Clear date filter
    function clearDateFilter() {
        startInput.value = '';
        endInput.value = '';
        window[`dateFilter_${tableId}`].startDate = null;
        window[`dateFilter_${tableId}`].endDate = null;

        if (typeof renderTable !== 'undefined') {
            window.currentPage = 1;
            renderTable();
        }
    }

    // Event listeners
    applyBtn.addEventListener('click', applyDateFilter);
    clearBtn.addEventListener('click', clearDateFilter);

    // Allow Enter key to apply filter
    startInput.addEventListener('keypress', (e) => e.key === 'Enter' && applyDateFilter());
    endInput.addEventListener('keypress', (e) => e.key === 'Enter' && applyDateFilter());

    // Expose toggle function
    window[`toggleDateFilter_${tableId}`] = () => {
        filterContainer.style.display = filterContainer.style.display === 'none' ? 'block' : 'none';
    };

    return {
        container: filterContainer,
        applyFilter: applyDateFilter,
        clearFilter: clearDateFilter
    };
}
