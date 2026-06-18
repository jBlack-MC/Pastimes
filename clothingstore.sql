-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2026 at 05:51 PM
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
-- Database: `clothingstore`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbladmin`
--

CREATE TABLE `tbladmin` (
  `admin_id` int(11) NOT NULL COMMENT 'AUTO_INCREMENT',
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblclothes`
--

CREATE TABLE `tblclothes` (
  `product_id` int(11) NOT NULL COMMENT 'AUTO_INCREMENT',
  `user_id` int(11) NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL DEFAULT 'placeholder-clothing.jpg',
  `brand` varchar(100) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblorder`
--

CREATE TABLE `tblorder` (
  `order_id` int(11) NOT NULL COMMENT 'AUTO_INCREMENT',
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `delivery_address` text NOT NULL,
  `order_date` timestamp NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending',
  `payment_status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbluser`
--

CREATE TABLE `tbluser` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblorderline`
--

CREATE TABLE `tblorderline` (
  `orderline_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcart`
--

CREATE TABLE `tblcart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblseller`
--

CREATE TABLE `tblseller` (
  `seller_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `brand_name` varchar(100) DEFAULT NULL,
  `description` text,
  `phone` varchar(30) DEFAULT NULL,
  `approval_status` varchar(20) DEFAULT 'pending',
  `approved_date` datetime DEFAULT NULL,
  `created_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbladmin`
--

INSERT INTO `tbladmin` (`admin_id`, `username`, `password`) VALUES
(1, 'thabo_admin', '$2y$12$KnAU2YCWpFePeXDomUCyiO7ZlEWB0P0KxRMEWGK2VlDH8p6pOnO26');

--
-- Dumping data for table `tbluser`
--

INSERT INTO `tbluser` (`user_id`, `name`, `email`, `username`, `password`, `status`) VALUES
(1,  'John Mokoena',  'john@gmail.com',    'john123',         '$2y$12$F4xsB32AmjKjTsLr2rmK0ePxqhGwmqIWS.N3IxWN/WnYakvpcfqGu', 'active'),
(2,  'Jane Botha',    'jane@gmail.com',    'jane123',         '$2y$12$e7FgLYDvAqCbYONj53vzQuvxY7go22.wymZCbArJDo.PXgkhiRWoa',  'active'),
(3,  'Mike Peters',   'mike@gmail.com',    'mike123',         '$2y$12$vxzAd48g5kDW5mRBXOwGae2VlDgcQy6AdSWodf8uS3O7VvFC8jXii',  'active'),
(4,  'Sara Nxumalo',  'sara@gmail.com',    'sara123',         '$2y$12$KSqv7aYv9AjLnflcWaG94OJH9bOueoOK.EEMBbS25bJ.SUSp0RD6a',  'active'),
(5,  'Lebo Khumalo',  'lebo@gmail.com',    'lebo123',         '$2y$12$/8lxa8rqVbwl51dzGdnVHupZnO7nRftCzOTluM9cKPBAjiQNUtsrq',   'active'),
(6,  'Zanele Moyo',   'zanele@gmail.com',  'zanele123',       '$2y$12$/8lxa8rqVbwl51dzGdnVHupZnO7nRftCzOTluM9cKPBAjiQNUtsrq',   'active'),
(7,  'Unathi Buyer',  'unathi@gmail.com',  'unathi_buys',     '$2y$12$yLQ2KOuQiwq7GmC6hL/jsebNDioqB5EMsys7iH5e2pwdmGv2Pyp9C',   'active'),
-- sellers (also registered as users so they can browse the shop)
(8,  'Amahle Dube',   'amahle@gmail.com',  'amahle_threads',  '$2y$12$bZ3d8E8oPZ6V1bd.cOxNq.sbButq6zmWM8kvEn/nH2rcmMlXhNE7q',  'active'),
(9,  'Sipho Cele',    'sipho@gmail.com',   'sipho_style',     '$2y$12$uUMceuJTavn2yZajKWalQeteZIYFs26Ql2Gc21/2T8T3/kdKOBGTK',   'active'),
(10, 'Priya Naidoo',  'priya@gmail.com',   'priya_boutique',  '$2y$12$Yf5rOTSGILMv6QDmcStTqOtiIfKPZcD4zhH1.FU3MV1syEYxO42Ly',  'active');

--
-- Dumping data for table `tblclothes`
--

-- Seller profiles (all approved so their products appear in shop)
INSERT INTO `tblseller` (`seller_id`, `user_id`, `brand_name`, `description`, `phone`, `approval_status`, `approved_date`) VALUES
(1, 8,  'Amahle Threads',  'Vintage streetwear and denim from the 80s and 90s.',            '0712345678', 'approved', '2026-01-10 09:00:00'),
(2, 9,  'Sipho Style',     'Smart-casual and formal menswear for every occasion.',           '0723456789', 'approved', '2026-01-12 10:30:00'),
(3, 10, 'Priya Boutique',  'Handcrafted accessories, knitwear, and unique artisan pieces.',  '0734567890', 'approved', '2026-01-15 14:00:00');

-- Products assigned to their respective sellers
-- Amahle Threads: vintage/denim items (seller_id=1, user_id=8)
-- Sipho Style: smart-casual items (seller_id=2, user_id=9)
-- Priya Boutique: artisan/knitwear items (seller_id=3, user_id=10)
INSERT INTO `tblclothes` (`product_id`, `user_id`, `seller_id`, `name`, `description`, `price`, `image`, `brand`, `stock`) VALUES
(1, 9,  2, 'Organic Cotton Tee',    'Soft, breathable everyday wear made from organic cotton.',    24.90, 'organic-cotton-tee.jpg',    'Heritage Basics',  25),
(2, 8,  1, 'Linen Blend Shirt',     'Lightweight linen-cotton blend shirt for warm weather.',      49.50, 'linen-blend-shirt.jpg',     'Thread and Oak',   18),
(3, 8,  1, 'Vintage Denim Jacket',  'Classic blue denim jacket with a durable worn-in finish.',   79.00, 'vintage-denim-jacket.jpg',  'Urban Rewind',     12),
(4, 10, 3, 'Handwoven Scarf',       'Artisan wool scarf with a soft handwoven finish.',            34.99, 'handwoven-scarf.jpg',       'Wool and Weave',   30),
(5, 9,  2, 'French Terry Joggers',  'Eco-friendly french terry joggers with tapered ankles.',      42.00, 'french-terry-joggers.jpg',  'Cozy Nest',        20),
(6, 9,  2, 'Full-Grain Belt',       'Genuine full-grain leather belt with brass buckle.',          28.99, 'full-grain-belt.jpg',       'Leathercraft Co',  15),
(7, 10, 3, 'Linen Midi Dress',      'Flowy linen dress with adjustable straps.',                   67.00, 'linen-midi-dress.jpg',      'Sunday Studio',    14);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbladmin`
--
ALTER TABLE `tbladmin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `tblclothes`
--
ALTER TABLE `tblclothes`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `tblorder`
--
ALTER TABLE `tblorder`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tbluser`
--
ALTER TABLE `tbluser`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `tblorderline`
--
ALTER TABLE `tblorderline`
  ADD PRIMARY KEY (`orderline_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tblcart`
--
ALTER TABLE `tblcart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `user_product` (`user_id`,`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tblseller`
--
ALTER TABLE `tblseller`
  ADD PRIMARY KEY (`seller_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbladmin`
--
ALTER TABLE `tbladmin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'AUTO_INCREMENT', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblclothes`
--
ALTER TABLE `tblclothes`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'AUTO_INCREMENT';

--
-- AUTO_INCREMENT for table `tblorder`
--
ALTER TABLE `tblorder`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'AUTO_INCREMENT';

--
-- AUTO_INCREMENT for table `tbluser`
--
ALTER TABLE `tbluser`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tblorderline`
--
ALTER TABLE `tblorderline`
  MODIFY `orderline_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcart`
--
ALTER TABLE `tblcart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblseller`
--
ALTER TABLE `tblseller`
  MODIFY `seller_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tblclothes`
--
ALTER TABLE `tblclothes`
  ADD CONSTRAINT `tblclothes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbluser` (`user_id`),
  ADD CONSTRAINT `tblclothes_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `tblseller` (`seller_id`) ON DELETE SET NULL;

--
-- Constraints for table `tblorder`
--
ALTER TABLE `tblorder`
  ADD CONSTRAINT `tblorder_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbluser` (`user_id`);

--
-- Constraints for table `tblorderline`
--
ALTER TABLE `tblorderline`
  ADD CONSTRAINT `tblorderline_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `tblorder` (`order_id`),
  ADD CONSTRAINT `tblorderline_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tblclothes` (`product_id`);

--
-- Constraints for table `tblcart`
--
ALTER TABLE `tblcart`
  ADD CONSTRAINT `tblcart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbluser` (`user_id`),
  ADD CONSTRAINT `tblcart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tblclothes` (`product_id`);

--
-- Constraints for table `tblseller`
--
ALTER TABLE `tblseller`
  ADD CONSTRAINT `tblseller_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbluser` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
