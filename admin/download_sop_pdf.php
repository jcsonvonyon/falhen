<?php
/**
 * Falhen Media - Dynamic Standard Operating Procedures (SOP) PDF / Print Generator
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

if (!$isHRorAdmin && strtolower($currentUsername) !== strtolower($targetUser)) {
    $targetUser = $currentUsername;
}

$onboardingData = getUserOnboardingData($targetUser);
$sopData = $onboardingData['sop'] ?? [];
$staffObj = getStaffAccountByUsername($targetUser);
$userFullName = $staffObj['full_name'] ?? ucwords(str_replace('.', ' ', $targetUser));

$sopAcknowledged = !empty($sopData['acknowledged']);
$sopAckAt = $sopData['acknowledged_at'] ?? date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standard Operating Procedures (SOP) - <?php echo htmlspecialchars($userFullName); ?> | Falhen Media</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page { size: A4; margin: 18mm; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #0f172a;
            background: #f8fafc;
            margin: 0;
            padding: 40px 20px;
            line-height: 1.6;
            font-size: 0.88rem;
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
        h3 {
            font-size: 0.98rem;
            font-weight: 700;
            color: #0f172a;
            margin-top: 22px;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        h4 {
            font-size: 0.9rem;
            font-weight: 700;
            color: #334155;
            margin-top: 14px;
            margin-bottom: 6px;
        }
        ul { margin-top: 6px; margin-bottom: 12px; padding-left: 20px; }
        li { margin-bottom: 4px; }
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
            <i class="fa-solid fa-print"></i> Print / Save SOP PDF
        </button>
    </div>

    <div class="page-container">
        <div class="header-box">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 42px; height: 42px; border-radius: 10px; background: #dc2626; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 900;">F</div>
                <div>
                    <div style="font-size: 1.4rem; font-weight: 900; color: #0f172a;">Falhen <span style="color: #dc2626;">Media</span></div>
                    <div style="font-size: 0.78rem; color: #64748b;">Human Resources & Operational Excellence</div>
                </div>
            </div>
            <div style="text-align: right; font-size: 0.82rem; color: #64748b;">
                <div><strong>Document Ref:</strong> FLH-SOP-2026</div>
                <div><strong>Effective Date:</strong> January 1, 2026</div>
            </div>
        </div>

        <div style="text-align: center; margin-bottom: 28px;">
            <div style="font-size: 1.5rem; font-weight: 900; color: #0f172a;">STANDARD OPERATING PROCEDURES (SOP)</div>
            <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">Operational Guidelines, Code of Conduct & Internal Workflows</div>
        </div>

        <h3>1. Purpose</h3>
        <p>The purpose of this Standard Operating Procedures document is to provide clear guidelines for the daily operations, internal processes, employee conduct, communication, approvals, documentation, and reporting within Falhen.</p>
        <p>This SOP is designed to ensure consistency, accountability, efficiency, transparency, and professionalism across all departments and team members.</p>

        <h3>2. Scope</h3>
        <p>This SOP applies to all employees, contract staff, interns, consultants, and authorized representatives of Falhen. It covers operational areas including attendance, leave management, communication, task assignment, record keeping, meetings, data protection, tools usage, escalation, and exit procedures.</p>

        <h3>3. General Work Conduct</h3>
        <p>All staff of Falhen are expected to maintain a high level of professionalism, discipline, and accountability in the discharge of their duties.</p>
        <ul>
            <li>Report to work on time</li>
            <li>Carry out assigned tasks diligently</li>
            <li>Respect colleagues, supervisors, clients, and partners</li>
            <li>Maintain confidentiality of company information</li>
            <li>Follow approved communication channels</li>
            <li>Submit reports when due</li>
            <li>Use company resources responsibly</li>
            <li>Represent Falhen positively at all times</li>
        </ul>

        <h3>4. Attendance Procedure</h3>
        <p>All employees are required to record their daily attendance through the approved Falhen attendance system or HR portal.</p>
        <h4>4.1 Check-In</h4>
        <p>Employees must check in at the official resumption time or immediately upon starting work.</p>
        <h4>4.2 Break</h4>
        <p>Employees may proceed on approved break periods based on company policy or departmental schedule.</p>
        <h4>4.3 Check-Out</h4>
        <p>Employees must check out at the close of work or after completing their approved work schedule.</p>

        <h3>5. Leave Management Procedure</h3>
        <p>All leave requests must be submitted through the approved HR portal or official leave request channel.</p>
        <h4>5.1 Types of Leave</h4>
        <p>Annual Leave, Sick Leave, Emergency Leave, Maternity Leave, Paternity Leave, Compassionate Leave, Study Leave, and Unpaid Leave.</p>
        <h4>5.2 Leave Application & Approval</h4>
        <p>Submit requests before intended dates with leave type, start/end dates, number of days, and reason. Requests are reviewed by supervisors and HR.</p>

        <h3>6. Communication Procedure</h3>
        <p>Falhen maintains professional internal communication via company email, HR portal, workstation platforms, and official memos. Sensitive matters must not be discussed on unauthorized channels.</p>

        <h3>7. Task Assignment and Reporting</h3>
        <p>Tasks assigned by supervisors clearly specify description, responsible person, deadline, expected outcome, and priority level. Updates must be reported promptly.</p>

        <h3>8. Approval Procedure</h3>
        <p>Requests for leave, procurement, expenses, travel, project execution, or public announcements follow the approved chain of command.</p>

        <h3>9. Documentation and Record Keeping</h3>
        <p>Falhen maintains proper documentation of staff records, attendance, leave, financial records, project reports, and contracts in approved secure locations.</p>

        <h3>10. Meeting Procedure</h3>
        <p>Meetings require a clear agenda, lead, action points, and follow-up timeline, with minutes shared with stakeholders.</p>

        <h3>11. Use of Company Tools and Systems</h3>
        <p>Employees must responsibly use company portals, emails, devices, and drives. Credentials must never be shared.</p>

        <h3>12. Data Protection and Confidentiality</h3>
        <p>All staff, client, financial, and operational data must be protected. Unauthorized disclosure leads to disciplinary action.</p>

        <h3>13. Performance and Reporting</h3>
        <p>Periodic daily, weekly, or monthly reports must be submitted factually and on time.</p>

        <h3>14. Escalation Procedure</h3>
        <p>Chain of escalation: 1. Immediate Supervisor &rarr; 2. Department Head &rarr; 3. HR/Admin &rarr; 4. Management.</p>

        <h3>15. Disciplinary Procedure</h3>
        <p>Violations result in progressive disciplinary action: Verbal Warning, Written Warning, Suspension, Reassignment, or Termination.</p>

        <h3>16. Onboarding Procedure</h3>
        <p>New staff complete document submission, system setup, tool assignment, policy orientation, and handbook review.</p>

        <h3>17. Exit Procedure</h3>
        <p>Leaving staff submit formal resignation, complete duty handover, return company assets, and complete clearance.</p>

        <h3>18. Review of SOP</h3>
        <p>Reviewed annually or as operational growth requires, approved by management.</p>

        <h3>19. Conclusion & Digital Acknowledgment</h3>
        <p>This document serves as a binding operational guide. Compliance is mandatory for all Falhen team members.</p>

        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 18px; margin-top: 24px;">
            <h4 style="font-size: 0.95rem; font-weight: 700; color: #166534; margin: 0 0 6px 0;">Acknowledgment & Confirmation</h4>
            <p style="margin: 0 0 8px 0;">I, <strong><?php echo htmlspecialchars($userFullName); ?></strong>, confirm that I have read, understood, and agree to comply with the Falhen Standard Operating Procedures (SOP).</p>
            <?php if ($sopAcknowledged): ?>
                <?php if (!empty($sopData['signature_data'])): ?>
                    <div style="margin: 8px 0; padding: 6px 12px; background: #ffffff; border: 1px dashed #bbf7d0; border-radius: 6px; display: inline-block;">
                        <div style="font-size: 0.7rem; color: #64748b; font-weight: 700; margin-bottom: 2px;">Employee Signature:</div>
                        <img src="<?php echo htmlspecialchars($sopData['signature_data']); ?>" alt="Digital Signature" style="max-height: 50px; display: block;">
                    </div>
                <?php endif; ?>
                <div style="font-size: 0.82rem; color: #166534; font-weight: 700; margin-top: 4px;">
                    <i class="fa-solid fa-file-signature"></i> Digitally Acknowledged & Verified by <?php echo htmlspecialchars($userFullName); ?> on <?php echo date('M d, Y H:i', strtotime($sopAckAt)); ?>
                </div>
            <?php endif; ?>
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
