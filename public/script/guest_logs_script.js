// Global variables
let table_rows = [];
let table_headings = [];
let search = null;
let allLogs = [];
let currentPage = 1;
const LOGS_PER_PAGE = 5;

// Load logs from API
async function loadLogs() {
    try {
        const response = await fetch('../../api/get_guest_logs.php');
        
        if (!response.ok) {
            let errorText = '';
            try {
                errorText = await response.text();
                const errorData = JSON.parse(errorText);
                console.error('API Error Response:', response.status, errorData);
                showErrorMessage(errorData.message || 'Failed to load logs. Please check your connection.');
            } catch (e) {
                console.error('API Error Response (non-JSON):', response.status, errorText);
                showErrorMessage('Failed to load logs. Please check your connection.');
            }
            return;
        }
        
        const responseText = await response.text();
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON Parse Error:', e, 'Response:', responseText);
            showErrorMessage('Invalid response from server. Please refresh the page.');
            return;
        }
        
        console.log('API Response:', data);
        
        if (data.status === 'success') {
            allLogs = Array.isArray(data.logs) ? data.logs : [];
            currentPage = 1;
            const startIndex = (currentPage - 1) * LOGS_PER_PAGE;
            const endIndex = startIndex + LOGS_PER_PAGE;
            populateTable(allLogs.slice(startIndex, endIndex));
            updatePagination();
            initializeTableFunctions();
        } else {
            console.error('API returned error:', data.message || 'Unknown error');
            showErrorMessage(data.message || 'Failed to load logs');
        }
    } catch (error) {
        console.error('Error loading logs:', error);
        showErrorMessage('Error loading logs. Please refresh the page.');
    }
}

// Update pagination controls
function updatePagination() {
    const paginationContainer = document.getElementById('pagination-container');
    const pageNumbers = document.getElementById('page-numbers');
    const prevBtn = document.getElementById('prev-page-btn');
    const nextBtn = document.getElementById('next-page-btn');
    const pageInfo = document.getElementById('page-info');
    
    if (!paginationContainer || !pageNumbers || !prevBtn || !nextBtn) return;
    
    const totalPages = Math.ceil(allLogs.length / LOGS_PER_PAGE);
    
    if (allLogs.length === 0 || totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }
    
    paginationContainer.style.display = 'block';
    
    // Update Previous button
    prevBtn.disabled = currentPage === 1;
    prevBtn.style.opacity = currentPage === 1 ? '0.5' : '1';
    prevBtn.style.cursor = currentPage === 1 ? 'not-allowed' : 'pointer';
    
    // Update Next button
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.style.opacity = currentPage === totalPages ? '0.5' : '1';
    nextBtn.style.cursor = currentPage === totalPages ? 'not-allowed' : 'pointer';
    
    // Clear existing page numbers
    pageNumbers.innerHTML = '';
    
    // Calculate which page numbers to show
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);
    
    // Adjust if we're near the start or end
    if (endPage - startPage < 4) {
        if (startPage === 1) {
            endPage = Math.min(totalPages, startPage + 4);
        } else if (endPage === totalPages) {
            startPage = Math.max(1, endPage - 4);
        }
    }
    
    // Add first page if not in range
    if (startPage > 1) {
        addPageNumber(1, pageNumbers);
        if (startPage > 2) {
            const ellipsis = document.createElement('span');
            ellipsis.textContent = '...';
            ellipsis.style.padding = '0 0.5rem';
            ellipsis.style.color = 'var(--secondary-color)';
            pageNumbers.appendChild(ellipsis);
        }
    }
    
    // Add page numbers in range
    for (let i = startPage; i <= endPage; i++) {
        addPageNumber(i, pageNumbers);
    }
    
    // Add last page if not in range
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ellipsis = document.createElement('span');
            ellipsis.textContent = '...';
            ellipsis.style.padding = '0 0.5rem';
            ellipsis.style.color = 'var(--secondary-color)';
            pageNumbers.appendChild(ellipsis);
        }
        addPageNumber(totalPages, pageNumbers);
    }
    
    // Update page info
    const startIndex = (currentPage - 1) * LOGS_PER_PAGE + 1;
    const endIndex = Math.min(currentPage * LOGS_PER_PAGE, allLogs.length);
    pageInfo.textContent = `Showing ${startIndex}-${endIndex} of ${allLogs.length} logs`;
}

// Add a page number button
function addPageNumber(pageNum, container) {
    const pageBtn = document.createElement('button');
    pageBtn.textContent = pageNum;
    pageBtn.className = 'page-number-btn';
    pageBtn.style.cssText = `
        min-width: 2.5rem;
        height: 2.5rem;
        background-color: ${pageNum === currentPage ? '#4f46e5' : '#6b7280'};
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-weight: ${pageNum === currentPage ? '700' : '600'};
        cursor: pointer;
        font-size: 0.9rem;
        transition: background-color 0.2s ease;
        padding: 0 0.5rem;
    `;
    
    if (pageNum === currentPage) {
        pageBtn.style.backgroundColor = '#4f46e5';
        pageBtn.style.cursor = 'default';
    } else {
        pageBtn.onmouseover = function() { 
            if (pageNum !== currentPage) {
                this.style.backgroundColor = '#4b5563'; 
            }
        };
        pageBtn.onmouseout = function() { 
            if (pageNum !== currentPage) {
                this.style.backgroundColor = '#6b7280'; 
            }
        };
    }
    
    pageBtn.onclick = () => goToPage(pageNum);
    container.appendChild(pageBtn);
}

