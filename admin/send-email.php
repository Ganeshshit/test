<?php
require_once '../includes/functions.php';
secureSessionStart();

// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the JSON data from the request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Check if required fields are present
if (!isset($data['recipients']) || !isset($data['subject']) || !isset($data['content'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Get SMTP configuration
$conn = getDbConnection();
$smtpQuery = $conn->query("SELECT * FROM smtp_config LIMIT 1");
$smtpConfig = $smtpQuery->fetch_assoc();

if (!$smtpConfig) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'SMTP configuration not found. Please configure SMTP settings first.']);
    exit;
}

// Include PHPMailer
require_once '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If scheduled, save to database and return success
if ($data['isScheduled'] && isset($data['scheduleDate'])) {
    $scheduleDate = $data['scheduleDate'];
    $recipients = json_encode($data['recipients']);
    $subject = $data['subject'];
    $content = $data['content'];
    
    $stmt = $conn->prepare("INSERT INTO scheduled_emails (recipients, subject, content, schedule_date, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $recipients, $subject, $content, $scheduleDate);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Email scheduled successfully for ' . $scheduleDate]);
        $stmt->close();
        $conn->close();
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error scheduling email: ' . $conn->error]);
        $stmt->close();
        $conn->close();
        exit;
    }
}

// Send emails immediately
$successCount = 0;
$failCount = 0;
$errors = [];

foreach ($data['recipients'] as $recipient) {
    // Skip if no email
    if (empty($recipient['email'])) {
        $failCount++;
        $errors[] = "No email address for " . $recipient['name'];
        continue;
    }
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtpConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtpConfig['username'];
        $mail->Password = $smtpConfig['password'];
        
        if ($smtpConfig['encryption'] == 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($smtpConfig['encryption'] == 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        
        $mail->Port = $smtpConfig['port'];
        
        // Recipients
        $mail->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
        $mail->addAddress($recipient['email'], $recipient['name']);
        // $mail->addBCC("info.medini@skilledprofessionals.in");
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $data['subject'];
        
        // Replace placeholders in content
        $personalizedContent = $data['content'];
        $personalizedContent = str_replace('{name}', $recipient['name'], $personalizedContent);
        $personalizedContent = str_replace('{score}', $recipient['score'], $personalizedContent);
        $personalizedContent = str_replace('{total}', $recipient['total'], $personalizedContent);
        $personalizedContent = str_replace('{field}', $recipient['field'], $personalizedContent);
        $personalizedContent = str_replace('{difficulty}', $recipient['difficulty'], $personalizedContent);
        
        $mail->Body = $personalizedContent;
        $mail->AltBody = strip_tags($personalizedContent);
        
        $mail->send();
        $successCount++;
        
        // Log the email
        $stmt = $conn->prepare("INSERT INTO email_logs (recipient_id, recipient_email, subject, content, status, sent_at) VALUES (?, ?, ?, ?, 'sent', NOW())");
        $stmt->bind_param("ssss", $recipient['id'], $recipient['email'], $data['subject'], $personalizedContent);
        $stmt->execute();
        $stmt->close();
        
    } catch (Exception $e) {
        $failCount++;
        $errors[] = "Error sending to {$recipient['email']}: {$mail->ErrorInfo}";
        
        // Log the failed email
        $stmt = $conn->prepare("INSERT INTO email_logs (recipient_id, recipient_email, subject, content, status, error_message, sent_at) VALUES (?, ?, ?, ?, 'failed', ?, NOW())");
        $stmt->bind_param("sssss", $recipient['id'], $recipient['email'], $data['subject'], $personalizedContent, $mail->ErrorInfo);
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();

// Return results
header('Content-Type: application/json');
if ($failCount == 0) {
    echo json_encode(['success' => true, 'message' => "Successfully sent {$successCount} emails"]);
} else {
    echo json_encode([
        'success' => $successCount > 0,
        'message' => "Sent {$successCount} emails, failed to send {$failCount} emails",
        'errors' => $errors
    ]);
}
?>

