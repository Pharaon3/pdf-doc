-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 18, 2025 at 10:19 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `test-database`
--

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `content_id` int NOT NULL,
  `section_id` int NOT NULL,
  `content_type` enum('text','blank','table','image') NOT NULL,
  `content_detail` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document`
--

CREATE TABLE `document` (
  `document_id` int NOT NULL,
  `document_title` varchar(255) NOT NULL,
  `logo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `document`
--

INSERT INTO `document` (`document_id`, `document_title`, `logo`) VALUES
(3, 'test Title1', ''),
(4, 'test Title1', ''),
(5, 'test Title1', ''),
(6, 'test Title1', 'uploads/lovely_star.jpg'),
(7, 'test Title1', 'uploads/logo.png'),
(8, 'test Title1', 'uploads/logo.png'),
(9, 'This is the title', 'uploads/lovely_star.jpg'),
(10, 'test Title1', 'uploads/Lovely_Logo.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `imagecontent`
--

CREATE TABLE `imagecontent` (
  `image_id` int NOT NULL,
  `section_id` int NOT NULL,
  `image_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `section_id` int NOT NULL,
  `document_id` int NOT NULL,
  `section_name` varchar(255) NOT NULL,
  `content` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`section_id`, `document_id`, `section_name`, `content`) VALUES
(1, 3, 'test section title 1', NULL),
(2, 4, 'test section title 1', NULL),
(3, 5, 'test section title 1', NULL),
(4, 6, 'test section title 1', NULL),
(5, 7, 'test section title 1', NULL),
(6, 8, 'test section title 1', NULL),
(7, 9, 'section title 1', NULL),
(8, 9, 'test section title 2', NULL),
(9, 10, 'test section title 1', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tablecontent`
--

CREATE TABLE `tablecontent` (
  `table_id` int NOT NULL,
  `section_id` int NOT NULL,
  `table_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `textcontent`
--

CREATE TABLE `textcontent` (
  `text_id` int NOT NULL,
  `section_id` int NOT NULL,
  `text_detail` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`content_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `document`
--
ALTER TABLE `document`
  ADD PRIMARY KEY (`document_id`);

--
-- Indexes for table `imagecontent`
--
ALTER TABLE `imagecontent`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `section`
--
ALTER TABLE `section`
  ADD PRIMARY KEY (`section_id`),
  ADD KEY `document_id` (`document_id`);

--
-- Indexes for table `tablecontent`
--
ALTER TABLE `tablecontent`
  ADD PRIMARY KEY (`table_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `textcontent`
--
ALTER TABLE `textcontent`
  ADD PRIMARY KEY (`text_id`),
  ADD KEY `section_id` (`section_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `content_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document`
--
ALTER TABLE `document`
  MODIFY `document_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `imagecontent`
--
ALTER TABLE `imagecontent`
  MODIFY `image_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `section`
--
ALTER TABLE `section`
  MODIFY `section_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tablecontent`
--
ALTER TABLE `tablecontent`
  MODIFY `table_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `textcontent`
--
ALTER TABLE `textcontent`
  MODIFY `text_id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `content`
--
ALTER TABLE `content`
  ADD CONSTRAINT `content_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`) ON DELETE CASCADE;

--
-- Constraints for table `imagecontent`
--
ALTER TABLE `imagecontent`
  ADD CONSTRAINT `imagecontent_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`) ON DELETE CASCADE;

--
-- Constraints for table `section`
--
ALTER TABLE `section`
  ADD CONSTRAINT `section_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `document` (`document_id`) ON DELETE CASCADE;

--
-- Constraints for table `tablecontent`
--
ALTER TABLE `tablecontent`
  ADD CONSTRAINT `tablecontent_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`) ON DELETE CASCADE;

--
-- Constraints for table `textcontent`
--
ALTER TABLE `textcontent`
  ADD CONSTRAINT `textcontent_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
