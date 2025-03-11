// Global variables for pagination
let currentPage = 1;
let rowsPerPage = 10;
let filteredRows = [];
let selectedStudents = [];

// Function to apply filters
// Function to apply filters
function applyFilters() {
    let field = document.getElementById("fieldFilter").value.toLowerCase();
    let difficulty = document.getElementById("difficultyFilter").value.toLowerCase();
    let name = document.getElementById("nameFilter").value.toLowerCase();
    let date = document.getElementById("dateFilter").value;
    let status = document.getElementById("statusFilter").value;

    let table = document.getElementById("resultsTable");
    let rows = table.querySelectorAll("tbody tr");
    
    // Reset filtered rows array
    filteredRows = [];
    
    // Process each row
    rows.forEach(row => {
        let showRow = true;
        
        // Use data attributes instead of cell content for more reliable filtering
        if (row.hasAttribute('data-student-name') && 
            row.hasAttribute('data-student-field') && 
            row.hasAttribute('data-student-difficulty')) {
            
            let studentName = row.getAttribute('data-student-name').toLowerCase();
            let studentField = row.getAttribute('data-student-field').toLowerCase();
            let studentDifficulty = row.getAttribute('data-student-difficulty').toLowerCase();
            
            // Get status from the appropriate cell
            let statusCell = row.querySelector('td.status-valid, td.status-invalid');
            let studentStatus = statusCell ? statusCell.textContent.trim() : '';
            
            // Get date from the date cell (assuming it's the second-to-last cell before Actions)
            let cells = row.querySelectorAll('td');
            let dateCell = cells[cells.length - 2];
            let studentDate = dateCell ? dateCell.textContent.split(" ")[0] : '';
            
            // Apply filters
            if (field && !studentField.includes(field)) showRow = false;
            if (difficulty && !studentDifficulty.includes(difficulty)) showRow = false;
            if (name && !studentName.includes(name)) showRow = false;
            if (date && studentDate !== date) showRow = false;
            if (status && studentStatus !== status) showRow = false;
        }
        
        // Initially hide all rows - we'll show them based on pagination
        row.style.display = "none";
        
        // Add visible rows to our filtered array
        if (showRow) {
            filteredRows.push(row);
        }
    });
    
    // Reset to first page when filters change
    currentPage = 1;
    
    // Update pagination and display rows
    updatePagination();
    displayRows();
}

// Function to update pagination controls
function updatePagination() {
const totalRows = filteredRows.length;
const totalPages = Math.ceil(totalRows / rowsPerPage);

// Update pagination info text
const infoElem = document.getElementById("paginationInfo");
if (totalRows === 0) {
    infoElem.textContent = "No results found";
} else {
    const startRow = (currentPage - 1) * rowsPerPage + 1;
    const endRow = Math.min(currentPage * rowsPerPage, totalRows);
    infoElem.textContent = `Showing ${startRow} to ${endRow} of ${totalRows} entries`;
}

// Create pagination buttons
const paginationElem = document.getElementById("pagination");
paginationElem.innerHTML = "";

// Only show pagination if we have results
if (totalRows > 0) {
    // Previous button
    const prevBtn = document.createElement("button");
    prevBtn.innerHTML = "&laquo;";
    prevBtn.disabled = currentPage === 1;
    prevBtn.addEventListener("click", () => {
        if (currentPage > 1) {
            currentPage--;
            displayRows();
            updatePagination();
        }
    });
    paginationElem.appendChild(prevBtn);
    
    // Page buttons
    // Determine range of page buttons to show
    const maxVisibleButtons = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisibleButtons / 2));
    let endPage = Math.min(totalPages, startPage + maxVisibleButtons - 1);
    
    // Adjust if we're near the end
    if (endPage - startPage + 1 < maxVisibleButtons && startPage > 1) {
        startPage = Math.max(1, endPage - maxVisibleButtons + 1);
    }
    
    // First page button if we're not starting from page 1
    if (startPage > 1) {
        const firstBtn = document.createElement("button");
        firstBtn.textContent = "1";
        firstBtn.addEventListener("click", () => {
            currentPage = 1;
            displayRows();
            updatePagination();
        });
        paginationElem.appendChild(firstBtn);
        
        // Ellipsis if needed
        if (startPage > 2) {
            const ellipsis = document.createElement("button");
            ellipsis.textContent = "...";
            ellipsis.disabled = true;
            paginationElem.appendChild(ellipsis);
        }
    }
    
    // Page number buttons
    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement("button");
        pageBtn.textContent = i;
        if (i === currentPage) {
            pageBtn.classList.add("active");
        }
        pageBtn.addEventListener("click", () => {
            currentPage = i;
            displayRows();
            updatePagination();
        });
        paginationElem.appendChild(pageBtn);
    }
    
    // Ellipsis and last page if needed
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ellipsis = document.createElement("button");
            ellipsis.textContent = "...";
            ellipsis.disabled = true;
            paginationElem.appendChild(ellipsis);
        }
        
        const lastBtn = document.createElement("button");
        lastBtn.textContent = totalPages;
        lastBtn.addEventListener("click", () => {
            currentPage = totalPages;
            displayRows();
            updatePagination();
        });
        paginationElem.appendChild(lastBtn);
    }
    
    // Next button
    const nextBtn = document.createElement("button");
    nextBtn.innerHTML = "&raquo;";
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.addEventListener("click", () => {
        if (currentPage < totalPages) {
            currentPage++;
            displayRows();
            updatePagination();
        }
    });
    paginationElem.appendChild(nextBtn);
}
}

