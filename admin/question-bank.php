<?php
require_once '../includes/functions.php';
secureSessionStart();

// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get fields and difficulty levels for filtering
$fields = [];
$conn = getDbConnection();
$result = $conn->query("SELECT f.*, d.name as domain_name FROM fields f JOIN domains d ON f.domain_id = d.id ORDER BY d.name, f.name");
while ($row = $result->fetch_assoc()) {
    $fields[] = $row;
}

$difficultyLevels = getDifficultyLevels();

// Handle search/filter
$fieldId = isset($_GET['field_id']) ? (int)$_GET['field_id'] : 0;
$difficultyId = isset($_GET['difficulty_id']) ? (int)$_GET['difficulty_id'] : 0;
$searchQuery = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query - fetch ALL questions first
$query = "SELECT q.*, f.name as field_name, d.name as difficulty_name 
          FROM questions q 
          JOIN fields f ON q.field_id = f.id 
          JOIN difficulty_levels d ON q.difficulty_id = d.id 
          ORDER BY q.id DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$questions = [];
while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}

$stmt->close();

// Handle delete
$success = false;
$error = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && isset($_POST['question_id'])) {
    $questionId = (int)$_POST['question_id'];
    
    // Debug statement
    error_log("Delete requested for question ID: " . $questionId);
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // First, delete related records in user_answers
        $stmt = $conn->prepare("DELETE FROM user_answers WHERE question_id = ?");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        
        // Then delete the question
        $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->bind_param("i", $questionId);
        
        if ($stmt->execute()) {
            $success = true;
            $conn->commit();
            
            // Refresh questions list by redirecting
            header("Location: question-bank.php?deleted=1");
            exit;
        } else {
            throw new Exception("Failed to delete question: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        error_log("Delete error: " . $error);
    }
}
// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update']) && isset($_POST['edit_question_id'])) {
    $questionId = (int)$_POST['edit_question_id'];
    $questionText = sanitizeInput($_POST['edit_question_text']);
    $fieldId = (int)$_POST['edit_field_id'];
    $difficultyId = (int)$_POST['edit_difficulty_id'];
    $optionA = sanitizeInput($_POST['edit_option_a']);
    $optionB = sanitizeInput($_POST['edit_option_b']);
    $optionC = sanitizeInput($_POST['edit_option_c']);
    $optionD = sanitizeInput($_POST['edit_option_d']);
    $correctAnswer = sanitizeInput($_POST['edit_correct_answer']);
    
    // Update question
    $stmt = $conn->prepare("UPDATE questions SET question_text = ?, field_id = ?, difficulty_id = ?, 
                          option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_answer = ? 
                          WHERE id = ?");
    $stmt->bind_param("siisssssi", $questionText, $fieldId, $difficultyId, $optionA, $optionB, $optionC, $optionD, $correctAnswer, $questionId);
    
    if ($stmt->execute()) {
        $success = true;
        
        // Refresh questions list
        header("Location: question-bank.php?updated=1");
        exit;
    } else {
        $error = "Failed to update question: " . $conn->error;
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Question Bank</title>
    <style>
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

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .error-container {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #1e3c72;
            font-weight: bold;
        }

        .filter-group select, 
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn {
            background: #1e3c72;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
            border: none;
            cursor: pointer;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #152c56;
        }

        .btn-primary {
            background: #1e3c72;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #bd2130;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-info {
            background: #17a2b8;
        }

        .btn-info:hover {
            background: #138496;
        }

        .btn-small {
            font-size: 12px;
            padding: 6px 10px;
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
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #1e3c72;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
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
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            max-width: 700px;
            width: 90%;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-content h3 {
            color: #1e3c72;
            margin-top: 0;
        }

        #questionText {
            font-style: italic;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .no-results {
            text-align: center;
            padding: 20px;
            font-size: 16px;
            color: #666;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Admin Dashboard - Question Bank</div>
        
        <div class="admin-menu">
            <a href="index.php">View Results</a>
            <a href="view-users.php">View Users</a>
            <a href="add-question.php">Add Question</a>
            <a href="question-bank.php" class="active">Question Bank</a>
            <a href="../logout.php">Logout</a>
        </div>
        
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
            <div class="success-message">
                <p>Question deleted successfully!</p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
            <div class="success-message">
                <p>Question updated successfully!</p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error-container">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="filter-section">
            <div class="filter-group">
                <label for="field_id">Field</label>
                <select id="field_id" class="filter-control">
                    <option value="0">All Fields</option>
                    <?php foreach ($fields as $field): ?>
                        <option value="<?php echo $field['id']; ?>" <?php echo $fieldId == $field['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($field['domain_name'] . ' - ' . $field['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="difficulty_id">Difficulty</label>
                <select id="difficulty_id" class="filter-control">
                    <option value="0">All Difficulties</option>
                    <?php foreach ($difficultyLevels as $level): ?>
                        <option value="<?php echo $level['id']; ?>" <?php echo $difficultyId == $level['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($level['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="search">Search</label>
                <input type="text" id="search" class="filter-control" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search questions...">
            </div>
        </div>
        
        <div class="table-container">
            <table id="questions-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Question</th>
                        <th>Field</th>
                        <th>Difficulty</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $question): ?>
                        <tr 
                            data-field-id="<?php echo $question['field_id']; ?>" 
                            data-difficulty-id="<?php echo $question['difficulty_id']; ?>" 
                            data-question-text="<?php echo htmlspecialchars($question['question_text']); ?>" 
                            data-option-a="<?php echo htmlspecialchars($question['option_a'] ?? ''); ?>"
                            data-option-b="<?php echo htmlspecialchars($question['option_b'] ?? ''); ?>"
                            data-option-c="<?php echo htmlspecialchars($question['option_c'] ?? ''); ?>"
                            data-option-d="<?php echo htmlspecialchars($question['option_d'] ?? ''); ?>"
                            data-correct-answer="<?php echo htmlspecialchars($question['correct_answer'] ?? ''); ?>"
                        >
                            <td><?php echo $question['id']; ?></td>
                            <td><?php echo htmlspecialchars(substr($question['question_text'], 0, 100) . (strlen($question['question_text']) > 100 ? '...' : '')); ?></td>
                            <td><?php echo htmlspecialchars($question['field_name']); ?></td>
                            <td><?php echo htmlspecialchars($question['difficulty_name']); ?></td>
                            <td class="action-buttons">
                                <button type="button" class="btn btn-small btn-info edit-btn"
                                        data-question-id="<?php echo $question['id']; ?>">
                                    Edit
                                </button>
                                <button type="button" class="btn btn-small btn-danger delete-btn"
                                        data-question-id="<?php echo $question['id']; ?>">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="no-results" class="no-results" style="display: none;">
                <p>No questions found.</p>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <h3>Confirm Delete</h3>
                <p>Are you sure you want to delete this question?</p>
                <p id="questionText"></p>
                <form method="POST" action="question-bank.php">
                    <input type="hidden" id="question_id" name="question_id" value="">
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                        <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Question Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <h3>Edit Question</h3>
                <form method="POST" action="question-bank.php">
                    <input type="hidden" id="edit_question_id" name="edit_question_id" value="">
                    
                    <div class="form-group">
                        <label for="edit_question_text">Question Text</label>
                        <textarea id="edit_question_text" name="edit_question_text" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_field_id">Field</label>
                        <select id="edit_field_id" name="edit_field_id" required>
                            <?php foreach ($fields as $field): ?>
                                <option value="<?php echo $field['id']; ?>">
                                    <?php echo htmlspecialchars($field['domain_name'] . ' - ' . $field['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_difficulty_id">Difficulty</label>
                        <select id="edit_difficulty_id" name="edit_difficulty_id" required>
                            <?php foreach ($difficultyLevels as $level): ?>
                                <option value="<?php echo $level['id']; ?>">
                                    <?php echo htmlspecialchars($level['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_option_a">Option A</label>
                        <input type="text" id="edit_option_a" name="edit_option_a" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_option_b">Option B</label>
                        <input type="text" id="edit_option_b" name="edit_option_b" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_option_c">Option C</label>
                        <input type="text" id="edit_option_c" name="edit_option_c" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_option_d">Option D</label>
                        <input type="text" id="edit_option_d" name="edit_option_d" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_correct_answer">Correct Answer</label>
                        <select id="edit_correct_answer" name="edit_correct_answer" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                        <button type="submit" name="update" class="btn btn-primary">Update Question</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get all filter elements
            const fieldSelect = document.getElementById('field_id');
            const difficultySelect = document.getElementById('difficulty_id');
            const searchInput = document.getElementById('search');
            const tableBody = document.querySelector('#questions-table tbody');
            const noResultsDiv = document.getElementById('no-results');
            const table = document.getElementById('questions-table');
            
            // Store all original rows for filtering
            const allRows = Array.from(tableBody.querySelectorAll('tr'));
            
            // Add event listeners to filter elements
            fieldSelect.addEventListener('change', applyFilters);
            difficultySelect.addEventListener('change', applyFilters);
            
            // Add debounce to search input
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyFilters, 300);
            });
            
            // Set up initial action button listeners
            setupActionButtons();
            
            // Function to apply filters
            function applyFilters() {
                const fieldId = fieldSelect.value;
                const difficultyId = difficultySelect.value;
                const searchText = searchInput.value.toLowerCase();
                
                // Clear the current table rows
                tableBody.innerHTML = '';
                
                // Filter rows based on criteria
                let visibleCount = 0;
                
                allRows.forEach(row => {
                    const rowFieldId = row.getAttribute('data-field-id');
                    const rowDifficultyId = row.getAttribute('data-difficulty-id');
                    const questionText = row.getAttribute('data-question-text').toLowerCase();
                    const optionA = row.getAttribute('data-option-a').toLowerCase();
                    const optionB = row.getAttribute('data-option-b').toLowerCase();
                    const optionC = row.getAttribute('data-option-c').toLowerCase();
                    const optionD = row.getAttribute('data-option-d').toLowerCase();
                    
                    let showRow = true;
                    
                    // Apply field filter
                    if (fieldId !== '0' && rowFieldId !== fieldId) {
                        showRow = false;
                    }
                    
                    // Apply difficulty filter
                    if (difficultyId !== '0' && rowDifficultyId !== difficultyId) {
                        showRow = false;
                    }
                    
                    // Apply search filter
                    if (searchText && !(
                        questionText.includes(searchText) || 
                        optionA.includes(searchText) || 
                        optionB.includes(searchText) || 
                        optionC.includes(searchText) || 
                        optionD.includes(searchText)
                    )) {
                        showRow = false;
                    }
                    
                    // If row passes all filters, show it
                    if (showRow) {
                        // We need to clone the row to ensure all data attributes are preserved
                        const newRow = row.cloneNode(true);
                        tableBody.appendChild(newRow);
                        visibleCount++;
                    }
                });
                
                // Show/hide no results message
                if (visibleCount === 0) {
                    table.style.display = 'none';
                    noResultsDiv.style.display = 'block';
                } else {
                    table.style.display = 'table';
                    noResultsDiv.style.display = 'none';
                }
                
                // Re-attach action button listeners to new DOM elements
                setupActionButtons();
            }
            
            // Function to set up action buttons
            function setupActionButtons() {
                // Delete buttons
                document.querySelectorAll('.delete-btn').forEach(button => {
                    // Remove old listeners to prevent duplicates
                    const newButton = button.cloneNode(true);
                    button.parentNode.replaceChild(newButton, button);
                    
                    newButton.addEventListener('click', function() {
                        const questionId = this.getAttribute('data-question-id');
                        const row = this.closest('tr');
                        const questionTextShort = row.querySelector('td:nth-child(2)').textContent;
                        confirmDelete(questionId, questionTextShort);
                    });
                });
                
                // Edit buttons
                document.querySelectorAll('.edit-btn').forEach(button => {
                    // Remove old listeners to prevent duplicates
                    const newButton = button.cloneNode(true);
                    button.parentNode.replaceChild(newButton, button);
                    
                    newButton.addEventListener('click', function() {
                        const questionId = this.getAttribute('data-question-id');
                        const row = this.closest('tr');
                        showEditModal(row);
                    });
                });
            }
            
            // Function to show edit modal
            window.showEditModal = function(row) {
                const questionId = row.querySelector('.edit-btn').getAttribute('data-question-id');
                const questionText = row.getAttribute('data-question-text');
                const fieldId = row.getAttribute('data-field-id');
                const difficultyId = row.getAttribute('data-difficulty-id');
                const optionA = row.getAttribute('data-option-a');
                const optionB = row.getAttribute('data-option-b');
                const optionC = row.getAttribute('data-option-c');
                const optionD = row.getAttribute('data-option-d');
                const correctAnswer = row.getAttribute('data-correct-answer');
                
                // Fill the edit form with current values
                document.getElementById('edit_question_id').value = questionId;
                document.getElementById('edit_question_text').value = questionText;
                document.getElementById('edit_field_id').value = fieldId;
                document.getElementById('edit_difficulty_id').value = difficultyId;
                document.getElementById('edit_option_a').value = optionA;
                document.getElementById('edit_option_b').value = optionB;
                document.getElementById('edit_option_c').value = optionC;
                document.getElementById('edit_option_d').value = optionD;
                document.getElementById('edit_correct_answer').value = correctAnswer;
                
                // Show modal
                document.getElementById('editModal').style.display = 'block';
            }
            
            // Function to confirm delete
            window.confirmDelete = function(id, text) {
                document.getElementById('question_id').value = id;
                document.getElementById('questionText').textContent = text;
                document.getElementById('deleteModal').style.display = 'block';
            }
            
            // Function to close modal
            window.closeModal = function(modalId) {
                document.getElementById(modalId).style.display = 'none';
            }
            
            // Close modal when clicking outside
            window.onclick = function(event) {
                const deleteModal = document.getElementById('deleteModal');
                const editModal = document.getElementById('editModal');
                if (event.target == deleteModal) {
                    closeModal('deleteModal');
                }
                if (event.target == editModal) {
                    closeModal('editModal');
                }
            }
            
            // Apply initial filters
            applyFilters();
        });
    </script>
</body>
</html>