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
            font-weight: 800;
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #ffe4e6;
        }
        .doc-title {
            font-size: 1.6rem;
            font-weight: 800;
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
            font-weight: 800;
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
            font-weight: 800;
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
            <h2 class="legal-section-title">1. Recitals &amp; Engagement Scope</h2>
            <p class="legal-text">
                This Agreement is entered into between <strong>Falhen Media Ltd</strong> (hereinafter "Company") and the undersigned individual or corporate entity (hereinafter "Contractor / Vendor"). The Company hereby engages the Contractor to render professional production, photography, cinematography, post-production, or project support services on an assigned project-by-project or term basis.
            </p>
            <p class="legal-text">
                The Contractor agrees to perform all duties with high artistic and technical standards, adhering strictly to production call-sheets, technical camera specifications, and director treatments provided by the Company.
            </p>
        </div>

        <div class="legal-section">
            <h2 class="legal-section-title">2. Workplace Conduct, Set Safety &amp; Media Handling</h2>
            <p class="legal-text">
                The Contractor has acknowledged and agreed to the Falhen Media Workplace Policies:
            </p>
            <ul style="color: #334155; font-size: 0.88rem; line-height: 1.7; padding-left: 20px;">
                <li><strong>On-Set Safety:</strong> Strict adherence to workplace safety protocols around cinema rigs, lighting stands, and drone operations. Zero tolerance for harassment, discrimination, or substance misuse.</li>
                <li><strong>Equipment Diligence:</strong> Rigorous handling of cinema cameras, lenses, audio gear, and lighting packages. Prompt reporting of equipment wear or malfunctions.</li>
                <li><strong>Media Security:</strong> Memory cards (SD/CFexpress) must never be formatted without verified dual-copy redundant ingestion into project primary and backup arrays.</li>
            </ul>
        </div>

        <div class="legal-section">
            <h2 class="legal-section-title">3. Intellectual Property, Work-for-Hire &amp; Assignment</h2>
            <p class="legal-text">
                All video footage, audio recordings, RAW photographic files, rough cuts, color grades, and creative outputs captured or created during this engagement constitute "Work Made for Hire". To the extent any deliverables do not qualify as work-for-hire, the Contractor hereby unconditionally transfers and assigns to Falhen Media Ltd all worldwide right, title, and interest, including copyrights, moral rights, and distribution licenses in perpetuity.
            </p>
        </div>

        <div class="legal-section">
            <h2 class="legal-section-title">4. Non-Disclosure Agreement (NDA) &amp; Social Media Embargo</h2>
            <p class="legal-text">
                The Contractor covenants to hold in strictest confidence all Company and client proprietary information, including but not limited to unreleased client campaigns, footage, budgets, creative treatments, scripts, and trade secrets. No footage, behind-the-scenes material, or social media teasers may be published without explicit written release from Falhen Media management.
            </p>
        </div>

        <div class="legal-section">
            <h2 class="legal-section-title">5. Independent Relationship &amp; Remuneration</h2>
            <p class="legal-text">
                The Contractor is an independent service provider (or volunteer/intern as specified in the engagement classification) and shall not be deemed an employee for tax, statutory benefits, or insurance purposes. Agreed day-rates or milestone stipends shall be settled according to the payment details recorded during onboarding upon satisfactory deliverable sign-off.
            </p>
        </div>

        <!-- SIGNATURES -->
        <div class="signature-card">
            <div class="sig-col">
                <div class="sig-title">For: Falhen Media Ltd</div>
                <div class="sig-script">Oluwatosin Ligali</div>
                <div class="sig-line"></div>
                <div style="font-weight: 700; font-size: 0.88rem;">Oluwatosin Ligali</div>
                <div class="sig-subtext">Executive Producer &bull; Falhen Media Ltd</div>
                <div class="verification-badge">
                    <i class="fa-solid fa-circle-check"></i> Authorized Counter-Signature
                </div>
            </div>

            <div class="sig-col">
                <div class="sig-title">Contractor / Vendor Signature</div>
                <div class="sig-script"><?php echo htmlspecialchars($sigName); ?></div>
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