// Function to display the current page of rows
function displayRows() {
// Hide all rows first
filteredRows.forEach(row => {
    row.style.display = "none";
});

// Calculate start and end indexes for current page
const startIndex = (currentPage - 1) * rowsPerPage;
const endIndex = Math.min(startIndex + rowsPerPage, filteredRows.length);

// Show only the rows for current page
for (let i = startIndex; i < endIndex; i++) {
    filteredRows[i].style.display = "";
}
}

// Function to export filtered table data to Excel
function exportToExcel() {
// Create a new workbook
const wb = XLSX.utils.book_new();

// Create an array to hold the data
const data = [];

// Get the header row
const table = document.getElementById("resultsTable");
const headerRow = table.getElementsByTagName("tr")[0];
const headers = [];

// Extract headers (excluding the "Actions" column and checkbox if present)
const checkboxVisible = document.querySelector('.checkbox-cell').style.display !== 'none';
const startIdx = checkboxVisible ? 1 : 0;

for (let i = startIdx; i < headerRow.cells.length - 1; i++) {
    headers.push(headerRow.cells[i].textContent);
}

// Add headers to data array
data.push(headers);

// Add all filtered rows (not just current page)
filteredRows.forEach(row => {
    const rowData = [];
    const cells = row.getElementsByTagName("td");
    
    // Extract cell data (excluding the checkbox and "Actions" column)
    for (let j = startIdx; j < cells.length - 1; j++) {
        rowData.push(cells[j].textContent.trim());
    }
    
    // Add row data to main data array
    data.push(rowData);
});

// Create worksheet from data
const ws = XLSX.utils.aoa_to_sheet(data);

// Add worksheet to workbook
XLSX.utils.book_append_sheet(wb, ws, "Quiz Results");

// Generate Excel file
const today = new Date();
const dateStr = today.toISOString().split('T')[0];
const filename = `quiz_results_${dateStr}.xlsx`;

// Write and download the file
XLSX.writeFile(wb, filename);
}

function viewAttemptDetails(attemptId) {
// Show modal and loading message
document.getElementById('resultsModal').style.display = 'block';
document.getElementById('modalContent').innerHTML = '<div class="loading">Loading...</div>';

// Fetch results data via AJAX
fetch(`results.php?attempt_id=${attemptId}`)
    .then(response => {
        return response.text();
    })
    .then(data => {
        document.getElementById('modalContent').innerHTML = data;
    })
    .catch(error => {
        document.getElementById('modalContent').innerHTML = `<div class="error">Error loading data: ${error.message}</div>`;
    });
}

// Function to reset the email form
function resetEmailForm() {
// Reset subject and content
document.getElementById('emailSubject').value = '';
document.getElementById('emailContent').innerHTML = '';

// Reset template selection
document.getElementById('emailTemplate').value = '';

// Hide schedule container if visible
document.getElementById('scheduleContainer').style.display = 'none';

// Reset schedule date if it exists
if (document.getElementById('scheduleDate')) {
    document.getElementById('scheduleDate').value = '';
}

// Clear any response messages
document.getElementById('emailResponse').style.display = 'none';
document.getElementById('emailResponse').innerHTML = '';
}

// Function to close modal
function closeModal(modalId) {
document.getElementById(modalId).style.display = 'none';

// Reset the email form if closing the email modal
if (modalId === 'emailModal') {
    resetEmailForm();
}
}

