<?php
/**
 * Falhen Media - Dynamic Offer Letter PDF / Print Generator
 */
session_start();
require_once __DIR__ . '/../includes/functions.php';

$currentUsername = $_SESSION['admin_username'] ?? $_SESSION['username'] ?? '';
$currentRole = $_SESSION['admin_role'] ?? $_SESSION['role'] ?? 'Staff';
$currentEmail = $_SESSION['admin_email'] ?? $_SESSION['email'] ?? '';

if (empty($currentUsername)) {
    header('Location: /admin/login.php');
    exit;
}

$isHRorAdmin = isAdminUser($currentRole, $currentEmail, $currentUsername);
$targetUser = trim($_GET['target_user'] ?? $currentUsername);

// Non-admin can only download their own offer letter
if (!$isHRorAdmin && strtolower($currentUsername) !== strtolower($targetUser)) {
    $targetUser = $currentUsername;
}

$onboardingData = getUserOnboardingData($targetUser);
$offerInfo = $onboardingData['offer_letter'] ?? [];
$staffObj = getStaffAccountByUsername($targetUser);
$userFullName = $staffObj['full_name'] ?? ucwords(str_replace('.', ' ', $targetUser));

$currency = $offerInfo['currency'] ?? 'NGN';
$sym = getCurrencySymbol($currency);

$jobTitle = $offerInfo['job_title'] ?? 'Senior Video Editor & Cinematographer';
$department = $offerInfo['department'] ?? 'Media Production & Post Studio';
$empType = $offerInfo['employment_type'] ?? 'Full-Time';
$startDate = $offerInfo['start_date'] ?? date('Y-m-d', strtotime('+7 days'));
$probation = $offerInfo['probation_period'] ?? '3 Months';
$expiryDate = $offerInfo['expiry_date'] ?? date('Y-m-d', strtotime('+14 days'));
$issuerName = $offerInfo['issued_by'] ?? 'Mojisola Emjay';
$notes = $offerInfo['notes'] ?? 'Welcome to Falhen Media! We are thrilled to invite you to join our creative production team.';

$baseSalary = $offerInfo['base_salary'] ?? ($sym . '200,000');
$techAllowance = $offerInfo['tech_allowance'] ?? ($sym . '30,000');
$wellnessAllowance = $offerInfo['wellness_allowance'] ?? ($sym . '20,000');
$totalEarnings = $offerInfo['total_earnings'] ?? ($sym . '250,000');
$monthlySalary = $offerInfo['salary'] ?? ($sym . '250,000 / month');

