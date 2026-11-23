<?php
/**
 * includes/pdf_template.php
 * Reusable Printable & PDF Layout Engine for Official Falhen Studio Documents
 * Consolidates CSS, Font Awesome, Print Styles, and Document Containers
 */

function renderPrintableDocument(array $config, callable $bodyRenderer): void {
    $title = htmlspecialchars($config['title'] ?? 'Falhen Studio Document');
    $category = htmlspecialchars($config['category'] ?? 'Human Resources & Operational Excellence');
    $backUrl = htmlspecialchars($config['back_url'] ?? '/admin/index.php');
    $backLabel = htmlspecialchars($config['back_label'] ?? 'Back to Dashboard');
    $autoPrint = !empty($_GET['print']);
    $printBtnLabel = htmlspecialchars($config['print_button_label'] ?? 'Print / Save PDF');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> | Falhen Productions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 18mm; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #0f172a;
            background: #f8fafc;
            margin: 0;
            padding: 40px 20px;
            line-height: 1.6;
            font-size: 0.88rem;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
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
        .btn-back {
            color: #64748b;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s ease;
        }
        .btn-back:hover { color: #0f172a; }
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            transition: background 0.2s ease;
        }
        .btn-print:hover { background: #dc2626; }
        h1, h2, h3, h4 { color: #0f172a; font-weight: 800; margin-top: 0; }
        h3 { font-size: 0.98rem; margin-top: 22px; margin-bottom: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        h4 { font-size: 0.9rem; margin-top: 14px; margin-bottom: 6px; color: #334155; }
        p { margin: 0 0 10px 0; }
        ul { margin-top: 6px; margin-bottom: 12px; padding-left: 20px; }
        li { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th, td { border: 1px solid #e2e8f0; padding: 10px 14px; text-align: left; }
        th { background: #f8fafc; font-weight: 700; color: #334155; }
        @media print {
            body { background: #ffffff; padding: 0; }
            .page-container { box-shadow: none; border: none; padding: 0; max-width: 100%; }
            .action-bar { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <a href="<?php echo $backUrl; ?>" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> <?php echo $backLabel; ?>
        </a>
        <button onclick="window.print()" class="btn-print">
            <i class="fa-solid fa-print"></i> <?php echo $printBtnLabel; ?>
        </button>
    </div>

    <div class="page-container">
        <div class="header-box">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #dc2626; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 900;">F</div>
                <div>
                    <div style="font-size: 1.4rem; font-weight: 900; color: #0f172a; line-height: 1.2;">Falhen <span style="color: #dc2626;">Productions</span></div>
                    <div style="font-size: 0.78rem; color: #64748b; font-weight: 600;"><?php echo $category; ?></div>
                </div>
            </div>
            <div style="text-align: right; font-size: 0.75rem; color: #64748b; line-height: 1.4;">
                <div style="font-weight: 700; color: #0f172a;">REF: <?php echo htmlspecialchars($config['ref'] ?? ('FAL-DOC-' . date('Ymd'))); ?></div>
                <div>Issued: <?php echo htmlspecialchars($config['date'] ?? date('F j, Y')); ?></div>
            </div>
        </div>

        <?php $bodyRenderer(); ?>

    </div>

    <script>
        window.onload = function() {
            if (<?php echo $autoPrint ? 'true' : 'false'; ?> || window.location.search.indexOf('print=1') !== -1) {
                setTimeout(function() { window.print(); }, 400);
            }
        };
    </script>
</body>
</html>
<?php
}
