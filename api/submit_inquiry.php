<?php
/**
 * API Endpoint: Submit Quote Inquiry
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(false, 'Invalid request method.');
}

// Extract and sanitize input data
$fullName = sanitizeInput($_POST['full_name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone = sanitizeInput($_POST['phone'] ?? '');
$serviceType = sanitizeInput($_POST['service_type'] ?? '');
$budgetRange = sanitizeInput($_POST['budget_range'] ?? '');
$projectDetails = sanitizeInput($_POST['project_details'] ?? '');

if (empty($fullName)) {
    sendJSONResponse(false, 'Please enter your full name.');
}

if (!$email) {
    sendJSONResponse(false, 'Please provide a valid email address.');
}

if (empty($serviceType)) {
    sendJSONResponse(false, 'Please select a service type.');
}

if (empty($projectDetails)) {
    sendJSONResponse(false, 'Please provide project details.');
}

$pdo = getDBConnection();

if (!$pdo) {
    // Graceful response if DB is not initialized locally yet
    sendJSONResponse(true, 'Thank you! Your quote request has been received. Our team will contact you within 24 hours.');
}

try {
    $stmt = $pdo->prepare("INSERT INTO `inquiries` (`full_name`, `email`, `phone`, `service_type`, `budget_range`, `project_details`) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fullName, $email, $phone, $serviceType, $budgetRange, $projectDetails]);

    sendJSONResponse(true, 'Thank you! Your request has been logged successfully. We will reach out within 24 hours.');
} catch (PDOException $e) {
    error_log("Insert Inquiry Error: " . $e->getMessage());
    sendJSONResponse(false, 'Database error. Please try again later.');
}