// Go to specific page
function goToPage(page) {
    const totalPages = Math.ceil(allLogs.length / LOGS_PER_PAGE);
    if (page < 1 || page > totalPages || page === currentPage) return;
    
    currentPage = page;
    const startIndex = (currentPage - 1) * LOGS_PER_PAGE;
    const endIndex = startIndex + LOGS_PER_PAGE;
    populateTable(allLogs.slice(startIndex, endIndex));
    updatePagination();
    initializeTableFunctions();
    
    // Scroll to top of table
    const tableBody = document.querySelector('.table__body');
    if (tableBody) {
        tableBody.scrollTop = 0;
    }
}

// Go to previous page
function goToPreviousPage() {
    if (currentPage > 1) {
        goToPage(currentPage - 1);
    }
}

// Go to next page
function goToNextPage() {
    const totalPages = Math.ceil(allLogs.length / LOGS_PER_PAGE);
    if (currentPage < totalPages) {
        goToPage(currentPage + 1);
    }
}

// Populate table with logs
function populateTable(logs) {
    const tbody = document.getElementById('logs-tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (logs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 2rem;">
                    <p>No logs found. Start navigating to see your logs here!</p>
                </td>
            </tr>
        `;
        return;
    }
    
    logs.forEach((log, index) => {
        const row = document.createElement('tr');
        const statusText = log.status === 'completed' ? 'completed' : 'cancelled';
        const statusColor = log.status === 'completed' ? '#10b981' : '#ef4444';
        
        // Use the original index from allLogs for proper numbering
        const originalIndex = allLogs.findIndex(l => l.log_id === log.log_id);
        const displayNumber = originalIndex >= 0 ? originalIndex + 1 : index + 1;
        
        row.innerHTML = `
            <td>${displayNumber}</td>
            <td><img src="${log.image_path}" alt="${log.office_name}" onerror="this.src='../../buildings/default.png'">${log.office_name}</td>
            <td style="color: ${statusColor}; font-weight: bold; text-transform: capitalize;">${statusText}</td>
            <td>${log.time}</td>
            <td>${log.date}</td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Update table_rows after populating
    table_rows = Array.from(tbody.querySelectorAll('tr'));
}

// Show error message
function showErrorMessage(message) {
    const tbody = document.getElementById('logs-tbody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 2rem; color: #ef4444;">
                    <p>${message}</p>
                </td>
            </tr>
        `;
    }
}

// Initialize table functions (search and sort)
function initializeTableFunctions() {
    search = document.querySelector('.input-group input');
    table_headings = document.querySelectorAll('thead th');
    table_rows = document.querySelectorAll('tbody tr');
    
    if (!search || !table_headings.length) return;
    
    // 1. Searching for specific data of HTML table
    search.removeEventListener('input', searchTable);
    search.addEventListener('input', searchTable);
    
    // 2. Sorting | Ordering data of HTML table
    table_headings.forEach((head, i) => {
        // Remove existing event listeners by cloning
        const newHead = head.cloneNode(true);
        head.parentNode.replaceChild(newHead, head);
        
        let sort_asc = true;
        newHead.onclick = () => {
            table_headings.forEach(h => h.classList.remove('active'));
            newHead.classList.add('active');
            
            document.querySelectorAll('td').forEach(td => td.classList.remove('active'));
            table_rows.forEach(row => {
                const tds = row.querySelectorAll('td');
                if (tds[i]) tds[i].classList.add('active');
            });
            
            newHead.classList.toggle('asc', sort_asc);
            sort_asc = newHead.classList.contains('asc') ? false : true;
            
            sortTable(i, sort_asc);
        };
    });
    
    // Update table_headings reference
    table_headings = document.querySelectorAll('thead th');
}

function searchTable() {
    if (!search) return;
    
    const search_data = search.value.toLowerCase().trim();
    
    // If searching, filter all logs and show matching results
    if (search_data) {
        const filteredLogs = allLogs.filter(log => {
            const searchText = `${log.office_name} ${log.status} ${log.time} ${log.date}`.toLowerCase();
            return searchText.includes(search_data);
        });
        
        // Populate table with filtered results
        populateTable(filteredLogs);
        
        // Hide pagination when searching
        const paginationContainer = document.getElementById('pagination-container');
        if (paginationContainer) {
            paginationContainer.style.display = 'none';
        }
    } else {
        // If no search, restore the paginated view
        const startIndex = (currentPage - 1) * LOGS_PER_PAGE;
        const endIndex = startIndex + LOGS_PER_PAGE;
        populateTable(allLogs.slice(startIndex, endIndex));
        
        // Show pagination when not searching
        updatePagination();
    }
    
    // Update table rows reference
    table_rows = document.querySelectorAll('tbody tr');
    
    // Update row backgrounds
    document.querySelectorAll('tbody tr:not(.hide)').forEach((visible_row, i) => {
        visible_row.style.backgroundColor = (i % 2 == 0) ? 'transparent' : '#0000000b';
    });
}

function sortTable(column, sort_asc) {
    table_rows = document.querySelectorAll('tbody tr');
    const tbody = document.querySelector('tbody');
    
    [...table_rows].sort((a, b) => {
        const tdsA = a.querySelectorAll('td');
        const tdsB = b.querySelectorAll('td');
        
        if (!tdsA[column] || !tdsB[column]) return 0;
        
        let first_row = tdsA[column].textContent.toLowerCase();
        let second_row = tdsB[column].textContent.toLowerCase();
        
        // Handle numeric sorting for ID column
        if (column === 0) {
            first_row = parseInt(first_row) || 0;
            second_row = parseInt(second_row) || 0;
            return sort_asc ? (first_row - second_row) : (second_row - first_row);
        }
        
        return sort_asc ? (first_row < second_row ? 1 : -1) : (first_row < second_row ? -1 : 1);
    })
    .forEach(sorted_row => tbody.appendChild(sorted_row));
    
    // Update table_rows after sorting
    table_rows = document.querySelectorAll('tbody tr');
}

// 3. Converting HTML table to PDF

const pdf_btn = document.querySelector('#toPDF');
const customers_table = document.querySelector('#customers_table');


const toPDF = function (customers_table) {
    const html_code = `
    <!DOCTYPE html>
    <link rel="stylesheet" type="text/css" href="style.css">
    <main class="table" id="customers_table">${customers_table.innerHTML}</main>`;

    const new_window = window.open();
     new_window.document.write(html_code);

    setTimeout(() => {
        new_window.print();
        new_window.close();
    }, 400);
}

if (pdf_btn) {
    pdf_btn.onclick = () => {
        toPDF(customers_table);
    }
}

// 4. Converting HTML table to JSON

// 5. Converting HTML table to CSV File

const csv_btn = document.querySelector('#toCSV');

const toCSV = function (table) {
    const t_heads = table.querySelectorAll('th'),
        tbody_rows = table.querySelectorAll('tbody tr:not(.hide)');

    const headings = [...t_heads].map(head => {
        let actual_head = head.textContent.trim().split(' ');
        return actual_head.splice(0, actual_head.length - 1).join(' ').toLowerCase();
    }).join(',') + ',' + 'image name';

    const table_data = [...tbody_rows].map(row => {
        const cells = row.querySelectorAll('td');
        const imgElement = row.querySelector('img');
        const img = imgElement ? decodeURIComponent(imgElement.src) : '';
        const data_without_img = [...cells].map(cell => cell.textContent.replace(/,/g, ".").trim()).join(',');

        return data_without_img + ',' + img;
    }).join('\n');

    return headings + '\n' + table_data;
}

if (csv_btn) {
    csv_btn.onclick = () => {
        const csv = toCSV(customers_table);
        downloadFile(csv, 'csv', 'guest logs');
    }
}

// 6. Converting HTML table to EXCEL File

const excel_btn = document.querySelector('#toEXCEL');

const toExcel = function (table) {
    const t_heads = table.querySelectorAll('th'),
        tbody_rows = table.querySelectorAll('tbody tr:not(.hide)');

    const headings = [...t_heads].map(head => {
        let actual_head = head.textContent.trim().split(' ');
        return actual_head.splice(0, actual_head.length - 1).join(' ').toLowerCase();
    }).join('\t') + '\t' + 'image name';

    const table_data = [...tbody_rows].map(row => {
        const cells = row.querySelectorAll('td');
        const imgElement = row.querySelector('img');
        const img = imgElement ? decodeURIComponent(imgElement.src) : '';
        const data_without_img = [...cells].map(cell => cell.textContent.trim()).join('\t');

        return data_without_img + '\t' + img;
    }).join('\n');

    return headings + '\n' + table_data;
}

if (excel_btn) {
    excel_btn.onclick = () => {
        const excel = toExcel(customers_table);
        downloadFile(excel, 'excel');
    }
}

const downloadFile = function (data, fileType, fileName = '') {
    const a = document.createElement('a');
    a.download = fileName;
    const mime_types = {
        'json': 'application/json',
        'csv': 'text/csv',
        'excel': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }
    a.href = `
        data:${mime_types[fileType]};charset=utf-8,${encodeURIComponent(data)}
    `;
    document.body.appendChild(a);
    a.click();
    a.remove();
}

// Load logs when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadLogs();
    
    // Add pagination button event listeners
    const prevBtn = document.getElementById('prev-page-btn');
    const nextBtn = document.getElementById('next-page-btn');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', goToPreviousPage);
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', goToNextPage);
    }
});

