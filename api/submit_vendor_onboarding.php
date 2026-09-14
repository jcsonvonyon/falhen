<?php
/**
 * api/submit_vendor_onboarding.php - Handle Vendor / Contractor Onboarding Submission
 * Falhen Media
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(false, 'Invalid request method. Only POST allowed.');
}

// Support JSON or Form POST
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $input = $json;
        }
    }
}

// 1. Role Validation
$role = trim($input['role'] ?? 'photographer_videographer');
$validRoles = ['photographer_videographer', 'contractor', 'intern', 'volunteer'];
if (!in_array($role, $validRoles, true)) {
    $role = 'photographer_videographer';
}

// 2. Personal & Contact Details
$fullName = sanitizeInput($input['full_name'] ?? '');
$email = sanitizeInput(strtolower($input['email'] ?? ''));
$phone = sanitizeInput($input['phone'] ?? '');
$address = sanitizeInput($input['address'] ?? '');

if (empty($fullName)) {
    sendJSONResponse(false, 'Please enter your Full Legal Name.');
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSONResponse(false, 'Please provide a valid email address.');
}

if (empty($phone)) {
    sendJSONResponse(false, 'Please provide a contact phone number.');
}

// 3. Identification & Tax
$idType = sanitizeInput($input['id_type'] ?? 'National ID');
$taxId = sanitizeInput($input['tax_id'] ?? '');
$idDocUrl = '';

// Handle File Upload if present
if (!empty($_FILES['id_document']['tmp_name'])) {
    $uploadDir = __DIR__ . '/../uploads/vendors/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $ext = strtolower(pathinfo($_FILES['id_document']['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
    if (in_array($ext, $allowedExts, true)) {
        $fileName = 'id_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $dest = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['id_document']['tmp_name'], $dest)) {
            $idDocUrl = '/uploads/vendors/' . $fileName;
        }
    }
} else if (!empty($input['id_document_url'])) {
    $idDocUrl = sanitizeInput($input['id_document_url']);
}

// 4. Remuneration & Bank Details (for contractors & interns)
$currency = sanitizeInput($input['currency'] ?? 'NGN');
$bankName = sanitizeInput($input['bank_name'] ?? '');
$accountNumber = sanitizeInput($input['account_number'] ?? '');
$accountName = sanitizeInput($input['account_name'] ?? '');

// 5. Emergency Contact
$emergencyName = sanitizeInput($input['emergency_name'] ?? '');
$emergencyRel = sanitizeInput($input['emergency_relationship'] ?? '');
$emergencyPhone = sanitizeInput($input['emergency_phone'] ?? '');

// 6. Policy & Agreement Acknowledgements
$workplacePolicyAgreed = !empty($input['workplace_policy_agreed']);
$ndaAgreed = !empty($input['nda_agreed']) || !empty($input['legal_agreements_agreed']);
$msaAgreed = !empty($input['msa_agreed']) || !empty($input['legal_agreements_agreed']);
$signatureName = sanitizeInput($input['signature_name'] ?? $fullName);
$signatureDate = sanitizeInput($input['signature_date'] ?? date('Y-m-d'));

if (!$workplacePolicyAgreed) {
    sendJSONResponse(false, 'You must review and agree to the Falhen Media Workplace Policies to proceed.');
}

if (!$ndaAgreed || !$msaAgreed) {
    sendJSONResponse(false, 'You must review and sign the Legal Agreements & NDA to finalize onboarding.');
}

if (empty($signatureName)) {
    sendJSONResponse(false, 'Please provide your legal digital signature.');
}

// 7. Assemble Record & Persist
$vendorData = [
    'role'                    => $role,
    'full_name'               => $fullName,
    'email'                   => $email,
    'phone'                   => $phone,
    'address'                 => $address,
    'id_type'                 => $idType,
    'tax_id'                  => $taxId,
    'id_document_url'         => $idDocUrl,
    'currency'                => $currency,
    'bank_name'               => $bankName,
    'account_number'          => $accountNumber,
    'account_name'            => $accountName,
    'emergency_name'          => $emergencyName,
    'emergency_relationship'  => $emergencyRel,
    'emergency_phone'         => $emergencyPhone,
    'workplace_policy_agreed' => 1,
    'nda_agreed'              => 1,
    'msa_agreed'              => 1,
    'signature_name'          => $signatureName,
    'signature_date'          => $signatureDate,
    'status'                  => 'active'
];

$saved = saveVendorRecord($vendorData);

$refCode = $saved['reference_code'] ?? '';
$downloadUrl = '/download_vendor_agreement.php?ref=' . urlencode($refCode);

sendJSONResponse(true, 'Vendor onboarding submitted successfully! Welcome to the Falhen Media team.', [
    'reference_code' => $refCode,
    'role_label'     => $saved['role_label'] ?? 'Vendor',
    'full_name'      => $fullName,
    'email'          => $email,
    'download_url'   => $downloadUrl,
    'created_at'     => $saved['created_at'] ?? date('Y-m-d H:i:s')
]);
