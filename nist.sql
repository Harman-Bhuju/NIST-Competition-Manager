-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 10, 2026 at 02:35 PM
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
-- Database: `nist`
--
CREATE DATABASE IF NOT EXISTS `nist` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `nist`;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `can_enter_marks` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `role`, `can_enter_marks`) VALUES
(1, 'admin', '$argon2id$v=19$m=65536,t=4,p=1$Q0E1cDA0Wll6VjJ5V240SA$xucrrovaPyu/qfXveLD2nZZYrdTM/vGdAJGAJYSy72M', 'admin', 0),
(2, 'harshit', '$2y$10$sFSdrxI2KzhOGJQtqDbyWORVLt1FVhAVhbtKViWHQKeKSBpz/MTr.', 'user', 1),
(3, 'harman', '$2y$10$p4Ef98Xwh8z9p3J32d6lAuEVoOKhvfpRDF9pHZ.XrWxT3l1xFT66y', 'user', 1),
(4, 'gd', '$2y$10$xGN7mHMoLAftY5Znu.7V/e32bI1muM7mI7GS7qxmzMFvXX7.yv7SC', 'user', 0),
(5, 'sn', '$2y$10$Ac58UxOK812haSutsEnO5.9vRVGteZoPd/XvjDKSPUZr.Vgtju8s.', 'user', 0),
(6, 'aarjit', '$2y$10$xdjz5ZpQMvRgp3UCHlOZl.a14isXkRbLl.awxU84aabPFWpDLa7Y2', 'user', 0),
(7, 'sanskar', '$2y$10$xjc4btFJVpmtEn1k/YD74eyBZbU7Y1tM16NjkrN11u6y6OGoZm4f6', 'user', 1),
(8, 'manjil', '$2y$10$31Qel26FyI0d5GO5gMv7wehXZwHi.P293Blx9r6otg6JfmfLZUWIS', 'user', 0),
(9, 'DP', '$2y$10$3o7N76NwJqymzg.ZvhwhK.AAfobtflCcjhzXoD1IoOfkhxnB/sul.', 'user', 0),
(10, 'UJ', '$2y$10$odivv6wG6n9vS9IZNB3vUuxACvGz0TafS/B78gW5d3KlRsObRUb2O', 'user', 0),
(11, 'Arbin', '$2y$10$fODXIf.fEMszqopRW2Z0jeuRqmqfkbocJo7vStMiHzvYwt1O3GbTW', 'user', 0);

-- --------------------------------------------------------

--
-- Table structure for table `competition_settings`
--

