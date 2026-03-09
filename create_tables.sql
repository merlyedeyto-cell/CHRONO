-- Database: `CALEN`
-- Table structure for calendar memory application

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS CALEN;
USE CALEN;

-- Table structure for table `memories`
CREATE TABLE IF NOT EXISTS `memories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `mood` varchar(50) DEFAULT NULL,
  `reaction` varchar(16) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `audio_path` varchar(500) DEFAULT NULL,
  `video_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `memory_tags`
CREATE TABLE IF NOT EXISTS `memory_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `memory_id` int(11) NOT NULL,
  `tag` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `memory_id` (`memory_id`),
  CONSTRAINT `memory_tags_ibfk_1` FOREIGN KEY (`memory_id`) REFERENCES `memories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `pinned_memories`
CREATE TABLE IF NOT EXISTS `pinned_memories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `memory_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pin` (`user_id`,`memory_id`),
  KEY `memory_id` (`memory_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample data
INSERT INTO `memories` (`date`, `title`, `description`, `image_path`) VALUES
('2024-01-15', 'New Year Celebration', 'Started the year with fireworks and good company', NULL),
('2024-02-14', 'Valentines Day', 'Special dinner with loved ones', NULL),
('2024-03-21', 'Spring Equinox', 'First day of spring, flowers starting to bloom', NULL);

INSERT INTO `memory_tags` (`memory_id`, `tag`) VALUES
(1, 'celebration'),
(1, 'new-year'),
(2, 'valentines'),
(2, 'love'),
(3, 'spring'),
(3, 'nature');

-- Display table structure
DESCRIBE memories;
DESCRIBE memory_tags;

-- Display sample data
SELECT * FROM memories;
SELECT * FROM memory_tags;
