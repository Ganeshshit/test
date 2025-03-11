<?php
require_once '../includes/functions.php';
secureSessionStart();

// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get all quiz attempts
$conn = getDbConnection();
$result = $conn->query("SELECT qa.*, u.name, u.email, f.name as field_name, d.name as difficulty_name 
                       FROM quiz_attempts qa 
                       JOIN students u ON qa.user_id = u.id 
                       JOIN fields f ON qa.field_id = f.id 
                       JOIN difficulty_levels d ON qa.difficulty_id = d.id 
                       ORDER BY qa.created_at DESC");

$attempts = [];
while ($row = $result->fetch_assoc()) {
    $attempts[] = $row;
}

// Get email templates if they exist
$templates = [];
$templatesQuery = $conn->query("SELECT id, name, subject, content FROM email_templates ORDER BY name");
if ($templatesQuery) {
    while ($row = $templatesQuery->fetch_assoc()) {
        $templates[] = $row;
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Quiz Results</title>
    <link rel="stylesheet" href="css/index.css"/>
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
        
        <!-- Actions container with Export and Email Buttons -->
        <div class="actions-container">
            <div>
                <select id="rowsPerPage">
                    <option value="10" selected>10 rows per page</option>
                    <option value="20">20 rows per page</option>
                    <option value="50">50 rows per page</option>
                    <option value="100">100 rows per page</option>
                </select>
            </div>
            <div>
                <button id="exportButton" class="btn-export">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Export to Excel
                </button>
                <button id="emailButton" class="btn-export email-action-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    Email Students
                </button>
            </div>
        </div>

        <!-- Selection Mode Toggle -->
        <div class="switch-container">
            <label class="switch">
                <input type="checkbox" id="selectionModeToggle">
                <span class="slider"></span>
            </label>
            <span>Enable Selection Mode</span>
        </div>

        <!-- Select All Option (hidden by default) -->
        <div class="select-all-container" id="selectAllContainer" style="display: none;">
            <input type="checkbox" id="selectAll">
            <label for="selectAll">Select All Students</label>
        </div>

        <div class="table-container">
            <table id="resultsTable">
                <thead>
                    <tr>
                        <th class="checkbox-cell" style="display: none;">Select</th>
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
                            <td colspan="10">No quiz attempts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attempts as $attempt): ?>
                            <tr data-student-id="<?php echo $attempt['user_id']; ?>" 
                                data-student-name="<?php echo htmlspecialchars($attempt['name']); ?>"
                                data-student-email="<?php echo htmlspecialchars($attempt['email'] ?? ''); ?>"
                                data-student-score="<?php echo $attempt['score']; ?>"
                                data-student-total="<?php echo $attempt['total_questions']; ?>"
                                data-student-field="<?php echo htmlspecialchars($attempt['field_name']); ?>"
                                data-student-difficulty="<?php echo htmlspecialchars($attempt['difficulty_name']); ?>">
                                <td class="checkbox-cell" style="display: none;">
                                    <input type="checkbox" class="student-select" value="<?php echo $attempt['user_id']; ?>">
                                </td>
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
        
        <!-- Results Modal -->
        <div id="resultsModal" class="modal">
            <div class="modal-content">
                <span class="close-button" onclick="closeModal('resultsModal')">&times;</span>
                <div id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Email Composition Modal -->
        <div id="emailModal" class="modal">
            <div class="modal-content email-modal">
                <span class="close-button" onclick="closeModal('emailModal')">&times;</span>
                <div class="email-header">
                    <h2>Email Students</h2>
                </div>
                
                <div id="emailResponse" style="display: none;"></div>
                
                <form id="emailForm" class="email-form">
                    <div class="form-group">
                        <label for="emailRecipients">Recipients:</label>
                        <div class="recipients-list" id="recipientsList">
                            <!-- Recipients will be added here -->
                        </div>
                    </div>
                    
                    <div class="template-actions">
                        <div class="form-group" style="flex: 1;">
                            <label for="emailTemplate">Load Template:</label>
                            <select id="emailTemplate">
                                <option value="">Select a template</option>
                                <?php foreach ($templates as $template): ?>
                                <option value="<?php echo $template['id']; ?>"><?php echo htmlspecialchars($template['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" id="saveTemplateBtn" class="btn">Save as Template</button>
                    </div>
                    
                    <div class="form-group">
                        <label for="emailSubject">Subject:</label>
                        <input type="text" id="emailSubject" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Message:</label>
                        <div class="wysiwyg-container">
                            <div class="wysiwyg-toolbar">
                                <button type="button" data-command="bold"><b>B</b></button>
                                <button type="button" data-command="italic"><i>I</i></button>
                                <button type="button" data-command="underline"><u>U</u></button>
                                <button type="button" data-command="insertUnorderedList">• List</button>
                                <button type="button" data-command="insertOrderedList">1. List</button>
                                <button type="button" data-command="createLink">Link</button>
                                <button type="button" data-command="removeFormat">Clear Format</button>
                            </div>
                            <div id="emailContent" class="wysiwyg-editor" contenteditable="true"></div>
                        </div>
                    </div>
                    
                    <div class="placeholder-list">
                        <p>Available Placeholders (click to insert):</p>
                        <span class="placeholder-tag" data-placeholder="{name}">Student Name</span>
                        <span class="placeholder-tag" data-placeholder="{score}">Score</span>
                        <span class="placeholder-tag" data-placeholder="{total}">Total Questions</span>
                        <span class="placeholder-tag" data-placeholder="{field}">Field</span>
                        <span class="placeholder-tag" data-placeholder="{difficulty}">Difficulty</span>
                    </div>
                    
                    <div class="email-actions">
                        <div>
                            <button type="button" id="sendNowBtn" class="btn">Send Now</button>
                            <button type="button" id="scheduleBtn" class="btn">Schedule</button>
                        </div>
                        <button type="button" onclick="closeModal('emailModal')" class="btn" style="background: #e74c3c;">Cancel</button>
                    </div>
                    
                    <div id="scheduleContainer" class="schedule-container">
                        <div class="form-group">
                            <label for="scheduleDate">Schedule Date and Time:</label>
                            <input type="datetime-local" id="scheduleDate" min="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <button type="button" id="confirmScheduleBtn" class="btn">Confirm Schedule</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Save Template Modal -->
        <div id="saveTemplateModal" class="modal">
            <div class="modal-content save-template-modal">
                <span class="close-button" onclick="closeModal('saveTemplateModal')">&times;</span>
                <h2>Save Email Template</h2>
                
                <form id="saveTemplateForm" class="email-form">
                    <div class="form-group">
                        <label for="templateName">Template Name:</label>
                        <input type="text" id="templateName" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" id="confirmSaveTemplateBtn" class="btn">Save Template</button>
                        <button type="button" onclick="closeModal('saveTemplateModal')" class="btn" style="background: #e74c3c;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- SMTP Configuration Modal -->
        <div id="smtpModal" class="modal">
            <div class="modal-content">
                <span class="close-button" onclick="closeModal('smtpModal')">&times;</span>
                <h2>SMTP Configuration</h2>
                
                <div id="smtpResponse" style="display: none;"></div>
                
                <div class="smtp-section">
                    <p>Configure your SMTP server settings to send emails. These settings will be used for all emails sent from the system.</p>
                    
                    <form id="smtpForm" class="smtp-form">
                        <div class="form-group">
                            <label for="smtpHost">SMTP Host:</label>
                            <input type="text" id="smtpHost" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="smtpPort">SMTP Port:</label>
                            <input type="number" id="smtpPort" required value="587">
                        </div>
                        
                        <div class="form-group">
                            <label for="smtpUsername">Username:</label>
                            <input type="text" id="smtpUsername" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="smtpPassword">Password:</label>
                            <input type="password" id="smtpPassword" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="smtpFrom">From Email:</label>
                            <input type="email" id="smtpFrom" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="smtpFromName">From Name:</label>
                            <input type="text" id="smtpFromName" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="smtpEncryption">Encryption:</label>
                            <select id="smtpEncryption">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="button" id="testSmtpBtn" class="btn">Test Connection</button>
                            <button type="button" id="saveSmtpBtn" class="btn">Save Configuration</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- SheetJS library for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="index.js"></script>
</body>
</html>

