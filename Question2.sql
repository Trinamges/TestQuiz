CREATE TABLE `payment` (
  `txn_id` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `currency` varchar(45) DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`txn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4

CREATE TABLE `points` (
  `user_id` int(11) NOT NULL,
  `txn_id` varchar(255) NOT NULL,
  `points` int(11) DEFAULT NULL,
  `unused_points` int(11) DEFAULT NULL,
  `expiry_date` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`,`txn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;