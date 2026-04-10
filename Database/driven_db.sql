-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 07, 2026 at 02:20 PM
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
-- Database: `driven_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`admin_id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', 'admin@123', '2026-03-23 13:41:45');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `car_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `images` text NOT NULL,
  `car_name` varchar(255) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `down_payment` decimal(12,2) NOT NULL,
  `monthly` decimal(12,2) NOT NULL,
  `terms` varchar(50) NOT NULL,
  `mileage` int(11) NOT NULL,
  `included_heading` enum('Including:','Included:','Inclusion:') NOT NULL,
  `included_items` text NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_low_dp` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`car_id`, `category_id`, `images`, `car_name`, `price`, `down_payment`, `monthly`, `terms`, `mileage`, `included_heading`, `included_items`, `description`, `created_at`, `updated_at`, `is_low_dp`) VALUES
(1, 2, '[\"1774249415_Satisfied_customer_2.jpg\",\"1774249415_Satisfied_customer_3.jpg\",\"1774249415_Satisfied_customer_4.jpg\",\"1774249415_Satisfied_customer_5.jpg\",\"1774249415_Satisfied_customer_6.jpg\",\"1774249415_We_offer.png\"]', '2018 Nissan Juke AT Gas', 32100.00, 2000.00, 5.00, '34', 15000, 'Including:', '[\"wsdawd a\",\"dwaae\"]', 'Assad ad', '2026-03-23 15:03:35', '2026-03-23 16:09:30', 0),
(3, 2, '[\"1774250875_69c0eb7bc890a.jpg\"]', '2018 Mercedez Benz GLC250 Coupe AWD 2.0L Turbo AT Gas', 450000.00, 100000.00, 8000.00, '12', 120000, 'Including:', '[\"2313\"]', '321413', '2026-03-23 15:27:55', '2026-03-23 16:09:08', 1),
(4, 1, '[\"1774262835_69c11a33c1670.jpg\",\"1774262835_69c11a33c1a90.jpg\",\"1774262835_69c11a33c1bc0.jpg\"]', 'Toyota Vios', 350000.00, 100000.00, 5000.00, '12', 300000, 'Including:', '[\"Shesh \",\"das\",\"dsad\"]', 'Sges egs babbd', '2026-03-23 18:47:15', '2026-03-23 21:32:37', 1),
(5, 3, '[\"toyota_fortuner_1.jpg\",\"toyota_fortuner_2.jpg\"]', '2020 Toyota Fortuner 2.8L Diesel AT', 1850000.00, 250000.00, 28500.00, '60', 45000, 'Including:', '[\"1 Year Warranty\",\"Free PMS for 6 months\",\"LTO Registration\",\"Backup Camera\"]', 'Well-maintained SUV, perfect for family use with 7-seater capacity', '2026-03-20 09:00:00', '2026-03-20 09:00:00', 0),
(6, 5, '[\"bmw_x5_1.jpg\",\"bmw_x5_2.jpg\",\"bmw_x5_3.jpg\"]', '2022 BMW X5 xDrive40i', 5200000.00, 1000000.00, 95000.00, '48', 15000, 'Including:', '[\"Comprehensive Insurance\",\"Free 3 Years Maintenance\",\"Premium Floor Mats\",\"Sunroof\"]', 'Luxury SUV with M Sport package, panoramic roof, and premium leather seats', '2026-03-20 10:30:00', '2026-03-20 10:30:00', 0),
(7, 6, '[\"ford_ranger_1.jpg\",\"ford_ranger_2.jpg\"]', '2021 Ford Ranger Raptor 2.0L Bi-Turbo', 2100000.00, 300000.00, 32000.00, '60', 28000, 'Included:', '[\"Roller Lid\",\"Step Board\",\"Tint\",\"Bedliner\"]', 'Off-road ready pickup truck with Fox suspension and sport mode', '2026-03-20 11:15:00', '2026-03-20 11:15:00', 1),
(8, 1, '[\"honda_civic_1.jpg\",\"honda_civic_2.jpg\"]', '2023 Honda Civic RS Turbo', 1650000.00, 200000.00, 28000.00, '60', 5000, 'Including:', '[\"Honda Sensing Suite\",\"Leather Seats\",\"Apple CarPlay\",\"Android Auto\"]', 'Sporty sedan with turbo engine, 18-inch wheels, and premium sound system', '2026-03-20 13:45:00', '2026-03-20 13:45:00', 0),
(9, 7, '[\"toyota_commuter_1.jpg\"]', '2019 Toyota Commuter Deluxe', 1250000.00, 150000.00, 19500.00, '48', 65000, 'Included:', '[\"15 Seater\",\"Aircon Unit\",\"Stereo System\",\"Backup Sensor\"]', 'Perfect for shuttle service, family outings, or business transport', '2026-03-20 14:30:00', '2026-03-20 14:30:00', 1),
(10, 4, '[\"mazda_cx5_1.jpg\",\"mazda_cx5_2.jpg\"]', '2022 Mazda CX-5 SkyActiv AWD', 1950000.00, 250000.00, 29500.00, '60', 18000, 'Including:', '[\"Bose Sound System\",\"Leather Seats\",\"360 Camera\",\"Heated Seats\"]', 'Elegant crossover with premium interior, KODO design, and smooth handling', '2026-03-21 09:00:00', '2026-03-21 09:00:00', 0),
(11, 2, '[\"hyundai_kona_1.jpg\"]', '2021 Hyundai Kona 1.6T GLS', 1250000.00, 150000.00, 19500.00, '48', 32000, 'Included:', '[\"Sunroof\",\"Keyless Entry\",\"Backup Camera\",\"Smartphone Connectivity\"]', 'Compact SUV with turbo engine, sporty design, and fuel efficient', '2026-03-21 10:15:00', '2026-03-21 10:15:00', 1),
(12, 8, '[\"isuzu_nhr_1.jpg\",\"isuzu_nhr_2.jpg\"]', '2020 Isuzu NHR 4x2 Cargo Truck', 980000.00, 100000.00, 16500.00, '60', 75000, 'Including:', '[\"Cargo Bed\",\"Toolbox\",\"Backup Alarm\",\"Roof Rack\"]', 'Light-duty commercial vehicle, ideal for deliveries and small business', '2026-03-21 11:30:00', '2026-03-21 11:30:00', 0),
(13, 5, '[\"mercedes_glc_1.jpg\",\"mercedes_glc_2.jpg\"]', '2021 Mercedes-Benz GLC 300 4MATIC', 3800000.00, 600000.00, 65000.00, '60', 25000, 'Including:', '[\"Burmester Sound\",\"Panoramic Roof\",\"Ambient Lighting\",\"MBUX System\"]', 'Luxury compact SUV with advanced safety features and elegant interior', '2026-03-21 13:00:00', '2026-03-21 13:00:00', 0),
(14, 3, '[\"mitsubishi_montero_1.jpg\",\"mitsubishi_montero_2.jpg\"]', '2020 Mitsubishi Montero Sport GT', 1650000.00, 200000.00, 27000.00, '60', 38000, 'Including:', '[\"8-Speed AT\",\"Super Select 4WD\",\"Apple CarPlay\",\"Leather Seats\"]', 'Powerful SUV with advanced safety features and superior off-road capability', '2026-03-21 14:45:00', '2026-03-21 14:45:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `display_order`, `created_at`) VALUES
(1, 'Sedan', 1, '2026-03-23 15:01:46'),
(2, 'Hatchback', 2, '2026-03-23 15:01:46'),
(3, 'SUV', 3, '2026-03-23 15:01:46'),
(4, 'Crossover', 4, '2026-03-23 15:01:46'),
(5, 'Luxury Cars', 5, '2026-03-23 15:01:46'),
(6, 'Pick-Up', 6, '2026-03-23 15:01:46'),
(7, 'Van', 7, '2026-03-23 15:01:46'),
(8, 'Commercial Vehicle', 8, '2026-03-23 15:01:46');

-- --------------------------------------------------------

--
-- Table structure for table `contact_inquiries`
--

CREATE TABLE `contact_inquiries` (
  `inquiry_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied','archived') DEFAULT 'new',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_inquiries`
