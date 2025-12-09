-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 09, 2025 at 04:55 AM
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
(1, 1, 'Vinceeeeee', '09445516426', NULL, '2025-12-09 11:45:34', '2025-12-02 06:26:51', '2025-12-09 03:45:34');

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
(2, 'Manuel Colorado', 'colorado@gmail.com', '09123456789', 'afhjyytrwdxs', '14b5a5500d0feab699807c3c73660eac', '2025-12-04 09:01:55', '2025-12-03 08:01:55', '2025-12-03 08:01:55'),
(3, 'ron medel', 'sad@gmail.com', '09154545454', 'adasdas', '0eb2404103e279f0f4fe34bf81dff5d0', '2025-12-06 15:15:12', '2025-12-05 14:15:12', '2025-12-05 14:15:12'),
(4, 'Rojhed Dizon', 'Dizon@gmail.com', '09461124588', 'going ccs office', '1d4c18913b9ab09aa5a0e5e587a7e0f7', '2025-12-06 16:44:41', '2025-12-05 15:44:41', '2025-12-05 15:44:41'),
(5, 'ron medel', 'sad@gmail.com', '09545645645', 'adasdas', '250314be2238c63cdf1cbff6a0cc8714', '2025-12-06 17:22:13', '2025-12-05 16:22:13', '2025-12-05 16:22:13'),
(6, 'Mans', 'adasd@gmail.com', '09451254651', 'asdasdas', 'fa2b8d006d2b0a89180a366299c8790d', '2025-12-07 10:34:35', '2025-12-06 09:34:35', '2025-12-06 09:34:35'),
(7, 'ron medel', 'asdasd@gmail.com', '25454511212', 'asdasdasd', '07ba48605d7e20b207e4e62222988241', '2025-12-07 11:11:03', '2025-12-06 10:11:03', '2025-12-06 10:11:03'),
(8, 'ron medel', 'sad@gmail.com', '09545645645', 'adasdas', '39ea97af3c2e090277c4b258ae05de0c', '2025-12-09 06:06:57', '2025-12-08 05:06:57', '2025-12-08 05:06:57');

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
(38, NULL, 7, 10, '2025-12-06 18:20:03', NULL, 'in_progress', '2025-12-06 10:20:03'),
(39, NULL, 7, 10, '2025-12-06 18:20:03', NULL, 'in_progress', '2025-12-06 10:20:03'),
(41, NULL, 7, 10, '2025-12-06 18:29:36', '2025-12-06 18:29:44', 'cancelled', '2025-12-06 10:29:36'),
(48, NULL, 7, 13, '2025-12-06 18:31:20', '2025-12-06 18:33:04', 'cancelled', '2025-12-06 10:31:20'),
(51, NULL, 7, 13, '2025-12-06 18:40:24', NULL, 'in_progress', '2025-12-06 10:40:24'),
(52, NULL, 7, 13, '2025-12-06 18:40:24', '2025-12-06 18:40:39', 'cancelled', '2025-12-06 10:40:24'),
(54, NULL, 7, 10, '2025-12-06 18:40:43', '2025-12-06 18:40:47', 'cancelled', '2025-12-06 10:40:43'),
(57, NULL, 7, 13, '2025-12-06 18:49:53', '2025-12-06 18:50:08', 'cancelled', '2025-12-06 10:49:53'),
(61, NULL, 7, 10, '2025-12-06 18:50:21', NULL, 'in_progress', '2025-12-06 10:50:21'),
(62, NULL, 7, 10, '2025-12-06 18:50:48', NULL, 'in_progress', '2025-12-06 10:50:48'),
(64, NULL, 7, 13, '2025-12-06 18:55:17', NULL, 'in_progress', '2025-12-06 10:55:17'),
(67, NULL, 7, 10, '2025-12-06 19:07:02', '2025-12-06 19:07:08', 'cancelled', '2025-12-06 11:07:02'),
(69, NULL, 7, 13, '2025-12-06 19:07:15', NULL, 'in_progress', '2025-12-06 11:07:15'),
(70, NULL, 7, 14, '2025-12-06 19:15:33', NULL, 'in_progress', '2025-12-06 11:15:33'),
(71, NULL, 7, 14, '2025-12-06 19:21:26', NULL, 'in_progress', '2025-12-06 11:21:26'),
(73, NULL, 7, 13, '2025-12-06 19:23:58', '2025-12-06 19:24:15', 'cancelled', '2025-12-06 11:23:58'),
(74, NULL, 7, 10, '2025-12-06 19:24:15', '2025-12-06 19:24:18', 'cancelled', '2025-12-06 11:24:15'),
(77, NULL, 7, 13, '2025-12-06 19:46:19', NULL, 'in_progress', '2025-12-06 11:46:19'),
(78, NULL, 7, 13, '2025-12-06 19:53:48', NULL, 'in_progress', '2025-12-06 11:53:48'),
(79, NULL, 7, 14, '2025-12-07 11:28:40', '2025-12-07 11:28:49', 'cancelled', '2025-12-07 03:28:40'),
(80, NULL, 7, 13, '2025-12-07 12:19:07', '2025-12-07 12:19:17', 'cancelled', '2025-12-07 04:19:07'),
(86, NULL, 7, 10, '2025-12-07 12:46:16', '2025-12-07 12:46:19', 'cancelled', '2025-12-07 04:46:16'),
(87, NULL, 7, 14, '2025-12-07 12:46:19', '2025-12-07 12:46:22', 'cancelled', '2025-12-07 04:46:19'),
(88, NULL, 7, 13, '2025-12-07 12:46:22', '2025-12-07 12:46:25', 'cancelled', '2025-12-07 04:46:22'),
(93, NULL, 7, 10, '2025-12-07 12:49:35', NULL, 'in_progress', '2025-12-07 04:49:35'),
(94, NULL, 7, 10, '2025-12-07 12:49:35', '2025-12-07 12:49:38', 'cancelled', '2025-12-07 04:49:35'),
(95, NULL, 7, 14, '2025-12-07 12:49:38', NULL, 'in_progress', '2025-12-07 04:49:38'),
(96, NULL, 7, 14, '2025-12-07 12:49:38', '2025-12-07 12:50:40', 'cancelled', '2025-12-07 04:49:38'),
(111, 12, NULL, 10, '2025-12-08 13:16:16', '2025-12-08 13:16:25', 'cancelled', '2025-12-08 05:16:16'),
(119, 12, NULL, 15, '2025-12-08 13:18:21', '2025-12-08 13:18:30', 'cancelled', '2025-12-08 05:18:21'),
(123, 12, NULL, 15, '2025-12-08 13:18:33', NULL, 'in_progress', '2025-12-08 05:18:33'),
(124, 12, NULL, 15, '2025-12-08 13:18:33', NULL, 'in_progress', '2025-12-08 05:18:33'),
(148, 12, NULL, 15, '2025-12-08 13:26:57', '2025-12-08 13:26:59', 'cancelled', '2025-12-08 05:26:57'),
(154, 12, NULL, 15, '2025-12-08 13:33:20', '2025-12-08 13:33:25', 'cancelled', '2025-12-08 05:33:20'),
(179, 12, NULL, 10, '2025-12-08 13:40:40', '2025-12-08 13:40:46', 'cancelled', '2025-12-08 05:40:40'),
(183, 12, NULL, 15, '2025-12-09 11:51:04', NULL, 'in_progress', '2025-12-09 03:51:04');

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
(10, 2, 'College of Computer Studies (CCS) second floor', 'CCS office is on the second floor of STGB building', 10.64294832, 122.94014284, 1, '2025-12-03 01:19:36', '2025-12-09 03:47:40'),
(13, 2, 'Library', 'asdasdasd', 10.64252758, 122.93839299, 1, '2025-12-06 10:31:00', '2025-12-06 10:31:00'),
(14, 2, 'RDCAGIS', 'asdasd', 10.64306972, 122.94017158, 1, '2025-12-06 11:15:02', '2025-12-06 11:15:02'),
(15, 2, 'AVR', 'asdasdas', 10.64278221, 122.93857495, 1, '2025-12-08 05:18:06', '2025-12-08 05:18:06');

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
(52, 10, 'buildings/office_10_marker.png', 1, '2025-12-03 01:19:36'),
(53, 10, 'building_content/office_10_1764724776_39dd038c.jpg', 0, '2025-12-03 01:19:36'),
(54, 10, 'building_content/office_10_1764724776_8c7b4933.jpg', 0, '2025-12-03 01:19:36'),
(55, 10, 'building_content/office_10_1764724776_a7a1af1a.jpg', 0, '2025-12-03 01:19:36'),
(56, 10, 'building_content/office_10_1764724776_e41ea4b3.jpg', 0, '2025-12-03 01:19:36'),
(67, 13, 'buildings/office_13_marker.png', 1, '2025-12-06 10:31:00'),
(68, 13, 'building_content/office_13_1765017060_f92e5f15.jpg', 0, '2025-12-06 10:31:00'),
(69, 13, 'building_content/office_13_1765017060_288f7a28.png', 0, '2025-12-06 10:31:00'),
(70, 13, 'building_content/office_13_1765017060_b8c41caa.jpg', 0, '2025-12-06 10:31:00'),
(71, 13, 'building_content/office_13_1765017060_177154f8.jpg', 0, '2025-12-06 10:31:00'),
(72, 14, 'buildings/office_14_marker.jpg', 1, '2025-12-06 11:15:02'),
(73, 14, 'building_content/office_14_1765019702_0bc663f9.jpg', 0, '2025-12-06 11:15:02'),
(74, 14, 'building_content/office_14_1765019702_c0ec1a80.jpg', 0, '2025-12-06 11:15:02'),
(75, 14, 'building_content/office_14_1765019702_eb1a5dd7.jpg', 0, '2025-12-06 11:15:02'),
(76, 14, 'building_content/office_14_1765019702_766956db.jpg', 0, '2025-12-06 11:15:02'),
(77, 15, 'buildings/office_15_marker.png', 1, '2025-12-08 05:18:06'),
(78, 15, 'building_content/office_15_1765171086_7bd0d4bd.jpg', 0, '2025-12-08 05:18:06'),
(79, 15, 'building_content/office_15_1765171086_a8af5b3a.png', 0, '2025-12-08 05:18:06'),
(80, 15, 'building_content/office_15_1765171086_42a6c629.jpg', 0, '2025-12-08 05:18:06'),
(81, 15, 'building_content/office_15_1765171086_0bb17dea.jpg', 0, '2025-12-08 05:18:06');

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
(11, 12, 'JSB02030300', 'Jasmin Martinez', 5, 'martinez@gmail.com', '09461578954', 'images/profiles/student_12_1764948632_d5018d53.png', '2025-12-03 06:12:46', '2025-12-05 15:30:32');

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
(12, 'JSB02030300', '$2y$10$vAh7ckH2fttATlz4gLET9OtGh8FLgFtQXqa2zVKOZmAtAtV62fBMG', 'martinez@gmail.com', 'student', '2025-12-03 06:12:46', '2025-12-05 15:30:32');

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
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `navigation_logs`
--
ALTER TABLE `navigation_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `office_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `office_categories`
--
ALTER TABLE `office_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `office_images`
--
ALTER TABLE `office_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

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
  ADD CONSTRAINT `navigation_logs_office_fk` FOREIGN KEY (`office_id`) REFERENCES `offices` (`office_id`) ON DELETE CASCADE;

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
