<?php
/**
 * vendor/index.php - Vendor Dashboard Portal
 * Falhen Media Production Administration & Vendor Management
 * 
 * Core Operational Focus:
 * 1. Calendar Booking & Availability Schedule
 * 2. Gigs Assigned (Active & Upcoming Shoots with Call Times)
 * 3. Gigs Done (Completed Productions, Deliverables & Payout Archive)
 * 4. Call Sheets (Instant Download & Upload of Signed Call Sheets / Wrap Reports)
 * 
 * Design System: 100% Consistent with Falhen Staff Dashboard Layout
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Auth Guard
if (!isVendorLoggedIn()) {
    header('Location: /admin/login.php?view=vendor');
    exit;
}

$vendor = getLoggedInVendor();
if (!$vendor) {
    clearVendorSession();
    header('Location: /admin/login.php?view=vendor&error=' . urlencode('Session expired. Please sign in again.'));
    exit;
}

$csrfToken = generateCSRFToken();
$successMsg = '';
$errorMsg = '';

// Active Section handling (defaults to gigs_assigned or dashboard)
$activeSection = $_GET['section'] ?? ($_GET['tab'] ?? 'dashboard');
if ($activeSection === 'overview') {
    $activeSection = 'dashboard';
}

$vendorRef = $vendor['reference_code'] ?? 'FAL-VND-UNKNOWN';
$vendorEmail = $vendor['email'] ?? '';

// Handle POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($submittedToken)) {
        $errorMsg = 'Security validation failed. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. Upload Call Sheet / Wrap Report
        if ($action === 'upload_call_sheet') {
            $gigId = trim(strip_tags($_POST['gig_id'] ?? ''));
            $gig = getVendorGigById($gigId);

            if (!$gig) {
                $errorMsg = 'Gig record not found.';
            } else if (empty($_FILES['call_sheet_file']['name']) || $_FILES['call_sheet_file']['error'] !== UPLOAD_ERR_OK) {
                $errorMsg = 'Please select a valid call sheet file (PDF, DOCX, or Image).';
            } else {
                $file = $_FILES['call_sheet_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png'];

                if (!in_array($ext, $allowed, true)) {
                    $errorMsg = 'Invalid file type. Allowed formats: PDF, DOCX, JPG, PNG.';
                } else if ($file['size'] > 15 * 1024 * 1024) {
                    $errorMsg = 'File size exceeds maximum limit of 15MB.';
                } else {
                    $targetDir = __DIR__ . '/../uploads/callsheets/';
                    if (!is_dir($targetDir)) {
                        @mkdir($targetDir, 0777, true);
                    }
                    $safeName = 'CallSheet_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $gigId) . '_' . time() . '.' . $ext;
                    $targetPath = $targetDir . $safeName;

                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $publicUrl = '/uploads/callsheets/' . $safeName;
                        saveVendorCallSheetUpload($gigId, $publicUrl, $file['name']);
                        $successMsg = 'Signed Call Sheet for "' . htmlspecialchars($gig['title']) . '" uploaded successfully!';
                    } else {
                        $errorMsg = 'Failed to save uploaded file. Please check server permissions.';
                    }
                }
            }
        }

        // 2. Confirm Gig Assignment
        else if ($action === 'confirm_gig') {
            $gigId = trim(strip_tags($_POST['gig_id'] ?? ''));
            if (updateVendorGigStatus($gigId, 'confirmed')) {
                $successMsg = 'Gig assignment confirmed! Production supervisor has been notified.';
            } else {
                $errorMsg = 'Failed to confirm gig.';
            }
        }

        // 3. Mark Gig as Done
        else if ($action === 'mark_gig_done') {
            $gigId = trim(strip_tags($_POST['gig_id'] ?? ''));
            $notes = trim(strip_tags($_POST['wrap_notes'] ?? ''));
            if (updateVendorGigStatus($gigId, 'done', $notes)) {
                $successMsg = 'Gig marked as Done! Production wrap records and deliverables status updated.';
            } else {
                $errorMsg = 'Failed to mark gig as done.';
            }
        }

        // 4. Save Calendar Availability
        else if ($action === 'save_availability') {
            $availableDatesStr = trim($_POST['available_dates'] ?? '');
            $availDates = array_filter(array_map('trim', explode(',', $availableDatesStr)));
            $notes = trim(strip_tags($_POST['availability_notes'] ?? ''));
            saveVendorAvailability($vendorRef, $availDates, [], $notes);
            $successMsg = 'Your calendar availability dates have been saved successfully.';
        }

        // 5. Update Profile Details
        else if ($action === 'update_profile') {
            $updateData = [
                'phone'                  => sanitizeInput($_POST['phone'] ?? ''),
                'street_address'         => sanitizeInput($_POST['street_address'] ?? ''),
                'city'                   => sanitizeInput($_POST['city'] ?? ''),
                'state'                  => sanitizeInput($_POST['state'] ?? ''),
                'zip'                    => sanitizeInput($_POST['zip'] ?? ''),
                'emergency_name'         => sanitizeInput($_POST['emergency_name'] ?? ''),
                'emergency_relationship' => sanitizeInput($_POST['emergency_relationship'] ?? ''),
                'emergency_phone'        => sanitizeInput($_POST['emergency_phone'] ?? ''),
                'currency'               => sanitizeInput($_POST['currency'] ?? 'USD')
            ];

            if (empty($updateData['phone'])) {
                $errorMsg = 'Phone number is required.';
            } else {
                updateVendorProfile($vendor['reference_code'], $updateData);
                $vendor = getLoggedInVendor();
                $successMsg = 'Your profile and contact details have been updated successfully.';
            }
        }

        // 6. Change Password
        else if ($action === 'change_password') {
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (strlen($newPassword) < 6) {
                $errorMsg = 'Password must be at least 6 characters long.';
            } else if ($newPassword !== $confirmPassword) {
                $errorMsg = 'Passwords do not match. Please verify and try again.';
            } else {
                setVendorPassword($vendor['reference_code'], $newPassword);
                $vendor = getLoggedInVendor();
                $successMsg = 'Your personal portal password has been saved successfully!';
            }
        }
    }
}

// Fetch Gigs for current vendor
$allVendorGigs = getVendorGigs($vendorRef);
$assignedGigs = array_filter($allVendorGigs, fn($g) => in_array($g['status'] ?? 'assigned', ['assigned', 'confirmed', 'in_progress'], true));
$doneGigs = array_filter($allVendorGigs, fn($g) => ($g['status'] ?? '') === 'done');

$assignedCount = count($assignedGigs);
$doneCount = count($doneGigs);
$totalGigsCount = count($allVendorGigs);

// Next upcoming gig
$nextGig = !empty($assignedGigs) ? array_values($assignedGigs)[0] : null;

// Calendar Data
$calendarMonth = (int)($_GET['month'] ?? date('n'));
$calendarYear = (int)($_GET['year'] ?? date('Y'));
$firstDayOfMonth = mktime(0, 0, 0, $calendarMonth, 1, $calendarYear);
$daysInMonth = date('t', $firstDayOfMonth);
$startDayOfWeek = date('w', $firstDayOfMonth); // 0 = Sunday
$monthName = date('F Y', $firstDayOfMonth);

$vendorAvailability = getVendorAvailability($vendorRef);
$savedAvailDates = $vendorAvailability['available_dates'] ?? [];

// Map gigs to dates for calendar
$gigsByDate = [];
foreach ($allVendorGigs as $g) {
    if (!empty($g['shoot_date'])) {
        $gigsByDate[$g['shoot_date']][] = $g;
    }
}

// Vendor Profile Details
$fullName = $vendor['full_name'] ?? 'Vendor';
$firstName = explode(' ', trim($fullName))[0] ?? 'Vendor';
$userInitial = strtoupper(substr($firstName, 0, 1));
$email = $vendor['email'] ?? '';
$phone = $vendor['phone'] ?? '';
$roleLabel = $vendor['role_label'] ?? 'Photographer / Videographer';
$refCode = $vendor['reference_code'] ?? 'N/A';
$sigDate = !empty($vendor['signature_date']) ? date('M d, Y', strtotime($vendor['signature_date'])) : date('M d, Y');
$downloadUrl = '/download_vendor_agreement.php?ref=' . urlencode($refCode);

$taxFormType = $vendor['tax_form_type'] ?? 'W-9';
$taxId = $vendor['tax_id'] ?? '';
$maskedTaxId = !empty($taxId) ? ('•••• ' . substr($taxId, -4)) : 'Not Provided';
$streetAddress = $vendor['street_address'] ?? ($vendor['address'] ?? '');
$city = $vendor['city'] ?? '';
$state = $vendor['state'] ?? '';
$zip = $vendor['zip'] ?? '';
$emergencyName = $vendor['emergency_name'] ?? 'Not Listed';
$emergencyRel = $vendor['emergency_relationship'] ?? 'N/A';
$emergencyPhone = $vendor['emergency_phone'] ?? 'N/A';

// Section title mapping
$sectionTitles = [
    'dashboard'     => 'Dashboard Overview',
    'gigs_assigned' => 'Gigs Assigned (Active Shoots)',
    'gigs_done'     => 'Gigs Done (Completed Productions)',
    'calendar'      => 'Calendar Booking & Schedule',
    'call_sheets'   => 'Call Sheets Vault',
    'profile'       => 'Contractor Profile & Contact',
    'legal'         => 'Legal Agreements & NDA',
    'payouts'       => 'Stripe Direct Deposit',
    'security'      => 'Security & Password'
];
$displayBreadcrumb = $sectionTitles[$activeSection] ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Falhen Media &mdash; Vendor Portal (<?php echo htmlspecialchars($displayBreadcrumb); ?>)</title>
    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="shortcut icon" type="image/png" href="/assets/img/icons/favicon.png">

    <!-- Fonts & Icons matching Staff Admin -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">

    <style>
        :root {
            --bg-body: #f8fafc;
            --sidebar-bg: #ffffff;
            --sidebar-border: #e2e8f0;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --accent-red: #dc2626;
            --accent-red-hover: #b91c1c;
            --accent-red-light: rgba(220, 38, 38, 0.08);
            --accent-red-border: rgba(220, 38, 38, 0.2);
            --accent-green: #10b981;
            --accent-green-light: #ecfdf5;
            --accent-blue: #2563eb;
            --accent-blue-light: #eff6ff;
            --accent-purple: #9333ea;
            --accent-purple-light: #f3e8ff;
            --input-bg: #ffffff;
            --input-border: #cbd5e1;
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
        }

        /* 1. SIDEBAR LAYOUT */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.25s ease;
        }

        .sidebar-brand-header {
            padding: 20px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #f1f5f9;
        }

        .sidebar-brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .sidebar-brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 7px;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.12);
            flex-shrink: 0;
        }

        .sidebar-brand-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .sidebar-brand-name {
            font-size: 1.15rem;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
            line-height: 1.1;
        }

        .sidebar-brand-badge {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--accent-green);
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-top: 2px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .sidebar-close-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 6px;
        }

        /* User Profile Box */
        .sidebar-user-box {
            margin: 14px 16px;
            padding: 12px 14px;
            background-color: #f8fafc;
            border: 1px solid var(--sidebar-border);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .user-left {
            display: flex;
            align-items: center;
            gap: 10px;
            overflow: hidden;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
            flex-shrink: 0;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .user-title {
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-sub {
            font-size: 0.72rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-pill-tag {
            font-size: 0.68rem;
            font-weight: 800;
            background: #dcfce7;
            color: #15803d;
            padding: 2px 7px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            flex-shrink: 0;
        }

        /* Navigation Categories */
        .nav-category {
            padding: 14px 20px 6px 20px;
            font-size: 0.72rem;
            font-weight: 800;
            color: var(--text-muted);
            letter-spacing: 0.8px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-list {
            list-style: none;
            padding: 0 10px;
            margin: 0 0 10px 0;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 12px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        .nav-item a:hover {
            background-color: #f1f5f9;
            color: var(--text-primary);
        }

        .nav-item.active a {
            background-color: var(--accent-red-light);
            color: var(--accent-red);
            font-weight: 700;
        }

        .nav-item.active a i {
            color: var(--accent-red);
        }

        .nav-icon {
            width: 18px;
            font-size: 0.95rem;
            color: var(--text-muted);
            text-align: center;
        }

        .nav-item.active .nav-icon {
            color: var(--accent-red);
        }

        .nav-badge-pill {
            font-size: 0.7rem;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 10px;
            background: #e2e8f0;
            color: #334155;
        }

        .nav-item.active .nav-badge-pill {
            background: var(--accent-red);
            color: #ffffff;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 16px 20px;
            border-top: 1px solid var(--sidebar-border);
            font-size: 0.75rem;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sidebar-footer a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.15s;
        }

        .sidebar-footer a:hover {
            color: var(--accent-red);
        }

        /* 2. MAIN CONTENT AREA */
        .main-wrapper {
            margin-left: 260px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: 60px;
            min-width: 0;
            overflow-x: hidden;
        }

        /* Top Header Navigation Bar */
        .top-header {
            height: 60px;
            background-color: #ffffff;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            z-index: 999;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .breadcrumb-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .mobile-nav-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.15rem;
            color: #334155;
            cursor: pointer;
            padding: 6px;
        }

        .breadcrumb {
            font-size: 0.88rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .breadcrumb strong {
            color: var(--text-primary);
            font-weight: 700;
        }

        /* Topbar Primary Nav Pills */
        .topbar-primary-nav {
            display: flex;
            align-items: center;
            gap: 4px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            margin-left: 8px;
        }

        .topbar-nav-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            border-radius: 7px;
            transition: all 0.15s ease;
            color: #475569;
        }

        .topbar-nav-link:hover {
            color: #0f172a;
        }

        .topbar-nav-link.active {
            background: #ffffff;
            color: var(--accent-red);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        .topbar-nav-link.active i {
            color: var(--accent-red);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ref-pill-btn {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }

        .ref-pill-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .ref-pill-btn code {
            font-family: monospace;
            color: var(--accent-red);
            font-weight: 800;
        }

        .user-avatar-btn {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-avatar-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--accent-red);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--accent-red);
            flex-shrink: 0;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .avatar-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            width: 270px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 14px 35px -5px rgba(0, 0, 0, 0.18), 0 0 1px 1px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            z-index: 10000;
            overflow: hidden;
            animation: avatarDropdownFade 0.18s ease;
        }

        @keyframes avatarDropdownFade {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-item-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            font-size: 0.86rem;
            color: #334155;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.15s, color 0.15s;
        }

        .dropdown-item-link:hover {
            background: #f8fafc;
            color: #0f172a;
        }

        .dropdown-item-link.logout {
            color: var(--accent-red);
            font-weight: 700;
        }

        .dropdown-item-link.logout:hover {
            background: #fef2f2;
        }

        /* Page Content Body */
        .content-body {
            padding: 32px;
            max-width: 1400px;
            width: 100%;
            margin: 0;
        }

        .section-header-bar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 26px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .section-header-title {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .section-header-desc {
            font-size: 0.92rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .btn-save-primary {
            background-color: var(--accent-red);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
            text-decoration: none;
            transition: background-color 0.2s, transform 0.15s;
            white-space: nowrap;
        }

        .btn-save-primary:hover {
            background-color: var(--accent-red-hover);
            transform: translateY(-1px);
        }

        .btn-header-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 8px 14px;
            font-size: 0.84rem;
            font-weight: 600;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #334155;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            white-space: nowrap;
        }

        .btn-header-action:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
            transform: translateY(-1px);
        }

        .btn-header-action.green {
            background: #10b981;
            color: #ffffff;
            border-color: #10b981;
            font-weight: 700;
        }

        .btn-header-action.green:hover {
            background: #059669;
            color: #ffffff;
        }

        /* Quick Stat Cards Grid */
        .stat-grid-four {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 18px;
            margin-bottom: 26px;
        }

        .stat-kpi-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 18px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            transition: all 0.2s ease;
        }

        .stat-kpi-card:hover {
            border-color: var(--accent-red);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .stat-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .stat-icon-box.green {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .stat-icon-box.red {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .stat-icon-box.blue {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #2563eb;
        }

        .stat-icon-box.purple {
            background: #f3e8ff;
            border: 1px solid #e9d5ff;
            color: #9333ea;
        }

        .stat-info-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
        }

        .stat-info-sub {
            font-size: 0.76rem;
            font-weight: 600;
            color: #64748b;
            margin-top: 2px;
        }

        /* Dashboard Cards */
        .dashboard-card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .card-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .card-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-icon-badge {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background-color: var(--accent-red-light);
            border: 1px solid var(--accent-red-border);
            color: var(--accent-red);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .card-title-text {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Spotlight Next Shoot Banner */
        .spotlight-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: var(--radius-lg);
            padding: 28px 32px;
            color: #ffffff;
            margin-bottom: 26px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15);
            position: relative;
            overflow: hidden;
        }

        .spotlight-banner::before {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 240px;
            height: 240px;
            background: radial-gradient(circle, rgba(220, 38, 38, 0.22) 0%, transparent 70%);
            pointer-events: none;
        }

        .spotlight-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.12);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #f1f5f9;
            margin-bottom: 10px;
        }

        .spotlight-title {
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -0.4px;
            margin-bottom: 8px;
        }

        .spotlight-meta {
            font-size: 0.88rem;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        /* Gig Item Card */
        .gig-item-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 22px;
            background: #ffffff;
            margin-bottom: 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            transition: all 0.2s ease;
        }

        .gig-item-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }

        .gig-top-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .gig-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.3px;
        }

        .gig-status-pill {
            font-size: 0.74rem;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .gig-status-pill.assigned {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }

        .gig-status-pill.confirmed {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .gig-status-pill.done {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .gig-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        .gig-action-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            padding-top: 14px;
            border-top: 1px solid #f1f5f9;
        }

        /* Calendar Styles */
        .calendar-header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }

        .cal-day-name {
            font-size: 0.76rem;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            text-align: center;
            padding: 8px 4px;
        }

        .cal-cell {
            min-height: 90px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .cal-cell:hover {
            border-color: var(--accent-red);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .cal-cell.today {
            border-color: var(--accent-red);
            background: #fffafa;
        }

        .cal-cell.empty {
            background: #f8fafc;
            border-color: transparent;
            cursor: default;
        }

        .cal-day-num {
            font-size: 0.86rem;
            font-weight: 700;
            color: #334155;
        }

        .cal-event-pill {
            font-size: 0.68rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            margin-top: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }

        .cal-event-pill.shoot {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .cal-event-pill.confirmed {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .cal-event-pill.done {
            background: #f1f5f9;
            color: #64748b;
        }

        /* Call Sheet Vault Table */
        .cs-vault-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        .cs-vault-table th {
            background: #f8fafc;
            padding: 12px 16px;
            text-align: left;
            font-size: 0.76rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }

        .cs-vault-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .cs-vault-table tr:hover td {
            background: #fcfcfd;
        }

        /* Form elements */
        .form-grid-two {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-field {
            margin-bottom: 18px;
        }

        .form-label-title {
            display: block;
            font-size: 0.84rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-input-text {
            width: 100%;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid var(--input-border);
            background: var(--input-bg);
            font-size: 0.9rem;
            color: var(--text-primary);
            outline: none;
            font-family: inherit;
        }

        .form-input-text:focus {
            border-color: var(--accent-red);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
        }

        /* Alerts */
        .alert-bar {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .alert-bar.success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .alert-bar.error {
            background: #fef2f2;
            border: 1px solid #fecdd3;
            color: #991b1b;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 10001;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
            animation: modalFadeIn 0.2s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.97); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-dialog {
            background: #ffffff;
            border-radius: var(--radius-lg);
            max-width: 580px;
            width: 100%;
            padding: 28px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--text-muted);
            cursor: pointer;
        }

        /* Responsive Breakpoints */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar-close-btn {
                display: block;
            }
            .sidebar-backdrop {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.5);
                backdrop-filter: blur(2px);
                z-index: 998;
            }
            .sidebar-backdrop.open {
                display: block;
            }
            .main-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .top-header {
                left: 0 !important;
                padding: 0 16px;
            }
            .mobile-nav-toggle {
                display: block;
            }
            .content-body {
                padding: 20px 16px;
            }
            .form-grid-two {
                grid-template-columns: 1fr;
            }
            .spotlight-banner {
                flex-direction: column;
                align-items: flex-start;
            }
            .calendar-grid {
                gap: 4px;
            }
            .cal-cell {
                min-height: 65px;
                padding: 4px;
            }
        }
    </style>
</head>
<body>

    <!-- 1. SIDEBAR NAVIGATION -->
    <aside class="sidebar" id="vendorSidebar">
        <!-- Sidebar Brand Header -->
        <div class="sidebar-brand-header">
            <a href="/vendor/index.php" class="sidebar-brand-link">
                <div class="sidebar-brand-icon">
                    <img src="/assets/img/icons/logo.png" alt="Falhen Logo" onerror="this.src='/assets/falhenmedia_logo.png'">
                </div>
                <div>
                    <div class="sidebar-brand-name">Falhen</div>
                    <div class="sidebar-brand-badge">
                        <i class="fa-solid fa-circle-check"></i> Vendor Portal
                    </div>
                </div>
            </a>
            <button type="button" class="sidebar-close-btn" onclick="toggleMobileSidebar()" aria-label="Close Sidebar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Sidebar User Badge Box -->
        <div class="sidebar-user-box">
            <div class="user-left">
                <div class="status-dot" title="Authenticated Contractor"></div>
                <div class="user-details">
                    <div class="user-title"><?php echo htmlspecialchars($fullName); ?></div>
                    <div class="user-sub"><?php echo htmlspecialchars($roleLabel); ?></div>
                </div>
            </div>
            <span class="user-pill-tag">Active</span>
        </div>

        <!-- Category: PRODUCTION & GIG OPERATIONS (CORE FOCUS) -->
        <div class="nav-category">
            <span>Production Gigs</span>
        </div>
        <ul class="nav-list">
            <li class="nav-item <?php echo ($activeSection === 'gigs_assigned') ? 'active' : ''; ?>">
                <a href="/vendor/index.php?section=gigs_assigned">
                    <span style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-clapperboard nav-icon"></i>
                        <span>Gigs Assigned</span>
                    </span>
                    <span class="nav-badge-pill"><?php echo $assignedCount; ?></span>
                </a>
            </li>
            <li class="nav-item <?php echo ($activeSection === 'gigs_done') ? 'active' : ''; ?>">
                <a href="/vendor/index.php?section=gigs_done">
                    <span style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-circle-check nav-icon"></i>
                        <span>Gigs Done</span>
                    </span>
                    <span class="nav-badge-pill"><?php echo $doneCount; ?></span>
                </a>
            </li>
            <li class="nav-item <?php echo ($activeSection === 'calendar') ? 'active' : ''; ?>">
                <a href="/vendor/index.php?section=calendar">
                    <span style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-calendar-days nav-icon"></i>
                        <span>Calendar Booking</span>
                    </span>
                </a>
            </li>
            <li class="nav-item <?php echo ($activeSection === 'call_sheets') ? 'active' : ''; ?>">
                <a href="/vendor/index.php?section=call_sheets">
                    <span style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-file-pdf nav-icon"></i>
                        <span>Call Sheets Vault</span>
                    </span>
                    <span class="nav-badge-pill"><?php echo $totalGigsCount; ?></span>
                </a>
            </li>
        </ul>

        <!-- Category: DASHBOARD OVERVIEW -->
        <div class="nav-category">
            <span>Overview</span>
        </div>
        <ul class="nav-list">
            <li class="nav-item <?php echo ($activeSection === 'dashboard') ? 'active' : ''; ?>">
                <a href="/vendor/index.php?section=dashboard">
                    <span style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-gauge-high nav-icon"></i>
                        <span>Dashboard Overview</span>
                    </span>
                </a>
            </li>
        </ul>

        <!-- Category: CONTRACTOR SETTINGS & LEGAL -->
        <div class="nav-category">
            <span>Account &amp; Legal</span>
        </div>
        <ul class="nav-list">
            <li class="nav-item <?php echo ($activeSection === 'profile') ? 'active' : ''; ?>">
                <a href="/vendor/index.php?section=profile">
                    <span style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-circle-user nav-icon"></i>
                        <span>Profile &amp; Contact</span>
                    </span>
                </a>
            </li>
            <li class="nav-item <?php echo ($activeSection === 'legal') ? 'active' : ''; ?>">
                <a href="/vendor/index.php?section=legal">
                    <span style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-file-contract nav-icon"></i>
                        <span>Legal Agreement &amp; NDA</span>
                    </span>
                </a>
            </li>
            <li class="nav-item <?php echo ($activeSection === 'payouts') ? 'active' : ''; ?>">
                <a href="/vendor/index.php?section=payouts">
                    <span style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-building-columns nav-icon"></i>
                        <span>Stripe Direct Deposit</span>
                    </span>
                </a>
            </li>
            <li class="nav-item <?php echo ($activeSection === 'security') ? 'active' : ''; ?>">
                <a href="/vendor/index.php?section=security">
                    <span style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-shield-halved nav-icon"></i>
                        <span>Security &amp; Password</span>
                    </span>
                </a>
            </li>
        </ul>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <a href="/" target="_blank">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>View Live Website</span>
            </a>
            <a href="/vendor/logout.php" style="color: var(--accent-red); font-weight: 700;">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Sign Out of Portal</span>
            </a>
            <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 4px;">
                Henad Innovations &amp; Global Services LLC
            </div>
        </div>
    </aside>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleMobileSidebar()"></div>

    <!-- 2. MAIN CONTENT WRAPPER -->
    <main class="main-wrapper">

        <!-- Top Navigation Header Bar -->
        <header class="top-header">
            <div class="breadcrumb-wrap">
                <button type="button" class="mobile-nav-toggle" onclick="toggleMobileSidebar()" aria-label="Toggle Navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="breadcrumb">
                    Vendor Portal &rsaquo; <strong><?php echo htmlspecialchars($displayBreadcrumb); ?></strong>
                </div>


            </div>

            <div class="header-actions">
                <button type="button" class="ref-pill-btn" onclick="copyRef('<?php echo htmlspecialchars($refCode); ?>')" title="Click to copy your unique reference code">
                    <i class="fa-regular fa-copy"></i>
                    <span>Ref: <code><?php echo htmlspecialchars($refCode); ?></code></span>
                </button>

                <!-- Avatar Dropdown Wrapper -->
                <div style="position: relative;">
                    <button type="button" class="user-avatar-btn" id="vendorAvatarBtn" onclick="toggleAvatarDropdown(event)" aria-haspopup="true" aria-expanded="false" title="Account Menu">
                        <div class="user-avatar-circle">
                            <?php echo $userInitial; ?>
                        </div>
                    </button>

                    <!-- Avatar Dropdown Menu -->
                    <div class="avatar-dropdown-menu" id="vendorAvatarDropdown">
                        <div style="padding: 16px 18px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--accent-red); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.05rem; flex-shrink: 0;">
                                <?php echo $userInitial; ?>
                            </div>
                            <div style="overflow: hidden; flex-grow: 1; text-align: left;">
                                <div style="font-weight: 800; font-size: 0.9rem; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($fullName); ?></div>
                                <div style="font-size: 0.74rem; font-weight: 700; color: #16a34a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px;">
                                    <i class="fa-solid fa-shield-check"></i> <?php echo htmlspecialchars($roleLabel); ?>
                                </div>
                                <div style="font-size: 0.72rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px;"><?php echo htmlspecialchars($email); ?></div>
                            </div>
                        </div>

                        <div style="padding: 6px 0; text-align: left;">
                            <a href="/vendor/index.php?section=gigs_assigned" class="dropdown-item-link">
                                <i class="fa-solid fa-clapperboard" style="width: 18px; color: #64748b; text-align: center;"></i>
                                <span>My Assigned Gigs</span>
                            </a>
                            <a href="/vendor/index.php?section=calendar" class="dropdown-item-link">
                                <i class="fa-solid fa-calendar-days" style="width: 18px; color: #64748b; text-align: center;"></i>
                                <span>Calendar &amp; Availability</span>
                            </a>
                            <a href="/vendor/index.php?section=call_sheets" class="dropdown-item-link">
                                <i class="fa-solid fa-file-pdf" style="width: 18px; color: var(--accent-red); text-align: center;"></i>
                                <span>Call Sheets Vault</span>
                            </a>
                            <a href="/vendor/index.php?section=profile" class="dropdown-item-link">
                                <i class="fa-solid fa-user-pen" style="width: 18px; color: #64748b; text-align: center;"></i>
                                <span>Profile &amp; Contact</span>
                            </a>
                        </div>

                        <div style="border-top: 1px solid #f1f5f9; padding: 6px 0; background: #ffffff; text-align: left;">
                            <a href="/vendor/logout.php" class="dropdown-item-link logout">
                                <i class="fa-solid fa-right-from-bracket" style="width: 18px; text-align: center;"></i>
                                <span>Sign Out</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- PAGE CONTENT BODY -->
        <div class="content-body">

            <!-- ALERTS -->
            <?php if ($successMsg): ?>
                <div class="alert-bar success">
                    <i class="fa-solid fa-circle-check" style="font-size: 1.1rem;"></i>
                    <div><?php echo htmlspecialchars($successMsg); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
                <div class="alert-bar error">
                    <i class="fa-solid fa-circle-exclamation" style="font-size: 1.1rem;"></i>
                    <div><?php echo htmlspecialchars($errorMsg); ?></div>
                </div>
            <?php endif; ?>

            <!-- ========================================================= -->
            <!-- SECTION 1: DASHBOARD OVERVIEW                             -->
            <!-- ========================================================= -->
            <?php if ($activeSection === 'dashboard'): ?>

                <!-- SECTION HEADER -->
                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Vendor Production Dashboard</h1>
                        <p class="section-header-desc">
                            Welcome back, <strong><?php echo htmlspecialchars($fullName); ?></strong> &mdash; manage your assigned shoots, download official studio call sheets, submit wrap reports, and update calendar bookings.
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <a href="/vendor/index.php?section=calendar" class="btn-header-action">
                            <i class="fa-solid fa-calendar-days"></i> Booking Calendar
                        </a>
                        <a href="/vendor/index.php?section=gigs_assigned" class="btn-save-primary">
                            <i class="fa-solid fa-clapperboard"></i> View Assigned Gigs (<?php echo $assignedCount; ?>)
                        </a>
                    </div>
                </div>

                <!-- 4 KPI QUICK STATUS METRIC CARDS -->
                <div class="stat-grid-four">
                    <a href="/vendor/index.php?section=gigs_assigned" class="stat-kpi-card">
                        <div class="stat-icon-box red">
                            <i class="fa-solid fa-clapperboard"></i>
                        </div>
                        <div>
                            <div class="stat-info-title"><?php echo $assignedCount; ?> Gigs Assigned</div>
                            <div class="stat-info-sub" style="color: var(--accent-red); font-weight: 700;">
                                Active &bull; Call Sheets Ready
                            </div>
                        </div>
                    </a>

                    <a href="/vendor/index.php?section=gigs_done" class="stat-kpi-card">
                        <div class="stat-icon-box green">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div>
                            <div class="stat-info-title"><?php echo $doneCount; ?> Gigs Done</div>
                            <div class="stat-info-sub" style="color: #15803d; font-weight: 700;">
                                Completed &bull; Payouts Handled
                            </div>
                        </div>
                    </a>

                    <a href="/vendor/index.php?section=calendar" class="stat-kpi-card">
                        <div class="stat-icon-box blue">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div>
                            <div class="stat-info-title">Calendar Schedule</div>
                            <div class="stat-info-sub">
                                <?php echo !empty($nextGig['shoot_date']) ? 'Next Shoot: ' . date('M d', strtotime($nextGig['shoot_date'])) : 'Open Availability'; ?>
                            </div>
                        </div>
                    </a>

                    <a href="/vendor/index.php?section=call_sheets" class="stat-kpi-card">
                        <div class="stat-icon-box purple">
                            <i class="fa-solid fa-file-pdf"></i>
                        </div>
                        <div>
                            <div class="stat-info-title">Call Sheets Vault</div>
                            <div class="stat-info-sub" style="color: #7e22ce; font-weight: 700;">
                                <?php echo $totalGigsCount; ?> Production Sheets
                            </div>
                        </div>
                    </a>
                </div>

                <!-- SPOTLIGHT BANNER: NEXT UPCOMING GIG -->
                <?php if ($nextGig): ?>
                    <div class="spotlight-banner">
                        <div style="position: relative; z-index: 2;">
                            <div class="spotlight-badge">
                                <i class="fa-solid fa-bell"></i> Next Scheduled Production Call
                            </div>
                            <h2 class="spotlight-title"><?php echo htmlspecialchars($nextGig['title']); ?></h2>
                            <div class="spotlight-meta">
                                <span><i class="fa-regular fa-calendar"></i> <?php echo date('l, M j, Y', strtotime($nextGig['shoot_date'])); ?></span>
                                <span>&bull;</span>
                                <span style="color: #fca5a5; font-weight: 800;"><i class="fa-solid fa-clock"></i> Call Time: <?php echo htmlspecialchars($nextGig['call_time']); ?></span>
                                <span>&bull;</span>
                                <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($nextGig['location']); ?></span>
                            </div>
                        </div>
                        <div style="position: relative; z-index: 2; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <a href="/download_call_sheet.php?gig_id=<?php echo urlencode($nextGig['id']); ?>" target="_blank" class="btn-save-primary" style="background: #ffffff; color: var(--accent-red); font-weight: 800; box-shadow: 0 4px 14px rgba(0,0,0,0.25);">
                                <i class="fa-solid fa-file-arrow-down"></i> Download Call Sheet
                            </a>
                            <button type="button" class="btn-header-action" onclick="openUploadModal('<?php echo htmlspecialchars($nextGig['id']); ?>', '<?php echo htmlspecialchars(addslashes($nextGig['title'])); ?>')" style="background: rgba(255,255,255,0.15); color: #ffffff; border-color: rgba(255,255,255,0.25);">
                                <i class="fa-solid fa-file-arrow-up"></i> Upload Signed Sheet
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- TWO COLUMN LAYOUT: Active Gigs List & Calendar Snapshot -->
                <div class="form-grid-two">
                    
                    <!-- LEFT COLUMN: Upcoming Assigned Shoots -->
                    <div>
                        <div class="dashboard-card">
                            <div class="card-header-row">
                                <div class="card-header-left">
                                    <div class="card-icon-badge">
                                        <i class="fa-solid fa-clapperboard"></i>
                                    </div>
                                    <h2 class="card-title-text">Active &amp; Upcoming Gigs</h2>
                                </div>
                                <a href="/vendor/index.php?section=gigs_assigned" class="btn-header-action">
                                    View All (<?php echo $assignedCount; ?>) <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>

                            <?php if (empty($assignedGigs)): ?>
                                <div style="text-align: center; padding: 32px 16px; color: #64748b;">
                                    <i class="fa-solid fa-film" style="font-size: 2.2rem; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                                    No active gigs currently assigned. Update your calendar availability to receive upcoming shoot requests!
                                </div>
                            <?php else: ?>
                                <?php foreach (array_slice($assignedGigs, 0, 2) as $gig): ?>
                                    <div class="gig-item-card">
                                        <div class="gig-top-row">
                                            <div>
                                                <div class="gig-title"><?php echo htmlspecialchars($gig['title']); ?></div>
                                                <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                                    Client: <?php echo htmlspecialchars($gig['client']); ?> &bull; Ref: <?php echo htmlspecialchars($gig['id']); ?>
                                                </div>
                                            </div>
                                            <span class="gig-status-pill <?php echo htmlspecialchars($gig['status']); ?>">
                                                <?php echo ucfirst($gig['status']); ?>
                                            </span>
                                        </div>

                                        <div class="gig-details-grid">
                                            <div>
                                                <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Call Time</div>
                                                <div style="font-weight: 800; color: var(--accent-red); font-size: 0.95rem; margin-top: 2px;">
                                                    <?php echo htmlspecialchars($gig['call_time']); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Shoot Date</div>
                                                <div style="font-weight: 700; color: #0f172a; font-size: 0.9rem; margin-top: 2px;">
                                                    <?php echo date('M d, Y', strtotime($gig['shoot_date'])); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Rate</div>
                                                <div style="font-weight: 700; color: #15803d; font-size: 0.9rem; margin-top: 2px;">
                                                    <?php echo htmlspecialchars($gig['day_rate'] ?? 'Per SOW'); ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="gig-action-toolbar">
                                            <a href="/download_call_sheet.php?gig_id=<?php echo urlencode($gig['id']); ?>" target="_blank" class="btn-save-primary" style="padding: 7px 12px; font-size: 0.8rem;">
                                                <i class="fa-solid fa-download"></i> Call Sheet
                                            </a>
                                            <button type="button" class="btn-header-action" onclick="openUploadModal('<?php echo htmlspecialchars($gig['id']); ?>', '<?php echo htmlspecialchars(addslashes($gig['title'])); ?>')">
                                                <i class="fa-solid fa-upload"></i> Upload Signed Sheet
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Calendar Snapshot & Gigs Done Preview -->
                    <div>
                        <div class="dashboard-card">
                            <div class="card-header-row">
                                <div class="card-header-left">
                                    <div class="card-icon-badge" style="background-color: #eff6ff; border-color: #bfdbfe; color: #2563eb;">
                                        <i class="fa-solid fa-calendar-days"></i>
                                    </div>
                                    <h2 class="card-title-text"><?php echo $monthName; ?> Schedule</h2>
                                </div>
                                <a href="/vendor/index.php?section=calendar" class="btn-header-action">
                                    Full Calendar <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>

                            <p style="font-size: 0.86rem; color: #475569; margin-bottom: 16px;">
                                Dates marked in red highlight your assigned production shoots and call times.
                            </p>

                            <!-- Mini Calendar Grid -->
                            <div class="calendar-grid" style="margin-bottom: 20px;">
                                <div class="cal-day-name">Su</div>
                                <div class="cal-day-name">Mo</div>
                                <div class="cal-day-name">Tu</div>
                                <div class="cal-day-name">We</div>
                                <div class="cal-day-name">Th</div>
                                <div class="cal-day-name">Fr</div>
                                <div class="cal-day-name">Sa</div>

                                <?php for ($i = 0; $i < $startDayOfWeek; $i++): ?>
                                    <div class="cal-cell empty"></div>
                                <?php endfor; ?>

                                <?php 
                                for ($d = 1; $d <= $daysInMonth; $d++): 
                                    $cellDate = sprintf('%04d-%02d-%02d', $calendarYear, $calendarMonth, $d);
                                    $hasGigs = !empty($gigsByDate[$cellDate]);
                                    $isToday = ($cellDate === date('Y-m-d'));
                                ?>
                                    <div class="cal-cell <?php echo $isToday ? 'today' : ''; ?>" style="min-height: 52px; padding: 4px;" onclick="window.location.href='/vendor/index.php?section=calendar&selected_date=<?php echo $cellDate; ?>'">
                                        <div class="cal-day-num" style="<?php echo $isToday ? 'color: var(--accent-red); font-weight: 900;' : ''; ?>"><?php echo $d; ?></div>
                                        <?php if ($hasGigs): ?>
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--accent-red); display: block; margin-top: 4px;" title="Shoot Scheduled"></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>

                            <!-- Gigs Done Mini List -->
                            <div style="border-top: 1px solid #f1f5f9; padding-top: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                    <span style="font-weight: 700; font-size: 0.92rem; color: #0f172a;">
                                        <i class="fa-solid fa-circle-check" style="color: #10b981;"></i> Recent Completed Gigs (<?php echo $doneCount; ?>)
                                    </span>
                                    <a href="/vendor/index.php?section=gigs_done" style="font-size: 0.78rem; color: var(--accent-red); text-decoration: none; font-weight: 700;">View Archive</a>
                                </div>

                                <?php if (empty($doneGigs)): ?>
                                    <div style="font-size: 0.82rem; color: #64748b;">No completed gigs recorded yet.</div>
                                <?php else: ?>
                                    <?php foreach (array_slice($doneGigs, 0, 2) as $dGig): ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #f8fafc; border-radius: 8px; margin-bottom: 8px; border: 1px solid #e2e8f0;">
                                            <div>
                                                <div style="font-weight: 700; font-size: 0.86rem; color: #0f172a;"><?php echo htmlspecialchars($dGig['title']); ?></div>
                                                <div style="font-size: 0.74rem; color: #64748b;"><?php echo date('M d, Y', strtotime($dGig['shoot_date'])); ?> &bull; Wrapped</div>
                                            </div>
                                            <span style="font-size: 0.72rem; font-weight: 800; color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 6px;">Done</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>

            <!-- ========================================================= -->
            <!-- SECTION 2: GIGS ASSIGNED (ACTIVE & UPCOMING)              -->
            <!-- ========================================================= -->
            <?php elseif ($activeSection === 'gigs_assigned'): ?>

                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Assigned Production Gigs</h1>
                        <p class="section-header-desc">
                            All active and confirmed shoot assignments for <strong><?php echo htmlspecialchars($fullName); ?></strong>. Download official call sheets, confirm call times, and upload signed sheets.
                        </p>
                    </div>
                    <a href="/vendor/index.php?section=calendar" class="btn-save-primary">
                        <i class="fa-solid fa-calendar-days"></i> Open Booking Calendar
                    </a>
                </div>

                <?php if (empty($assignedGigs)): ?>
                    <div class="dashboard-card" style="text-align: center; padding: 48px 24px;">
                        <i class="fa-solid fa-clapperboard" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 16px; display: block;"></i>
                        <h2 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 8px;">No Gigs Currently Assigned</h2>
                        <p style="font-size: 0.92rem; color: #64748b; max-width: 480px; margin: 0 auto 20px auto;">
                            You currently have no active shoots assigned. Keep your calendar availability up to date so the production team can book you for upcoming commercial and video projects.
                        </p>
                        <a href="/vendor/index.php?section=calendar" class="btn-save-primary">
                            Set Calendar Availability
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($assignedGigs as $gig): ?>
                        <div class="gig-item-card">
                            <div class="gig-top-row">
                                <div>
                                    <div class="gig-title"><?php echo htmlspecialchars($gig['title']); ?></div>
                                    <div style="font-size: 0.84rem; color: #64748b; margin-top: 4px;">
                                        <strong>Client:</strong> <?php echo htmlspecialchars($gig['client']); ?> &bull; 
                                        <strong>Gig Ref:</strong> <code style="color: var(--accent-red); font-weight: 700;"><?php echo htmlspecialchars($gig['id']); ?></code>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="gig-status-pill <?php echo htmlspecialchars($gig['status']); ?>">
                                        <i class="fa-solid fa-circle-dot"></i> <?php echo ucfirst($gig['status']); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="gig-details-grid">
                                <div>
                                    <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Call Time</div>
                                    <div style="font-weight: 800; color: var(--accent-red); font-size: 1.1rem; margin-top: 2px;">
                                        <?php echo htmlspecialchars($gig['call_time']); ?>
                                    </div>
                                    <div style="font-size: 0.74rem; color: #64748b;">Wrap: <?php echo htmlspecialchars($gig['wrap_time'] ?? '06:00 PM'); ?></div>
                                </div>
                                <div>
                                    <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Shoot Date</div>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 0.95rem; margin-top: 2px;">
                                        <?php echo date('l, F j, Y', strtotime($gig['shoot_date'])); ?>
                                    </div>
                                    <div style="font-size: 0.74rem; color: #64748b;">Production Day 1 of 1</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Role &amp; Rate</div>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 0.95rem; margin-top: 2px;">
                                        <?php echo htmlspecialchars($gig['role']); ?>
                                    </div>
                                    <div style="font-size: 0.74rem; color: #15803d; font-weight: 700;"><?php echo htmlspecialchars($gig['day_rate'] ?? 'Per SOW'); ?></div>
                                </div>
                                <div>
                                    <div style="font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Location / Stage</div>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 0.9rem; margin-top: 2px; line-height: 1.4;">
                                        <?php echo htmlspecialchars($gig['location']); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes if any -->
                            <?php if (!empty($gig['notes'])): ?>
                                <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 10px 14px; font-size: 0.84rem; color: #92400e; margin-bottom: 16px;">
                                    <strong><i class="fa-solid fa-lightbulb"></i> Director Directives:</strong> <?php echo htmlspecialchars($gig['notes']); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Uploaded Call Sheet Status -->
                            <?php if (!empty($gig['uploaded_call_sheet'])): ?>
                                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 14px; font-size: 0.84rem; color: #166534; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
                                    <div>
                                        <i class="fa-solid fa-circle-check"></i> <strong>Signed Call Sheet on File:</strong> <?php echo htmlspecialchars($gig['uploaded_call_sheet_name'] ?? 'Signed_CallSheet.pdf'); ?>
                                        <span style="font-size: 0.76rem; color: #15803d; margin-left: 6px;">(Uploaded <?php echo !empty($gig['uploaded_call_sheet_at']) ? date('M d, h:i A', strtotime($gig['uploaded_call_sheet_at'])) : 'Recently'; ?>)</span>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($gig['uploaded_call_sheet']); ?>" target="_blank" style="color: #15803d; font-weight: 700; text-decoration: none;">View File</a>
                                </div>
                            <?php endif; ?>

                            <!-- Action Toolbar -->
                            <div class="gig-action-toolbar">
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <!-- 1. Download Call Sheet -->
                                    <a href="/download_call_sheet.php?gig_id=<?php echo urlencode($gig['id']); ?>" target="_blank" class="btn-save-primary">
                                        <i class="fa-solid fa-file-arrow-down"></i> Download Call Sheet
                                    </a>

                                    <!-- 2. Upload Signed Call Sheet / Wrap Report -->
                                    <button type="button" class="btn-header-action" onclick="openUploadModal('<?php echo htmlspecialchars($gig['id']); ?>', '<?php echo htmlspecialchars(addslashes($gig['title'])); ?>')">
                                        <i class="fa-solid fa-file-arrow-up"></i> Upload Signed Sheet
                                    </button>
                                </div>

                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <!-- Confirm Gig (if assigned) -->
                                    <?php if ($gig['status'] === 'assigned'): ?>
                                        <form method="POST" action="/vendor/index.php?section=gigs_assigned" style="margin: 0;">
                                            <input type="hidden" name="action" value="confirm_gig">
                                            <input type="hidden" name="gig_id" value="<?php echo htmlspecialchars($gig['id']); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <button type="submit" class="btn-header-action green">
                                                <i class="fa-solid fa-check"></i> Confirm Gig
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Mark as Done -->
                                    <form method="POST" action="/vendor/index.php?section=gigs_assigned" style="margin: 0;" onsubmit="return confirm('Mark this shoot as Wrapped & Done?');">
                                        <input type="hidden" name="action" value="mark_gig_done">
                                        <input type="hidden" name="gig_id" value="<?php echo htmlspecialchars($gig['id']); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <button type="submit" class="btn-header-action" title="Mark shoot as wrapped">
                                            <i class="fa-solid fa-flag-checkered"></i> Mark as Done
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            <!-- ========================================================= -->
            <!-- SECTION 3: GIGS DONE (COMPLETED SHOOTS ARCHIVE)           -->
            <!-- ========================================================= -->
            <?php elseif ($activeSection === 'gigs_done'): ?>

                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Completed Productions (Gigs Done)</h1>
                        <p class="section-header-desc">
                            Archive of all wrapped productions, media deliverables status, archived call sheets, and direct deposit disbursement records.
                        </p>
                    </div>
                </div>

                <?php if (empty($doneGigs)): ?>
                    <div class="dashboard-card" style="text-align: center; padding: 48px 24px;">
                        <i class="fa-solid fa-circle-check" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 16px; display: block;"></i>
                        <h2 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 8px;">No Completed Gigs in Archive</h2>
                        <p style="font-size: 0.92rem; color: #64748b; max-width: 480px; margin: 0 auto;">
                            Completed productions will be catalogued here once shoot wraps are recorded and deliverables are signed off.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="dashboard-card" style="padding: 0; overflow: hidden;">
                        <table class="cs-vault-table">
                            <thead>
                                <tr>
                                    <th>Production Title &amp; Client</th>
                                    <th>Shoot Date</th>
                                    <th>Role &amp; Compensation</th>
                                    <th>Deliverables &amp; Media</th>
                                    <th>Call Sheet</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doneGigs as $gig): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($gig['title']); ?></div>
                                            <div style="font-size: 0.78rem; color: #64748b; margin-top: 2px;">
                                                Client: <?php echo htmlspecialchars($gig['client']); ?> &bull; Ref: <?php echo htmlspecialchars($gig['id']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: #0f172a;"><?php echo date('M d, Y', strtotime($gig['shoot_date'])); ?></div>
                                            <div style="font-size: 0.74rem; color: #64748b;">Wrapped at <?php echo htmlspecialchars($gig['wrap_time'] ?? '06:00 PM'); ?></div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($gig['role']); ?></div>
                                            <div style="font-size: 0.76rem; color: #15803d; font-weight: 800;"><?php echo htmlspecialchars($gig['day_rate'] ?? 'Per SOW'); ?></div>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.84rem; font-weight: 700; color: #0f172a;">
                                                <i class="fa-solid fa-cloud-arrow-up" style="color: #2563eb;"></i> <?php echo htmlspecialchars($gig['deliverables_status'] ?? 'Delivered'); ?>
                                            </div>
                                            <div style="font-size: 0.74rem; color: #15803d; font-weight: 600;">
                                                <?php echo htmlspecialchars($gig['payment_status'] ?? 'Processed via Stripe Direct Deposit'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="/download_call_sheet.php?gig_id=<?php echo urlencode($gig['id']); ?>" target="_blank" class="btn-header-action" style="font-size: 0.78rem; padding: 6px 10px;">
                                                <i class="fa-solid fa-file-pdf" style="color: var(--accent-red);"></i> Call Sheet
                                            </a>
                                            <?php if (!empty($gig['uploaded_call_sheet'])): ?>
                                                <div style="margin-top: 4px;">
                                                    <a href="<?php echo htmlspecialchars($gig['uploaded_call_sheet']); ?>" target="_blank" style="font-size: 0.72rem; color: #15803d; font-weight: 700; text-decoration: none;">
                                                        <i class="fa-solid fa-paperclip"></i> Signed Sheet
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.72rem; font-weight: 800; background: #dcfce7; color: #15803d; padding: 3px 9px; border-radius: 20px; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fa-solid fa-circle-check"></i> Wrapped
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <!-- ========================================================= -->
            <!-- SECTION 4: CALENDAR BOOKING & AVAILABILITY                -->
            <!-- ========================================================= -->
            <?php elseif ($activeSection === 'calendar'): ?>

                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Calendar Booking &amp; Schedule</h1>
                        <p class="section-header-desc">
                            View all scheduled production call dates, manage your contractor availability, and submit booking requests for upcoming commercial shoots.
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn-save-primary" onclick="openAvailabilityModal()">
                            <i class="fa-solid fa-calendar-check"></i> Set Availability Dates
                        </button>
                    </div>
                </div>

                <div class="dashboard-card">
                    <!-- Calendar Month Nav -->
                    <div class="calendar-header-nav">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; letter-spacing: -0.3px;">
                                <?php echo $monthName; ?>
                            </h2>
                            <span style="font-size: 0.76rem; font-weight: 700; background: #fee2e2; color: var(--accent-red); padding: 3px 8px; border-radius: 6px;">
                                <?php echo $assignedCount; ?> Scheduled Gigs
                            </span>
                        </div>

                        <div style="display: flex; gap: 6px;">
                            <?php 
                            $prevMonth = ($calendarMonth == 1) ? 12 : ($calendarMonth - 1);
                            $prevYear = ($calendarMonth == 1) ? ($calendarYear - 1) : $calendarYear;
                            $nextMonth = ($calendarMonth == 12) ? 1 : ($calendarMonth + 1);
                            $nextYear = ($calendarMonth == 12) ? ($calendarYear + 1) : $calendarYear;
                            ?>
                            <a href="/vendor/index.php?section=calendar&month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn-header-action" title="Previous Month">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                            <a href="/vendor/index.php?section=calendar&month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn-header-action">
                                Today
                            </a>
                            <a href="/vendor/index.php?section=calendar&month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn-header-action" title="Next Month">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="calendar-grid">
                        <div class="cal-day-name">Sunday</div>
                        <div class="cal-day-name">Monday</div>
                        <div class="cal-day-name">Tuesday</div>
                        <div class="cal-day-name">Wednesday</div>
                        <div class="cal-day-name">Thursday</div>
                        <div class="cal-day-name">Friday</div>
                        <div class="cal-day-name">Saturday</div>

                        <?php for ($i = 0; $i < $startDayOfWeek; $i++): ?>
                            <div class="cal-cell empty"></div>
                        <?php endfor; ?>

                        <?php 
                        for ($d = 1; $d <= $daysInMonth; $d++): 
                            $cellDate = sprintf('%04d-%02d-%02d', $calendarYear, $calendarMonth, $d);
                            $cellGigs = $gigsByDate[$cellDate] ?? [];
                            $isToday = ($cellDate === date('Y-m-d'));
                            $isAvail = in_array($cellDate, $savedAvailDates, true);
                        ?>
                            <div class="cal-cell <?php echo $isToday ? 'today' : ''; ?>" onclick="inspectDateGigs('<?php echo $cellDate; ?>')">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span class="cal-day-num" style="<?php echo $isToday ? 'color: var(--accent-red); font-weight: 900;' : ''; ?>">
                                        <?php echo $d; ?>
                                    </span>
                                    <?php if ($isAvail): ?>
                                        <span style="font-size: 0.65rem; color: #15803d;" title="Marked Available"><i class="fa-solid fa-check"></i></span>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <?php foreach ($cellGigs as $cg): ?>
                                        <span class="cal-event-pill <?php echo htmlspecialchars($cg['status']); ?>" title="<?php echo htmlspecialchars($cg['title']); ?> (<?php echo htmlspecialchars($cg['call_time']); ?>)">
                                            <?php echo htmlspecialchars($cg['call_time']); ?> &bull; <?php echo htmlspecialchars($cg['title']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Selected Date Quick Inspector Card (hidden until clicked or if date passed) -->
                <div id="dateInspectorCard" class="dashboard-card" style="display: none;">
                    <div class="card-header-row">
                        <div class="card-header-left">
                            <div class="card-icon-badge">
                                <i class="fa-solid fa-calendar-day"></i>
                            </div>
                            <h2 class="card-title-text" id="inspectorDateTitle">Schedule for Date</h2>
                        </div>
                        <button type="button" class="btn-header-action" onclick="closeDateInspector()">Close</button>
                    </div>
                    <div id="inspectorDateContent" style="font-size: 0.9rem; color: #334155;"></div>
                </div>

            <!-- ========================================================= -->
            <!-- SECTION 5: CALL SHEETS VAULT (CENTRAL VAULT)              -->
            <!-- ========================================================= -->
            <?php elseif ($activeSection === 'call_sheets'): ?>

                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Call Sheets Repository</h1>
                        <p class="section-header-desc">
                            Direct access to all official studio call sheets, call times, location specs, and signed contractor wrap uploads across all your gigs.
                        </p>
                    </div>
                </div>

                <div class="dashboard-card" style="padding: 0; overflow: hidden;">
                    <table class="cs-vault-table">
                        <thead>
                            <tr>
                                <th>Production Shoot</th>
                                <th>Call Time &amp; Date</th>
                                <th>Director / Producer</th>
                                <th>Studio Call Sheet</th>
                                <th>Signed Vendor Copy</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allVendorGigs as $gig): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($gig['title']); ?></div>
                                        <div style="font-size: 0.76rem; color: #64748b;">Ref: <?php echo htmlspecialchars($gig['id']); ?> &bull; <?php echo htmlspecialchars($gig['location']); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 800; color: var(--accent-red);"><?php echo htmlspecialchars($gig['call_time']); ?></div>
                                        <div style="font-size: 0.74rem; color: #0f172a;"><?php echo date('M d, Y', strtotime($gig['shoot_date'])); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 0.84rem;"><?php echo htmlspecialchars($gig['director'] ?? 'Oluwatosin Ligali'); ?></div>
                                        <div style="font-size: 0.74rem; color: #64748b;"><?php echo htmlspecialchars($gig['director_phone'] ?? '08027487449'); ?></div>
                                    </td>
                                    <td>
                                        <a href="/download_call_sheet.php?gig_id=<?php echo urlencode($gig['id']); ?>" target="_blank" class="btn-save-primary" style="padding: 6px 12px; font-size: 0.8rem;">
                                            <i class="fa-solid fa-download"></i> Download PDF
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (!empty($gig['uploaded_call_sheet'])): ?>
                                            <a href="<?php echo htmlspecialchars($gig['uploaded_call_sheet']); ?>" target="_blank" style="color: #15803d; font-weight: 700; font-size: 0.82rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fa-solid fa-file-check"></i> <?php echo htmlspecialchars($gig['uploaded_call_sheet_name'] ?? 'Signed.pdf'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="font-size: 0.76rem; color: #94a3b8; font-style: italic;">Not yet uploaded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-header-action" onclick="openUploadModal('<?php echo htmlspecialchars($gig['id']); ?>', '<?php echo htmlspecialchars(addslashes($gig['title'])); ?>')">
                                            <i class="fa-solid fa-upload"></i> Upload
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <!-- ========================================================= -->
            <!-- SECTION 6: PROFILE, LEGAL & SECURITY (PRESERVED CLEANLY)  -->
            <!-- ========================================================= -->
            <?php elseif ($activeSection === 'profile'): ?>

                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Contractor Profile &amp; Contact Details</h1>
                        <p class="section-header-desc">
                            Update your personal contractor information, residential address, emergency contact, and currency preference.
                        </p>
                    </div>
                </div>

                <div class="dashboard-card" style="max-width: 800px;">
                    <form method="POST" action="/vendor/index.php?section=profile">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <div class="card-header-row">
                            <div class="card-header-left">
                                <div class="card-icon-badge">
                                    <i class="fa-solid fa-address-card"></i>
                                </div>
                                <h2 class="card-title-text">Personal &amp; Contact Information</h2>
                            </div>
                        </div>

                        <div class="form-grid-two">
                            <div class="form-field">
                                <label class="form-label-title">Full Legal Name</label>
                                <input type="text" class="form-input-text" value="<?php echo htmlspecialchars($fullName); ?>" disabled style="background: #f1f5f9; cursor: not-allowed;">
                            </div>
                            <div class="form-field">
                                <label class="form-label-title">Email Address</label>
                                <input type="email" class="form-input-text" value="<?php echo htmlspecialchars($email); ?>" disabled style="background: #f1f5f9; cursor: not-allowed;">
                            </div>
                            <div class="form-field">
                                <label class="form-label-title">Phone Number *</label>
                                <input type="tel" name="phone" class="form-input-text" value="<?php echo htmlspecialchars($phone); ?>" required>
                            </div>
                            <div class="form-field">
                                <label class="form-label-title">Preferred Currency</label>
                                <select name="currency" class="form-input-text">
                                    <option value="USD" <?php echo (($vendor['currency'] ?? '') === 'USD') ? 'selected' : ''; ?>>USD ($ - United States Dollar)</option>
                                    <option value="NGN" <?php echo (($vendor['currency'] ?? '') === 'NGN') ? 'selected' : ''; ?>>NGN (₦ - Nigerian Naira)</option>
                                    <option value="GBP" <?php echo (($vendor['currency'] ?? '') === 'GBP') ? 'selected' : ''; ?>>GBP (£ - British Pound)</option>
                                    <option value="EUR" <?php echo (($vendor['currency'] ?? '') === 'EUR') ? 'selected' : ''; ?>>EUR (€ - Euro)</option>
                                    <option value="CAD" <?php echo (($vendor['currency'] ?? '') === 'CAD') ? 'selected' : ''; ?>>CAD ($ - Canadian Dollar)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-field">
                            <label class="form-label-title">Street Address *</label>
                            <input type="text" name="street_address" class="form-input-text" value="<?php echo htmlspecialchars($streetAddress); ?>" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 14px; margin-bottom: 24px;">
                            <div>
                                <label class="form-label-title">City</label>
                                <input type="text" name="city" class="form-input-text" value="<?php echo htmlspecialchars($city); ?>">
                            </div>
                            <div>
                                <label class="form-label-title">State</label>
                                <input type="text" name="state" class="form-input-text" value="<?php echo htmlspecialchars($state); ?>">
                            </div>
                            <div>
                                <label class="form-label-title">ZIP</label>
                                <input type="text" name="zip" class="form-input-text" value="<?php echo htmlspecialchars($zip); ?>">
                            </div>
                        </div>

                        <div class="card-header-row" style="margin-top: 24px; padding-top: 18px; border-top: 1px solid #f1f5f9;">
                            <div class="card-header-left">
                                <div class="card-icon-badge">
                                    <i class="fa-solid fa-phone-volume"></i>
                                </div>
                                <h2 class="card-title-text">Emergency Contact Information</h2>
                            </div>
                        </div>

                        <div class="form-grid-two">
                            <div class="form-field">
                                <label class="form-label-title">Contact Name</label>
                                <input type="text" name="emergency_name" class="form-input-text" value="<?php echo htmlspecialchars($emergencyName); ?>">
                            </div>
                            <div class="form-field">
                                <label class="form-label-title">Relationship</label>
                                <input type="text" name="emergency_relationship" class="form-input-text" value="<?php echo htmlspecialchars($emergencyRel); ?>">
                            </div>
                        </div>

                        <div class="form-field">
                            <label class="form-label-title">Emergency Phone Number</label>
                            <input type="tel" name="emergency_phone" class="form-input-text" value="<?php echo htmlspecialchars($emergencyPhone); ?>">
                        </div>

                        <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 12px;">
                            <a href="/vendor/index.php" class="btn-header-action">Cancel</a>
                            <button type="submit" class="btn-save-primary">
                                <i class="fa-solid fa-floppy-disk"></i> Save Profile Details
                            </button>
                        </div>
                    </form>
                </div>

            <?php elseif ($activeSection === 'legal'): ?>

                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Executed Legal Agreements &amp; NDA</h1>
                        <p class="section-header-desc">
                            Review and download your fully counter-signed Master Services Agreement, Non-Disclosure Agreement, and Workplace Policies.
                        </p>
                    </div>
                    <a href="<?php echo htmlspecialchars($downloadUrl); ?>" target="_blank" class="btn-save-primary">
                        <i class="fa-solid fa-download"></i> Download Counter-Signed PDF
                    </a>
                </div>

                <div class="dashboard-card">
                    <div class="card-header-row">
                        <div class="card-header-left">
                            <div class="card-icon-badge">
                                <i class="fa-solid fa-file-contract"></i>
                            </div>
                            <h2 class="card-title-text">Digital Document Vault</h2>
                        </div>
                        <span style="font-size: 0.8rem; font-weight: 800; background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 8px;">
                            <i class="fa-solid fa-circle-check"></i> Legally Binding Execution
                        </span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: #fee2e2; color: var(--accent-red); display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                                <i class="fa-solid fa-file-pdf"></i>
                            </div>
                            <div>
                                <div style="font-weight: 800; font-size: 1rem; color: #0f172a;">Master Services Agreement &amp; Non-Disclosure Agreement</div>
                                <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">Signed on <?php echo htmlspecialchars($sigDate); ?> &bull; Ref: <?php echo htmlspecialchars($refCode); ?></div>
                            </div>
                        </div>
                        <a href="<?php echo htmlspecialchars($downloadUrl); ?>" target="_blank" class="btn-save-primary">
                            <i class="fa-solid fa-download"></i> Download Official PDF
                        </a>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 14px; font-size: 0.9rem; color: #334155; line-height: 1.6;">
                        <div><strong>1. Intellectual Property Ownership:</strong> You are engaged as an independent contractor, not an employee. All work product, including raw and edited media, is assigned to Henad Innovations and Global Services LLC DBA Falhenmedia and Productions upon creation. You retain no license or ownership rights.</div>
                        <div><strong>2. Comprehensive Non-Disclosure Agreement (NDA):</strong> Strictest confidentiality regarding unreleased client media, call-sheets, production schedules, and proprietary creative assets.</div>
                        <div><strong>3. Compensation &amp; Indemnification:</strong> Compensation is outlined in individual SOWs. You agree to indemnify Henad Innovations and Global Services LLC DBA Falhenmedia and Productions against claims arising from your negligence or breach of this Agreement.</div>
                    </div>
                </div>

            <?php elseif ($activeSection === 'payouts'): ?>

                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Direct Deposit &amp; Payout Settings</h1>
                        <p class="section-header-desc">
                            Manage your contractor payment preferences and view your secure Stripe Connect direct deposit integration.
                        </p>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px; display: flex; gap: 16px; align-items: flex-start; margin-bottom: 20px;">
                        <div style="width: 44px; height: 44px; border-radius: 10px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0;">
                            <i class="fa-solid fa-shield-check"></i>
                        </div>
                        <div>
                            <h2 style="font-weight: 800; color: #15803d; font-size: 1.05rem; margin-bottom: 4px;">Bank-Grade Direct Deposit via Stripe Connect</h2>
                            <p style="color: #166534; font-size: 0.88rem; line-height: 1.6;">
                                No bank account or routing numbers are stored directly on our servers. When projects and Statements of Work (SOWs) are wrapped, Falhen Media initiates a tokenized direct deposit payout via <strong>Stripe Connect</strong> with 256-bit encryption.
                            </p>
                        </div>
                    </div>

                    <div class="form-grid-two">
                        <div>
                            <div style="font-size: 0.74rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Preferred Currency</div>
                            <div style="font-weight: 700; color: #0f172a; font-size: 1rem; margin-top: 2px;"><?php echo htmlspecialchars($vendor['currency'] ?? 'USD'); ?></div>
                        </div>
                        <div>
                            <div style="font-size: 0.74rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Direct Deposit Status</div>
                            <div style="font-weight: 700; color: #15803d; font-size: 1rem; margin-top: 2px;">
                                <i class="fa-solid fa-circle-check"></i> Stripe Connect Verified
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($activeSection === 'security'): ?>

                <div class="section-header-bar">
                    <div>
                        <h1 class="section-header-title">Security &amp; Portal Password</h1>
                        <p class="section-header-desc">
                            Set or change your personal vendor portal password for signing in with your email and password.
                        </p>
                    </div>
                </div>

                <div class="dashboard-card" style="max-width: 600px;">
                    <div class="card-header-row">
                        <div class="card-header-left">
                            <div class="card-icon-badge">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <h2 class="card-title-text">Personal Portal Password</h2>
                        </div>
                    </div>

                    <p style="font-size: 0.88rem; color: #475569; margin-bottom: 20px; line-height: 1.5;">
                        You can set a personal password to sign in to the Vendor Portal using your email and password, or you can continue logging in using your assigned Reference Code (<strong><?php echo htmlspecialchars($refCode); ?></strong>).
                    </p>

                    <form method="POST" action="/vendor/index.php?section=security">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <div class="form-field">
                            <label class="form-label-title" for="new_password">New Password</label>
                            <input type="password" name="new_password" id="new_password" class="form-input-text" placeholder="Minimum 6 characters" minlength="6" required>
                        </div>

                        <div class="form-field">
                            <label class="form-label-title" for="confirm_password">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-input-text" placeholder="Re-enter new password" minlength="6" required>
                        </div>

                        <button type="submit" class="btn-save-primary">
                            <i class="fa-solid fa-shield-check"></i> Save Personal Password
                        </button>
                    </form>
                </div>

            <?php endif; ?>

        </div>
    </main>

    <!-- MODAL 1: UPLOAD SIGNED CALL SHEET / WRAP REPORT -->
    <div id="uploadCallSheetModal" class="modal-overlay">
        <div class="modal-dialog">
            <button type="button" class="modal-close-btn" onclick="closeUploadModal()" aria-label="Close modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin-bottom: 6px;">
                Upload Signed Call Sheet / Wrap Report
            </h2>
            <p id="modalGigTitleDesc" style="font-size: 0.86rem; color: #64748b; margin-bottom: 20px;">
                Attach your signed physical or electronic call sheet, crew sign-in sheet, or shoot wrap report.
            </p>

            <form method="POST" action="/vendor/index.php?section=<?php echo htmlspecialchars($activeSection); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_call_sheet">
                <input type="hidden" name="gig_id" id="modalUploadGigId" value="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div class="form-field">
                    <label class="form-label-title">Select Call Sheet Document (PDF, DOCX, JPG, PNG)</label>
                    <input type="file" name="call_sheet_file" class="form-input-text" accept=".pdf,.docx,.doc,.jpg,.jpeg,.png" required>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">Max file size: 15MB. Ensure signatures or shoot wrap logs are clearly legible.</div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                    <button type="button" class="btn-header-action" onclick="closeUploadModal()">Cancel</button>
                    <button type="submit" class="btn-save-primary">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload &amp; Submit Document
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: SET CALENDAR AVAILABILITY -->
    <div id="availabilityModal" class="modal-overlay">
        <div class="modal-dialog">
            <button type="button" class="modal-close-btn" onclick="closeAvailabilityModal()" aria-label="Close modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin-bottom: 6px;">
                Set Calendar Availability
            </h2>
            <p style="font-size: 0.86rem; color: #64748b; margin-bottom: 20px;">
                Declare your available dates for upcoming commercial and production shoots.
            </p>

            <form method="POST" action="/vendor/index.php?section=calendar">
                <input type="hidden" name="action" value="save_availability">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div class="form-field">
                    <label class="form-label-title">Available Dates (Comma-separated YYYY-MM-DD)</label>
                    <input type="text" name="available_dates" class="form-input-text" value="<?php echo htmlspecialchars(implode(', ', $savedAvailDates)); ?>" placeholder="e.g. 2026-09-20, 2026-09-25, 2026-10-02">
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">Format: YYYY-MM-DD. Separate multiple dates with commas.</div>
                </div>

                <div class="form-field">
                    <label class="form-label-title">Schedule Notes &amp; Gear Preferences</label>
                    <textarea name="availability_notes" class="form-input-text" rows="3" placeholder="e.g. Available for weekend shoots. Operating Sony FX6 package."><?php echo htmlspecialchars($vendorAvailability['notes'] ?? ''); ?></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                    <button type="button" class="btn-header-action" onclick="closeAvailabilityModal()">Cancel</button>
                    <button type="submit" class="btn-save-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Save Availability
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('vendorSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar && backdrop) {
                sidebar.classList.toggle('open');
                backdrop.classList.toggle('open');
            }
        }

        function toggleAvatarDropdown(event) {
            event.stopPropagation();
            const menu = document.getElementById('vendorAvatarDropdown');
            if (menu) {
                menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
            }
        }

        document.addEventListener('click', function(e) {
            const menu = document.getElementById('vendorAvatarDropdown');
            if (menu && !e.target.closest('#vendorAvatarBtn')) {
                menu.style.display = 'none';
            }
        });

        function copyRef(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Reference Code ' + text + ' copied to clipboard!');
            }).catch(() => {
                prompt('Copy reference code:', text);
            });
        }

        function openUploadModal(gigId, gigTitle) {
            document.getElementById('modalUploadGigId').value = gigId;
            document.getElementById('modalGigTitleDesc').innerText = 'Attach your signed call sheet or shoot wrap report for: ' + gigTitle;
            document.getElementById('uploadCallSheetModal').classList.add('active');
        }

        function closeUploadModal() {
            document.getElementById('uploadCallSheetModal').classList.remove('active');
        }

        function openAvailabilityModal() {
            document.getElementById('availabilityModal').classList.add('active');
        }

        function closeAvailabilityModal() {
            document.getElementById('availabilityModal').classList.remove('active');
        }

        // Calendar Inspector
        const gigsByDateMap = <?php echo json_encode($gigsByDate); ?>;
        function inspectDateGigs(dateStr) {
            const inspector = document.getElementById('dateInspectorCard');
            const titleEl = document.getElementById('inspectorDateTitle');
            const contentEl = document.getElementById('inspectorDateContent');

            if (!inspector || !contentEl) return;

            titleEl.innerText = 'Production Schedule for ' + dateStr;
            const gigs = gigsByDateMap[dateStr] || [];

            if (gigs.length === 0) {
                contentEl.innerHTML = '<div style="padding: 12px 0; color: #64748b;"><i class="fa-regular fa-calendar"></i> No shoots currently scheduled on this date. You are marked available for studio bookings.</div>';
            } else {
                let html = '<div style="display: flex; flex-direction: column; gap: 12px;">';
                gigs.forEach(g => {
                    html += `
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 800; font-size: 0.95rem; color: #0f172a;">${g.title}</div>
                                <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">Call Time: <strong style="color: #dc2626;">${g.call_time}</strong> &bull; Location: ${g.location}</div>
                            </div>
                            <a href="/download_call_sheet.php?gig_id=${encodeURIComponent(g.id)}" target="_blank" class="btn-save-primary" style="padding: 6px 12px; font-size: 0.78rem;">
                                Call Sheet
                            </a>
                        </div>
                    `;
                });
                html += '</div>';
                contentEl.innerHTML = html;
            }

            inspector.style.display = 'block';
            inspector.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function closeDateInspector() {
            const inspector = document.getElementById('dateInspectorCard');
            if (inspector) inspector.style.display = 'none';
        }
    </script>
</body>
</html>
