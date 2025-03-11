<?php
require_once '../includes/functions.php';
secureSessionStart();

// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get all quiz attempts
$conn = getDbConnection();
$result = $conn->query("SELECT qa.*, u.name, f.name as field_name, d.name as difficulty_name 
                       FROM quiz_attempts qa 
                       JOIN students u ON qa.user_id = u.id 
                       JOIN fields f ON qa.field_id = f.id 
                       JOIN difficulty_levels d ON qa.difficulty_id = d.id 
                       ORDER BY qa.created_at DESC");

$attempts = [];
while ($row = $result->fetch_assoc()) {
    $attempts[] = $row;
}
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Quiz Results</title>
    <style>
        /* Existing CSS styles remain the same */
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(to right, #1e3c72, #a8c0ff);
            margin: 0;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 1200px;
        }

        .header {
            font-size: 28px;
            font-weight: bold;
            color: #1e3c72;
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #1e3c72;
            margin-bottom: 20px;
        }

        .admin-menu {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
        }

        .admin-menu a {
            background: #e9eef8;
            color: #1e3c72;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .admin-menu a:hover {
            background: #d0d9ec;
        }

        .admin-menu a.active {
            background: #1e3c72;
            color: white;
        }

        .filters {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filters select, .filters input {
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 18%;
            min-width: 150px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #1e3c72;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .btn {
            background: #1e3c72;
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 14px;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn:hover {
            background: #152c56;
        }

        .btn-small {
            font-size: 12px;
            padding: 6px 10px;
        }

        .btn-refresh {
            background: #fcc250;
            color: black;
            font-size: 16px;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-refresh:hover {
            background: #e0a63b;
        }

        .admin-content h2 {
            color: #1e3c72;
            margin-bottom: 15px;
        }

        .status-valid {
            color: green;
            font-weight: bold;
        }

        .status-invalid {
            color: red;
            font-weight: bold;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            width: 80%;
            max-width: 900px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .close-button {
            position: absolute;
            top: 10px;
            right: 20px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-button:hover {
            color: #1e3c72;
        }

        .loading {
            text-align: center;
            padding: 30px;
            font-size: 18px;
            color: #666;
        }

        .error {
            text-align: center;
            padding: 20px;
            color: #e74c3c;
            background-color: #fdecea;
            border-radius: 5px;
            margin: 20px 0;
        }

        /* Results styles for modal */
        .results-summary {
            background-color: #f5f8ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #1e3c72;
        }

        .question-result {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .question-result.correct {
            border-left: 4px solid #2ecc71;
        }

        .question-result.incorrect {
            border-left: 4px solid #e74c3c;
        }

        .question-text {
            font-weight: bold;
            margin-bottom: 15px;
        }

        .options {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .option {
            padding: 10px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .option-label {
            font-weight: bold;
            margin-right: 5px;
        }

        .correct-answer {
            background-color: rgba(46, 204, 113, 0.2);
            border: 1px solid #2ecc71;
        }

        .wrong-answer {
            background-color: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
        }

        .user-choice {
            font-style: italic;
            margin-left: 10px;
            color: #7f8c8d;
        }

        .invalid-message {
            color: #e74c3c;
            font-weight: bold;
        }
        
        /* Export button styles */
        .actions-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .btn-export {
            background: #27ae60;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-export:hover {
            background: #219653;
        }
        
        .btn-export svg {
            width: 16px;
            height: 16px;
        }
        
        /* Pagination styles */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 25px;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .pagination button {
            background: #e9eef8;
            color: #1e3c72;
            border: 1px solid #1e3c72;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .pagination button:hover {
            background: #d0d9ec;
        }
        
        .pagination button.active {
            background: #1e3c72;
            color: white;
        }
        
        .pagination button:disabled {
            background: #f5f5f5;
            color: #999;
            border-color: #ddd;
            cursor: not-allowed;
        }
        
        .pagination-info {
            text-align: center;
            margin-top: 10px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">Admin Dashboard - Quiz Results</div>
        
        <div class="admin-menu">
            <a href="index.php" class="active">View Results</a>
            <a href="view-users.php">View Users</a>
            <a href="add-question.php">Add Question</a>
            <a href="question-bank.php">Question Bank</a>
            <a href="../logout.php">Logout</a>
        </div>
        
        <div class="filters">
            <select id="fieldFilter">
                <option value="">Filter by Field</option>
                <?php
                // Get unique fields from attempts
                $fields = array_unique(array_column($attempts, 'field_name'));
                foreach ($fields as $field) {
                    echo "<option value=\"" . htmlspecialchars($field) . "\">" . htmlspecialchars($field) . "</option>";
                }
                ?>
            </select>
            
            <select id="difficultyFilter">
                <option value="">Filter by Difficulty</option>
                <?php
                // Get unique difficulty levels
                $difficulties = array_unique(array_column($attempts, 'difficulty_name'));
                foreach ($difficulties as $difficulty) {
                    echo "<option value=\"" . htmlspecialchars($difficulty) . "\">" . htmlspecialchars($difficulty) . "</option>";
                }
                ?>
            </select>
            
            <input type="text" id="nameFilter" placeholder="Filter by Name">
            
            <input type="date" id="dateFilter">
            
            <select id="statusFilter">
                <option value="">Filter by Status</option>
                <option value="Valid">Valid</option>
                <option value="Invalid">Invalid</option>
            </select>
        </div>
        
        <!-- Actions container with Export Button -->
        <div class="actions-container">
            <div>
                <select id="rowsPerPage">
                    <option value="10" selected>10 rows per page</option>
                    <option value="20">20 rows per page</option>
                    <option value="50">50 rows per page</option>
                    <option value="100">100 rows per page</option>
                </select>
            </div>
            <button id="exportButton" class="btn-export">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Export to Excel
            </button>
        </div>

        <div class="table-container">
            <table id="resultsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Field</th>
                        <th>Difficulty</th>
                        <th>Score</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attempts)): ?>
                        <tr>
                            <td colspan="9">No quiz attempts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attempts as $attempt): ?>
                            <tr>
                                <td><?php echo $attempt['id']; ?></td>
                                <td><?php echo htmlspecialchars($attempt['name']); ?></td>
                                <td><?php echo htmlspecialchars($attempt['field_name']); ?></td>
                                <td><?php echo htmlspecialchars($attempt['difficulty_name']); ?></td>
                                <td><?php echo $attempt['score']; ?></td>
                                <td><?php echo $attempt['total_questions']; ?></td>
                                <td class="<?php echo $attempt['is_valid'] ? 'status-valid' : 'status-invalid'; ?>">
                                    <?php echo $attempt['is_valid'] ? 'Valid' : 'Invalid'; ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($attempt['created_at'])); ?></td>
                                <td>
                                    <button onclick="viewAttemptDetails(<?php echo $attempt['id']; ?>)" class="btn btn-small">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination controls -->
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination" id="pagination"></div>
        
        <div id="resultsModal" class="modal">
            <div class="modal-content">
                <div id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>

    </div>

    <!-- SheetJS library for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
    // Global variables for pagination
    let currentPage = 1;
    let rowsPerPage = 10;
    let filteredRows = [];
    
    // Function to apply filters
    function applyFilters() {
        let field = document.getElementById("fieldFilter").value.toLowerCase();
        let difficulty = document.getElementById("difficultyFilter").value.toLowerCase();
        let name = document.getElementById("nameFilter").value.toLowerCase();
        let date = document.getElementById("dateFilter").value;
        let status = document.getElementById("statusFilter").value;

        let table = document.getElementById("resultsTable");
        let rows = table.getElementsByTagName("tr");
        
        // Reset filtered rows array
        filteredRows = [];
        
        // Skip header row (index 0)
        for (let i = 1; i < rows.length; i++) {
            let cells = rows[i].getElementsByTagName("td");
            if (cells.length === 0) continue; // Skip if no cells
            
            let showRow = true;

            let studentName = cells[1].textContent.toLowerCase();
            let studentField = cells[2].textContent.toLowerCase();
            let studentDifficulty = cells[3].textContent.toLowerCase();
            let studentStatus = cells[6].textContent.trim();
            let studentDate = cells[7].textContent.split(" ")[0]; // Get just the date part

            if (field && studentField !== field) showRow = false;
            if (difficulty && studentDifficulty !== difficulty) showRow = false;
            if (name && !studentName.includes(name)) showRow = false;
            if (date && studentDate !== date) showRow = false;
            if (status && studentStatus !== status) showRow = false;

            // Initially hide all rows - we'll show them based on pagination
            rows[i].style.display = "none";
            
            // Add visible rows to our filtered array
            if (showRow) {
                filteredRows.push(rows[i]);
            }
        }
        
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
        
        // Extract headers (excluding the "Actions" column)
        for (let i = 0; i < headerRow.cells.length - 1; i++) {
            headers.push(headerRow.cells[i].textContent);
        }
        
        // Add headers to data array
        data.push(headers);
        
        // Add all filtered rows (not just current page)
        filteredRows.forEach(row => {
            const rowData = [];
            const cells = row.getElementsByTagName("td");
            
            // Extract cell data (excluding the "Actions" column)
            for (let j = 0; j < cells.length - 1; j++) {
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

    // Function to close modal
    function closeModal() {
        document.getElementById('resultsModal').style.display = 'none';
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        let modal = document.getElementById('resultsModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // Function to handle rows per page change
    function handleRowsPerPageChange() {
        rowsPerPage = parseInt(document.getElementById("rowsPerPage").value);
        currentPage = 1; // Reset to first page
        updatePagination();
        displayRows();
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
    });
</script>
</body>
</html>