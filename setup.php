<?php
// setup.php - Builds the database tables on the live server
require __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>SuperAjo Database Setup</h2>";

$sql = "
CREATE TABLE IF NOT EXISTS `ajos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT, 
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(100) NOT NULL, 
  `admin_token` VARCHAR(100) NOT NULL,
  `amount` INT(11) NOT NULL DEFAULT 0, 
  `total_members` INT(11) NOT NULL DEFAULT 0,
  `frequency` ENUM('daily','weekly','monthly') NOT NULL DEFAULT 'monthly',
  `start_date` DATE DEFAULT NULL, 
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), 
  UNIQUE KEY `slug` (`slug`), 
  UNIQUE KEY `admin_token` (`admin_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `members` (
  `id` INT(11) NOT NULL AUTO_INCREMENT, 
  `ajo_id` INT(11) NOT NULL,
  `name` VARCHAR(255) NOT NULL, 
  `phone` VARCHAR(20) DEFAULT NULL,
  `payout_position` INT(11) NOT NULL DEFAULT 1, 
  `payout_received` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), 
  KEY `ajo_id` (`ajo_id`),
  CONSTRAINT `members_ibfk_1` FOREIGN KEY (`ajo_id`) REFERENCES `ajos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT, 
  `ajo_id` INT(11) NOT NULL, 
  `member_id` INT(11) NOT NULL,
  `period_label` VARCHAR(50) NOT NULL DEFAULT 'Period 1', 
  `amount` INT(11) NOT NULL DEFAULT 0,
  `proof_image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
  `transaction_id` VARCHAR(100) DEFAULT NULL, 
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), 
  KEY `ajo_id` (`ajo_id`), 
  KEY `member_id` (`member_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`ajo_id`) REFERENCES `ajos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (mysqli_multi_query($conn, $sql)) {
    echo "<h3 style='color:green;'>✅ Success! All 3 tables were created perfectly.</h3>";
    echo "<p>You can now delete this <b>setup.php</b> file from GitHub, and visit your main app:</p>";
    echo "<p><a href='index.php' style='font-size:18px;'>Go to SuperAjo</a></p>";
} else {
    echo "<h3 style='color:red;'>❌ Error creating tables: " . mysqli_error($conn) . "</h3>";
}
?>