DROP TABLE IF EXISTS `competition_settings`;
CREATE TABLE `competition_settings` (
  `id` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `status` enum('not_started','running','paused','finished') DEFAULT 'not_started',
  `start_time` datetime DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 20,
  `volunteer_can_mark` tinyint(1) DEFAULT 0,
  `end_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `competition_settings`
--

INSERT INTO `competition_settings` (`id`, `category`, `status`, `start_time`, `duration_minutes`, `volunteer_can_mark`, `end_time`) VALUES
(1, 'c_debug', 'finished', '2026-02-06 11:35:50', 20, 0, '2026-02-06 11:55:50');

-- --------------------------------------------------------

--
-- Table structure for table `c_debug_members`
--

DROP TABLE IF EXISTS `c_debug_members`;
CREATE TABLE `c_debug_members` (
  `id` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `team_member` varchar(100) DEFAULT NULL,
  `section` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `c_debug_members`
--

INSERT INTO `c_debug_members` (`id`, `team_id`, `team_member`, `section`) VALUES
(5, 2, 'Kritan Prajapati', 'E2'),
(6, 2, 'Shubham Khadka', 'E2'),
(7, 2, 'Rijan Lama', 'E2'),
(8, 2, 'Jisan Karki', 'E2'),
(12, 4, 'Rabin Napit', 'P2'),
(13, 4, 'Devraj Dahal', 'P2'),
(14, 4, 'Prajit Shrestha', 'P2'),
(15, 4, 'Sushant Timalsina', 'P2'),
(16, 5, 'Prabesh Shrestha', 'P2'),
(17, 5, 'Arbin Satyal', 'P3'),
(18, 5, 'Shubham Pradhananga', 'P3'),
(19, 5, 'Nikesh Pasachhe', 'P2'),
(24, 7, 'Ayush Sainju', 'E3'),
(25, 7, 'Uttam Dhakal', 'E3'),
(26, 7, 'Ronish Lamichhane', 'E3'),
(27, 7, 'Swopnil Shrestha', 'E3'),
(36, 10, 'Deep Darshan Shrestha', 'N5'),
(37, 10, 'Sonam Tamang', 'N5'),
(38, 10, 'Sworup Thapa', 'E2'),
(39, 11, 'Royan Baidar', 'P2'),
(40, 11, 'Suman Neupane', 'P2'),
(41, 11, 'Rohit Satyal', 'P2'),
(42, 11, 'Nis Manandhar', 'P2'),
(43, 12, 'Aryan Danuwar', 'M4'),
(44, 12, 'Aayush Bhujel', 'M4'),
(45, 12, 'Oshish Shahi', 'M4'),
(46, 13, 'Prayush Shrestha', 'P3'),
(47, 13, 'Krishna Neupane', 'P2'),
(48, 13, 'Prashil Baidya', 'P3'),
(49, 13, 'Samarpan Humagain', 'P2'),
(53, 15, 'Prabesh KC', 'E2'),
(54, 15, 'Prerak Bogati', 'E2'),
(55, 15, 'Mohit Manandhar', 'E2'),
(56, 15, 'Ishan Manandhar', 'E2'),
(61, 17, 'Avishek Banjara', 'P2'),
(62, 17, 'Ishant KC', 'P2'),
(63, 17, 'Bibek Kharel', 'P2'),
(64, 17, 'Prayush Ranjit', 'P2'),
(65, 18, 'Prashun Parajuli', 'P2'),
(66, 18, 'Aayush KC', 'P2'),
(67, 18, 'Subigya Dahal', 'P2'),
(68, 18, 'Saphal Sapkota', 'P2'),
(69, 19, 'Rasin Shrestha', '-'),
(70, 19, 'Saugat Ghimire', '-'),
(71, 19, 'Sushasan Gautam', '-'),
(124, 8, 'Niraj Ghimire', 'E2'),
(125, 8, 'Saphin That', 'E2'),
(126, 8, 'Sizal Sharma', 'E2'),
(127, 8, 'Umang Thapa', 'E2'),
(141, 1, 'Dipesh Karki', 'E3'),
(142, 1, 'Roshan Sapkota', 'E3'),
(143, 1, 'Anjan Bajagain', 'E3'),
(144, 1, 'Swapnil Dahal', 'E3'),
(145, 20, 'Yash Gupta', 'E2'),
(146, 20, 'Sudip Tamang', 'E2'),
(147, 20, 'Siddhant Shyanyan', 'E2'),
(148, 20, 'Bidhan Dahal', 'E2'),
(149, 21, 'Saimon Shrestha', 'E3'),
(150, 21, 'Ishan Sainju', 'E3'),
(154, 25, 'Janish Thapa', 'P3'),
(155, 25, 'Ashrin Tamang', 'P3'),
(156, 25, 'Shishir Khadka', 'P3'),
(157, 25, 'Pasang Lama', 'P3'),
(158, 6, 'Bidhan Aryal', 'P1'),
(159, 6, 'Sanjay Thapa', 'P1'),
(160, 6, 'Pukar Parajuli', 'P1'),
(165, 9, 'Bijesh Bhochibhoya', 'M4'),
(166, 9, 'Sayub Shakya', 'M4'),
(167, 9, 'Sayash Shrestha', 'M4'),
(168, 9, 'Karan Bhujel', 'M4'),
(169, 31, 'Sushant Khatri', 'P2'),
(170, 31, 'Kriman Basnet', 'P3'),
(171, 31, 'Manit Dangal', 'P1'),
(172, 23, 'Arohi Gautam', 'N2'),
(173, 23, 'Hringsal Tamang', 'N2'),
(174, 23, 'Riya Rogmi', 'N2'),
(175, 23, 'Dipika Sapkota', 'N5'),
(176, 24, 'Manisha Dhungel', 'P2'),
(177, 24, 'Samrachana Dahal', 'P3'),
(178, 24, 'Shital Rajbhandari', 'P2'),
(179, 24, 'Kritika Banjara', 'P1'),
(180, 16, 'Aryan Maske', 'E2'),
(181, 16, 'Genuine Dhungana', 'E2'),
(182, 16, 'Prajil Manandhar', 'E2'),
(183, 16, 'Upakar Pyakurel', 'E2');

-- --------------------------------------------------------

--
-- Table structure for table `c_debug_teams`
--

DROP TABLE IF EXISTS `c_debug_teams`;
CREATE TABLE `c_debug_teams` (
  `id` int(11) NOT NULL,
  `team_name` varchar(100) DEFAULT NULL,
  `laptop` enum('Yes','No') DEFAULT NULL,
  `easy_solved` int(11) DEFAULT 0,
  `intermediate_solved` int(11) DEFAULT 0,
  `hard_solved` int(11) DEFAULT 0,
  `marks` float DEFAULT 0,
  `scored_by_id` int(11) DEFAULT NULL,
  `timer_status` enum('not_started','running','stopped') DEFAULT 'not_started',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `stopped_by_id` int(11) DEFAULT NULL,
  `attendance` tinyint(1) DEFAULT 0,
  `attendance_updated_by_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `c_debug_teams`
--

INSERT INTO `c_debug_teams` (`id`, `team_name`, `laptop`, `easy_solved`, `intermediate_solved`, `hard_solved`, `marks`, `scored_by_id`, `timer_status`, `start_time`, `end_time`, `stopped_by_id`, `attendance`, `attendance_updated_by_id`) VALUES
(1, 'Team 1X', 'No', 4, 4, 0, 16, 3, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:54:14', 8, 1, 5),
(2, 'Team Action', 'No', 5, 2, 0, 11, 2, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:55:50', NULL, 1, 5),
(4, 'Team Alpha', 'No', 3, 6, 2, 31, 2, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:51:54', 9, 1, 9),
(5, 'Team Argyle', 'No', 5, 7, 1, 31, 2, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:54:59', 9, 1, 9),
(6, 'Team Aura', 'Yes', 0, 0, 0, 0, 1, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:55:50', NULL, 1, 9),
(7, 'Team Aurs', 'No', 4, 6, 0, 22, 2, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:55:50', NULL, 1, 5),
(8, 'Team Back boy!!!', 'No', 3, 6, 0, 21, 7, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:54:47', 3, 1, 7),
(9, 'Team Pluto', 'Yes', 3, 3, 0, 12, 2, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:51:39', 2, 1, 2),
(10, 'Team D Coder S', 'Yes', 3, 3, 0, 12, 2, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:53:32', 11, 1, 1),
(11, 'Team Blunder', 'Yes', 0, 2, 1, 11, 7, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:52:25', 2, 1, 1),
(12, 'Team Caso', 'Yes', 1, 8, 3, 40, 2, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:55:50', NULL, 1, 1),
(13, 'Team Chulo', 'Yes', 5, 5, 0, 20, 2, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:52:11', 2, 1, 1),
(15, 'Team Genz', 'No', 3, 5, 1, 23, 2, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:51:44', 11, 1, 1),
(16, 'Team Hello world', 'No', 3, 2, 0, 9, 2, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:50:25', 11, 1, 1),
(17, 'Team Kali Yug Hunters', 'Yes', 0, 0, 0, 0, 1, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:55:37', 4, 1, 1),
(18, 'Team Nonchalant Sepal', 'No', 0, 0, 0, 0, 1, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:54:18', 8, 1, 9),
(19, 'Team Pirates', 'No', 4, 2, 2, 20, 3, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:55:50', NULL, 1, 9),
(20, 'Team SuperNova', 'No', 2, 4, 0, 14, 2, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:55:50', NULL, 1, 5),
(21, 'Team Warrior alpha', 'Yes', 4, 5, 3, 34, 2, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:54:19', 11, 1, 1),
(23, 'Team Yahoo!', 'No', 2, 1, 0, 5, 3, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:55:50', NULL, 1, 9),
(24, 'Team SSMK', 'Yes', 5, 4, 1, 22, 3, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:53:43', 10, 1, 2),
(25, 'Team JASP', 'Yes', 4, 6, 3, 37, 3, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:55:43', 10, 1, 1),
(31, 'Team Potato', 'Yes', 5, 6, 1, 28, 7, 'stopped', '2026-02-06 11:35:50', '2026-02-06 11:55:33', 4, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `uiux_members`
--

DROP TABLE IF EXISTS `uiux_members`;
CREATE TABLE `uiux_members` (
  `id` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `member_name` varchar(100) DEFAULT NULL,
  `section` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `uiux_members`
--

INSERT INTO `uiux_members` (`id`, `team_id`, `member_name`, `section`) VALUES
(9, 3, 'Avishek Banjara', 'P2'),
(10, 3, 'Ishant KC', 'P2'),
(11, 3, 'Prayush Ranjit', 'P2'),
(12, 3, 'Bibek Kharel', 'P2'),
(13, 4, 'Oshish Shahi', 'M4'),
(14, 4, 'Aayush Bhujel', 'M4'),
(15, 4, 'Aryan Danuwar', 'M4'),
(16, 5, 'Prerak Bogati', 'E2'),
(17, 5, 'Sworup Thapa', 'E2'),
(18, 5, 'Mohit Manandhar', 'E2'),
(19, 5, 'Prabhesh KC', 'E2'),
(24, 7, 'Saimon Shrestha', 'E3'),
(25, 7, 'Ishan Sanju', 'E3'),
(26, 8, 'Aryan Maske', 'E2'),
(27, 8, 'Aananda Humagain', 'E2'),
(28, 8, 'Prajil Manandhar', 'E2'),
(29, 8, 'Upakar Pyakurel', 'E2'),
(36, 11, 'Sanskar Ghimire', 'P2'),
(37, 11, 'Manjil Timalsina', 'P3'),
(55, 10, 'Harman Bhuju', 'P2'),
(56, 10, 'Harshit Bhuju', 'P2'),
(61, 13, 'Manisha Dhungel', 'P2'),
(62, 13, 'Shital Rajbhandari', 'P2'),
(63, 13, 'Samrachana Dahal', 'P3'),
(64, 13, 'Kritika Banjara ', 'P1'),
(69, 1, 'Prayush Shrestha', 'P3'),
(70, 1, 'Prashil Baidhya', 'P3'),
(71, 1, 'Krishna Neupane', 'P2'),
(72, 1, 'Samarpan Humagain', 'P2'),
(78, 16, 'Sushant Khatri', 'P2'),
(79, 16, 'Kriman Basnet', 'P3'),
(80, 16, 'Manit Dangal', 'P1'),
(83, 14, 'Grishan Humaghain', 'N5'),
(84, 14, 'Deep Darshan Shrestha ', 'N5'),
(85, 14, 'Ishan Sapkota', 'N4'),
(86, 14, 'Sonam Tamang', 'N5'),
(91, 12, 'Nis Manandhar', 'P2'),
(92, 12, 'Royan Baidar', 'P2'),
(93, 12, 'Rohit Satyal', 'P2'),
(94, 12, 'Suman Neupane', 'P2');

-- --------------------------------------------------------

--
-- Table structure for table `uiux_teams`
--

DROP TABLE IF EXISTS `uiux_teams`;
CREATE TABLE `uiux_teams` (
  `id` int(11) NOT NULL,
  `team_name` varchar(100) NOT NULL,
  `payment` enum('paid','not_paid') DEFAULT 'not_paid',
  `attendance` tinyint(1) DEFAULT 0,
  `attendance_updated_by_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `uiux_teams`
--

INSERT INTO `uiux_teams` (`id`, `team_name`, `payment`, `attendance`, `attendance_updated_by_id`) VALUES
(1, 'Team Chulo', 'paid', 1, 7),
(3, 'Team Kali Yug Hunters', 'paid', 1, 7),
(4, 'Team Caso', 'paid', 1, 7),
(5, 'Team Good Boys', 'paid', 1, 3),
(7, 'Team Warrior Alpha', 'paid', 1, 7),
(8, 'Team Cyber World', 'paid', 1, 7),
(10, 'Team Agile', 'paid', 1, 3),
(11, 'Team Tentacles', 'paid', 1, 3),
(12, 'Team Not Found', 'paid', 1, 3),
(13, 'Team SSMK', 'paid', 1, 7),
(14, 'Team The Figmans', 'paid', 1, 3),
(16, 'Team Potato', 'paid', 1, 7);

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_assignments`
--

DROP TABLE IF EXISTS `volunteer_assignments`;
CREATE TABLE `volunteer_assignments` (
  `id` int(11) NOT NULL,
  `volunteer_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `status` enum('pending','accepted') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `volunteer_assignments`
--

INSERT INTO `volunteer_assignments` (`id`, `volunteer_id`, `team_id`, `status`, `created_at`) VALUES
(56, 9, 4, 'accepted', '2026-02-06 10:22:53'),
(57, 9, 5, 'accepted', '2026-02-06 10:23:03'),
(59, 8, 19, 'accepted', '2026-02-06 10:23:12'),
(60, 8, 1, 'accepted', '2026-02-06 10:23:16'),
(61, 11, 15, 'accepted', '2026-02-06 10:23:32'),
(62, 11, 10, 'accepted', '2026-02-06 10:23:46'),
(63, 11, 16, 'accepted', '2026-02-06 10:23:54'),
(64, 8, 6, 'accepted', '2026-02-06 10:25:18'),
(65, 8, 18, 'accepted', '2026-02-06 10:25:35'),
(67, 8, 23, 'accepted', '2026-02-06 10:27:14'),
(68, 3, 2, 'accepted', '2026-02-06 10:29:22'),
(69, 3, 8, 'accepted', '2026-02-06 10:29:25'),
(70, 3, 20, 'accepted', '2026-02-06 10:29:37'),
(71, 10, 25, 'accepted', '2026-02-06 10:30:03'),
(72, 10, 24, 'accepted', '2026-02-06 10:30:21'),
(74, 10, 12, 'accepted', '2026-02-06 10:30:35'),
(75, 2, 9, 'accepted', '2026-02-06 10:30:53'),
(77, 2, 11, 'accepted', '2026-02-06 10:31:01'),
(78, 2, 13, 'accepted', '2026-02-06 10:31:11'),
(79, 4, 17, 'accepted', '2026-02-06 10:31:35'),
(80, 11, 21, 'accepted', '2026-02-06 10:32:26'),
(81, 4, 31, 'accepted', '2026-02-06 10:32:34'),
(82, 7, 7, 'accepted', '2026-02-06 10:33:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `competition_settings`
--
ALTER TABLE `competition_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category` (`category`);

--
-- Indexes for table `c_debug_members`
--
ALTER TABLE `c_debug_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `c_debug_teams`
--
ALTER TABLE `c_debug_teams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `uiux_members`
--
ALTER TABLE `uiux_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `uiux_teams`
--
ALTER TABLE `uiux_teams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `volunteer_assignments`
--
ALTER TABLE `volunteer_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `volunteer_id` (`volunteer_id`),
  ADD KEY `team_id` (`team_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `competition_settings`
--
ALTER TABLE `competition_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `c_debug_members`
--
ALTER TABLE `c_debug_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT for table `c_debug_teams`
--
ALTER TABLE `c_debug_teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `uiux_members`
--
ALTER TABLE `uiux_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `uiux_teams`
--
ALTER TABLE `uiux_teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `volunteer_assignments`
--
ALTER TABLE `volunteer_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `c_debug_members`
--
ALTER TABLE `c_debug_members`
  ADD CONSTRAINT `c_debug_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `c_debug_teams` (`id`);

--
-- Constraints for table `uiux_members`
--
ALTER TABLE `uiux_members`
  ADD CONSTRAINT `uiux_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `uiux_teams` (`id`);

--
-- Constraints for table `volunteer_assignments`
--
ALTER TABLE `volunteer_assignments`
  ADD CONSTRAINT `volunteer_assignments_ibfk_1` FOREIGN KEY (`volunteer_id`) REFERENCES `admin` (`id`),
  ADD CONSTRAINT `volunteer_assignments_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `c_debug_teams` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
