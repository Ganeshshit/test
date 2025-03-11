<?php
require_once '../includes/functions.php';
secureSessionStart();

// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get all users
$conn = getDbConnection();
$result = $conn->query("SELECT id, name, email, phone, institution, university, usn, github, linkedin, resume_path, is_admin, registration_date FROM students ORDER BY registration_date DESC");

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - View Users</title>
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

        .filters {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            /* gap: 10px; */
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

        .admin-content h2 {
            color: #1e3c72;
            margin-bottom: 15px;
        }

        .status-admin {
            color: #1e3c72;
            font-weight: bold;
        }

        .status-user {
            color: #555;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Admin Dashboard - View Users</div>
        
        <div class="admin-menu">
            <a href="index.php">View Results</a>
            <a href="view-users.php" class="active">View Users</a>
            <a href="add-question.php">Add Question</a>
            <a href="question-bank.php">Question Bank</a>
            <a href="../logout.php">Logout</a>
        </div>
        
        <div class="filters">
            <input type="text" id="nameFilter" placeholder="Filter by Name">
            <input type="text" id="emailFilter" placeholder="Filter by Email">
            <input type="text" id="institutionFilter" placeholder="Filter by Institution">
            <input type="text" id="universityFilter" placeholder="Filter by University">
            <select id="roleFilter">
                <option value="">Filter by Role</option>
                <option value="Admin">Admin</option>
                <option value="User">User</option>
            </select>
        </div>

        <div class="table-container">
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Institution</th>
                        <th>University</th>
                        <th>USN</th>
                        <th>GitHub</th>
                        <th>LinkedIn</th>
                        <th>Resume</th>
                        <th>Role</th>
                        <th>Registered On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="12">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td><?php echo htmlspecialchars($user['institution']); ?></td>
                                <td><?php echo htmlspecialchars($user['university']); ?></td>
                                <td><?php echo htmlspecialchars($user['usn']); ?></td>
                                <td>
                                    <?php if (!empty($user['github'])): ?>
                                        <a href="<?php echo htmlspecialchars($user['github']); ?>" target="_blank" class="btn btn-small">View</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($user['linkedin'])): ?>
                                        <a href="<?php echo htmlspecialchars($user['linkedin']); ?>" target="_blank" class="btn btn-small">View</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($user['resume_path'])): ?>
                                        <a href="../uploads/<?php echo htmlspecialchars($user['resume_path']); ?>" target="_blank" class="btn btn-small">Download</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="<?php echo $user['is_admin'] ? 'status-admin' : 'status-user'; ?>">
                                    <?php echo $user['is_admin'] ? 'Admin' : 'User'; ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($user['registration_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    // Function to apply filters
    function applyFilters() {
        let name = document.getElementById("nameFilter").value.toLowerCase();
        let email = document.getElementById("emailFilter").value.toLowerCase();
        let institution = document.getElementById("institutionFilter").value.toLowerCase();
        let university = document.getElementById("universityFilter").value.toLowerCase();
        let role = document.getElementById("roleFilter").value;

        let table = document.getElementById("usersTable");
        let rows = table.getElementsByTagName("tr");

        for (let i = 1; i < rows.length; i++) {
            let cells = rows[i].getElementsByTagName("td");
            if (cells.length === 0) continue; // Skip if no cells (like header)
            
            let showRow = true;

            let userName = cells[1].textContent.toLowerCase();
            let userEmail = cells[2].textContent.toLowerCase();
            let userInstitution = cells[4].textContent.toLowerCase();
            let userUniversity = cells[5].textContent.toLowerCase();
            let userRole = cells[10].textContent.trim();

            if (name && !userName.includes(name)) showRow = false;
            if (email && !userEmail.includes(email)) showRow = false;
            if (institution && !userInstitution.includes(institution)) showRow = false;
            if (university && !userUniversity.includes(university)) showRow = false;
            if (role && userRole !== role) showRow = false;

            rows[i].style.display = showRow ? "" : "none";
        }
    }

    // Add event listeners to all filter elements
    document.addEventListener('DOMContentLoaded', function() {
        // Get all filter elements
        const filterElements = [
            document.getElementById("nameFilter"),
            document.getElementById("emailFilter"),
            document.getElementById("institutionFilter"),
            document.getElementById("universityFilter"),
            document.getElementById("roleFilter")
        ];
        
        // Add event listeners to each filter
        filterElements.forEach(element => {
            if(element) {
                if(element.tagName === "INPUT" && element.type === "text") {
                    // For text inputs, use input event for real-time filtering
                    element.addEventListener('input', applyFilters);
                } else {
                    // For dropdowns, use change event
                    element.addEventListener('change', applyFilters);
                }
            }
        });
    });
    </script>
</body>
</html>