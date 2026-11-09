-- Falhen Media Database Schema
CREATE DATABASE IF NOT EXISTS `falhen_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `falhen_db`;

-- 1. Table for Client Inquiries & Quote Requests
CREATE TABLE IF NOT EXISTS `inquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `service_type` VARCHAR(100) NOT NULL,
    `budget_range` VARCHAR(100) DEFAULT NULL,
    `project_details` TEXT NOT NULL,
    `status` ENUM('new', 'in_review', 'contacted', 'archived') DEFAULT 'new',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table for Services Offered
CREATE TABLE IF NOT EXISTS `services` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `title` VARCHAR(150) NOT NULL,
    `subtitle` VARCHAR(255) DEFAULT NULL,
    `description` TEXT NOT NULL,
    `icon` VARCHAR(100) NOT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `featured` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table for Portfolio & Case Studies
CREATE TABLE IF NOT EXISTS `portfolio` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `client` VARCHAR(150) DEFAULT NULL,
    `thumbnail_url` VARCHAR(255) NOT NULL,
    `video_url` VARCHAR(255) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `featured` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table for Testimonials
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_name` VARCHAR(150) NOT NULL,
    `company_role` VARCHAR(150) DEFAULT NULL,
    `avatar_url` VARCHAR(255) DEFAULT NULL,
    `content` TEXT NOT NULL,
    `rating` TINYINT(1) DEFAULT 5,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Table for Admin Users & Staff Authentication
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `alt_email` VARCHAR(150) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL DEFAULT 'Staff Member',
    `role` VARCHAR(50) DEFAULT 'Staff',
    `is_owner` TINYINT(1) DEFAULT 0,
    `status` ENUM('active', 'pending_activation', 'suspended') DEFAULT 'active',
    `reset_otp` VARCHAR(64) DEFAULT NULL,
    `reset_token` VARCHAR(255) DEFAULT NULL,
    `reset_expires` DATETIME DEFAULT NULL,
    `remember_token` VARCHAR(255) DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `last_login_ip` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_admin_users_email_status` (`email`, `status`),
    INDEX `idx_admin_users_username_status` (`username`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pre-populate Initial Services
INSERT INTO `services` (`slug`, `title`, `subtitle`, `description`, `icon`, `image_url`, `featured`) VALUES
('video-production', 'Video Production', '4K/8K Cinematography & Brand Films', 'High-impact corporate videos, commercial ads, brand documentaries, and narrative films shot with state-of-the-art gear and expert cinematography crews.', 'fa-solid fa-film', 'https://res.cloudinary.com/pnabfi91/image/upload/v1791224271/falhen/portfolio/ysfriy8jlhhkcuzl8j16.jpg', 1),
('post-production', 'Post Production', 'Editing, Color Grading & VFX', 'Precision video editing, Hollywood-grade color grading, sound design, mixing, motion visuals, and master delivery optimized for every platform.', 'fa-solid fa-sliders', 'https://res.cloudinary.com/pnabfi91/image/upload/v1791380765/falhen/bts/bqpxfuekjfuqrbq1sqed.jpg', 1),
('live-streaming', 'Live Streaming', 'Multi-Camera Broadcast & Events', 'Ultra-low latency live event broadcast streaming to YouTube, Facebook, LinkedIn, or private RTMP endpoints with redundant backup systems.', 'fa-solid fa-tower-broadcast', 'https://res.cloudinary.com/pnabfi91/image/upload/v1791222750/falhen/portfolio/blpzc2fznaqr0czm3ji8.jpg', 1),
('animation', 'Animation & Motion Graphics', '2D/3D Motion Visuals', 'Captivating 2D vector animation, 3D product visualizations, logo stings, kinetic typography, and visual FX that elevate brand narratives.', 'fa-solid fa-wand-magic-sparkles', 'https://res.cloudinary.com/pnabfi91/image/upload/v1791380709/falhen/bts/yqjuxtubd9ka0gyqw68c.jpg', 1),
('wedding-events', 'Wedding & Event Coverage', 'Cinematic Love Stories & High-Profile Events', 'Emotional, cinematic wedding videography, same-day edit highlights, drone aerials, and full multi-cam event coverage.', 'fa-solid fa-heart', 'https://res.cloudinary.com/pnabfi91/image/upload/v1791380777/falhen/bts/hu9buzs1ccakow1eqsna.jpg', 1),
('content-strategy', 'Content Strategy', 'Brand Positioning & Production Planning', 'End-to-end media strategy, creative concept development, scriptwriting, campaign planning, and channel distribution optimization.', 'fa-solid fa-lightbulb', 'https://res.cloudinary.com/pnabfi91/image/upload/v1791224320/falhen/portfolio/qdkldutyaqhrena0mrza.jpg', 1),
('photography', 'Commercial Photography', 'Editorial, Product & Event Stills', 'Stunning commercial photography, corporate portraits, architectural stills, product showcases, and high-fashion editorial imagery.', 'fa-solid fa-camera', 'https://res.cloudinary.com/pnabfi91/image/upload/v1794096443/falhen/client-galleries/king-david-grad-shoot/king-david-grad-shoot-01.jpg', 1);

-- Pre-populate Initial Portfolio Showcase
INSERT INTO `portfolio` (`title`, `category`, `client`, `thumbnail_url`, `video_url`, `description`, `featured`) VALUES
('Apex Global Brand Anthem', 'Video Production', 'Apex Tech', 'https://res.cloudinary.com/pnabfi91/image/upload/v1791224271/falhen/portfolio/ysfriy8jlhhkcuzl8j16.jpg', 'https://www.youtube.com/embed/ySus5ZS0b94', 'An inspiring brand anthem capturing human innovation across 5 global hubs.', 1),
('Urban Odyssey Commercial', 'Commercial', 'Nike Sportswear', 'https://res.cloudinary.com/pnabfi91/image/upload/v1791222920/falhen/portfolio/c5k5xwoa1misejie2oxy.jpg', 'https://www.youtube.com/embed/ySus5ZS0b94', 'High-energy fast-cut street sports commercial.', 1),
('Symphony of Lights Festival', 'Live Streaming', 'Global Culture Fest', 'https://res.cloudinary.com/pnabfi91/image/upload/v1791222750/falhen/portfolio/blpzc2fznaqr0czm3ji8.jpg', 'https://www.youtube.com/embed/ySus5ZS0b94', '4K multi-cam live broadcast to over 1.2M online attendees.', 1),
('Elegance in Bloom - Wedding Film', 'Wedding', 'Sophia & Marcus', 'https://res.cloudinary.com/pnabfi91/image/upload/v1791380777/falhen/bts/hu9buzs1ccakow1eqsna.jpg', 'https://www.youtube.com/embed/ySus5ZS0b94', 'A breathtaking destination wedding film shot in Cape Town.', 1),
('Neura3D Product Reveal', 'Animation', 'Neura Tech', 'https://res.cloudinary.com/pnabfi91/image/upload/v1791380709/falhen/bts/yqjuxtubd9ka0gyqw68c.jpg', 'https://www.youtube.com/embed/ySus5ZS0b94', 'Photorealistic 3D product animation showcasing futuristic hardware.', 1);

-- Pre-populate Sample Testimonials
INSERT INTO `testimonials` (`client_name`, `company_role`, `avatar_url`, `content`, `rating`) VALUES
('David Vance', 'Marketing Director, Horizon Global', 'https://res.cloudinary.com/pnabfi91/image/upload/v1786712075/falhen/team/ctxjp9mymgqdh2ecg2hu.jpg', 'Falhen delivered far beyond our expectations. The visual storytelling in our brand launch video generated record-breaking engagement.', 5),
('Claire Dupont', 'Event Lead, Vantage Summit', 'https://res.cloudinary.com/pnabfi91/image/upload/v1786714006/falhen/team/ini79za7jvbjokrf1dup.jpg', 'Their live streaming crew executed a flawless multi-camera broadcast for our global conference. Reliable, calm under pressure, and ultra-professional.', 5),
('Marcus & Evelyn', 'Destination Wedding Couple', 'https://res.cloudinary.com/pnabfi91/image/upload/v1786713958/falhen/team/b9r0j3jqvqflp2x7f64w.jpg', 'Our wedding video feels like a Hollywood motion picture! Every detail, emotion, and song match was perfection.', 5);

-- Insert Default Owner / Super Admin and Staff Accounts
INSERT INTO `admin_users` (`username`, `email`, `alt_email`, `password_hash`, `role`, `full_name`, `is_owner`, `status`) VALUES
('henry.falonipe', 'henry.falonipe@falhen.com', 'henry@falhen.com', '$2y$10$w8T.N0V3F0r.rC3TqN197.W84d7O8w4Q8gU4E9hR2F1H0Z3F4X5Y6', 'Owner / Super Admin', 'Henry Falonipe', 1, 'active'),
('admin', 'admin@falhen.com', 'henry@falhen.com', '$2y$10$w8T.N0V3F0r.rC3TqN197.W84d7O8w4Q8gU4E9hR2F1H0Z3F4X5Y6', 'Owner / Super Admin', 'Henry Falonipe', 1, 'active'),
('staff', 'mail@falhenmedia.com', 'team@falhen.com', '$2y$10$w8T.N0V3F0r.rC3TqN197.W84d7O8w4Q8gU4E9hR2F1H0Z3F4X5Y6', 'Staff', 'Production Specialist', 0, 'active')
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`), `is_owner` = VALUES(`is_owner`), `status` = VALUES(`status`), `alt_email` = VALUES(`alt_email`);
