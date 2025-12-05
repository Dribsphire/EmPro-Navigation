-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 05, 2025 at 02:30 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `empro_navigation`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `user_id`, `full_name`, `phone`, `profile_picture`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 1, 'Vinceeeeee', '09445516426', NULL, '2025-12-05 20:56:52', '2025-12-02 06:26:51', '2025-12-05 12:56:52');

-- --------------------------------------------------------

--
-- Table structure for table `drill_alerts`
--

CREATE TABLE `drill_alerts` (
  `alert_id` int(11) NOT NULL,
  `alert_type` enum('fire','earthquake','tsunami','lockdown','other') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ended_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drill_alerts`
--

INSERT INTO `drill_alerts` (`alert_id`, `alert_type`, `title`, `description`, `is_active`, `created_by`, `created_at`, `ended_at`) VALUES
(1, 'earthquake', '???? EARTHQUAKE DRILL ALERT', 'asd', 0, 1, '2025-12-04 03:32:25', '2025-12-04 10:44:00'),
(2, 'earthquake', '???? EARTHQUAKE DRILL ALERT', 'adsdsdasdasdasdas dasdasdas', 0, 1, '2025-12-04 10:46:53', '2025-12-05 11:59:32'),
(3, 'earthquake', 'Earthquake Drill', 'adasd', 0, 1, '2025-12-05 12:04:53', '2025-12-05 12:58:07'),
(4, 'earthquake', 'Earthquake Drill', 'adasd', 0, 1, '2025-12-05 13:28:43', '2025-12-05 13:29:07');

-- --------------------------------------------------------

--
-- Table structure for table `guests`
--

CREATE TABLE `guests` (
  `guest_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `token` varchar(100) NOT NULL,
  `token_expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guests`
--

INSERT INTO `guests` (`guest_id`, `full_name`, `email`, `phone`, `reason`, `token`, `token_expiry`, `created_at`, `updated_at`) VALUES
(1, 'Colorado_ Manuel', 'coloradomanuel.002@gmail.com', '09462265986', 'asdsd', '909558a5ec50fdddc22b4fca2f58dfed', '2025-12-04 07:04:51', '2025-12-03 06:04:51', '2025-12-03 06:04:51'),
(2, 'Manuel Colorado', 'colorado@gmail.com', '09123456789', 'afhjyytrwdxs', '14b5a5500d0feab699807c3c73660eac', '2025-12-04 09:01:55', '2025-12-03 08:01:55', '2025-12-03 08:01:55');

-- --------------------------------------------------------

--
-- Table structure for table `navigation_logs`
--

CREATE TABLE `navigation_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `office_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('in_progress','completed','cancelled') NOT NULL DEFAULT 'in_progress',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `navigation_logs`
--

INSERT INTO `navigation_logs` (`log_id`, `user_id`, `guest_id`, `office_id`, `start_time`, `end_time`, `status`, `created_at`) VALUES
(1, NULL, 1, 10, '2025-12-03 14:05:01', NULL, 'completed', '2025-12-03 06:05:01'),
(2, NULL, 1, 10, '2025-12-03 14:05:01', '2025-12-03 14:05:06', 'completed', '2025-12-03 06:05:01'),
(3, 12, NULL, 6, '2025-12-03 14:15:03', '2025-12-03 14:15:09', 'completed', '2025-12-03 06:15:03'),
(4, 4, NULL, 6, '2025-12-03 14:26:30', '2025-12-03 14:26:40', 'cancelled', '2025-12-03 06:26:30'),
(5, 4, NULL, 9, '2025-12-03 14:27:16', '2025-12-03 14:27:19', 'cancelled', '2025-12-03 06:27:16'),
(6, 4, NULL, 6, '2025-12-03 14:34:22', '2025-12-03 14:34:47', 'cancelled', '2025-12-03 06:34:22'),
(7, 12, NULL, 9, '2025-12-03 16:00:59', NULL, 'in_progress', '2025-12-03 08:00:59'),
(8, 12, NULL, 6, '2025-12-04 18:57:52', '2025-12-04 18:57:59', 'cancelled', '2025-12-04 10:57:52');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `office_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `office_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location_lat` decimal(10,8) NOT NULL,
  `location_lng` decimal(11,8) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`office_id`, `category_id`, `office_name`, `description`, `location_lat`, `location_lng`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Registrar\'s Office', 'Handles student records and registration', 10.64073900, 122.96887300, 1, '2025-12-02 06:26:51', '2025-12-02 06:26:51'),
(6, 2, 'ccs office', 'dadasd', 10.64270614, 122.93947421, 1, '2025-12-02 16:08:51', '2025-12-02 16:08:51'),
(9, 2, 'GAD OFFICE', 'Gender and Development Office', 10.64234518, 122.93971466, 1, '2025-12-03 01:08:21', '2025-12-03 01:08:21'),
(10, 2, 'College of Computer Studies (CCS)', 'CCS office is on the second floor of STGB building', 10.64294832, 122.94014284, 1, '2025-12-03 01:19:36', '2025-12-03 01:20:04'),
(12, 2, 'Clinic', 'asdasdasd', 10.64186410, 122.93888182, 1, '2025-12-05 11:21:24', '2025-12-05 11:21:24');

-- --------------------------------------------------------

--
-- Table structure for table `office_categories`
--

CREATE TABLE `office_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `office_categories`
--

INSERT INTO `office_categories` (`category_id`, `name`, `description`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'Administrative', 'Main administrative offices', 'fa-building', '2025-12-02 06:26:51', '2025-12-02 06:26:51'),
(2, 'Academic', 'Academic departments and offices', 'fa-graduation-cap', '2025-12-02 06:26:51', '2025-12-02 06:26:51'),
(3, 'Services', 'Student services and support', 'fa-hands-helping', '2025-12-02 06:26:51', '2025-12-02 06:26:51');

-- --------------------------------------------------------

--
-- Table structure for table `office_images`
--

CREATE TABLE `office_images` (
  `image_id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `office_images`
--

INSERT INTO `office_images` (`image_id`, `office_id`, `image_path`, `is_primary`, `uploaded_at`) VALUES
(28, 6, 'buildings/office_6_marker.png', 1, '2025-12-02 16:08:51'),
(29, 6, 'building_content/office_6_1764691731_1f2ae0ba.jpg', 0, '2025-12-02 16:08:51'),
(30, 6, 'building_content/office_6_1764691731_c0845a05.jpg', 0, '2025-12-02 16:08:51'),
(31, 6, 'building_content/office_6_1764691731_c7e2f5dd.jpg', 0, '2025-12-02 16:08:51'),
(32, 6, 'building_content/office_6_1764691731_4ef7f231.jpg', 0, '2025-12-02 16:08:51'),
(48, 9, 'buildings/office_9_marker.JPG', 1, '2025-12-03 01:08:21'),
(49, 9, 'building_content/office_9_1764724101_8c807387.jpg', 0, '2025-12-03 01:08:21'),
(50, 9, 'building_content/office_9_1764724101_80fb8032.jpg', 0, '2025-12-03 01:08:21'),
(51, 9, 'building_content/office_9_1764724101_16efedde.jpg', 0, '2025-12-03 01:08:21'),
(52, 10, 'buildings/office_10_marker.png', 1, '2025-12-03 01:19:36'),
(53, 10, 'building_content/office_10_1764724776_39dd038c.jpg', 0, '2025-12-03 01:19:36'),
(54, 10, 'building_content/office_10_1764724776_8c7b4933.jpg', 0, '2025-12-03 01:19:36'),
(55, 10, 'building_content/office_10_1764724776_a7a1af1a.jpg', 0, '2025-12-03 01:19:36'),
(56, 10, 'building_content/office_10_1764724776_e41ea4b3.jpg', 0, '2025-12-03 01:19:36'),
(62, 12, 'buildings/office_12_marker.jpg', 1, '2025-12-05 11:21:24'),
(63, 12, 'building_content/office_12_1764933684_750c8dc5.jpg', 0, '2025-12-05 11:21:24'),
(64, 12, 'building_content/office_12_1764933684_f9b7529c.png', 0, '2025-12-05 11:21:24'),
(65, 12, 'building_content/office_12_1764933684_9a06efb2.jpg', 0, '2025-12-05 11:21:24'),
(66, 12, 'building_content/office_12_1764933684_637f7ec8.jpg', 0, '2025-12-05 11:21:24');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `section_id` int(11) NOT NULL,
  `section_code` varchar(20) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_id`, `section_code`, `section_name`, `created_at`, `updated_at`) VALUES
(1, 'BSIT-1A', 'Bachelor of Science in Information Technology 1A', '2025-12-02 06:26:51', '2025-12-02 06:26:51'),
(2, 'BSIT-1B', 'Bachelor of Science in Information Technology 1B', '2025-12-02 06:26:51', '2025-12-02 06:26:51'),
(3, 'BSCS-1A', 'Bachelor of Science in Computer Science 1A', '2025-12-02 06:26:51', '2025-12-02 06:26:51'),
(4, 'BSIT-4A', 'BSIT-4A', '2025-12-02 10:04:25', '2025-12-02 10:04:25'),
(5, 'BSIT-4B', 'BSIT-4B', '2025-12-02 10:09:00', '2025-12-02 10:09:00');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `school_id` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `section_id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `school_id`, `full_name`, `section_id`, `email`, `phone`, `profile_picture`, `created_at`, `updated_at`) VALUES
(1, 2, 'DRS07100000', 'Rojed Dizon', 4, 'Dizon@gmail.com', '09461135268', NULL, '2025-12-02 10:04:26', '2025-12-02 10:04:26'),
(3, 4, 'CMA12040300', 'Manuel Colorado', 5, 'colorado@gmail.com', '09421165238', NULL, '2025-12-02 10:20:16', '2025-12-02 10:20:16'),
(11, 12, 'JSB02030300', 'Jasmin Martinez', 5, 'Martinez@gmail.com', '09461578954', NULL, '2025-12-03 06:12:46', '2025-12-03 06:12:46');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_type` enum('admin','student','guest') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `user_type`, `created_at`, `updated_at`) VALUES
(1, 'JVA10130300', '$2y$10$BxjlR4.tCXIOH6UPFu1g5OWu6OAubadYISHKQUEzRNjkt1.2yeMu.', 'vince@gmail.com', 'admin', '2025-12-02 06:26:51', '2025-12-02 09:52:51'),
(2, 'DRS07100000', '$2y$10$2OLRDqTBTvHxzPXzWSYrfOho30XlGrE6irSzrEo9mm4e3KQBo8Ovy', 'Dizon@gmail.com', 'student', '2025-12-02 10:04:26', '2025-12-02 10:04:26'),
(4, 'CMA12040300', '$2y$10$1rFsDkTGOUjjSuYkNgnoYeIs.8MUs2xSGYyJ0H6qIFa7Cpu42DtfK', 'colorado@gmail.com', 'student', '2025-12-02 10:20:16', '2025-12-03 06:24:16'),
(12, 'JSB02030300', '$2y$10$vAh7ckH2fttATlz4gLET9OtGh8FLgFtQXqa2zVKOZmAtAtV62fBMG', 'Martinez@gmail.com', 'student', '2025-12-03 06:12:46', '2025-12-04 03:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `user_visits`
--

CREATE TABLE `user_visits` (
  `visit_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `office_id` int(11) NOT NULL,
  `visit_time` datetime NOT NULL,
  `purpose` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `idx_admin_user` (`user_id`);

--
-- Indexes for table `drill_alerts`
--
ALTER TABLE `drill_alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `idx_alert_active` (`is_active`),
  ADD KEY `idx_alert_created` (`created_at`);

--
-- Indexes for table `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`guest_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`);

--
-- Indexes for table `navigation_logs`
--
ALTER TABLE `navigation_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_navigation_user` (`user_id`),
  ADD KEY `idx_navigation_guest` (`guest_id`),
  ADD KEY `idx_navigation_office` (`office_id`),
  ADD KEY `idx_navigation_time` (`start_time`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notification_user` (`user_id`),
  ADD KEY `idx_notification_read` (`is_read`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`office_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_office_name` (`office_name`),
  ADD KEY `idx_location` (`location_lat`,`location_lng`);

--
-- Indexes for table `office_categories`
--
ALTER TABLE `office_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `office_images`
--
ALTER TABLE `office_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_office` (`office_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `section_code` (`section_code`),
  ADD KEY `idx_section_code` (`section_code`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `school_id` (`school_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `idx_student_user` (`user_id`),
  ADD KEY `idx_school_id` (`school_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_type` (`user_type`);

--
-- Indexes for table `user_visits`
--
ALTER TABLE `user_visits`
  ADD PRIMARY KEY (`visit_id`),
  ADD KEY `idx_visit_user` (`user_id`),
  ADD KEY `idx_visit_guest` (`guest_id`),
  ADD KEY `idx_visit_office` (`office_id`),
  ADD KEY `idx_visit_time` (`visit_time`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `drill_alerts`
--
ALTER TABLE `drill_alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `navigation_logs`
--
ALTER TABLE `navigation_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `office_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `office_categories`
--
ALTER TABLE `office_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `office_images`
--
ALTER TABLE `office_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `user_visits`
--
ALTER TABLE `user_visits`
  MODIFY `visit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `navigation_logs`
--
ALTER TABLE `navigation_logs`
  ADD CONSTRAINT `navigation_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `navigation_logs_ibfk_2` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `navigation_logs_ibfk_3` FOREIGN KEY (`office_id`) REFERENCES `offices` (`office_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `offices`
--
ALTER TABLE `offices`
  ADD CONSTRAINT `offices_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `office_categories` (`category_id`),
  ADD CONSTRAINT `offices_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admins` (`admin_id`);

--
-- Constraints for table `office_images`
--
ALTER TABLE `office_images`
  ADD CONSTRAINT `office_images_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`office_id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`);

--
-- Constraints for table `user_visits`
--
ALTER TABLE `user_visits`
  ADD CONSTRAINT `user_visits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_visits_ibfk_2` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_visits_ibfk_3` FOREIGN KEY (`office_id`) REFERENCES `offices` (`office_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
