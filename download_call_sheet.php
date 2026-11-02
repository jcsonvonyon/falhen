<?php
/**
 * download_call_sheet.php
 * Official Studio Call Sheet Viewer & PDF Printable Document
 * Falhen Media Productions / Henad Innovations and Global Services LLC
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$gigId = trim($_GET['gig_id'] ?? '');
if (empty($gigId)) {
    // If no gig specified, default to first available gig
    $gigs = getVendorGigsRepository();
    $gig = $gigs[0] ?? null;
} else {
    $gig = getVendorGigById($gigId);
}

if (!$gig) {
    http_response_code(404);
    echo "Call Sheet not found for gig: " . htmlspecialchars($gigId);
    exit;
}

$title = $gig['title'] ?? 'Studio Production Shoot';
$client = $gig['client'] ?? 'Falhen Media Production';
$date = !empty($gig['shoot_date']) ? date('l, F j, Y', strtotime($gig['shoot_date'])) : date('l, F j, Y');
$callTime = $gig['call_time'] ?? '08:00 AM';
$wrapTime = $gig['wrap_time'] ?? '06:00 PM';
$location = $gig['location'] ?? 'Falhen Main Soundstage, Gbagada, Lagos';
$role = $gig['role'] ?? 'Production Crew';
$director = $gig['director'] ?? 'Oluwatosin Ligali (Executive Producer)';
$directorPhone = $gig['director_phone'] ?? '08027487449';
$notes = $gig['notes'] ?? 'Standard high-definition capture. Adhere strictly to studio NDA.';

// Authenticated vendor details if logged in
$vendor = isVendorLoggedIn() ? getLoggedInVendor() : null;
$contractorName = $vendor['full_name'] ?? ($gig['assigned_to_name'] ?? 'Assigned Vendor / Contractor');
$refCode = $vendor['reference_code'] ?? ($gig['assigned_to_ref'] ?? 'FAL-VND-CREW');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call Sheet &mdash; <?php echo htmlspecialchars($title); ?> | Falhen Media</title>
    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary: #dc2626;
            --dark: #0f172a;
            --slate-700: #334155;
            --slate-500: #64748b;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #e2e8f0;
            color: var(--dark);
            padding: 30px 20px 60px;
            min-height: 100vh;
        }

        /* Top Action Bar (hidden in print) */
        .toolbar {
            max-width: 900px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            font-size: 0.88rem;
            font-weight: 700;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
        }

        .btn-print {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(220, 38, 38, 0.3);
        }

        .btn-print:hover {
            background: #b91c1c;
        }

        .btn-back {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .btn-back:hover {
            background: #e2e8f0;
        }

        /* Sheet Document Container */
        .callsheet-container {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid #cbd5e1;
        }

        /* Header block */
        .cs-header {
            background: #0f172a;
            color: #ffffff;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 4px solid var(--primary);
        }

        .cs-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .cs-logo {
            width: 44px;
            height: 44px;
            background: #ffffff;
            border-radius: 10px;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cs-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .cs-title-box h1 {
            font-size: 1.25rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }

        .cs-title-box span {
            font-size: 0.74rem;
            color: #ef4444;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .cs-meta-box {
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82rem;
            color: #94a3b8;
        }

        .cs-meta-box strong {
            color: #ffffff;
            font-size: 0.95rem;
            display: block;
        }

        /* Big Highlight Strip */
        .cs-hero-strip {
            background: #fff1f2;
            border-bottom: 1px solid #fecdd3;
            padding: 20px 32px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 20px;
        }

        .cs-hero-item label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            color: #991b1b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 4px;
        }

        .cs-hero-item .val {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
        }

        .cs-hero-item .time-highlight {
            font-size: 1.45rem;
            font-weight: 900;
            color: var(--primary);
            font-family: 'JetBrains Mono', monospace;
        }

        /* Sections inside Sheet */
        .cs-body {
            padding: 28px 32px;
        }

        .cs-section {
            margin-bottom: 24px;
        }

        .cs-section-title {
            font-size: 0.84rem;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cs-section-title i {
            color: var(--primary);
        }

        /* Two columns grid */
        .cs-grid-two {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .cs-detail-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 18px;
        }

        .cs-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px dotted #e2e8f0;
            font-size: 0.86rem;
        }

        .cs-row:last-child {
            border-bottom: none;
        }

        .cs-row .lbl {
            color: var(--slate-500);
            font-weight: 600;
        }

        .cs-row .val {
            font-weight: 700;
            color: var(--dark);
            text-align: right;
        }

        /* Schedule Table */
        .cs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            margin-top: 8px;
        }

        .cs-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.74rem;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }

        .cs-table td {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            color: var(--dark);
        }

        .cs-table tr:nth-child(even) td {
            background: #fafafa;
        }

        /* Notice & Sign-off Box */
        .cs-notice-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 14px 18px;
            font-size: 0.82rem;
            color: #166534;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        .cs-signoff {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #cbd5e1;
        }

        .cs-sign-line {
            border-bottom: 1px solid #0f172a;
            height: 38px;
            margin-bottom: 6px;
        }

        .cs-sign-desc {
            font-size: 0.75rem;
            color: var(--slate-500);
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .toolbar {
                display: none !important;
            }
            .callsheet-container {
                box-shadow: none;
                border: none;
                max-width: 100%;
                border-radius: 0;
            }
            .cs-header {
                padding: 16px 20px;
            }
            .cs-hero-strip {
                padding: 14px 20px;
            }
            .cs-body {
                padding: 18px 20px;
            }
        }
    </style>
