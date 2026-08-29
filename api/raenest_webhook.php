<?php
/**
 * Falhen Media - Raenest Global Payment Webhook Handler
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$gatewaySettings = getPaymentGatewaysSettings();
$raenestConfig = $gatewaySettings['raenest'] ?? [];
$webhookSecret = $raenestConfig['webhook_secret'] ?? '';

// Retrieve request body and headers
$rawPayload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAENEST_SIGNATURE'] ?? $_SERVER['HTTP_SIGNATURE'] ?? '';

if (empty($rawPayload)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Empty webhook payload.']);
    exit;
}

// If secret is set, verify HMAC signature
if (!empty($webhookSecret) && !empty($signature)) {
    $expectedSignature = hash_hmac('sha256', $rawPayload, $webhookSecret);
    if (!hash_equals($expectedSignature, $signature)) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid webhook signature.']);
        exit;
    }
}

$eventData = json_decode($rawPayload, true);
if (!is_array($eventData)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Malformed JSON payload.']);
    exit;
}

$eventType = $eventData['event'] ?? $eventData['type'] ?? 'payment.success';
$txData = $eventData['data'] ?? $eventData;
$txReference = $txData['reference'] ?? $txData['tx_ref'] ?? ('RAE-' . time());
$amount = $txData['amount'] ?? 0;
$currency = strtoupper($txData['currency'] ?? 'USD');
$customerEmail = $txData['customer']['email'] ?? ($txData['email'] ?? '');

// Log to audit trail
logDatabaseAudit(
    'system_webhook',
    'raenest_payment_received',
    'payment',
    $txReference,
    "Raenest {$eventType}: {$currency} {$amount} from {$customerEmail} (Ref: {$txReference})"
);

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Webhook received and processed successfully.',
    'reference' => $txReference
]);
