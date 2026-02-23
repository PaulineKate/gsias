-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 23, 2026 at 09:27 AM
-- Server version: 12.2.2-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gsias_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_creds`
--

CREATE TABLE `admin_creds` (
  `admin_name` varchar(25) NOT NULL,
  `admin_user` varchar(20) NOT NULL,
  `admin_pass` varchar(255) NOT NULL,
  `admin_image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `admin_creds`
--

INSERT INTO `admin_creds` (`admin_name`, `admin_user`, `admin_pass`, `admin_image`) VALUES
('Arden Clyde De Ramos', 'arden123', '$2y$10$6Mh4CWi0FHI6HwQRKGxLGempJJdam3C5oUfS0DOrpi5xb206uuZc2', 'admin_699bfca6ad61d.JPG');

-- --------------------------------------------------------

--
-- Table structure for table `designation_list`
--

CREATE TABLE `designation_list` (
  `d_id` smallint(6) NOT NULL,
  `d_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `designation_list`
--

INSERT INTO `designation_list` (`d_id`, `d_name`) VALUES
(1, 'UTILITY WORKER'),
(2, 'ELECTRICIAN'),
(3, 'PLUMBER'),
(4, 'PAINTER'),
(5, 'TECHNICIAN'),
(6, 'PROJECT AIDE'),
(7, 'WELDER'),
(8, 'CARPENTER'),
(9, 'LABORER'),
(10, 'SOUND SYSTEM OPERATOR'),
(11, 'ENCODER'),
(12, 'ADMIN AIDE'),
(13, 'MASON'),
(15, 'IT');

-- --------------------------------------------------------

--
-- Table structure for table `funding_charges_list`
--

CREATE TABLE `funding_charges_list` (
  `fc_id` tinyint(4) NOT NULL,
  `fc_name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `funding_charges_list`
--

INSERT INTO `funding_charges_list` (`fc_id`, `fc_name`) VALUES
(1, 'GENERAL SERVICES'),
(2, 'JANITORIAL SERVICES');

-- --------------------------------------------------------

--
-- Table structure for table `jo_contracts`
--

CREATE TABLE `jo_contracts` (
  `jo_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `designation` varchar(30) NOT NULL,
  `rate` mediumint(6) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `funding_charges` varchar(30) NOT NULL,
  `ref_folder` varchar(100) NOT NULL,
  `ref_file` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `designation_list`
--
ALTER TABLE `designation_list`
  ADD PRIMARY KEY (`d_id`);

--
-- Indexes for table `funding_charges_list`
--
ALTER TABLE `funding_charges_list`
  ADD PRIMARY KEY (`fc_id`);

--
-- Indexes for table `jo_contracts`
--
ALTER TABLE `jo_contracts`
  ADD PRIMARY KEY (`jo_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `designation_list`
--
ALTER TABLE `designation_list`
  MODIFY `d_id` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `funding_charges_list`
--
ALTER TABLE `funding_charges_list`
  MODIFY `fc_id` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `jo_contracts`
--
ALTER TABLE `jo_contracts`
  MODIFY `jo_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