// Close modal when clicking outside of it
window.onclick = function(event) {
const modals = document.getElementsByClassName('modal');
for (let i = 0; i < modals.length; i++) {
    if (event.target == modals[i]) {
        modals[i].style.display = 'none';
        
        // Reset the email form if closing the email modal
        if (modals[i].id === 'emailModal') {
            resetEmailForm();
        }
    }
}
}

// Function to handle rows per page change
function handleRowsPerPageChange() {
rowsPerPage = parseInt(document.getElementById("rowsPerPage").value);
currentPage = 1; // Reset to first page
updatePagination();
displayRows();
}

// Toggle selection mode
function toggleSelectionMode() {
const checkboxCells = document.querySelectorAll('.checkbox-cell');
const selectAllContainer = document.getElementById('selectAllContainer');

if (document.getElementById('selectionModeToggle').checked) {
    // Show checkboxes
    checkboxCells.forEach(cell => {
        cell.style.display = '';
    });
    selectAllContainer.style.display = '';
} else {
    // Hide checkboxes
    checkboxCells.forEach(cell => {
        cell.style.display = 'none';
    });
    selectAllContainer.style.display = 'none';
    
    // Clear selections
    selectedStudents = [];
    document.querySelectorAll('.student-select').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('selectAll').checked = false;
}
}

// Handle select all checkbox
function handleSelectAll() {
const isChecked = document.getElementById('selectAll').checked;
const checkboxes = document.querySelectorAll('.student-select');

checkboxes.forEach(checkbox => {
    // Only affect visible rows (current page)
    const row = checkbox.closest('tr');
    if (row.style.display !== 'none') {
        checkbox.checked = isChecked;
        handleStudentSelection(checkbox);
    }
});
}

// Handle individual student selection
function handleStudentSelection(checkbox) {
const row = checkbox.closest('tr');
const studentId = checkbox.value;
const studentName = row.getAttribute('data-student-name');
const studentEmail = row.getAttribute('data-student-email');
const studentScore = row.getAttribute('data-student-score');
const studentTotal = row.getAttribute('data-student-total');
const studentField = row.getAttribute('data-student-field');
const studentDifficulty = row.getAttribute('data-student-difficulty');

if (checkbox.checked) {
    // Add to selected students if not already there
    if (!selectedStudents.some(student => student.id === studentId)) {
        selectedStudents.push({
            id: studentId,
            name: studentName,
            email: studentEmail,
            score: studentScore,
            total: studentTotal,
            field: studentField,
            difficulty: studentDifficulty
        });
    }
} else {
    // Remove from selected students
    selectedStudents = selectedStudents.filter(student => student.id !== studentId);
}
}

// Open email modal with selected students
function openEmailModal() {
if (selectedStudents.length === 0) {
    alert('Please select at least one student to email.');
    return;
}

// Populate recipients list
const recipientsList = document.getElementById('recipientsList');
recipientsList.innerHTML = '';

selectedStudents.forEach(student => {
    const recipientItem = document.createElement('div');
    recipientItem.className = 'recipient-item';
    recipientItem.innerHTML = `
        <span>${student.name} (${student.email})</span>
        <span class="remove-recipient" data-id="${student.id}">&times;</span>
    `;
    recipientsList.appendChild(recipientItem);
});

// Show the modal
document.getElementById('emailModal').style.display = 'block';

// Add event listeners to remove buttons
document.querySelectorAll('.remove-recipient').forEach(button => {
    button.addEventListener('click', function() {
        const studentId = this.getAttribute('data-id');
        selectedStudents = selectedStudents.filter(student => student.id !== studentId);
        this.closest('.recipient-item').remove();
        
        // If no recipients left, close the modal
        if (selectedStudents.length === 0) {
            closeModal('emailModal');
        }
    });
});
}

// WYSIWYG Editor functions
function execCommand(command, value = null) {
document.execCommand(command, false, value);
document.getElementById('emailContent').focus();
}

// Insert placeholder into editor
function insertPlaceholder(placeholder) {
const selection = window.getSelection();
const range = selection.getRangeAt(0);
const span = document.createElement('span');
span.className = 'placeholder-tag';
span.textContent = placeholder;
range.deleteContents();
range.insertNode(span);

// Move cursor after the inserted placeholder
range.setStartAfter(span);
range.setEndAfter(span);
selection.removeAllRanges();
selection.addRange(range);
}

// Toggle schedule container
function toggleScheduleContainer() {
const container = document.getElementById('scheduleContainer');
if (container.style.display === 'none' || container.style.display === '') {
    container.style.display = 'block';
} else {
    container.style.display = 'none';
}
}

