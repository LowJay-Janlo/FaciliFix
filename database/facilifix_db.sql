-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 06, 2026 at 11:53 AM
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
-- Database: `facilifix_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `equipment_name` varchar(150) NOT NULL,
  `status` enum('Working','Broken','Missing') NOT NULL DEFAULT 'Working',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `room_id`, `equipment_name`, `status`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'Dell Desktop PC #1', 'Working', NULL, '2026-10-05 13:35:13', '2026-10-05 13:35:13'),
(2, 1, 'Epson Projector', 'Working', NULL, '2026-10-05 13:35:13', '2026-10-05 13:35:13'),
(3, 2, 'Ceiling Fan', 'Working', NULL, '2026-10-05 13:35:13', '2026-10-05 13:35:13'),
(4, 3, 'Computer', 'Working', 'Reading station PC, near the entrance.', '2026-10-06 09:52:59', '2026-10-06 09:52:59'),
(5, 3, 'Electric Fan', 'Broken', 'Blades wobble and the motor makes a loud noise.', '2026-10-06 09:52:59', '2026-10-06 09:52:59'),
(6, 3, 'TV Remote', 'Missing', 'Last seen on the shelf by the projector screen.', '2026-10-06 09:52:59', '2026-10-06 09:52:59'),
(7, 3, 'Mini PC', 'Working', 'Used by the librarian for the catalog.', '2026-10-06 09:52:59', '2026-10-06 09:52:59'),
(8, 3, 'Computer', 'Working', NULL, '2026-10-06 09:52:59', '2026-10-06 09:52:59'),
(9, 3, 'Printer', 'Working', 'Black and white only.', '2026-10-06 09:52:59', '2026-10-06 09:52:59'),
(10, 3, 'Air Conditioner', 'Broken', 'Does not cool. Technician already informed.', '2026-10-06 09:52:59', '2026-10-06 09:52:59');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status_reported` enum('Working','Broken','Missing') NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `room_tag` enum('1st Floor','2nd Floor','3rd Floor','4th Floor') DEFAULT NULL,
  `room_icon` enum('office','staff','student','computer_lab') NOT NULL DEFAULT 'student',
  `building` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_name`, `room_tag`, `room_icon`, `building`, `created_at`) VALUES
(1, 'Computer Lab 1', '4th Floor', 'computer_lab', 'IT Building', '2026-10-05 13:35:13'),
(2, 'Room 101', '1st Floor', 'student', 'Main Building', '2026-10-05 13:35:13'),
(3, 'Library 002', '2nd Floor', 'student', NULL, '2026-10-05 16:02:42'),
(4, 'Library 001', '2nd Floor', 'student', NULL, '2026-10-05 16:02:42'),
(5, 'Room 202', '2nd Floor', 'student', NULL, '2026-10-05 16:02:42'),
(6, 'Room 401', '4th Floor', 'student', NULL, '2026-10-05 16:02:42'),
(7, 'CIT Office', '1st Floor', 'office', NULL, '2026-10-05 16:02:42'),
(8, 'CAS Office', '1st Floor', 'office', NULL, '2026-10-05 16:02:42'),
(9, 'Registrar', '1st Floor', 'office', NULL, '2026-10-05 16:02:42'),
(10, 'Com Lab 404', '4th Floor', 'computer_lab', NULL, '2026-10-05 16:02:42'),
(11, 'Computer Lab 2', '3rd Floor', 'computer_lab', NULL, '2026-10-06 08:57:41'),
(12, 'Staff', '2nd Floor', 'student', NULL, '2026-10-06 08:58:28'),
(13, 'CitStaff Room', '1st Floor', 'staff', NULL, '2026-10-06 09:15:55'),
(14, 'Gamin Room', '4th Floor', 'computer_lab', NULL, '2026-10-06 09:28:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Staff') NOT NULL DEFAULT 'Staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'System Admin', 'admin@facilifix.com', 'admin123', 'Admin', '2026-10-05 13:35:13'),
(2, 'Sample Teacher', 'staff@facilifix.com', 'staff123', 'Staff', '2026-10-05 13:35:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_equipment_room` (`room_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reports_equipment` (`equipment_id`),
  ADD KEY `fk_reports_user` (`user_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `equipment`
--
ALTER TABLE `equipment`
  ADD CONSTRAINT `fk_equipment_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
