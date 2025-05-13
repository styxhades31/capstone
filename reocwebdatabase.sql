-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 22, 2024 at 07:18 PM
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
-- Database: `reocwebdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `application_status`
--

CREATE TABLE `application_status` (
  `id` int(11) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_status`
--

INSERT INTO `application_status` (`id`, `status`) VALUES
(1, 'open');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `researcher_title_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assign_reviewer`
--

CREATE TABLE `assign_reviewer` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `researcher_info_id` int(11) NOT NULL,
  `status` enum('Ongoing','Complete') NOT NULL DEFAULT 'Ongoing'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificate_generated`
--

CREATE TABLE `certificate_generated` (
  `id` int(11) NOT NULL,
  `rti_id` int(11) NOT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) NOT NULL DEFAULT 'PDF',
  `status` enum('Hide','Show') NOT NULL DEFAULT 'Hide'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificate_generatednouser`
--

CREATE TABLE `certificate_generatednouser` (
  `id` int(11) NOT NULL,
  `rti_id` int(11) NOT NULL,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) NOT NULL DEFAULT 'PDF',
  `status` enum('Hide','Show') NOT NULL DEFAULT 'Hide'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `colleges`
--

CREATE TABLE `colleges` (
  `id` int(11) NOT NULL,
  `college_name_and_color` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `colleges`
--

INSERT INTO `colleges` (`id`, `college_name_and_color`) VALUES
(1, 'College of Agriculture - Green'),
(2, 'College of Architecture - Maroon'),
(3, 'College of Asian and Islamic Studies - Violet'),
(4, 'College of Computing Studies - Maroon'),
(5, 'College of Criminal Justice Education - Black'),
(6, 'College of Engineering - Yellow'),
(7, 'College of Forestry and Environmental Studies - Green'),
(8, 'College of Home Economics - Violet'),
(9, 'College of Law - Black'),
(10, 'College of Liberal Arts - Red'),
(11, 'College of Medicine - Pink'),
(12, 'College of Nursing - Pink'),
(13, 'College of Public Administration and Development Studies - Orange'),
(14, 'College of Science and Mathematics - Orange'),
(15, 'College of Social Work and Community Development - Orange'),
(16, 'College of Sports Science and Physical Education - Red'),
(17, 'College of Teacher Education - Blue'),
(18, 'External Studies Unit - Brown'),
(19, 'Institutionally Funded Research - Brown'),
(20, 'Other Institution - Brown');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_members`
--

CREATE TABLE `faculty_members` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_members`
--

INSERT INTO `faculty_members` (`id`, `name`, `picture`, `created_at`, `updated_at`) VALUES
(1, '', 'faculty_1_1734012011_faculty_1_1733071905_Faculty.jpeg', '2024-12-12 14:00:11', '2024-12-12 14:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `notavail_appointment`
--

CREATE TABLE `notavail_appointment` (
  `id` int(11) NOT NULL,
  `unavailable_date` date NOT NULL,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notavail_appointment`
--

INSERT INTO `notavail_appointment` (`id`, `unavailable_date`, `added_at`) VALUES
(17, '2024-12-22', '2024-12-22 22:13:06');

-- --------------------------------------------------------

--
-- Table structure for table `reoc_dynamic_data`
--

CREATE TABLE `reoc_dynamic_data` (
  `id` int(11) NOT NULL,
  `certificate_version` varchar(255) NOT NULL,
  `date_effective` varchar(255) NOT NULL,
  `let_code` varchar(255) NOT NULL,
  `Signature` varchar(255) DEFAULT NULL,
  `appointment_capacity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reoc_dynamic_data`
--

INSERT INTO `reoc_dynamic_data` (`id`, `certificate_version`, `date_effective`, `let_code`, `Signature`, `appointment_capacity`) VALUES
(1, '001.2', '27-Nov-2024', '006.02', 'Mysteffa C. Hajal', 1);

-- --------------------------------------------------------

--
-- Table structure for table `researcherinvolved_nouser`
--

CREATE TABLE `researcherinvolved_nouser` (
  `id` int(11) NOT NULL,
  `researcher_title_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `middle_initial` varchar(2) DEFAULT NULL,
  `suffix` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `researchertitleinfo_nouser`
--

CREATE TABLE `researchertitleinfo_nouser` (
  `id` int(11) NOT NULL,
  `study_protocol_title` varchar(255) DEFAULT NULL,
  `college` varchar(100) DEFAULT NULL,
  `research_category` varchar(100) DEFAULT NULL,
  `adviser_name` varchar(255) DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `type_of_review` varchar(100) DEFAULT 'For Initial Review',
  `payment` varchar(100) DEFAULT 'None'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `researcher_files`
--

CREATE TABLE `researcher_files` (
  `id` int(11) NOT NULL,
  `researcher_title_id` int(11) DEFAULT NULL,
  `file_type` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `researcher_involved`
--

CREATE TABLE `researcher_involved` (
  `id` int(11) NOT NULL,
  `researcher_title_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `middle_initial` varchar(2) DEFAULT NULL,
  `suffix` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `researcher_profiles`
--

CREATE TABLE `researcher_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mobile_number` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `researcher_title_informations`
--

CREATE TABLE `researcher_title_informations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `study_protocol_title` varchar(255) DEFAULT NULL,
  `college` varchar(100) DEFAULT NULL,
  `research_category` varchar(100) DEFAULT NULL,
  `adviser_name` varchar(255) DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `type_of_review` varchar(100) DEFAULT 'For Initial Review',
  `payment` varchar(100) DEFAULT 'None',
  `Revision_document` varchar(255) DEFAULT NULL,
  `Revised_document` varchar(255) DEFAULT NULL,
  `Revision_status` varchar(255) DEFAULT 'None',
  `Revision_Upload_button` varchar(5) DEFAULT 'None',
  `new_date_column` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_codes`
--

CREATE TABLE `research_codes` (
  `id` int(11) NOT NULL,
  `code_acronym` varchar(10) NOT NULL,
  `code_number` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `research_codes`
--

INSERT INTO `research_codes` (`id`, `code_acronym`, `code_number`) VALUES
(1, 'UG', 56),
(2, 'GS', 2),
(3, 'IF', 3),
(4, 'EF', 4);

-- --------------------------------------------------------

--
-- Table structure for table `reviewer_profiles`
--

CREATE TABLE `reviewer_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_initial` char(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `revised_document_history`
--

CREATE TABLE `revised_document_history` (
  `id` int(11) NOT NULL,
  `researcher_info_id` int(11) NOT NULL,
  `old_revised_document` varchar(255) NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'Admin'),
(2, 'Reviewer'),
(3, 'Researcher');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`id`, `name`, `picture`, `created_at`, `updated_at`) VALUES
(1, '', 'schedule_1_1734012022_schedule_1_1733071934_Schedule.jpeg', '2024-12-12 14:00:22', '2024-12-12 14:00:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `isActive` int(1) NOT NULL DEFAULT 1,
  `verification_code` char(6) DEFAULT NULL,
  `temporaryemailholder` varchar(255) DEFAULT NULL,
  `status` enum('Free','Occupied') NOT NULL DEFAULT 'Free',
  `number_of_reviews` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_unavailable_dates`
--

CREATE TABLE `user_unavailable_dates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `unavailable_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vision_mission`
--

CREATE TABLE `vision_mission` (
  `id` int(11) NOT NULL,
  `statement_type` enum('Vision','Mission','Goals') NOT NULL,
  `content` text NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vision_mission`
--

INSERT INTO `vision_mission` (`id`, `statement_type`, `content`, `last_updated`) VALUES
(1, 'Vision', 'The Western Mindanao State University Research Ethics Oversight Committee(WMSU REOC) / College Research Ethics Committee (CERC) is an accredited board instituted to conduct ethics review in various fields of researches that involve human participants and animal subjects in the University and the region.', '2024-12-12 14:01:01'),
(2, 'Mission', 'WMSU-REOC/CERC safeguards the general welfare of human participants and animal subjects in the conduct of researches.', '2024-12-12 14:01:01'),
(3, 'Goals', 'WMSU-REOC attempts to achieve the following goals:\r\n\r\n1. Conduct a quality and standard ethical review process for all researches in order to safeguard the rights and welfare of participants in any research.\r\n\r\n2. Establish and maintain a pool of professional multidisciplinary reviewers to do expedited and full review procedure.\r\n\r\n3. Ensure compliance to ethical standards in the implementation of research protocols.', '2024-12-12 14:01:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `application_status`
--
ALTER TABLE `application_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `researcher_title_id` (`researcher_title_id`);

--
-- Indexes for table `assign_reviewer`
--
ALTER TABLE `assign_reviewer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `certificate_generated`
--
ALTER TABLE `certificate_generated`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rti_id` (`rti_id`);

--
-- Indexes for table `certificate_generatednouser`
--
ALTER TABLE `certificate_generatednouser`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rti_id` (`rti_id`);

--
-- Indexes for table `colleges`
--
ALTER TABLE `colleges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `faculty_members`
--
ALTER TABLE `faculty_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notavail_appointment`
--
ALTER TABLE `notavail_appointment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reoc_dynamic_data`
--
ALTER TABLE `reoc_dynamic_data`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `researcherinvolved_nouser`
--
ALTER TABLE `researcherinvolved_nouser`
  ADD PRIMARY KEY (`id`),
  ADD KEY `researcher_title_id` (`researcher_title_id`);

--
-- Indexes for table `researchertitleinfo_nouser`
--
ALTER TABLE `researchertitleinfo_nouser`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `researcher_files`
--
ALTER TABLE `researcher_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `researcher_title_id` (`researcher_title_id`);

--
-- Indexes for table `researcher_involved`
--
ALTER TABLE `researcher_involved`
  ADD PRIMARY KEY (`id`),
  ADD KEY `researcher_title_id` (`researcher_title_id`);

--
-- Indexes for table `researcher_profiles`
--
ALTER TABLE `researcher_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `researcher_title_informations`
--
ALTER TABLE `researcher_title_informations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `research_codes`
--
ALTER TABLE `research_codes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviewer_profiles`
--
ALTER TABLE `reviewer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `revised_document_history`
--
ALTER TABLE `revised_document_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `researcher_info_id` (`researcher_info_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `user_unavailable_dates`
--
ALTER TABLE `user_unavailable_dates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `vision_mission`
--
ALTER TABLE `vision_mission`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `application_status`
--
ALTER TABLE `application_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `assign_reviewer`
--
ALTER TABLE `assign_reviewer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `certificate_generated`
--
ALTER TABLE `certificate_generated`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `certificate_generatednouser`
--
ALTER TABLE `certificate_generatednouser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `colleges`
--
ALTER TABLE `colleges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `faculty_members`
--
ALTER TABLE `faculty_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notavail_appointment`
--
ALTER TABLE `notavail_appointment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `reoc_dynamic_data`
--
ALTER TABLE `reoc_dynamic_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `researcherinvolved_nouser`
--
ALTER TABLE `researcherinvolved_nouser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `researchertitleinfo_nouser`
--
ALTER TABLE `researchertitleinfo_nouser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `researcher_files`
--
ALTER TABLE `researcher_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `researcher_involved`
--
ALTER TABLE `researcher_involved`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `researcher_profiles`
--
ALTER TABLE `researcher_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `researcher_title_informations`
--
ALTER TABLE `researcher_title_informations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `research_codes`
--
ALTER TABLE `research_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reviewer_profiles`
--
ALTER TABLE `reviewer_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `revised_document_history`
--
ALTER TABLE `revised_document_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `user_unavailable_dates`
--
ALTER TABLE `user_unavailable_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vision_mission`
--
ALTER TABLE `vision_mission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`researcher_title_id`) REFERENCES `researcher_title_informations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `certificate_generated`
--
ALTER TABLE `certificate_generated`
  ADD CONSTRAINT `certificate_generated_ibfk_1` FOREIGN KEY (`rti_id`) REFERENCES `researcher_title_informations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `certificate_generatednouser`
--
ALTER TABLE `certificate_generatednouser`
  ADD CONSTRAINT `certificate_generatednouser_ibfk_1` FOREIGN KEY (`rti_id`) REFERENCES `researchertitleinfo_nouser` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `researcherinvolved_nouser`
--
ALTER TABLE `researcherinvolved_nouser`
  ADD CONSTRAINT `researcherinvolved_nouser_ibfk_1` FOREIGN KEY (`researcher_title_id`) REFERENCES `researchertitleinfo_nouser` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `researcher_files`
--
ALTER TABLE `researcher_files`
  ADD CONSTRAINT `researcher_files_ibfk_1` FOREIGN KEY (`researcher_title_id`) REFERENCES `researcher_title_informations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `researcher_involved`
--
ALTER TABLE `researcher_involved`
  ADD CONSTRAINT `researcher_involved_ibfk_1` FOREIGN KEY (`researcher_title_id`) REFERENCES `researcher_title_informations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `researcher_profiles`
--
ALTER TABLE `researcher_profiles`
  ADD CONSTRAINT `researcher_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `researcher_title_informations`
--
ALTER TABLE `researcher_title_informations`
  ADD CONSTRAINT `researcher_title_informations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviewer_profiles`
--
ALTER TABLE `reviewer_profiles`
  ADD CONSTRAINT `reviewer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `revised_document_history`
--
ALTER TABLE `revised_document_history`
  ADD CONSTRAINT `revised_document_history_ibfk_1` FOREIGN KEY (`researcher_info_id`) REFERENCES `researcher_title_informations` (`id`);

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `user_unavailable_dates`
--
ALTER TABLE `user_unavailable_dates`
  ADD CONSTRAINT `user_unavailable_dates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
