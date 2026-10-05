<?php
/**
 * Falhen Media - cPanel Git Auto-Deployment Script
 */

// Secret key protection to prevent unauthorized access
$secretKey = 'falhen_deploy_2026';
$providedKey = $_GET['key'] ?? $_POST['key'] ?? '';

if ($providedKey !== $secretKey) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized deployment request. Invalid secret key.'
    ]);
    exit;
}

header('Content-Type: text/plain');
echo "===========================================\n";
echo "   FALHEN CPANEL GIT DEPLOYMENT SYSTEM     \n";
echo "===========================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";

$repoDir = __DIR__;
putenv("HOME=/home/henadinnovate");

$output = [];
$returnVar = 0;

// Stash any server-side generated modifications (e.g. settings.json), pull latest, and re-apply
exec("cd {$repoDir} && git config --global --add safe.directory {$repoDir} 2>&1", $output);
exec("cd {$repoDir} && git stash 2>&1", $output);
exec("cd {$repoDir} && git pull origin main 2>&1", $output, $returnVar);

echo "Command: git pull origin main\n";
echo "-------------------------------------------\n";
echo implode("\n", $output) . "\n";
echo "-------------------------------------------\n";

if ($returnVar === 0) {
    echo "STATUS: SUCCESS - cPanel site updated to latest commit!\n";
} else {
    echo "STATUS: FAILED - Git pull exit code: " . $returnVar . "\n";
}
