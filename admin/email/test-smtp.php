<?php
// Prevent unexpected output
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');
error_reporting(E_ALL);

require_once '../../includes/functions.php';
secureSessionStart();

if (!isLoggedIn() || !isAdmin()) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

if (!isset($data['host'], $data['port'], $data['username'], $data['from_email'], $data['from_name'])) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if (file_exists('../../vendor/autoload.php')) {
    require_once '../../vendor/autoload.php';
} else {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'PHPMailer not found. Please install it using Composer.']);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = SMTP::DEBUG_OFF; // Disable debug output
    $mail->isSMTP();
    $mail->Host = $data['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $data['username'];

    if ($data['password'] === '********') {
        $conn = getDbConnection();
        $result = $conn->query("SELECT password FROM smtp_config LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $mail->Password = $row['password'];
        } else {
            ob_clean();
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Cannot test with masked password when no configuration exists']);
            exit;
        }
        $conn->close();
    } else {
        $mail->Password = $data['password'];
    }

    if ($data['encryption'] == 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($data['encryption'] == 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    }

    $mail->Port = $data['port'];

    // Test SMTP connection
    $connectionResult = $mail->smtpConnect();

    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => $connectionResult, 'message' => $connectionResult ? 'SMTP connection successful!' : 'SMTP connection failed']);
    exit;
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'message' => 'SMTP connection failed: ' . $mail->ErrorInfo,
        'error_details' => $e->getMessage()
    ]);
    exit;
}