// Format amounts with correct currency symbol
if (!preg_match('/^[₦$€£]/u', $baseSalary)) $baseSalary = $sym . $baseSalary;
if (!preg_match('/^[₦$€£]/u', $techAllowance)) $techAllowance = $sym . $techAllowance;
if (!preg_match('/^[₦$€£]/u', $wellnessAllowance)) $wellnessAllowance = $sym . $wellnessAllowance;
if (!preg_match('/^[₦$€£]/u', $totalEarnings)) $totalEarnings = $sym . $totalEarnings;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offer Letter - <?php echo htmlspecialchars($userFullName); ?> | Falhen Media</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #0f172a;
            background: #f8fafc;
            margin: 0;
            padding: 40px 20px;
            line-height: 1.6;
        }
        .page-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px 48px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }
        .branding-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #dc2626;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo-badge {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: #dc2626;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.4rem;
        }
        .logo-text {
            font-size: 1.5rem;
            font-weight: 900;
            color: #0f172a;
        }
        .doc-title-box {
            text-align: center;
            margin-bottom: 30px;
        }
        .doc-title {
            font-size: 1.6rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .doc-subtitle {
            font-size: 0.88rem;
            color: #64748b;
            margin-top: 4px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 28px;
            font-size: 0.9rem;
        }
        .breakdown-box {
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 28px;
            background: #ffffff;
        }
        .breakdown-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 14px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }
        .breakdown-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.88rem;
            border-bottom: 1px dashed #f1f5f9;
        }
        .breakdown-row.total {
            border-top: 2px solid #cbd5e1;
            border-bottom: none;
            padding-top: 12px;
            margin-top: 6px;
            font-weight: 700;
            font-size: 1rem;
            color: #166534;
        }
        .action-bar {
            max-width: 800px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background: #0f172a;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            border: none;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .page-container {
                box-shadow: none;
                border: none;
                padding: 0;
            }
            .action-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <a href="/admin/index.php?section=onboarding" style="color: #64748b; text-decoration: none; font-size: 0.88rem; font-weight: 700;">
            <i class="fa-solid fa-arrow-left"></i> Back to Onboarding
        </a>
        <button onclick="window.print()" class="btn-print">
            <i class="fa-solid fa-print"></i> Print / Save as PDF
        </button>
    </div>

    <div class="page-container">
        <div class="branding-header">
            <div class="logo-box">
                <div class="logo-badge">F</div>
                <div>
                    <div class="logo-text">Falhen <span style="color: #dc2626;">Media</span></div>
                    <div style="font-size: 0.78rem; color: #64748b;">Human Resources & Talent Acquisition</div>
                </div>
            </div>
            <div style="text-align: right; font-size: 0.82rem; color: #64748b;">
                <div><strong>Ref:</strong> FLH-OFFER-<?php echo strtoupper(substr(md5($targetUser), 0, 6)); ?></div>
                <div><strong>Date:</strong> <?php echo date('F j, Y'); ?></div>
            </div>
        </div>

        <div class="doc-title-box">
            <div class="doc-title">OFFICIAL OFFER OF EMPLOYMENT</div>
            <div class="doc-subtitle">Private & Confidential Document</div>
        </div>

        <p>Dear <strong><?php echo htmlspecialchars($userFullName); ?></strong>,</p>

        <p>We are delighted to extend an official offer of employment to join <strong>Falhen Media</strong>. Following our assessment, we believe your skills and expertise will be invaluable to our studio operations.</p>

        <div class="details-grid">
            <div><strong>Candidate Name:</strong> <?php echo htmlspecialchars($userFullName); ?> (@<?php echo htmlspecialchars($targetUser); ?>)</div>
            <div><strong>Position / Title:</strong> <?php echo htmlspecialchars($jobTitle); ?></div>
            <div><strong>Department:</strong> <?php echo htmlspecialchars($department); ?></div>
            <div><strong>Employment Type:</strong> <?php echo htmlspecialchars($empType); ?></div>
            <div><strong>Proposed Start Date:</strong> <?php echo htmlspecialchars(date('F j, Y', strtotime($startDate))); ?></div>
            <div><strong>Probation Period:</strong> <?php echo htmlspecialchars($probation); ?></div>
        </div>

        <div class="breakdown-box">
            <div class="breakdown-title"><i class="fa-solid fa-calculator" style="color: #dc2626;"></i> Compensation & Allowance Breakdown</div>
            <div class="breakdown-row">
                <span>Base Salary</span>
                <strong><?php echo htmlspecialchars($baseSalary); ?></strong>
            </div>
            <div class="breakdown-row">
                <span>Technical / Data Allowance</span>
                <strong><?php echo htmlspecialchars($techAllowance); ?></strong>
            </div>
            <div class="breakdown-row">
                <span>Wellness / Pension Equivalent</span>
                <strong><?php echo htmlspecialchars($wellnessAllowance); ?></strong>
            </div>
            <div class="breakdown-row total">
                <span>Total Monthly Earnings / Package</span>
                <strong><?php echo htmlspecialchars($totalEarnings); ?></strong>
            </div>
        </div>

        <div style="margin-bottom: 28px; background: #fffbe6; border: 1px solid #fef08a; padding: 16px; border-radius: 10px; font-size: 0.86rem; color: #b45309;">
            <strong>Special Terms & Notes:</strong><br>
            <?php echo nl2br(htmlspecialchars($notes)); ?>
        </div>

        <div style="margin-top: 40px; display: flex; justify-content: space-between; align-items: flex-end; padding-top: 20px; border-top: 2px dashed #cbd5e1;">
            <div>
                <div style="font-weight: 700; font-size: 0.95rem; color: #0f172a;"><?php echo htmlspecialchars($issuerName); ?></div>
                <div style="font-size: 0.82rem; color: #64748b;">Human Resources Lead, Falhen Media</div>
            </div>
            <div style="text-align: right;">
                <?php if (!empty($offerInfo['signature_data'])): ?>
                    <div style="margin-bottom: 6px; padding: 4px 8px; background: #ffffff; border: 1px dashed #bbf7d0; border-radius: 6px; display: inline-block;">
                        <img src="<?php echo htmlspecialchars($offerInfo['signature_data']); ?>" alt="Candidate Signature" style="max-height: 45px; display: block;">
                    </div>
                <?php endif; ?>
                <div style="font-weight: 700; font-size: 0.95rem; color: #166534;"><i class="fa-solid fa-certificate"></i> Verified Studio Offer</div>
                <div style="font-size: 0.82rem; color: #64748b;">Response Deadline: <?php echo htmlspecialchars(date('F j, Y', strtotime($expiryDate))); ?></div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            if (window.location.search.indexOf('print=1') !== -1) {
                setTimeout(function() { window.print(); }, 400);
            }
        };
    </script>
</body>
</html>
