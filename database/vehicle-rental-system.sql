-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2026 at 06:14 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vehicle_rental`
--

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `verification_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `otp_type` enum('email','phone') DEFAULT 'email',
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`, `created_at`) VALUES
(1, 'user', 'Regular customer who browses and books vehicles', '2026-03-29 07:11:03'),
(2, 'company', 'Organization/company account managing multiple vehicles and staff', '2026-03-29 07:11:03'),
(3, 'agent', 'Partner/agent who can manage bookings on behalf of users', '2026-03-29 07:11:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 1,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('pending','active','suspended') DEFAULT 'pending',
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `role_id`, `username`, `full_name`, `email`, `phone`, `address`, `dob`, `password`, `status`, `email_verified`, `created_at`, `updated_at`) VALUES
(1, 'Chaos', 'Daoist', 1, 'basantabomjan095@gmail.com', 'Chaos Daoist', 'basantabomjan095@gmail.com', '', 'kathmandu', NULL, '$2y$10$62HFCu0CsXN835Vllq4.ZuK2pF0SzrIgALLX/nM2njoh8oE01toPm', 'pending', 0, '2026-03-30 09:09:19', '2026-03-30 09:09:19'),
(2, 'sajal', 'bardewa', 1, 'bardewasajal', 'sajal bardewa', 'bardewasajal55@gmail.com', '9768408956', 'kathmandu', NULL, '$2y$10$EUbeBnu9MRSYsj5EAUsWQeH7efLIoCLIFw4gaJ.JZi5oejlrQgps2', 'active', 0, '2026-03-30 09:17:01', '2026-03-30 11:32:37'),
(3, 'utsav', 'bhandari', 1, 'utsavbhandari', 'utsav bhandari', 'utsav@gmail.com', '9768408956', 'kathmandu', NULL, '$2y$10$Q.n5tRjJ713ltllg0vWn6.xqj/2hQ.o12LuBE25PF3N4IlxlP1nx.', 'pending', 0, '2026-03-30 11:34:57', '2026-03-30 11:34:57'),
(4, 'subham', 'shrestha', 1, 'subhamshrestha', 'subham shrestha', 'subhamshrestha@gmail.com', '', 'kathmandu', NULL, '$2y$10$pOdzitUE/9djaBCTk0H4B.87tdX1CP0ivgdL24gadUas2sb10yMyu', 'active', 0, '2026-03-30 11:37:01', '2026-03-30 11:38:45'),
(5, 'omnath', 'bomjan', 1, 'omnathbomjan', 'omnath bomjan', 'omnathbomjan095@gmail.com', '', 'kathmandu', NULL, '$2y$10$pc0ku95sR7KYtfhYWsT/3.aupv1ZUTTCS0Lzju4aYEDFtZkPCr2x2', 'active', 0, '2026-03-30 11:41:08', '2026-03-30 11:43:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD CONSTRAINT `otp_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
