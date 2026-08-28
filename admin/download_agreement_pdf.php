<?php
/**
 * Falhen Media - Dynamic Employment Agreement PDF / Print Generator
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

// Non-admin can only download their own agreement
if (!$isHRorAdmin && strtolower($currentUsername) !== strtolower($targetUser)) {
    $targetUser = $currentUsername;
}

$onboardingData = getUserOnboardingData($targetUser);
$offerInfo = $onboardingData['offer_letter'] ?? [];
$agrData = $onboardingData['employment_agreement'] ?? [];
$staffObj = getStaffAccountByUsername($targetUser);
$userFullName = $staffObj['full_name'] ?? ucwords(str_replace('.', ' ', $targetUser));

$userEmail = $staffObj['email'] ?? ($targetUser . '@falhen.com');
$agrSupervisorName = $agrData['supervisor_name'] ?? 'Oluwatosin Ligali';
$agrSupervisorRole = $agrData['supervisor_role'] ?? 'Admin';
$agrWorkLocation = $agrData['work_location'] ?? 'Remote';
$agrWorkDays = $agrData['work_days'] ?? 'Monday to Friday';
$agrResumptionTime = $agrData['resumption_time'] ?? '9:00 AM';
$agrClosingTime = $agrData['closing_time'] ?? '5:00 PM';
$agrAddress = $agrData['address'] ?? '6 Olorunfunmi Street, Pedro, Gbagada, Lagos';
$agrPhone = $agrData['phone'] ?? '08027487449';
$agrSigned = !empty($agrData['signed']);
$agrSignedAt = $agrData['signed_at'] ?? date('Y-m-d H:i:s');

$currency = $offerInfo['currency'] ?? 'NGN';
$currencySymbols = ['NGN' => '₦', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'];
$sym = $currencySymbols[$currency] ?? '₦';
$rawSalary = $offerInfo['salary'] ?? ($sym . '250,000 / month');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employment Agreement - <?php echo htmlspecialchars($userFullName); ?> | Falhen Media</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1e293b;
            background: #f8fafc;
            margin: 0;
            padding: 40px 20px;
            line-height: 1.6;
            font-size: 0.9rem;
        }
        .page-container {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px 48px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }
        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #dc2626;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .action-bar {
            max-width: 850px;
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
            body { background: #ffffff; padding: 0; }
            .page-container { box-shadow: none; border: none; padding: 0; }
            .action-bar { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <a href="/admin/index.php?section=onboarding" style="color: #64748b; text-decoration: none; font-size: 0.88rem; font-weight: 700;">
            <i class="fa-solid fa-arrow-left"></i> Back to Onboarding
        </a>
        <button onclick="window.print()" class="btn-print">
            <i class="fa-solid fa-print"></i> Print / Save Agreement PDF
        </button>
    </div>

    <div class="page-container">
        <div class="header-box">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 42px; height: 42px; border-radius: 10px; background: #dc2626; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 900;">F</div>
                <div>
                    <div style="font-size: 1.4rem; font-weight: 900; color: #0f172a;">Falhen <span style="color: #dc2626;">Media</span></div>
                    <div style="font-size: 0.78rem; color: #64748b;">Human Resources & Talent Acquisition</div>
                </div>
            </div>
            <div style="text-align: right; font-size: 0.82rem; color: #64748b;">
                <div><strong>Contract Ref:</strong> FLH-AGR-<?php echo strtoupper(substr(md5($targetUser), 0, 6)); ?></div>
                <div><strong>Date:</strong> <?php echo date('F j, Y'); ?></div>
            </div>
        </div>

        <div style="text-align: center; margin-bottom: 28px;">
            <div style="font-size: 1.5rem; font-weight: 900; color: #0f172a;">EMPLOYMENT AGREEMENT</div>
            <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">Made on this <strong><?php echo date('F jS, Y', strtotime($offerInfo['start_date'] ?? '2026-07-01')); ?></strong></div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin-bottom: 24px;">
            <div style="font-weight: 800; color: #0f172a; margin-bottom: 6px;">BETWEEN:</div>
            <p style="margin: 0 0 12px 0;"><strong>Falhen</strong>, hereinafter referred to as <em>"the Employer"</em> or <em>"the Company,"</em></p>
            <div style="font-weight: 800; color: #0f172a; margin-bottom: 6px;">AND:</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 0.85rem;">
                <div><strong>Employee Name:</strong> <?php echo htmlspecialchars($userFullName); ?></div>
                <div><strong>Address:</strong> <?php echo htmlspecialchars($agrAddress); ?></div>
                <div><strong>Phone Number:</strong> <?php echo htmlspecialchars($agrPhone); ?></div>
                <div><strong>Email Address:</strong> <?php echo htmlspecialchars($userEmail); ?></div>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div>
                <h4 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">1. Appointment</h4>
                <p style="margin: 0;">The Employer appoints the Employee to the position of <strong><?php echo htmlspecialchars($offerInfo['job_title'] ?? 'Senior Video Editor'); ?></strong> in the <strong><?php echo htmlspecialchars($offerInfo['department'] ?? 'Media Production'); ?></strong> department (<strong><?php echo htmlspecialchars($offerInfo['employment_type'] ?? 'Full-Time'); ?></strong>) commencing on <strong><?php echo htmlspecialchars($offerInfo['start_date'] ?? date('Y-m-d')); ?></strong>.</p>
            </div>
            <div>
                <h4 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">2. Reporting Line</h4>
                <p style="margin: 0;">The Employee shall report directly to <strong><?php echo htmlspecialchars($agrSupervisorName); ?></strong> (<?php echo htmlspecialchars($agrSupervisorRole); ?>).</p>
            </div>
            <div>
                <h4 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">3. Place of Work & Hours</h4>
                <p style="margin: 0;">Work Location: <strong><?php echo htmlspecialchars($agrWorkLocation); ?></strong>. Work Days: <strong><?php echo htmlspecialchars($agrWorkDays); ?></strong> (<?php echo htmlspecialchars($agrResumptionTime); ?> - <?php echo htmlspecialchars($agrClosingTime); ?>).</p>
            </div>
            <div>
                <h4 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">4. Salary and Compensation</h4>
                <p style="margin: 0;">The Employee shall be paid a gross compensation of <strong><?php echo htmlspecialchars(formatMoneyWithCommas($rawSalary)); ?></strong>.</p>
            </div>
            <div>
                <h4 style="font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">5. Governing Law</h4>
                <p style="margin: 0;">This Agreement shall be governed by the laws of the Federal Republic of Nigeria.</p>
            </div>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px; margin-top: 14px;">
                <h4 style="font-size: 0.95rem; font-weight: 800; color: #166534; margin: 0 0 6px 0;">Acceptance & Confirmation</h4>
                <p style="margin: 0 0 8px 0;">I, <strong><?php echo htmlspecialchars($userFullName); ?></strong>, confirm that I have read, understood, and accepted all terms of this Employment Agreement.</p>
                <?php if ($agrSigned): ?>
                    <?php if (!empty($agrData['signature_data'])): ?>
                        <div style="margin: 8px 0; padding: 6px 12px; background: #ffffff; border: 1px dashed #bbf7d0; border-radius: 6px; display: inline-block;">
                            <div style="font-size: 0.7rem; color: #64748b; font-weight: 700; margin-bottom: 2px;">Employee Signature:</div>
                            <img src="<?php echo htmlspecialchars($agrData['signature_data']); ?>" alt="Digital Signature" style="max-height: 50px; display: block;">
                        </div>
                    <?php endif; ?>
                    <div style="font-size: 0.82rem; color: #166534; font-weight: 800; margin-top: 4px;">
                        <i class="fa-solid fa-file-signature"></i> Digitally Signed & Verified by <?php echo htmlspecialchars($userFullName); ?> on <?php echo date('M d, Y H:i', strtotime($agrSignedAt)); ?>
                    </div>
                <?php endif; ?>
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
