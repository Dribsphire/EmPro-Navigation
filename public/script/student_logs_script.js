// Global variables
let table_rows = [];
let table_headings = [];
let search = null;

// Load logs from API
async function loadLogs() {
    try {
        const response = await fetch('../../api/get_student_logs.php');
        const data = await response.json();
        
        if (data.status === 'success' && data.logs) {
            populateTable(data.logs);
            initializeTableFunctions();
        } else {
            showErrorMessage('Failed to load logs');
        }
    } catch (error) {
        console.error('Error loading logs:', error);
        showErrorMessage('Error loading logs. Please refresh the page.');
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
        
        row.innerHTML = `
            <td>${index + 1}</td>
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
    
    const search_data = search.value.toLowerCase();
    table_rows = document.querySelectorAll('tbody tr');
    
    table_rows.forEach((row, i) => {
        let table_data = row.textContent.toLowerCase();
        row.classList.toggle('hide', table_data.indexOf(search_data) < 0);
        row.style.setProperty('--delay', i / 25 + 's');
    });

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

pdf_btn.onclick = () => {
    toPDF(customers_table);
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

csv_btn.onclick = () => {
    const csv = toCSV(customers_table);
    downloadFile(csv, 'csv', 'customer orders');
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
});