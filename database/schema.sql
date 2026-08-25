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

-- 5. Table for Admin Users
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `remember_token` VARCHAR(255) DEFAULT NULL,
    `reset_token` VARCHAR(255) DEFAULT NULL,
    `reset_expires` DATETIME DEFAULT NULL,
    `role` VARCHAR(50) DEFAULT 'Staff',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pre-populate Initial Services
INSERT INTO `services` (`slug`, `title`, `subtitle`, `description`, `icon`, `image_url`, `featured`) VALUES
('video-production', 'Video Production', '4K/8K Cinematography & Brand Films', 'High-impact corporate videos, commercial ads, brand documentaries, and narrative films shot with state-of-the-art gear and expert cinematography crews.', 'fa-solid fa-film', 'https://images.unsplash.com/photo-1579165466741-7f35e4755660?q=80&w=1000&auto=format&fit=crop', 1),
('post-production', 'Post Production', 'Editing, Color Grading & VFX', 'Precision video editing, Hollywood-grade color grading, sound design, mixing, motion visuals, and master delivery optimized for every platform.', 'fa-solid fa-sliders', 'https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?q=80&w=1000&auto=format&fit=crop', 1),
('live-streaming', 'Live Streaming', 'Multi-Camera Broadcast & Events', 'Ultra-low latency live event broadcast streaming to YouTube, Facebook, LinkedIn, or private RTMP endpoints with redundant backup systems.', 'fa-solid fa-tower-broadcast', 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1000&auto=format&fit=crop', 1),
('animation', 'Animation & Motion Graphics', '2D/3D Motion Visuals', 'Captivating 2D vector animation, 3D product visualizations, logo stings, kinetic typography, and visual FX that elevate brand narratives.', 'fa-solid fa-wand-magic-sparkles', 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?q=80&w=1000&auto=format&fit=crop', 1),
('wedding-events', 'Wedding & Event Coverage', 'Cinematic Love Stories & High-Profile Events', 'Emotional, cinematic wedding videography, same-day edit highlights, drone aerials, and full multi-cam event coverage.', 'fa-solid fa-heart', 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1000&auto=format&fit=crop', 1),
('content-strategy', 'Content Strategy', 'Brand Positioning & Production Planning', 'End-to-end media strategy, creative concept development, scriptwriting, campaign planning, and channel distribution optimization.', 'fa-solid fa-lightbulb', 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=1000&auto=format&fit=crop', 1),
('photography', 'Commercial Photography', 'Editorial, Product & Event Stills', 'Stunning commercial photography, corporate portraits, architectural stills, product showcases, and high-fashion editorial imagery.', 'fa-solid fa-camera', 'https://images.unsplash.com/photo-1542038784456-1ea8e935640e?q=80&w=1000&auto=format&fit=crop', 1);

-- Pre-populate Initial Portfolio Showcase
INSERT INTO `portfolio` (`title`, `category`, `client`, `thumbnail_url`, `video_url`, `description`, `featured`) VALUES
('Apex Global Brand Anthem', 'Video Production', 'Apex Tech', 'https://images.unsplash.com/photo-1536240478700-b869070f9279?q=80&w=1000&auto=format&fit=crop', 'https://www.youtube.com/embed/ySus5ZS0b94', 'An inspiring brand anthem capturing human innovation across 5 global hubs.', 1),
('Urban Odyssey Commercial', 'Commercial', 'Nike Sportswear', 'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?q=80&w=1000&auto=format&fit=crop', 'https://www.youtube.com/embed/ySus5ZS0b94', 'High-energy fast-cut street sports commercial.', 1),
('Symphony of Lights Festival', 'Live Streaming', 'Global Culture Fest', 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=1000&auto=format&fit=crop', 'https://www.youtube.com/embed/ySus5ZS0b94', '4K multi-cam live broadcast to over 1.2M online attendees.', 1),
('Elegance in Bloom - Wedding Film', 'Wedding', 'Sophia & Marcus', 'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?q=80&w=1000&auto=format&fit=crop', 'https://www.youtube.com/embed/ySus5ZS0b94', 'A breathtaking destination wedding film shot in Cape Town.', 1),
('Neura3D Product Reveal', 'Animation', 'Neura Tech', 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=1000&auto=format&fit=crop', 'https://www.youtube.com/embed/ySus5ZS0b94', 'Photorealistic 3D product animation showcasing futuristic hardware.', 1);

-- Pre-populate Sample Testimonials
INSERT INTO `testimonials` (`client_name`, `company_role`, `avatar_url`, `content`, `rating`) VALUES
('David Vance', 'Marketing Director, Horizon Global', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200&auto=format&fit=crop', 'Falhen delivered far beyond our expectations. The visual storytelling in our brand launch video generated record-breaking engagement.', 5),
('Claire Dupont', 'Event Lead, Vantage Summit', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=200&auto=format&fit=crop', 'Their live streaming crew executed a flawless multi-camera broadcast for our global conference. Reliable, calm under pressure, and ultra-professional.', 5),
('Marcus & Evelyn', 'Destination Wedding Couple', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=200&auto=format&fit=crop', 'Our wedding video feels like a Hollywood motion picture! Every detail, emotion, and song match was perfection.', 5);

-- Insert Default Admin & Staff User (Username: admin, Password: Password123#)
INSERT INTO `admin_users` (`username`, `email`, `password_hash`, `role`) VALUES
('admin', 'kim@falhen.com', '$2y$10$w8T.N0V3F0r.rC3TqN197.W84d7O8w4Q8gU4E9hR2F1H0Z3F4X5Y6', 'Administrator'),
('staff', 'mail@falhenmedia.com', '$2y$10$w8T.N0V3F0r.rC3TqN197.W84d7O8w4Q8gU4E9hR2F1H0Z3F4X5Y6', 'Staff');
