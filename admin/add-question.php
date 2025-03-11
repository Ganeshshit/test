<?php
require_once '../includes/functions.php';
secureSessionStart();

// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get fields and difficulty levels
$fields = [];
$conn = getDbConnection();
$result = $conn->query("SELECT f.*, d.name as domain_name FROM fields f JOIN domains d ON f.domain_id = d.id ORDER BY d.name, f.name");
while ($row = $result->fetch_assoc()) {
    $fields[] = $row;
}

$difficultyLevels = getDifficultyLevels();

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fieldId = (int)$_POST['field_id'];
    $difficultyId = (int)$_POST['difficulty_id'];
    $questionText = sanitizeInput($_POST['question_text']);
    $optionA = sanitizeInput($_POST['option_a']);
    $optionB = sanitizeInput($_POST['option_b']);
    $optionC = sanitizeInput($_POST['option_c']);
    $optionD = sanitizeInput($_POST['option_d']);
    $correctAnswer = sanitizeInput($_POST['correct_answer']);
    
    // Validate inputs
    if (empty($questionText)) {
        $errors[] = "Question text is required";
    }
    
    if (empty($optionA) || empty($optionB) || empty($optionC) || empty($optionD)) {
        $errors[] = "All options are required";
    }
    
    if (empty($correctAnswer) || !in_array($correctAnswer, ['A', 'B', 'C', 'D'])) {
        $errors[] = "Valid correct answer is required";
    }
    
    // If no errors, add question
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO questions (field_id, difficulty_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssss", $fieldId, $difficultyId, $questionText, $optionA, $optionB, $optionC, $optionD, $correctAnswer);
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Failed to add question: " . $conn->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question - Quiz App</title>
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
            background-color: rgba(46, 204, 113, 0.2);
            border: 1px solid #2ecc71;
            color: #27ae60;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .error-container {
            background-color: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .error {
            color: #e74c3c;
            margin: 5px 0;
        }

        .question-form {
            background-color: #f5f8ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #1e3c72;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #1e3c72;
        }

        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 90%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s ease;
        }

        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus {
            border-color: #1e3c72;
            outline: none;
            box-shadow: 0 0 5px rgba(30, 60, 114, 0.3);
        }

        .btn {
            background: #1e3c72;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            display: inline-block;
            transition: background 0.3s ease;
            margin-top: 10px;
        }

        .btn:hover {
            background: #152c56;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        @media screen and (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .form-row .form-group {
                margin-bottom: 15px;
            }
            
            .admin-menu {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Add Question</div>
        
        <div class="admin-menu">
            <a href="index.php">View Results</a>
            <a href="view-users.php">View Users</a>
            <a href="add-question.php" class="active">Add Question</a>
            <a href="question-bank.php">Question Bank</a>
            <a href="../logout.php">Logout</a>
        </div>
        
        <div class="admin-content">
            <?php if ($success): ?>
                <div class="success-message">
                    <p>Question added successfully!</p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error-container">
                    <?php foreach ($errors as $error): ?>
                        <p class="error"><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="add-question.php" class="question-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="field_id">Field</label>
                        <select id="field_id" name="field_id" required>
                            <option value="">Select Field</option>
                            <?php foreach ($fields as $field): ?>
                                <option value="<?php echo $field['id']; ?>"><?php echo $field['domain_name'] . ' - ' . $field['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="difficulty_id">Difficulty Level</label>
                        <select id="difficulty_id" name="difficulty_id" required>
                            <option value="">Select Difficulty</option>
                            <?php foreach ($difficultyLevels as $level): ?>
                                <option value="<?php echo $level['id']; ?>"><?php echo $level['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="question_text">Question</label>
                    <textarea id="question_text" name="question_text" rows="3" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="option_a">Option A</label>
                        <input type="text" id="option_a" name="option_a" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="option_b">Option B</label>
                        <input type="text" id="option_b" name="option_b" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="option_c">Option C</label>
                        <input type="text" id="option_c" name="option_c" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="option_d">Option D</label>
                        <input type="text" id="option_d" name="option_d" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="correct_answer">Correct Answer</label>
                    <select id="correct_answer" name="correct_answer" required>
                        <option value="">Select Correct Answer</option>
                        <option value="A">Option A</option>
                        <option value="B">Option B</option>
                        <option value="C">Option C</option>
                        <option value="D">Option D</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Add Question</button>
            </form>
        </div>
    </div>
</body>
</html>