</head>
<body>

    <!-- TOOLBAR -->
    <div class="toolbar">
        <a href="/vendor/index.php?section=gigs_assigned" class="btn-action btn-back">
            <i class="fa-solid fa-arrow-left"></i> Return to Vendor Portal
        </a>
        <button type="button" class="btn-action btn-print" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print / Save as PDF
        </button>
    </div>

    <!-- MAIN CALL SHEET -->
    <div class="callsheet-container">
        
        <!-- Header -->
        <header class="cs-header">
            <div class="cs-brand">
                <div class="cs-logo">
                    <img src="/assets/img/icons/logo.png" alt="Falhen Logo" onerror="this.src='/assets/falhenmedia_logo.png'">
                </div>
                <div class="cs-title-box">
                    <h1>Falhen Media Productions</h1>
                    <span>Official Production Call Sheet &bull; Confidential (NDA)</span>
                </div>
            </div>
            <div class="cs-meta-box">
                <strong><?php echo htmlspecialchars($gig['id'] ?? 'GIG-2026-CALL'); ?></strong>
                <span>Date: <?php echo date('M d, Y', strtotime($gig['shoot_date'] ?? 'now')); ?></span>
            </div>
        </header>

        <!-- Hero Strip -->
        <div class="cs-hero-strip">
            <div class="cs-hero-item">
                <label>Production Title &amp; Client</label>
                <div class="val"><?php echo htmlspecialchars($title); ?></div>
                <div style="font-size: 0.82rem; color: #64748b; margin-top: 2px;">Client: <?php echo htmlspecialchars($client); ?></div>
            </div>
            <div class="cs-hero-item">
                <label>General Crew Call</label>
                <div class="time-highlight"><?php echo htmlspecialchars($callTime); ?></div>
            </div>
            <div class="cs-hero-item">
                <label>Estimated Wrap</label>
                <div class="time-highlight" style="color: #0f172a;"><?php echo htmlspecialchars($wrapTime); ?></div>
            </div>
        </div>

        <!-- Sheet Body -->
        <div class="cs-body">

            <!-- Location & Hospital Grid -->
            <div class="cs-section">
                <div class="cs-section-title">
                    <i class="fa-solid fa-location-dot"></i> Shoot Location &amp; Emergency Facilities
                </div>
                <div class="cs-grid-two">
                    <div class="cs-detail-box">
                        <div style="font-weight: 700; font-size: 0.95rem; color: #0f172a; margin-bottom: 4px;">
                            <i class="fa-solid fa-clapperboard" style="color: var(--primary);"></i> On-Set Location
                        </div>
                        <div style="font-size: 0.88rem; color: #334155; line-height: 1.5;">
                            <?php echo htmlspecialchars($location); ?>
                        </div>
                        <div style="font-size: 0.78rem; color: #64748b; margin-top: 6px;">
                            Parking: On-site secured production bay &bull; Sound Stage Check-in at Gate 2.
                        </div>
                    </div>

                    <div class="cs-detail-box">
                        <div style="font-weight: 700; font-size: 0.95rem; color: #0f172a; margin-bottom: 4px;">
                            <i class="fa-solid fa-truck-medical" style="color: #16a34a;"></i> Nearest Emergency Hospital
                        </div>
                        <div style="font-size: 0.88rem; color: #334155; line-height: 1.5;">
                            Gbagada General Hospital &bull; 1 Hospital Rd, Gbagada, Lagos
                        </div>
                        <div style="font-size: 0.78rem; color: #64748b; margin-top: 6px;">
                            Emergency Line: 112 / 08027487449 (Falhen Studio Safety Officer)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assigned Contractor / Crew Focus -->
            <div class="cs-section">
                <div class="cs-section-title">
                    <i class="fa-solid fa-user-tag"></i> Assigned Contractor Details
                </div>
                <div class="cs-detail-box">
                    <div class="cs-row">
                        <span class="lbl">Contractor Name</span>
                        <span class="val"><?php echo htmlspecialchars($contractorName); ?> (Ref: <?php echo htmlspecialchars($refCode); ?>)</span>
                    </div>
                    <div class="cs-row">
                        <span class="lbl">Assigned Role</span>
                        <span class="val" style="color: var(--primary); font-weight: 700;"><?php echo htmlspecialchars($role); ?></span>
                    </div>
                    <div class="cs-row">
                        <span class="lbl">Individual Call Time</span>
                        <span class="val" style="font-family: monospace; font-size: 0.95rem;"><?php echo htmlspecialchars($callTime); ?> (On-Set)</span>
                    </div>
                    <div class="cs-row">
                        <span class="lbl">Executive Producer / Director</span>
                        <span class="val"><?php echo htmlspecialchars($director); ?> &bull; <?php echo htmlspecialchars($directorPhone); ?></span>
                    </div>
                </div>
            </div>

            <?php
            // Load or parse production schedule
            $scheduleItems = $gig['schedule_items'] ?? [];
            if (empty($scheduleItems) && !empty($gig['schedule_csv_url'])) {
                $csvLocalPath = __DIR__ . $gig['schedule_csv_url'];
                if (file_exists($csvLocalPath) && ($hCsv = fopen($csvLocalPath, 'r')) !== false) {
                    $rCount = 0;
                    while (($rData = fgetcsv($hCsv, 2000, ',')) !== false) {
                        $rCount++;
                        if ($rCount === 1 && str_contains(strtolower(trim($rData[0] ?? '')), 'time')) {
                            continue;
                        }
                        if (empty(array_filter($rData))) continue;
                        $scheduleItems[] = [
                            'time'        => trim($rData[0] ?? ''),
                            'scene'       => trim($rData[1] ?? ''),
                            'description' => trim($rData[2] ?? ''),
                            'personnel'   => trim($rData[3] ?? '')
                        ];
                    }
                    fclose($hCsv);
                }
            }

            // Default sequence breakdown if no CSV uploaded
            if (empty($scheduleItems)) {
                $scheduleItems = [
                    ['time' => $callTime, 'scene' => 'Crew Call & Equipment Load-in', 'description' => 'Lighting rig setup, gimbal balance, wireless video link calibration.', 'personnel' => 'Camera Crew & Gaffer'],
                    ['time' => '09:15 AM', 'scene' => 'Wardrobe & Lighting Check', 'description' => 'Talent staging, color temperature white balance, sound check.', 'personnel' => 'Director, Talent & Audio'],
                    ['time' => '10:00 AM', 'scene' => 'Scene 1 & Principal Photography', 'description' => 'Hero motion tracking, high-speed 60fps editorial passes.', 'personnel' => 'All Crew'],
                    ['time' => '01:30 PM', 'scene' => 'Catering & Lunch Break (45 Mins)', 'description' => 'Hot lunch served at studio commissary • battery recharge.', 'personnel' => 'All Production'],
                    ['time' => '02:30 PM', 'scene' => 'Scene 2 & Commercial BTS', 'description' => 'Close-up product details, dynamic moving shots, b-roll.', 'personnel' => 'Camera & BTS Ops'],
                    ['time' => $wrapTime, 'scene' => 'Shoot Wrap & Media Handover', 'description' => 'Dual-card DIT data verification, call sheet sign-off.', 'personnel' => 'All Crew & Producer'],
                ];
            }
            ?>

            <!-- Timeline / Shooting Schedule -->
            <div class="cs-section">
                <div class="cs-section-title" style="display: flex; justify-content: space-between; align-items: center;">
                    <div><i class="fa-solid fa-clock"></i> Production Schedule &amp; Sequence Breakdown</div>
                    <?php if (!empty($gig['schedule_csv_url'])): ?>
                        <a href="<?php echo htmlspecialchars($gig['schedule_csv_url']); ?>" download style="font-size: 0.72rem; color: #2563eb; text-decoration: none; font-weight: 700; text-transform: none; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="fa-solid fa-file-csv"></i> <?php echo htmlspecialchars($gig['schedule_csv_name'] ?? 'Download CSV'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <table class="cs-table">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Time</th>
                            <th>Segment / Scene</th>
                            <th>Description &amp; Setup</th>
                            <th style="width: 150px;">Key Personnel</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scheduleItems as $sIdx => $sItem): 
                            $isLast = ($sIdx === count($scheduleItems) - 1);
                        ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 700; <?php echo $isLast ? 'color: #dc2626;' : ''; ?>">
                                    <?php echo htmlspecialchars($sItem['time'] ?? ''); ?>
                                </td>
                                <td style="font-weight: 600; color: #0f172a;">
                                    <?php echo htmlspecialchars($sItem['scene'] ?? ''); ?>
                                </td>
                                <td style="color: #475569;">
                                    <?php echo htmlspecialchars($sItem['description'] ?? ''); ?>
                                </td>
                                <td style="font-weight: 500; color: #334155;">
                                    <?php echo htmlspecialchars($sItem['personnel'] ?? ''); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Technical Instructions & NDA Notice -->
            <div class="cs-section">
                <div class="cs-section-title">
                    <i class="fa-solid fa-triangle-exclamation"></i> Production Notes &amp; Studio Guidelines
                </div>
                <div class="cs-notice-box">
                    <strong>CONFIDENTIALITY &amp; NDAs IN FULL EFFECT:</strong> No unauthorized photos, live streaming, or behind-the-scenes posting on social media without prior written clearance from Falhen Media Executive Producers. All captured media is Work Made For Hire and the exclusive property of Henad Innovations and Global Services LLC DBA Falhenmedia and Productions.
                </div>
                <div style="font-size: 0.85rem; color: #475569; line-height: 1.6; background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <strong>Special Directives:</strong> <?php echo htmlspecialchars($notes); ?>
                </div>
            </div>

            <!-- Sign-Off Line for Call Sheet Verification -->
            <div class="cs-signoff">
                <div>
                    <div style="min-height: 42px;"></div>
                    <div class="cs-sign-line"></div>
                    <div class="cs-sign-desc">Assigned Contractor Signature / Date</div>
                    <div style="font-size: 0.82rem; font-weight: 700; color: #0f172a; margin-top: 3px;"><?php echo htmlspecialchars($contractorName); ?></div>
                </div>
                <div>
                    <?php 
                    $authSigner = function_exists('getAuthorizedCounterSigner') ? getAuthorizedCounterSigner() : null;
                    $supSigData = $authSigner['signature_data'] ?? '';
                    $supName = !empty($director) && $director !== 'Falhen Media Production' ? $director : ($authSigner['full_name'] ?? 'Production Supervisor');
                    ?>
                    <div style="min-height: 42px; display: flex; align-items: flex-end; justify-content: center; margin-bottom: 4px;">
                        <?php if (!empty($supSigData) && (strpos($supSigData, 'data:image') === 0 || strpos($supSigData, 'http') === 0 || strpos($supSigData, '/') === 0)): ?>
                            <img src="<?php echo htmlspecialchars($supSigData); ?>" alt="Supervisor Signature" style="max-height: 42px; max-width: 180px; object-fit: contain;">
                        <?php elseif (!empty($authSigner['full_name'])): ?>
                            <span style="font-family: 'Dancing Script', cursive; font-size: 1.5rem; color: #0f172a;"><?php echo htmlspecialchars($supName); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="cs-sign-line"></div>
                    <div class="cs-sign-desc">Falhen Production Supervisor Signature</div>
                    <div style="font-size: 0.82rem; font-weight: 700; color: #0f172a; margin-top: 3px;"><?php echo htmlspecialchars($supName); ?></div>
                </div>
            </div>

        </div>

    </div>

</body>
</html>