// Load email template
function loadEmailTemplate() {
const templateId = document.getElementById('emailTemplate').value;
if (!templateId) return;

// Fetch template data via AJAX
fetch(`email/get-template.php?id=${templateId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('emailSubject').value = data.template.subject;
            document.getElementById('emailContent').innerHTML = data.template.content;
        } else {
            alert('Error loading template: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error loading template: ' + error.message);
    });
}

// Open save template modal
function openSaveTemplateModal() {
document.getElementById('saveTemplateModal').style.display = 'block';
}

// Save email template
function saveEmailTemplate() {
const templateName = document.getElementById('templateName').value;
const subject = document.getElementById('emailSubject').value;
const content = document.getElementById('emailContent').innerHTML;

if (!templateName || !subject || !content) {
    alert('Please fill in all fields');
    return;
}

// Send data via AJAX
fetch('email/save-template.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        name: templateName,
        subject: subject,
        content: content
    }),
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert('Template saved successfully!');
        closeModal('saveTemplateModal');
        
        // Add to template dropdown
        const option = document.createElement('option');
        option.value = data.template_id;
        option.textContent = templateName;
        document.getElementById('emailTemplate').appendChild(option);
    } else {
        alert('Error saving template: ' + data.message);
    }
})
.catch(error => {
    alert('Error saving template: ' + error.message);
});
}

// Send email now
function sendEmailNow() {
sendEmail(false);
}

// Schedule email
function scheduleEmail() {
const scheduleDate = document.getElementById('scheduleDate').value;
if (!scheduleDate) {
    alert('Please select a date and time to schedule the email');
    return;
}

sendEmail(true, scheduleDate);
}

// Send email function
function sendEmail(isScheduled = false, scheduleDate = null) {
const subject = document.getElementById('emailSubject').value;
const content = document.getElementById('emailContent').innerHTML;

if (!subject || !content) {
    alert('Please fill in all required fields');
    return;
}

// Prepare data
const emailData = {
    recipients: selectedStudents,
    subject: subject,
    content: content,
    isScheduled: isScheduled,
    scheduleDate: scheduleDate
};

// Show loading
const responseDiv = document.getElementById('emailResponse');
responseDiv.innerHTML = '<div class="loading">Sending emails...</div>';
responseDiv.style.display = 'block';

// Send data via AJAX
fetch('send-email.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify(emailData),
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        responseDiv.innerHTML = `<div class="success-message">${data.message}</div>`;
        
        // Clear form after successful send
        setTimeout(() => {
            closeModal('emailModal');
            responseDiv.style.display = 'none';
            
            // Clear selections
            selectedStudents = [];
            document.querySelectorAll('.student-select').forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('selectAll').checked = false;
        }, 2000);
    } else {
        responseDiv.innerHTML = `<div class="error-message">${data.message}</div>`;
    }
})
.catch(error => {
    responseDiv.innerHTML = `<div class="error-message">Error sending email: ${error.message}</div>`;
});
}

// Open SMTP configuration modal
function openSmtpModal() {
// Fetch current SMTP settings
fetch('email/get-smtp-config.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('smtpHost').value = data.config.host || '';
            document.getElementById('smtpPort').value = data.config.port || '587';
            document.getElementById('smtpUsername').value = data.config.username || '';
            document.getElementById('smtpPassword').value = data.config.password || '';
            document.getElementById('smtpFrom').value = data.config.from_email || '';
            document.getElementById('smtpFromName').value = data.config.from_name || '';
            document.getElementById('smtpEncryption').value = data.config.encryption || 'tls';
        }
    })
    .catch(error => {
        console.error('Error fetching SMTP config:', error);
    });

document.getElementById('smtpModal').style.display = 'block';
}

// Test SMTP connection
function testSmtpConnection() {
const smtpData = {
    host: document.getElementById('smtpHost').value,
    port: document.getElementById('smtpPort').value,
    username: document.getElementById('smtpUsername').value,
    password: document.getElementById('smtpPassword').value,
    from_email: document.getElementById('smtpFrom').value,
    from_name: document.getElementById('smtpFromName').value,
    encryption: document.getElementById('smtpEncryption').value
};

// Show loading
const responseDiv = document.getElementById('smtpResponse');
responseDiv.innerHTML = '<div class="loading">Testing connection...</div>';
responseDiv.style.display = 'block';

// Send test request
fetch('email/test-smtp.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify(smtpData),
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        responseDiv.innerHTML = `<div class="success-message">${data.message}</div>`;
    } else {
        responseDiv.innerHTML = `<div class="error-message">${data.message}</div>`;
    }
})
.catch(error => {
    responseDiv.innerHTML = `<div class="error-message">Error testing connection: ${error.message}</div>`;
});
}

// Save SMTP configuration
function saveSmtpConfig() {
const smtpData = {
    host: document.getElementById('smtpHost').value,
    port: document.getElementById('smtpPort').value,
    username: document.getElementById('smtpUsername').value,
    password: document.getElementById('smtpPassword').value,
    from_email: document.getElementById('smtpFrom').value,
    from_name: document.getElementById('smtpFromName').value,
    encryption: document.getElementById('smtpEncryption').value
};

// Show loading
const responseDiv = document.getElementById('smtpResponse');
responseDiv.innerHTML = '<div class="loading">Saving configuration...</div>';
responseDiv.style.display = 'block';

// Send save request
fetch('email/save-smtp-config.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify(smtpData),
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        responseDiv.innerHTML = `<div class="success-message">${data.message}</div>`;
        
        // Close modal after successful save
        setTimeout(() => {
            closeModal('smtpModal');
            responseDiv.style.display = 'none';
        }, 2000);
    } else {
        responseDiv.innerHTML = `<div class="error-message">${data.message}</div>`;
    }
})
.catch(error => {
    responseDiv.innerHTML = `<div class="error-message">Error saving configuration: ${error.message}</div>`;
});
}

// Add event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
// Initialize pagination
applyFilters();

// Add export button event listener
document.getElementById('exportButton').addEventListener('click', exportToExcel);

// Add rows per page change handler
document.getElementById('rowsPerPage').addEventListener('change', handleRowsPerPageChange);

// Get all filter elements
const filterElements = [
    document.getElementById("fieldFilter"),
    document.getElementById("difficultyFilter"),
    document.getElementById("nameFilter"),
    document.getElementById("dateFilter"),
    document.getElementById("statusFilter")
];

// Add event listeners to each filter
filterElements.forEach(element => {
    if(element) {
        if(element.tagName === "INPUT" && element.type === "text") {
            // For text inputs, use input event for real-time filtering
            element.addEventListener('input', applyFilters);
        } else {
            // For dropdowns and date inputs, use change event
            element.addEventListener('change', applyFilters);
        }
    }
});

// Selection mode toggle
document.getElementById('selectionModeToggle').addEventListener('change', toggleSelectionMode);

// Select all checkbox
document.getElementById('selectAll').addEventListener('change', handleSelectAll);

// Student selection checkboxes
document.querySelectorAll('.student-select').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        handleStudentSelection(this);
    });
});

// Email button
document.getElementById('emailButton').addEventListener('click', openEmailModal);

// WYSIWYG editor buttons
document.querySelectorAll('.wysiwyg-toolbar button').forEach(button => {
    button.addEventListener('click', function() {
        const command = this.getAttribute('data-command');
        
        if (command === 'createLink') {
            const url = prompt('Enter the link URL:');
            if (url) execCommand(command, url);
        } else {
            execCommand(command);
        }
    });
});

// Placeholder tags
document.querySelectorAll('.placeholder-tag').forEach(tag => {
    tag.addEventListener('click', function() {
        insertPlaceholder(this.getAttribute('data-placeholder'));
    });
});

// Email template selection
document.getElementById('emailTemplate').addEventListener('change', loadEmailTemplate);

// Save template button
document.getElementById('saveTemplateBtn').addEventListener('click', openSaveTemplateModal);
document.getElementById('confirmSaveTemplateBtn').addEventListener('click', saveEmailTemplate);

// Send now button
document.getElementById('sendNowBtn').addEventListener('click', sendEmailNow);

// Schedule buttons
document.getElementById('scheduleBtn').addEventListener('click', toggleScheduleContainer);
document.getElementById('confirmScheduleBtn').addEventListener('click', scheduleEmail);

// SMTP configuration
// Add a menu item for SMTP config
const adminMenu = document.querySelector('.admin-menu');
const smtpConfigLink = document.createElement('a');
smtpConfigLink.href = '#';
smtpConfigLink.textContent = 'SMTP Config';
smtpConfigLink.addEventListener('click', function(e) {
    e.preventDefault();
    openSmtpModal();
});
adminMenu.appendChild(smtpConfigLink);

// SMTP modal buttons
document.getElementById('testSmtpBtn').addEventListener('click', testSmtpConnection);
document.getElementById('saveSmtpBtn').addEventListener('click', saveSmtpConfig);
});