--

INSERT INTO `contact_inquiries` (`inquiry_id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 'Patrick John S. Brucal', 'pat@gmail.com', '09977114098', 'Cars', 'w1231e', 'new', '2026-03-23 22:38:46'),
(2, 'Patrick John S. Brucal', 'pat@gmail.com', '09977114098', 'Cars', 'w1231e', 'read', '2026-03-23 22:40:16'),
(3, 'Juan Dela Cruz', 'juan.delacruz@gmail.com', '09171234567', 'Test Drive Inquiry', 'I would like to schedule a test drive for the Toyota Fortuner this weekend. Please let me know available slots.', 'new', '2026-03-20 09:15:00'),
(4, 'Maria Santos', 'maria.santos@yahoo.com', '09281234567', 'Financing Options', 'What are the financing options available for the Honda Civic? Do you offer zero down payment?', 'read', '2026-03-20 10:30:00'),
(5, 'Roberto Mendoza', 'roberto.mendoza@gmail.com', '09151234567', 'Trade-in Inquiry', 'I have a 2018 Mitsubishi Mirage that I want to trade in. How does the trade-in process work?', 'replied', '2026-03-20 13:45:00'),
(6, 'Catherine Reyes', 'catherine.reyes@outlook.com', '09361234567', 'Warranty Coverage', 'What warranty coverage do you provide for second-hand cars? Is there an option to extend?', 'new', '2026-03-21 08:00:00'),
(7, 'Michael Tan', 'michael.tan@gmail.com', '09181234567', 'Commercial Fleet', 'I need 3 units of vans for my transport business. Do you offer fleet discounts?', 'read', '2026-03-21 11:20:00'),
(8, 'Isabella Garcia', 'isabella.garcia@gmail.com', '09451234567', 'Price Negotiation', 'Is the price negotiable for the BMW X5? I am a serious buyer ready for down payment.', 'replied', '2026-03-21 14:30:00'),
(9, 'Fernando Lopez', 'fernando.lopez@yahoo.com', '09191234567', 'Car Maintenance', 'Do you have a service center for regular maintenance? How much is PMS cost?', 'new', '2026-03-22 09:45:00'),
(10, 'Patricia Fernandez', 'patricia.fernandez@gmail.com', '09271234567', 'Registration Process', 'What documents do I need for car registration? How long does it take?', 'read', '2026-03-22 13:15:00'),
(11, 'Ricardo Villanueva', 'ricardo.villanueva@outlook.com', '09381234567', 'Insurance Inquiry', 'Do you provide comprehensive insurance? Can I use my own insurance provider?', 'new', '2026-03-23 10:00:00'),
(12, 'Carmen Aguilar', 'carmen.aguilar@gmail.com', '09161234567', 'Test Drive Request', 'I am interested in the Mazda CX-5. Can I schedule a test drive this week?', 'read', '2026-03-23 15:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `transaction_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `payment_number` int(11) NOT NULL COMMENT 'Which payment number (1st, 2nd, 3rd, etc.)',
  `payment_amount` decimal(12,2) NOT NULL COMMENT 'Amount paid',
  `payment_date` datetime NOT NULL COMMENT 'Date and time of payment',
  `payment_method` enum('Cash','Bank Transfer','Check','GCash','PayMaya','Credit Card','Other') NOT NULL COMMENT 'Type of payment',
  `reference_number` varchar(100) DEFAULT NULL COMMENT 'Check number, transaction ID, or reference',
  `status` enum('paid','pending','overdue','partial') DEFAULT 'paid' COMMENT 'Payment status',
  `notes` text DEFAULT NULL COMMENT 'Additional notes about payment',
  `recorded_by` varchar(50) DEFAULT NULL COMMENT 'Admin who recorded the payment',
  `created_at` datetime DEFAULT current_timestamp(),
  `due_date` date NOT NULL COMMENT 'Expected payment date',
  `payment_period` int(11) DEFAULT NULL COMMENT 'Which month payment (1st, 2nd, etc.)',
  `days_late` int(11) DEFAULT 0,
  `penalty_fee` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_transactions`
--

INSERT INTO `payment_transactions` (`transaction_id`, `sale_id`, `payment_number`, `payment_amount`, `payment_date`, `payment_method`, `reference_number`, `status`, `notes`, `recorded_by`, `created_at`, `due_date`, `payment_period`, `days_late`, `penalty_fee`) VALUES
(3, 5, 1, 50000.00, '2026-03-24 00:00:00', 'Cash', '', 'paid', 'Down payment', 'admin', '2026-03-24 12:48:43', '0000-00-00', NULL, 0, 0.00),
(4, 5, 2, 8000.00, '2026-03-24 00:00:00', 'Cash', '', 'paid', '', 'admin', '2026-03-24 12:49:01', '0000-00-00', NULL, 0, 0.00),
(10, 10, 1, 28500.00, '2026-04-25 09:00:00', 'Bank Transfer', 'BPI-28500-001', 'paid', 'First monthly payment, auto-debit arrangement', 'admin', '2026-04-25 09:00:00', '0000-00-00', NULL, 0, 0.00),
(11, 10, 2, 28500.00, '2026-05-25 14:30:00', 'GCash', 'GCASH-28500-001', 'paid', 'On-time payment via GCash', 'admin', '2026-05-25 14:30:00', '0000-00-00', NULL, 0, 0.00),
(12, 11, 1, 28000.00, '2026-04-26 11:15:00', 'Cash', 'CASH-0426-001', 'paid', 'Cash payment at branch, receipt issued', 'admin', '2026-04-26 11:15:00', '0000-00-00', NULL, 0, 0.00),
(13, 12, 1, 19500.00, '2026-04-27 10:00:00', 'Bank Transfer', 'UB-19500-0427', 'paid', 'UnionBank transfer', 'admin', '2026-04-27 10:00:00', '0000-00-00', NULL, 0, 0.00),
(14, 13, 1, 32000.00, '2026-04-28 15:20:00', 'Check', 'CHK-32000-001', 'paid', 'Metrobank check no. 123456', 'admin', '2026-04-28 15:20:00', '0000-00-00', NULL, 0, 0.00),
(15, 13, 2, 32000.00, '2026-05-28 13:45:00', 'Bank Transfer', 'MBTC-32000-002', 'paid', 'Auto-debit from Metrobank account', 'admin', '2026-05-28 13:45:00', '0000-00-00', NULL, 0, 0.00),
(16, 14, 1, 29500.00, '2026-04-29 09:30:00', 'PayMaya', 'PAYMAYA-29500-001', 'paid', 'Digital payment, confirmation received', 'admin', '2026-04-29 09:30:00', '0000-00-00', NULL, 0, 0.00),
(17, 15, 1, 27000.00, '2026-04-30 14:00:00', 'Cash', 'CASH-0430-002', 'paid', 'Down payment balance paid in cash', 'admin', '2026-04-30 14:00:00', '0000-00-00', NULL, 0, 0.00),
(18, 16, 1, 95000.00, '2026-05-01 11:30:00', 'Bank Transfer', 'BDO-95000-0501', 'pending', 'Awaiting bank confirmation', 'admin', '2026-05-01 11:30:00', '0000-00-00', NULL, 0, 0.00),
(19, 17, 1, 19500.00, '2026-05-02 10:15:00', 'Credit Card', 'CC-19500-001', 'paid', 'Credit card payment via terminal', 'admin', '2026-05-02 10:15:00', '0000-00-00', NULL, 0, 0.00),
(20, 18, 1, 16500.00, '2026-05-03 16:00:00', 'Bank Transfer', 'SB-16500-0503', 'pending', 'Scheduled payment, processing', 'admin', '2026-05-03 16:00:00', '0000-00-00', NULL, 0, 0.00),
(21, 19, 1, 65000.00, '2026-05-04 12:00:00', 'Check', 'CHK-65000-002', 'paid', 'Corporate check, cleared', 'admin', '2026-05-04 12:00:00', '0000-00-00', NULL, 0, 0.00),
(22, 19, 2, 7000000.00, '2026-04-07 00:00:00', 'Cash', '', 'paid', 'qwqd', 'admin', '2026-04-07 13:41:53', '0000-00-00', NULL, 0, 0.00),
(23, 18, 2, 50000000.00, '2026-04-08 00:00:00', 'Bank Transfer', '', 'partial', 'huju', 'admin', '2026-04-07 13:44:36', '0000-00-00', NULL, 0, 0.00),
(24, 18, 3, 50000.00, '2026-04-07 00:00:00', 'Check', '', 'partial', 'twre', 'admin', '2026-04-07 13:45:52', '0000-00-00', NULL, 0, 0.00),
(25, 18, 4, 50000.00, '2026-04-07 00:00:00', 'Cash', '231', 'paid', '2313', 'admin', '2026-04-07 13:46:17', '0000-00-00', NULL, 0, 0.00),
(26, 17, 2, 500.00, '2026-04-10 00:00:00', 'Check', '', 'partial', 'weq', 'admin', '2026-04-07 13:58:11', '0000-00-00', NULL, 0, 0.00),
(27, 17, 3, 200000.00, '2026-04-12 00:00:00', 'Cash', '093213', 'partial', '', 'admin', '2026-04-07 13:58:44', '0000-00-00', NULL, 0, 0.00),
(28, 16, 2, 95000.00, '2026-04-07 00:00:00', 'Cash', '3213', 'partial', '', 'admin', '2026-04-07 13:59:14', '0000-00-00', NULL, 0, 0.00),
(29, 16, 3, 5000.00, '2026-04-12 00:00:00', 'Cash', '093214', 'partial', '', 'admin', '2026-04-07 13:59:43', '0000-00-00', NULL, 0, 0.00),
(30, 15, 2, 73000.00, '2026-04-08 00:00:00', 'Cash', '321341', 'partial', '', 'admin', '2026-04-07 14:00:22', '0000-00-00', NULL, 0, 0.00),
(32, 17, 4, 500000.00, '2026-04-07 00:00:00', 'Cash', '', 'paid', '', 'admin', '2026-04-07 15:01:59', '0000-00-00', NULL, 0, 0.00),
(33, 14, 2, 70500.00, '2026-04-07 00:00:00', 'Cash', '', 'partial', '', 'admin', '2026-04-07 15:03:59', '0000-00-00', NULL, 0, 0.00),
(36, 23, 1, 50000.00, '2026-04-07 00:00:00', 'Cash', '', 'paid', 'Down payment', 'admin', '2026-04-07 15:16:25', '0000-00-00', NULL, 0, 0.00),
(37, 23, 2, 900000.00, '2026-04-07 00:00:00', 'Cash', '', 'partial', '', 'admin', '2026-04-07 15:16:38', '0000-00-00', NULL, 0, 0.00),
(38, 24, 1, 100000.00, '2026-04-08 00:00:00', 'Cash', '', 'paid', 'Down payment', 'admin', '2026-04-07 19:50:09', '0000-00-00', NULL, 0, 0.00),
(39, 24, 2, 8000.00, '2026-04-07 00:00:00', 'Cash', '', 'partial', '', 'admin', '2026-04-07 19:50:26', '0000-00-00', NULL, 0, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `car_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `message` text DEFAULT NULL,
  `type` enum('car_reservation','appointment') DEFAULT 'car_reservation',
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `car_id`, `customer_name`, `customer_email`, `customer_phone`, `appointment_date`, `appointment_time`, `message`, `type`, `status`, `created_at`) VALUES
(1, 3, 'Patrick', 'pat@gmail.com', '09977114098', '2026-03-24', '17:30:00', 'I\'m interested in the 2018 Mercedez Benz GLC250 Coupe AWD 2.0L Turbo AT Gas. Please contact me with more information.', 'car_reservation', 'confirmed', '2026-03-23 20:39:39'),
(4, 3, 'jerry', 'your@gmail.com', '039284921', '2026-04-24', '14:22:00', 'I\'m interested in the 2018 Mercedez Benz GLC250 Coupe AWD 2.0L Turbo AT Gas. Please contact me with more information.', 'car_reservation', 'confirmed', '2026-04-07 14:22:27');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `final_price` decimal(12,2) NOT NULL COMMENT 'Final agreed price after discounts',
  `down_payment` decimal(12,2) NOT NULL COMMENT 'Actual down payment collected',
  `monthly_payment` decimal(12,2) NOT NULL COMMENT 'Monthly payment amount',
  `terms_months` varchar(50) NOT NULL COMMENT 'Loan term in months (e.g., 12, 24, 36, 48, 60)',
  `sale_date` date NOT NULL,
  `payment_method` enum('Cash','Bank Loan','In-House Financing') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_amount_paid` decimal(12,2) DEFAULT 0.00,
  `remaining_balance` decimal(12,2) GENERATED ALWAYS AS (`final_price` - `down_payment` - `total_amount_paid`) STORED,
  `payment_status` enum('active','completed','defaulted') DEFAULT 'active',
  `start_date` date DEFAULT NULL COMMENT 'First payment due date',
  `last_payment_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `car_id`, `customer_name`, `customer_email`, `customer_phone`, `final_price`, `down_payment`, `monthly_payment`, `terms_months`, `sale_date`, `payment_method`, `notes`, `created_at`, `updated_at`, `total_amount_paid`, `payment_status`, `start_date`, `last_payment_date`) VALUES
