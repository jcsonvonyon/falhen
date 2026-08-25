<?php
// api/submit_application.php - Handle Candidate Job Application Submissions
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(false, 'Invalid request method.');
}

$jobId = (int)($_POST['job_id'] ?? 0);
$jobTitle = sanitizeInput($_POST['job_title'] ?? '');
$fullName = sanitizeInput($_POST['full_name'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$portfolioUrl = sanitizeInput($_POST['portfolio_url'] ?? '');
$linkedinUrl = sanitizeInput($_POST['linkedin_url'] ?? '');
$coverNote = sanitizeInput($_POST['cover_note'] ?? '');

if (empty($fullName) || empty($email)) {
    sendJSONResponse(false, 'Please fill in your Full Name and Email Address.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse(false, 'Please provide a valid Email Address.');
}

$currentSettings = getSiteSettings();
$applications = $currentSettings['job_applications'] ?? [];
if (!is_array($applications)) {
    $applications = [];
}

$maxId = 0;
foreach ($applications as $app) {
    $id = (int)($app['id'] ?? 0);
    if ($id > $maxId) {
        $maxId = $id;
    }
}
$newAppId = $maxId + 1;

$newApp = [
    'id' => $newAppId,
    'job_id' => $jobId,
    'job_title' => !empty($jobTitle) ? $jobTitle : 'General Application',
    'full_name' => $fullName,
    'email' => $email,
    'phone' => $phone,
    'portfolio_url' => $portfolioUrl,
    'linkedin_url' => $linkedinUrl,
    'cover_note' => $coverNote,
    'status' => 'new',
    'applied_at' => date('Y-m-d H:i:s')
];

$applications[] = $newApp;
saveSiteSettings(['job_applications' => $applications]);

sendJSONResponse(true, 'Your application has been submitted successfully! Our talent team will review your application and contact you soon.', [
    'application_id' => $newAppId
]);
