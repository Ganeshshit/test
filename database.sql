-- students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    institution VARCHAR(100) NOT NULL,
    university VARCHAR(100) NOT NULL,
    usn VARCHAR(50) NOT NULL,
    github VARCHAR(255),
    linkedin VARCHAR(255),
    resume_path VARCHAR(255),
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP
    is_admin TINYINT(1) DEFAULT 0
);
-- Medini@67
-- domains table
CREATE TABLE domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);
INSERT INTO domains (name) VALUES ('it'), ('non-it');

-- fields table
CREATE TABLE IF NOT EXISTS fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    domain_id INT NOT NULL
);
INSERT INTO fields (name, domain_id) VALUES
('Frontend', 1),
('Backend', 1),
('Full Stack', 1),
('Android', 1),
('Data Science', 1),
('ML/AI', 1),
('Flutter', 1),
('React Native', 1),
('AEM', 1),
('BI', 1),
('Data Engg.', 1),
('DBA', 1),
('Drupal', 1),
('MS Apps', 1),
('PM', 1),
('SAP', 1),
('Salesforce', 1),
('Scrum Master', 1),
('ServiceNow', 1),
('SharePoint', 1),
('SRE', 1),
('iOS', 1);

INSERT INTO fields (name, domain_id) VALUES
('Finance', 2),
('Marketing', 2),
('HR', 2),
('Sales', 2),
('Law', 2),
('Healthcare', 2),
('Education', 2),
('Psychology', 2);

-- difficulty_levels table 

CREATE TABLE IF NOT EXISTS difficulty_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);
INSERT INTO difficulty_levels (name) VALUES
('Beginner'),
('Intermediate'),
('Advanced');

-- Questions table
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_id INT,
    difficulty_id INT,
    question_text TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    correct_answer CHAR(1) NOT NULL,
    FOREIGN KEY (field_id) REFERENCES fields(id),
    FOREIGN KEY (difficulty_id) REFERENCES difficulty_levels(id)
);

INSERT INTO questions (field_id, difficulty_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
(1, 1, 'What does HTML stand for?', 'Hyper Text Markup Language', 'Hyperlinks and Text Markup Language', 'Home Tool Markup Language', 'High Tech Markup Language', 'A'),
(1, 2, 'Which CSS property is used to change text color?', 'text-color', 'color', 'font-color', 'foreground', 'B'),
(2, 1, 'What is the main function of an operating system?', 'Manage hardware resources', 'Run user applications', 'Control network connections', 'Handle security alone', 'A'),
(3, 3, 'What is the time complexity of binary search?', 'O(n)', 'O(log n)', 'O(n log n)', 'O(1)', 'B'),
(4, 2, 'Which language is primarily used for Android development?', 'Python', 'Swift', 'Java/Kotlin', 'C++', 'C'),
(5, 3, 'Which of these is a supervised learning algorithm?', 'K-Means Clustering', 'Decision Tree', 'DBSCAN', 'Apriori Algorithm', 'B');

-- Quiz attempts
CREATE TABLE quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    field_id INT,
    difficulty_id INT,
    score INT,
    total_questions INT,
    is_valid TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES students(id),
    FOREIGN KEY (field_id) REFERENCES fields(id),
    FOREIGN KEY (difficulty_id) REFERENCES difficulty_levels(id)
);

-- User answers
CREATE TABLE user_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT,
    question_id INT,
    user_answer CHAR(1),
    is_correct TINYINT(1),
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id),
    FOREIGN KEY (question_id) REFERENCES questions(id)
);

-- Email templates table
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
);

-- SMTP configuration table
CREATE TABLE IF NOT EXISTS smtp_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    host VARCHAR(255) NOT NULL,
    port INT NOT NULL DEFAULT 587,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    from_name VARCHAR(255) NOT NULL,
    encryption ENUM('tls', 'ssl', 'none') NOT NULL DEFAULT 'tls',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Email logs table
CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('sent', 'failed') NOT NULL,
    error_message TEXT NULL,
    sent_at DATETIME NOT NULL,
    INDEX (recipient_id),
    INDEX (status),
    INDEX (sent_at)
);

-- Scheduled emails table
CREATE TABLE IF NOT EXISTS scheduled_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipients TEXT NOT NULL COMMENT 'JSON array of recipient data',
    subject VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    schedule_date DATETIME NOT NULL,
    status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    error_message TEXT NULL,
    INDEX (status),
    INDEX (schedule_date)
);

INSERT INTO smtp_config (host, port, username, password, from_email, from_name, encryption, created_at)
VALUES ('smtp.hostinger.com', 465, 'username', 'password', 'noreply@example.com', 'Quiz System', 'ssl', NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();