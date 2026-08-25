<?php
// team.php - Dedicated Team Overview Page matching falhen.com/team screenshots
require_once __DIR__ . '/includes/functions.php';

$teamMembers = getTeamMembers();
$pageTitle = "Meet the Team — Falhen Media";
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

        .team-nav-bar {
            padding: 24px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: #030305;
        }

        .team-nav-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .team-back-link {
            color: #a1a1aa;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.25s ease;
        }

        .team-back-link:hover {
            color: #ffffff;
        }

        .join-us-link {
            color: #ff4d4d;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: opacity 0.25s ease;
        }

        .join-us-link:hover {
            opacity: 0.85;
        }

        /* Hero Stage Section */
        .team-hero-section {
            position: relative;
            padding: 70px 0 60px 0;
            background: linear-gradient(180deg, rgba(3, 3, 5, 0.75) 0%, rgba(3, 3, 5, 0.95) 100%), url('/assets/img/team/team.jpeg') center/cover no-repeat;
            overflow: hidden;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .team-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .team-hero-badge {
            display: inline-block;
            background: rgba(220, 38, 38, 0.18);
            border: 1px solid rgba(220, 38, 38, 0.35);
            color: #ff4d4d;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 5px 16px;
            border-radius: 50px;
            margin-bottom: 20px;
        }

        .team-hero-title {
            font-size: 3.8rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.1;
            letter-spacing: -1.5px;
            margin: 0 0 20px 0;
            max-width: 650px;
        }

        .team-hero-desc {
            font-size: 1.05rem;
            color: #a1a1aa;
            line-height: 1.7;
            max-width: 580px;
            margin-bottom: 50px;
        }

        /* Metrics Row */
        .team-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .stat-card-box {
            background: rgba(18, 18, 24, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px 28px;
            transition: border-color 0.3s ease;
        }

        .stat-card-box:hover {
            border-color: rgba(220, 38, 38, 0.3);
        }

        .stat-num-val {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ff4d4d;
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-label-text {
            font-size: 0.88rem;
            color: #71717a;
            font-weight: 600;
        }

        /* Filter & Search Bar */
        .team-controls-bar {
            padding: 40px 0 20px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .filter-tabs-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-btn-tab {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #d4d4d8;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .filter-btn-tab:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .filter-btn-tab.active {
            background: #dc2626;
            border-color: #dc2626;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4);
        }

        .search-box-wrap {
            position: relative;
            width: 300px;
        }

        .search-box-wrap i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #71717a;
            font-size: 0.85rem;
        }

        .search-box-input {
            width: 100%;
            background: rgba(18, 18, 24, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 50px;
            padding: 10px 18px 10px 42px;
            color: #ffffff;
            font-size: 0.85rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.25s ease;
        }

        .search-box-input:focus {
            border-color: rgba(220, 38, 38, 0.5);
        }

        .showing-counter-text {
            color: #71717a;
            font-size: 0.88rem;
            margin-bottom: 24px;
            font-weight: 500;
        }

        /* 4-Column Grid */
        .full-team-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 70px;
        }

        .page-team-card {
            background: #0e0e12;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .page-team-card:hover {
            border-color: rgba(220, 38, 38, 0.5);
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(220, 38, 38, 0.25);
        }

        .page-card-thumb {
            width: 100%;
            height: 340px;
            overflow: hidden;
            position: relative;
        }

        .page-card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .page-team-card:hover .page-card-thumb img {
            transform: scale(1.06);
        }

        .team-tags-overlay {
            position: absolute;
            bottom: 14px;
            left: 14px;
            right: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            opacity: 0;
            transform: translateY(8px);
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 3;
            pointer-events: none;
        }

        .page-team-card:hover .team-tags-overlay {
            opacity: 1;
            transform: translateY(0);
        }

        .team-skill-tag {
            background: rgba(12, 12, 16, 0.88);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        .page-card-body {
            padding: 22px 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
        }

        .page-card-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .page-card-name {
            font-size: 1.15rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
        }

        .page-dept-pill {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 10px;
            border-radius: 50px;
        }

        .page-dept-pill.creative {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #ef4444;
        }

        .page-dept-pill.strategy {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }

        .page-dept-pill.operations {
            background: rgba(37, 99, 235, 0.15);
            border: 1px solid rgba(37, 99, 235, 0.3);
            color: #3b82f6;
        }

        .page-card-role {
            font-size: 0.85rem;
            font-weight: 700;
            color: #ff4d4d;
            margin-bottom: 12px;
        }

        .page-card-bio {
            font-size: 0.85rem;
            color: #a1a1aa;
            line-height: 1.55;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .page-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.82rem;
            color: #71717a;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .page-card-footer span {
            color: #a1a1aa;
            transition: color 0.25s ease;
        }

        .page-team-card:hover .page-card-footer span {
            color: #ff4d4d;
        }

        .think-fit-card {
            background: linear-gradient(135deg, rgba(35, 10, 15, 0.9) 0%, rgba(14, 14, 18, 0.95) 100%);
            border: 1px solid rgba(220, 38, 38, 0.25);
            border-radius: 20px;
            padding: 48px;
            margin-bottom: 35px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .think-fit-left h3 {
            font-size: 2.2rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 10px 0;
            letter-spacing: -0.5px;
        }

        .think-fit-left p {
            color: #a1a1aa;
            font-size: 1rem;
            margin: 0;
            max-width: 580px;
            line-height: 1.6;
        }

        .btn-view-openings {
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

        .btn-view-openings:hover {
            background: #ef4444;
            transform: translateY(-2px);
        }

        /* Footer Bar */
        .team-footer-bar {
            padding: 18px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.88rem;
            color: #71717a;
        }

        .team-footer-links {
            display: flex;
            gap: 24px;
        }

        .team-footer-links a {
            color: #a1a1aa;
            text-decoration: none;
            transition: color 0.25s ease;
        }

        .team-footer-links a:hover {
            color: #ffffff;
        }

        @media (max-width: 1100px) {
            .full-team-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .team-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .full-team-grid {
                grid-template-columns: 1fr;
            }
            .team-stats-grid {
                grid-template-columns: 1fr;
            }
            .team-hero-title {
                font-size: 2.8rem;
            }
            .think-fit-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 24px;
            }
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="team-nav-bar">
        <div class="team-nav-container">
            <a href="/index.php#about" class="team-back-link"><i class="fa-solid fa-arrow-left"></i> Back to About</a>
            <a href="/"><img src="/assets/img/icons/logo.png" alt="Falhen Logo" style="height: 38px;"></a>
            <a href="/contact.php" class="join-us-link">Join Us <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="team-hero-section">
        <div class="team-container">
            <div class="team-hero-badge">8+ Creative Professionals</div>
            <h1 class="team-hero-title">Meet the People<br>Behind the Stories</h1>
            <p class="team-hero-desc">Our global team brings diverse perspectives, deep expertise, and relentless creative energy to every project. From Lagos to London, we collaborate across borders to craft visual experiences that move audiences.</p>

            <!-- Metrics Grid -->
            <div class="team-stats-grid">
                <div class="stat-card-box">
                    <div class="stat-num-val" data-target="8" data-suffix="">0</div>
                    <div class="stat-label-text">Team Members</div>
                </div>
                <div class="stat-card-box">
                    <div class="stat-num-val" data-target="6" data-suffix="">0</div>
                    <div class="stat-label-text">Countries</div>
                </div>
                <div class="stat-card-box">
                    <div class="stat-num-val" data-target="23" data-suffix="+">0</div>
                    <div class="stat-label-text">Combined Skills</div>
                </div>
                <div class="stat-card-box">
                    <div class="stat-num-val" data-target="3" data-suffix="+">0</div>
                    <div class="stat-label-text">Avg. Years Experience</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Team Content Container -->
    <main class="team-container">
        
        <!-- Filter & Search Controls -->
        <div class="team-controls-bar">
            <div class="filter-tabs-group">
                <button class="filter-btn-tab active" data-filter="all">All</button>
                <?php 
                $activeDepts = getTeamDepartments();
                foreach ($activeDepts as $dOpt): 
                ?>
                    <button class="filter-btn-tab" data-filter="<?php echo htmlspecialchars($dOpt); ?>"><?php echo htmlspecialchars($dOpt); ?></button>
                <?php endforeach; ?>
            </div>

            <div class="search-box-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="team-search-input" class="search-box-input" placeholder="Search by name, role, skill...">
            </div>
        </div>

        <div class="showing-counter-text" id="counter-display">Showing 8 members</div>

        <!-- 4-Column Team Grid -->
        <div class="full-team-grid" id="team-grid-container">
            <?php foreach ($teamMembers as $m): ?>
                <a href="/team-single.php?id=<?php echo $m['id']; ?>" class="page-team-card" data-dept="<?php echo htmlspecialchars($m['department']); ?>" data-name="<?php echo htmlspecialchars(strtolower($m['name'])); ?>" data-role="<?php echo htmlspecialchars(strtolower($m['role'])); ?>">
                    <div class="page-card-thumb">
                        <img src="<?php echo htmlspecialchars($m['image']); ?>" alt="<?php echo htmlspecialchars($m['name']); ?>">
                        <?php if (!empty($m['skills'])): ?>
                            <div class="team-tags-overlay">
                                <?php foreach ($m['skills'] as $s): ?>
                                    <span class="team-skill-tag"><?php echo htmlspecialchars($s); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="page-card-body">
                        <div>
                            <div class="page-card-header-row">
                                <h3 class="page-card-name"><?php echo htmlspecialchars($m['name']); ?></h3>
                                <span class="page-dept-pill <?php echo strtolower($m['department']); ?>"><?php echo htmlspecialchars($m['department']); ?></span>
                            </div>
                            <div class="page-card-role"><?php echo htmlspecialchars($m['role']); ?></div>
                            <p class="page-card-bio"><?php echo htmlspecialchars($m['bio']); ?></p>
                        </div>
                        <div class="page-card-footer">
                            <span><?php echo htmlspecialchars($m['experience']); ?></span>
                            <span>See openings <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Careers Banner -->
        <div class="think-fit-card">
            <div class="think-fit-left">
                <h3>Think You Would Fit In?</h3>
                <p>We are always looking for passionate creators, strategists, and technical talent to join our growing team. Check out our open roles.</p>
            </div>
            <a href="/careers.php" class="btn-view-openings"><i class="fa-solid fa-briefcase"></i> View Openings</a>
        </div>

        <!-- Footer Bar -->
        <div class="team-footer-bar">
            <div>© 2026 Falhen Media. All rights reserved.</div>
            <div class="team-footer-links">
                <a href="/">Home</a>
                <a href="/index.php#about">About</a>
                <a href="/portfolio.php">Portfolio</a>
                <a href="/contact.php">Contact</a>
            </div>
        </div>

    </main>

    <!-- Client-Side Filter, Search & Counter Animation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Smooth Animated Counter for Stat Numbers
            function animateCounters() {
                const statNumbers = document.querySelectorAll('.stat-num-val');
                
                statNumbers.forEach(counter => {
                    const target = parseInt(counter.getAttribute('data-target'), 10);
                    const suffix = counter.getAttribute('data-suffix') || '';
                    const duration = 1600; // 1.6 seconds smooth count
                    const startTime = performance.now();

                    function updateCount(currentTime) {
                        const elapsedTime = currentTime - startTime;
                        const progress = Math.min(elapsedTime / duration, 1);
                        
                        // Ease-out cubic easing for elegant count finish
                        const easeProgress = 1 - Math.pow(1 - progress, 3);
                        const currentVal = Math.floor(easeProgress * target);

                        counter.textContent = currentVal + suffix;

                        if (progress < 1) {
                            requestAnimationFrame(updateCount);
                        } else {
                            counter.textContent = target + suffix;
                        }
                    }

                    requestAnimationFrame(updateCount);
                });
            }

            // Start counter animation
            animateCounters();

            const filterBtns = document.querySelectorAll('.filter-btn-tab');
            const searchInput = document.getElementById('team-search-input');
            const teamCards = document.querySelectorAll('.page-team-card');
            const counterDisplay = document.getElementById('counter-display');

            let currentFilter = 'all';
            let currentSearch = '';

            function updateFilter() {
                let visibleCount = 0;

                teamCards.forEach(card => {
                    const dept = card.getAttribute('data-dept');
                    const name = card.getAttribute('data-name');
                    const role = card.getAttribute('data-role');

                    const matchesDept = (currentFilter === 'all' || dept.toLowerCase() === currentFilter.toLowerCase());
                    const matchesSearch = (!currentSearch || name.includes(currentSearch) || role.includes(currentSearch));

                    if (matchesDept && matchesSearch) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (counterDisplay) {
                    counterDisplay.textContent = `Showing ${visibleCount} members`;
                }
            }

            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentFilter = btn.getAttribute('data-filter');
                    updateFilter();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    currentSearch = e.target.value.toLowerCase().trim();
                    updateFilter();
                });
            }
        });
    </script>

</body>
</html>
