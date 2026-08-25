<?php
// team-single.php - Team Member Detail Page matching falhen.com screenshots
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$member = getTeamMemberById($id);
$pageTitle = $member['name'] . " — " . $member['role'] . " | Falhen Media";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="shortcut icon" type="image/png" href="/assets/img/icons/favicon.png">
    <link rel="apple-touch-icon" href="/assets/img/icons/favicon.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body {
            background-color: #030305;
            color: #d4d4d8;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
        }

        .member-nav-bar {
            padding: 24px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
        }

        .member-nav-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .member-back-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .member-back-link:hover {
            color: #ffffff;
        }

        .work-with-us-link {
            color: #ff4d4d;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: opacity 0.25s ease;
        }

        .work-with-us-link:hover {
            opacity: 0.85;
        }

        .member-main-section {
            padding: 60px 0 80px 0;
        }

        .member-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .member-profile-grid {
            display: grid;
            grid-template-columns: 440px 1fr;
            gap: 60px;
            align-items: start;
        }

        .member-portrait-card {
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
            background: #0e0e12;
            height: 540px;
        }

        .member-portrait-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .member-details-col {
            padding-top: 10px;
        }

        .member-badges-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .dept-badge-pill {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #ef4444;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 50px;
        }

        .exp-badge-pill {
            color: #a1a1aa;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .member-name {
            font-size: 3.5rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 4px 0;
            letter-spacing: -1px;
            line-height: 1.1;
        }

        .member-role-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #ff4d4d;
            margin: 0 0 10px 0;
        }

        .member-location-row {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #a1a1aa;
            font-size: 0.95rem;
            margin-bottom: 16px;
        }

        .member-bio-text {
            font-size: 1.05rem;
            color: #d4d4d8;
            line-height: 1.7;
            margin-bottom: 22px;
        }

        .skills-heading {
            font-size: 0.78rem;
            font-weight: 800;
            color: #71717a;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .skills-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 24px;
        }

        .skill-item-pill {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #d4d4d8;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 50px;
            transition: border-color 0.25s ease;
        }

        .skill-item-pill:hover {
            border-color: rgba(220, 38, 38, 0.4);
        }

        .action-buttons-row {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .btn-work-red {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.4);
        }

        .btn-work-red:hover {
            background: #ef4444;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.5);
        }

        .btn-view-portfolio {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .btn-view-portfolio:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* Meet the Rest of the Team Callout Box */
        .rest-team-card {
            background: linear-gradient(135deg, rgba(35, 10, 15, 0.9) 0%, rgba(14, 14, 18, 0.95) 100%);
            border: 1px solid rgba(220, 38, 38, 0.25);
            border-radius: 20px;
            padding: 48px;
            margin-top: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .rest-team-left h3 {
            font-size: 2.2rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 10px 0;
            letter-spacing: -0.5px;
        }

        .rest-team-left p {
            color: #a1a1aa;
            font-size: 1rem;
            margin: 0;
        }

        .btn-view-full-team {
            background: #dc2626;
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.4);
        }

        .btn-view-full-team:hover {
            background: #ef4444;
            transform: translateY(-2px);
        }

        /* Footer */
        .member-footer-bar {
            padding: 40px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            margin-top: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.88rem;
            color: #71717a;
        }

        .member-footer-links {
            display: flex;
            gap: 24px;
        }

        .member-footer-links a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.25s ease;
        }

        .member-footer-links a:hover {
            color: #ffffff;
        }

        @media (max-width: 992px) {
            .member-profile-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .member-portrait-card {
                height: 420px;
            }
            .member-name {
                font-size: 2.8rem;
            }
            .rest-team-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 24px;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="member-nav-bar">
        <div class="member-nav-container">
            <a href="/team.php" class="member-back-link"><i class="fa-solid fa-arrow-left"></i> Back to Team</a>
            <a href="/"><img src="/assets/img/icons/logo.png" alt="Falhen Logo" style="height: 38px;"></a>
            <a href="/contact.php" class="work-with-us-link">Work With Us <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="member-main-section">
        <div class="member-container">
            
            <!-- Member Profile Grid -->
            <div class="member-profile-grid">
                <!-- Left Column: Portrait Card -->
                <div class="member-portrait-card">
                    <img src="<?php echo htmlspecialchars($member['image']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                </div>

                <!-- Right Column: Details -->
                <div class="member-details-col">
                    <!-- Badges -->
                    <div class="member-badges-row">
                        <span class="dept-badge-pill"><?php echo htmlspecialchars($member['department']); ?></span>
                        <span class="exp-badge-pill"><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($member['experience']); ?></span>
                    </div>

                    <!-- Name & Role -->
                    <h1 class="member-name"><?php echo htmlspecialchars($member['name']); ?></h1>
                    <h2 class="member-role-title"><?php echo htmlspecialchars($member['role']); ?></h2>

                    <!-- Location -->
                    <div class="member-location-row">
                        <i class="fa-solid fa-location-dot" style="color: #ef4444;"></i> <?php echo htmlspecialchars($member['location']); ?>
                    </div>

                    <!-- Bio Paragraph -->
                    <p class="member-bio-text"><?php echo htmlspecialchars($member['bio']); ?></p>

                    <!-- Skills & Expertise -->
                    <div class="skills-heading">SKILLS & EXPERTISE</div>
                    <div class="skills-row">
                        <?php foreach ($member['skills'] as $skill): ?>
                            <span class="skill-item-pill"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endforeach; ?>
                    </div>

                    <!-- Action Buttons Row -->
                    <div class="action-buttons-row">
                        <a href="/contact.php" class="btn-work-red"><i class="fa-solid fa-envelope"></i> Work With Us</a>
                        <a href="/portfolio.php" class="btn-view-portfolio"><i class="fa-solid fa-layer-group"></i> View Portfolio</a>
                    </div>
                </div>
            </div>

            <!-- Meet the Rest of the Team Banner -->
            <div class="rest-team-card">
                <div class="rest-team-left">
                    <h3>Meet the Rest of the Team</h3>
                    <p>Discover the diverse talents and perspectives that make Falhen Media a global creative force.</p>
                </div>
                <a href="/team.php" class="btn-view-full-team"><i class="fa-solid fa-users"></i> View Full Team</a>
            </div>

            <!-- Footer Bar -->
            <div class="member-footer-bar">
                <div>© 2026 Falhen Media. All rights reserved.</div>
                <div class="member-footer-links">
                    <a href="/">Home</a>
                    <a href="/index.php#about">About</a>
                    <a href="/portfolio.php">Portfolio</a>
                    <a href="/contact.php">Contact</a>
                </div>
            </div>

        </div>
    </main>

</body>
</html>
