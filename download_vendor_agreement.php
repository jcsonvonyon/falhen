<?php
/**
 * Falhen Media - Official Vendor & Contractor Onboarding Agreement PDF / Print Document
 */
require_once __DIR__ . '/includes/functions.php';

$ref = trim($_GET['ref'] ?? $_GET['id'] ?? '');
$vendor = null;

if (!empty($ref)) {
    $vendor = getVendorById($ref);
}

if (!$vendor) {
    // If not found, show clean message
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>Agreement Not Found</title><style>body{font-family:sans-serif;padding:40px;text-align:center;color:#475569;} a{color:#dc2626;font-weight:700;}</style></head><body>';
    echo '<h2>Agreement Not Found</h2><p>The requested vendor agreement could not be located. Please check the reference code.</p>';
    echo '<p><a href="/vendor-onboarding.php">&larr; Return to Vendor Onboarding</a></p>';
    echo '</body></html>';
    exit;
}

$fullName = $vendor['full_name'] ?? 'Vendor / Contractor';
$roleLabel = $vendor['role_label'] ?? 'Photographer / Videographer';
$role = $vendor['role'] ?? 'photographer_videographer';
$refCode = $vendor['reference_code'] ?? 'FAL-VND-2026';
$email = $vendor['email'] ?? '';
$phone = $vendor['phone'] ?? '';
$address = $vendor['address'] ?? '';
$sigName = !empty($vendor['signature_name']) ? $vendor['signature_name'] : $fullName;
$sigDate = !empty($vendor['signature_date']) ? date('F j, Y', strtotime($vendor['signature_date'])) : date('F j, Y');
$createdAt = !empty($vendor['created_at']) ? date('F j, Y, g:i A', strtotime($vendor['created_at'])) : date('F j, Y');
$status = ucfirst($vendor['status'] ?? 'Active');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Engagement Agreement - <?php echo htmlspecialchars($fullName); ?> (<?php echo htmlspecialchars($refCode); ?>) | Falhen Media</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #0f172a;
            background: #f8fafc;
            margin: 0;
            padding: 30px 15px;
            line-height: 1.6;
            font-size: 0.92rem;
        }
        .action-bar {
            max-width: 850px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-back {
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .btn-back:hover {
            background: #f1f5f9;
        }
        .btn-print {
            background: #dc2626;
            color: #ffffff;
            border: none;
        }
        .btn-print:hover {
            background: #b91c1c;
        }
        .page-container {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            padding: 48px 56px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 24px;
            margin-bottom: 28px;
        }
        .logo-box img {
            height: 48px;
            width: auto;
            object-fit: contain;
        }
        .badge-ref {
            display: inline-block;
            background: #fff1f2;
            color: #e11d48;
            font-weight: 700;
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #ffe4e6;
        }
        .doc-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #0f172a;
            margin: 10px 0 4px 0;
        }
        .doc-subtitle {
            font-size: 0.95rem;
            color: #64748b;
            margin: 0;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 28px;
        }
        .meta-item-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .meta-item-val {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
        }
        .legal-section {
            margin-bottom: 24px;
        }
        .legal-section-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            border-left: 4px solid #dc2626;
            padding-left: 10px;
            margin: 0 0 10px 0;
        }
        .legal-text {
            color: #334155;
            font-size: 0.88rem;
            line-height: 1.7;
            margin-bottom: 12px;
            text-align: justify;
        }
        .signature-card {
            margin-top: 36px;
            padding-top: 24px;
            border-top: 2px dashed #cbd5e1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .sig-col {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
        }
        .sig-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 14px;
        }
        .sig-script {
            font-family: 'Dancing Script', cursive;
            font-size: 2.2rem;
            color: #0f172a;
            line-height: 1.2;
            min-height: 48px;
            display: flex;
            align-items: center;
        }
        .sig-line {
            border-bottom: 1px solid #94a3b8;
            margin: 10px 0 8px 0;
        }
        .sig-subtext {
            font-size: 0.78rem;
            color: #64748b;
        }
        .verification-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #15803d;
            font-size: 0.78rem;
            font-weight: 700;
            margin-top: 6px;
        }
        @media print {
            body { background: #ffffff; padding: 0; }
            .action-bar { display: none !important; }
            .page-container { box-shadow: none; border: none; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <a href="/vendor-onboarding.php" class="btn-action btn-back">
            <i class="fa-solid fa-arrow-left"></i> Vendor Onboarding
        </a>
        <div style="display: flex; gap: 10px;">
            <a href="/" class="btn-action btn-back">
                <i class="fa-solid fa-house"></i> Home
            </a>
            <button onclick="window.print()" class="btn-action btn-print">
                <i class="fa-solid fa-print"></i> Print / Save as PDF
            </button>
        </div>
    </div>

    <div class="page-container">
        <!-- HEADER -->
        <div class="header-box">
            <div>
                <span class="badge-ref"><i class="fa-solid fa-shield-check"></i> Verified Execution &bull; <?php echo htmlspecialchars($refCode); ?></span>
                <h1 class="doc-title"><?php echo htmlspecialchars($roleLabel); ?> Engagement Agreement</h1>
                <p class="doc-subtitle">Formal Onboarding, Master Services Agreement (MSA) &amp; Non-Disclosure Agreement (NDA)</p>
            </div>
            <div class="logo-box">
                <img src="/assets/img/icons/logo.png" alt="Falhen Media Logo" onerror="this.src='/assets/falhenmedia_logo.png'">
            </div>
        </div>

        <!-- METADATA GRID -->
        <div class="meta-grid">
            <div>
                <div class="meta-item-label">Contractor / Vendor</div>
                <div class="meta-item-val"><?php echo htmlspecialchars($fullName); ?></div>
            </div>
            <div>
                <div class="meta-item-label">Engagement Category</div>
                <div class="meta-item-val"><?php echo htmlspecialchars($roleLabel); ?></div>
            </div>
            <div>
                <div class="meta-item-label">Contact Details</div>
                <div class="meta-item-val"><?php echo htmlspecialchars($phone); ?></div>
                <div style="font-size: 0.78rem; color: #64748b;"><?php echo htmlspecialchars($email); ?></div>
            </div>
            <div>
                <div class="meta-item-label">Execution Date</div>
                <div class="meta-item-val"><?php echo htmlspecialchars($sigDate); ?></div>
                <div style="font-size: 0.78rem; color: #15803d; font-weight: 700;">Status: <?php echo htmlspecialchars($status); ?></div>
            </div>
        </div>

        <!-- LEGAL TERMS & CONDITIONS -->
        <div class="legal-section">
            <h2 class="legal-section-title">1. Master Service Agreement (MSA)</h2>
            <p class="legal-text">
                You are engaged as an independent contractor, not an employee. Services are described in individual Statements of Work (SOWs). All work product, including raw and edited media, is assigned to Henad Innovations and Global Services LLC DBA Falhenmedia and Productions upon creation. You retain no license or ownership rights. Compensation is outlined in individual SOWs. You agree to indemnify Henad Innovations and Global Services LLC DBA Falhenmedia and Productions against claims arising from your negligence or breach of this Agreement.
            </p>
        </div>

        <div class="legal-section">
            <h2 class="legal-section-title">2. Henad Innovations and Global Services LLC DBA Falhenmedia and Productions — Vendor Workplace Policy</h2>
            <p class="legal-text">
                The Contractor / Vendor has reviewed, acknowledged, and agreed to the Henad Innovations and Global Services LLC DBA Falhenmedia and Productions Workplace Policy:
            </p>
            <ol style="color: #334155; font-size: 0.88rem; line-height: 1.7; padding-left: 20px; margin-bottom: 12px;">
                <li style="margin-bottom: 8px;"><strong>IP Ownership:</strong> All photographs, video footage, raw files, edited files, motion graphics, audio recordings, and any derivative works created during your engagement are the sole and exclusive property of Henad Innovations and Global Services LLC DBA Falhenmedia and Productions. You waive moral rights and may not use, reproduce, or distribute any such materials without written consent.</li>
                <li style="margin-bottom: 8px;"><strong>Equipment:</strong> Henad Innovations and Global Services LLC DBA Falhenmedia and Productions equipment must be handled with care and returned in the same condition. Loss or damage due to negligence may result in liability.</li>
                <li style="margin-bottom: 8px;"><strong>Conduct:</strong> All vendors, interns, and volunteers must maintain professional conduct on set, in client interactions, and on social media. Representing Henad Innovations and Global Services LLC DBA Falhenmedia and Productions without written approval is prohibited.</li>
                <li style="margin-bottom: 8px;"><strong>Confidentiality:</strong> Client names, project details, rates, and internal processes are strictly confidential during and after your engagement.</li>
                <li style="margin-bottom: 8px;"><strong>Deliverables:</strong> All deliverables must be submitted in the agreed format and timeline. Unauthorized retention of raw or edited files is prohibited.</li>
                <li><strong>Termination:</strong> Henad Innovations and Global Services LLC DBA Falhenmedia and Productions reserves the right to terminate any engagement at any time for policy violations. All materials must be surrendered immediately upon termination.</li>
            </ol>
        </div>

        <div class="legal-section">
            <h2 class="legal-section-title">3. Non-Disclosure Agreement (NDA) &amp; Confidentiality</h2>
            <p class="legal-text">
                All client identities, project briefs, production schedules, pricing, methodologies, and proprietary workflows disclosed during your engagement are strictly confidential. You agree not to disclose any such information to third parties without prior written consent. These obligations survive termination for three (3) years. Upon request or termination, you agree to promptly return or destroy all materials containing Confidential Information.
            </p>
        </div>

        <!-- SIGNATURES -->
        <div class="signature-card">
            <div class="sig-col">
                <div class="sig-title">For: Falhen Media Ltd</div>
                <?php 
                $counterSigner = getAuthorizedCounterSigner();
                $csName = $counterSigner['full_name'] ?? 'Oluwatosin Ligali';
                $csTitle = $counterSigner['designation'] ?? 'Executive Producer';
                $csSigData = $counterSigner['signature_data'] ?? '';
                $csSigType = $counterSigner['signature_type'] ?? 'typed';
                ?>
                <?php if (!empty($csSigData) && (strpos($csSigData, 'data:image') === 0 || strpos($csSigData, 'http') === 0 || strpos($csSigData, '/') === 0)): ?>
                    <div style="min-height: 48px; display: flex; align-items: center;">
                        <img src="<?php echo htmlspecialchars($csSigData); ?>" alt="Authorized Counter-Signature" style="max-height: 48px; max-width: 220px; object-fit: contain;">
                    </div>
                <?php else: ?>
                    <div class="sig-script"><?php echo htmlspecialchars($csName); ?></div>
                <?php endif; ?>
                <div class="sig-line"></div>
                <div style="font-weight: 700; font-size: 0.88rem;"><?php echo htmlspecialchars($csName); ?></div>
                <div class="sig-subtext"><?php echo htmlspecialchars($csTitle); ?> &bull; Falhen Media Ltd</div>
                <div class="verification-badge">
                    <i class="fa-solid fa-circle-check"></i> Authorized Counter-Signature
                </div>
            </div>

            <div class="sig-col">
                <div class="sig-title">Contractor / Vendor Signature</div>
                <?php if (!empty($vendor['signature_data']) && strpos($vendor['signature_data'], 'data:image') === 0): ?>
                    <div style="min-height: 48px; display: flex; align-items: center;">
                        <img src="<?php echo htmlspecialchars($vendor['signature_data']); ?>" alt="Contractor Signature" style="max-height: 48px; max-width: 220px; object-fit: contain;">
                    </div>
                <?php else: ?>
                    <div class="sig-script"><?php echo htmlspecialchars($sigName); ?></div>
                <?php endif; ?>
                <div class="sig-line"></div>
                <div style="font-weight: 700; font-size: 0.88rem;"><?php echo htmlspecialchars($fullName); ?></div>
                <div class="sig-subtext"><?php echo htmlspecialchars($roleLabel); ?> &bull; <?php echo htmlspecialchars($sigDate); ?></div>
                <div class="verification-badge">
                    <i class="fa-solid fa-circle-check"></i> Legally Binding Digital Execution (Ref: <?php echo htmlspecialchars($refCode); ?>)
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div style="margin-top: 36px; padding-top: 18px; border-top: 1px solid #e2e8f0; font-size: 0.75rem; color: #94a3b8; display: flex; justify-content: space-between; align-items: center;">
            <div>Falhen Media Ltd &bull; 6 Olorunfunmi Street, Pedro, Gbagada, Lagos, Nigeria</div>
            <div>Recorded at <?php echo htmlspecialchars($createdAt); ?> &bull; System Verified</div>
        </div>
    </div>

</body>
</html>