(5, 3, 'Laure Realty Law Office', 'lauregroup02@gmail.com', '09977114098', 450000.00, 50000.00, 0.00, 'N/A', '2026-03-24', 'Cash', '8k per month\r\n', '2026-03-24 12:48:43', '2026-03-24 12:48:43', 0.00, 'active', NULL, NULL),
(10, 5, 'Juan Dela Cruz', 'juan.delacruz@gmail.com', '09171234567', 1850000.00, 250000.00, 28500.00, '60', '2026-03-25', 'Bank Loan', 'Approved by BPI, 5-year loan term', '2026-03-25 14:30:00', '2026-03-25 14:30:00', 0.00, 'active', NULL, NULL),
(11, 8, 'Maria Santos', 'maria.santos@yahoo.com', '09281234567', 1600000.00, 200000.00, 28000.00, '48', '2026-03-26', 'Cash', 'Full payment with 50,000 discount, free tint and floor mats', '2026-03-26 10:15:00', '2026-03-26 10:15:00', 0.00, 'active', NULL, NULL),
(12, 11, 'Roberto Mendoza', 'roberto.mendoza@gmail.com', '09151234567', 1200000.00, 150000.00, 19500.00, '48', '2026-03-27', 'In-House Financing', 'Trade-in vehicle accepted, 3 months free insurance', '2026-03-27 11:00:00', '2026-03-27 11:00:00', 0.00, 'active', NULL, NULL),
(13, 7, 'Michael Tan', 'michael.tan@gmail.com', '09181234567', 2050000.00, 400000.00, 32000.00, '48', '2026-03-28', 'Bank Loan', 'Business account, approved by Metrobank', '2026-03-28 09:45:00', '2026-03-28 09:45:00', 0.00, 'active', NULL, NULL),
(14, 10, 'Isabella Garcia', 'isabella.garcia@gmail.com', '09451234567', 1900000.00, 300000.00, 29500.00, '60', '2026-03-29', 'Cash', 'Full payment with 50,000 discount, free 1-year PMS', '2026-03-29 13:30:00', '2026-03-29 13:30:00', 0.00, 'active', NULL, NULL),
(15, 14, 'Fernando Lopez', 'fernando.lopez@yahoo.com', '09191234567', 1600000.00, 200000.00, 27000.00, '60', '2026-03-30', 'In-House Financing', 'With extended warranty, free LTO registration', '2026-03-30 10:00:00', '2026-03-30 10:00:00', 0.00, 'active', NULL, NULL),
(16, 6, 'Catherine Reyes', 'catherine.reyes@outlook.com', '09361234567', 5100000.00, 1000000.00, 95000.00, '48', '2026-03-31', 'Bank Loan', 'Preferred client, approved by BDO', '2026-03-31 14:15:00', '2026-03-31 14:15:00', 0.00, 'active', NULL, NULL),
(17, 9, 'Patricia Fernandez', 'patricia.fernandez@gmail.com', '09271234567', 1220000.00, 200000.00, 19500.00, '36', '2026-04-01', 'Cash', 'For family and business use, free car accessories', '2026-04-01 11:30:00', '2026-04-01 11:30:00', 0.00, 'active', NULL, NULL),
(18, 12, 'Ricardo Villanueva', 'ricardo.villanueva@outlook.com', '09381234567', 950000.00, 100000.00, 16500.00, '60', '2026-04-02', 'Bank Loan', 'For delivery business, approved by Security Bank', '2026-04-02 09:00:00', '2026-04-02 09:00:00', 0.00, 'active', NULL, NULL),
(19, 13, 'Carmen Aguilar', 'carmen.aguilar@gmail.com', '09161234567', 3700000.00, 700000.00, 65000.00, '48', '2026-04-03', 'Cash', 'Corporate purchase, free premium maintenance package', '2026-04-03 15:45:00', '2026-04-03 15:45:00', 0.00, 'active', NULL, NULL),
(23, 10, 'jerry', 'your@gmail.com', '0983812', 1950000.00, 50000.00, 8000.00, '12', '2026-04-07', 'Cash', '', '2026-04-07 15:16:25', '2026-04-07 15:16:25', 0.00, 'active', NULL, NULL),
(24, 6, 'arnel', 'arnel@gmail.com', '093013291', 5200000.00, 100000.00, 8000.00, '12', '2026-04-08', 'Cash', '', '2026-04-07 19:50:09', '2026-04-07 19:50:09', 0.00, 'active', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`car_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  ADD PRIMARY KEY (`inquiry_id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `payment_date` (`payment_date`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_pt_sale_status` (`sale_id`,`status`),
  ADD KEY `idx_pt_date_status` (`payment_date`,`status`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `car_id` (`car_id`),
  ADD KEY `sale_date` (`sale_date`),
  ADD KEY `payment_method` (`payment_method`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `car_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `contact_inquiries`
--
ALTER TABLE `contact_inquiries`
  MODIFY `inquiry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cars`
--
ALTER TABLE `cars`
  ADD CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`) ON DELETE SET NULL;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


CREATE TABLE `satisfied_customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(100) NOT NULL,
  `car_id` int(11) DEFAULT NULL COMMENT 'Foreign key to cars table',
  `image` varchar(255) NOT NULL COMMENT 'Path to customer image',
  `description` text NOT NULL COMMENT 'Testimonial or feedback',
  `rating` tinyint(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
  PRIMARY KEY (`customer_id`),
  KEY `car_id` (`car_id`),
  CONSTRAINT `satisfied_customers_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;