/**
 * Table Totals Helper
 * Adds a subtotal row above filter rows for numeric columns
 * Recalculates on filter changes
 */

function initTableTotals(options = {}) {
    const {
        tableId = 'dataTable',
        fieldsToSum = [],
        dateFields = [],
        formatters = {},
        defaultFormatter = (val) => typeof val === 'number' ? val.toLocaleString('tr-TR', { minimumFractionDigits: 2 }) : val
    } = options;

    const table = document.getElementById(tableId);
    if (!table) {
        console.warn(`Table with id "${tableId}" not found`);
        return;
    }

    console.log('initTableTotals called for table:', tableId);

    // Ensure we have a totals row in thead
    let totalsRow = document.getElementById(`${tableId}_totalsRow`);
    console.log('Looking for existing totalsRow with id:', `${tableId}_totalsRow`, 'Found:', !!totalsRow);

    if (!totalsRow) {
        const thead = table.querySelector('thead');
        console.log('thead element found:', !!thead);
        const filtersRow = thead.querySelector('tr:last-child');
        console.log('filtersRow (last row) found:', !!filtersRow);

        if (filtersRow) {
            totalsRow = document.createElement('tr');
            totalsRow.id = `${tableId}_totalsRow`;
            totalsRow.className = 'table-totals-row';
            filtersRow.parentNode.insertBefore(totalsRow, filtersRow);
            console.log('Created new totalsRow and inserted before filtersRow');
        }
    }

    if (!totalsRow) {
        console.warn('Could not find or create totals row');
        return;
    }

    console.log('totalsRow successfully created/found with id:', totalsRow.id);

    // Function to calculate and update totals
    function updateTotals(visibleData) {
        // Query for the totalsRow each time since we may not have a persistent reference
        const totalsRow = document.getElementById(`${tableId}_totalsRow`);
        console.log('updateTotals function entered. totalsRow found?', !!totalsRow);
        if (!totalsRow) {
            console.warn('totalsRow does NOT exist! Exiting updateTotals. Looking for id:', `${tableId}_totalsRow`);
            return;
        }

        console.log('updateTotals called with window.DATA:', window.DATA ? window.DATA.length + ' records' : 'undefined', 'fieldsToSum:', fieldsToSum, 'dateFields:', dateFields);

        totalsRow.innerHTML = '';

        // Get all visible column headers to know how many cells we need
        // IMPORTANT: The header row is the second row in thead (filtersRow is first, headerRow is second)
        const headerRow = table.querySelector('thead tr#headerRow') || table.querySelector('thead tr:nth-child(2)');
        const headers = headerRow ? headerRow.querySelectorAll('th') : [];
        const colCount = headers.length;
        console.log('Found header row?', !!headerRow, 'Found', colCount, 'header columns');

        // Create a totals cell for each column
        for (let i = 0; i < colCount; i++) {
            const th = document.createElement('th');
            th.className = 'table-totals-cell';
            th.style.backgroundColor = '#f8f9fa';
            th.style.fontWeight = 'bold';
            th.style.borderTop = '2px solid #dee2e6';

            // Check if this column should be summed
            const fieldName = headers[i]?.getAttribute('data-field');
            console.log('Column', i, 'fieldName:', fieldName, 'isInFieldsToSum:', fieldsToSum.includes(fieldName));

            if (fieldName && fieldsToSum.includes(fieldName)) {
                // Calculate sum
                let sum = 0;

                // If we have access to the data, sum from there
                if (window.DATA && Array.isArray(window.DATA)) {
                    console.log('Calculating sum for field:', fieldName, 'from', window.DATA.length, 'total records');
                    let filteredData = window.DATA;

                    // Apply filters if they exist - using same logic as views
                    if (window.filters) {
                        filteredData = window.DATA.filter(record => {
                            for (let field in window.filters) {
                                if (dateFields.includes(field)) {
                                    // Date filtering with three modes
                                    const filterObj = window.filters[field];
                                    if (!filterObj || (!filterObj.start_date && !filterObj.end_date)) continue;

                                    const recordDate = String(record[field] || '').split('T')[0]; // Get YYYY-MM-DD
                                    const mode = filterObj.mode || 'interval';
                                    const startDate = filterObj.start_date;
                                    const endDate = filterObj.end_date;

                                    if (mode === 'after') {
                                        if (startDate && recordDate < startDate) return false;
                                    } else if (mode === 'up_to') {
                                        if (endDate && recordDate > endDate) return false;
                                    } else {
                                        // interval
                                        if (startDate && recordDate < startDate) return false;
                                        if (endDate && recordDate > endDate) return false;
                                    }
                                } else {
                                    // Text field filtering
                                    const value = String(record[field] || '').toLowerCase();
                                    const filter = String(window.filters[field] || '').toLowerCase();
                                    if (filter && !value.includes(filter)) return false;
                                }
                            }
                            return true;
                        });
                    }

                    console.log('After filtering:', filteredData.length, 'records for field:', fieldName);

                    filteredData.forEach(record => {
                        const val = parseFloat(record[fieldName]);
                        console.log('Record', fieldName, ':', record[fieldName], '-> parsed:', val, 'isNaN:', isNaN(val));
                        if (!isNaN(val)) sum += val;
                    });
                }

                // Format the sum
                const formatter = formatters[fieldName] || defaultFormatter;
                th.textContent = formatter(sum);
                console.log('Field:', fieldName, 'Sum:', sum, 'Formatted:', formatter(sum));
                th.className += ' text-end';
                th.style.paddingRight = '15px';
            } else if (i === 0) {
                // Add label in first cell
                th.textContent = 'TOPLAM';
                th.style.fontStyle = 'italic';
            }

            totalsRow.appendChild(th);
        }
    }

    // Expose update function globally for use in render functions
    window[`updateTotals_${tableId}`] = updateTotals;

    // Don't call updateTotals here - let renderTable() handle the initial call
    // This ensures window.DATA is populated before calculating totals

    return {
        updateTotals,
        totalsRow
    };
}
