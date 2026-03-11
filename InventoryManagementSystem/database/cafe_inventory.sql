-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 25, 2026 at 06:59 AM
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
-- Database: `cafe_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `unit_of_measure` varchar(20) NOT NULL,
  `current_stock` decimal(10,2) DEFAULT 0.00,
  `min_stock_level` decimal(10,2) DEFAULT 10.00,
  `reorder_level` decimal(10,2) DEFAULT 20.00,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `storage_location` enum('bar','stockroom','kitchen') DEFAULT 'stockroom',
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `item_code`, `item_name`, `category_id`, `unit_of_measure`, `current_stock`, `min_stock_level`, `reorder_level`, `unit_cost`, `storage_location`, `expiry_date`, `is_active`, `created_at`) VALUES
(1, 'CFB-001', 'Coffee Beans', NULL, 'kg', 100.00, 10.00, 20.00, 0.00, 'stockroom', NULL, 1, '2026-02-04 14:28:23'),
(2, 'ERC-002', 'Espresso Roast Coffee Beans', NULL, 'kg', 100.00, 10.00, 20.00, 0.00, 'stockroom', NULL, 1, '2026-02-04 14:37:25'),
(3, 'MIL-003', 'Milk', NULL, 'liter', 30.00, 10.00, 20.00, 0.00, 'stockroom', NULL, 1, '2026-02-04 14:41:11'),
(4, 'WTR-123', 'Water', NULL, 'liter', 10.00, 10.00, 20.00, 0.00, 'stockroom', NULL, 1, '2026-02-11 01:12:18');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `order_date` date NOT NULL,
  `expected_delivery` date DEFAULT NULL,
  `status` enum('pending','ordered','delivered','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `prepared_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `po_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` int(11) NOT NULL,
  `recipe_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `recipe_name`, `description`, `selling_price`, `is_active`, `created_at`) VALUES
(1, 'Americano', 'An Americano is a coffee drink that’s as straightforward as it is delightful.', 100.00, 1, '2026-02-11 01:10:32');

-- --------------------------------------------------------

--
-- Table structure for table `recipe_items`
--

CREATE TABLE `recipe_items` (
  `id` int(11) NOT NULL,
  `recipe_id` int(11) DEFAULT NULL,
  `item_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipe_items`
--

INSERT INTO `recipe_items` (`id`, `recipe_id`, `item_id`, `quantity`, `created_at`) VALUES
(1, 1, 1, 1.00, '2026-02-11 01:11:17'),
(2, 1, 4, 1.00, '2026-02-11 01:12:41');

-- --------------------------------------------------------

--
-- Table structure for table `reorder_suggestions`
--

CREATE TABLE `reorder_suggestions` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `current_stock` int(11) NOT NULL,
  `reorder_level` int(11) NOT NULL,
  `suggested_qty` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reorder_suggestions`
--

INSERT INTO `reorder_suggestions` (`id`, `item_id`, `current_stock`, `reorder_level`, `suggested_qty`, `status`, `created_at`) VALUES
(1, 1, 10, 10, 0, 'pending', '2026-02-10 23:17:21'),
(2, 3, 10, 10, 0, 'pending', '2026-02-10 23:18:55'),
(3, 3, 10, 10, 0, 'pending', '2026-02-10 23:34:50'),
(4, 3, 10, 20, 10, 'pending', '2026-02-11 00:34:09'),
(5, 1, 10, 20, 10, 'pending', '2026-02-11 00:47:21'),
(6, 2, 10, 20, 10, 'pending', '2026-02-11 01:05:21'),
(7, 1, 40, 20, 0, 'pending', '2026-02-11 04:18:18');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

CREATE TABLE `stock_transactions` (
  `id` int(11) NOT NULL,
  `transaction_type` enum('purchase','usage','adjustment','spoilage','breakage','staff_drink','transfer') NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `location_from` varchar(50) DEFAULT NULL,
  `location_to` varchar(50) DEFAULT NULL,
  `performed_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_transactions`
--

INSERT INTO `stock_transactions` (`id`, `transaction_type`, `item_id`, `quantity`, `unit_cost`, `reference_id`, `reference_number`, `notes`, `location_from`, `location_to`, `performed_by`, `approved_by`, `status`, `created_at`) VALUES
(29, 'purchase', 1, 30.00, NULL, NULL, NULL, NULL, NULL, NULL, 13, NULL, 'pending', '2026-02-11 00:58:43'),
(35, 'purchase', 1, 80.00, NULL, NULL, NULL, NULL, NULL, NULL, 13, NULL, 'pending', '2026-02-11 04:20:49'),
(36, 'purchase', 2, 90.00, NULL, NULL, NULL, NULL, NULL, NULL, 13, NULL, 'pending', '2026-02-11 04:21:13'),
(37, 'usage', 1, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, 13, NULL, 'pending', '2026-02-11 05:44:05'),
(38, 'purchase', 3, 20.00, NULL, NULL, NULL, NULL, NULL, NULL, 13, NULL, 'pending', '2026-02-11 05:47:08');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `supplier_code` varchar(50) NOT NULL,
  `supplier_name` varchar(200) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `supplier_code`, `supplier_name`, `contact_person`, `email`, `phone`, `address`, `is_active`, `created_at`) VALUES
(1, 'A3B2C1', 'Kristell', 'Kristell', 'kristell.dar200@gmail.com', '09987456321', 'Home', 1, '2026-02-11 01:07:10'),
(2, 'Z1Y2X5', 'Gerwin', 'Jigs', 'jig123@mail.com', '09987132582', 'Home', 1, '2026-02-11 04:24:26'),
(3, 'QWE-123', 'Mafe', 'Mafe', 'mafe123@gmail.com', '09789321842', 'Home', 1, '2026-02-11 05:42:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('owner','staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `is_active`, `created_at`) VALUES
(13, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin@cafe.com', 'staff', 1, '2026-02-04 13:59:20'),
(15, 'superadmin', '$2y$10$XFZ2dIUjufNZIv9WVx22UOSLwgvxUZdabdHjMyT6xmgW2wQJa7c8a', 'Super Administrator', 'superadmin@cafe.com', 'owner', 1, '2026-02-10 12:32:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `prepared_by` (`prepared_by`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recipe_items`
--
ALTER TABLE `recipe_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_id` (`recipe_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `reorder_suggestions`
--
ALTER TABLE `reorder_suggestions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `performed_by` (`performed_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `supplier_code` (`supplier_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `recipe_items`
--
ALTER TABLE `recipe_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reorder_suggestions`
--
ALTER TABLE `reorder_suggestions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `recipe_items`
--
ALTER TABLE `recipe_items`
  ADD CONSTRAINT `recipe_items_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recipe_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `stock_transactions_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `stock_transactions_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
