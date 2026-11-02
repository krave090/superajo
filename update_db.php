<?php
require __DIR__ . '/config.php';
header('Content-Type: text/html; charset=utf-8');
echo "<h2>SuperAjo Database Upgrade</h2>";

// 1. Update the ajos table to add approval tracking
$sql1 = "ALTER TABLE `ajos` 
  ADD COLUMN `approval_code` VARCHAR(50) DEFAULT NULL,
  ADD COLUMN `admin_name` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN `admin_email` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN `admin_phone` VARCHAR(20) DEFAULT NULL,
  ADD COLUMN `status` ENUM('pending', 'active', 'suspended') DEFAULT 'pending',
  ADD COLUMN `expires_at` DATE DEFAULT NULL;";
mysqli_query($conn, $sql1);

// 2. Create a Settings table for your Super Admin controls
$sql2 = "CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $sql2);

// 3. Insert default settings if they don't exist
$defaults = [
    'subscription_price' => '2000',
    'bank_name' => 'Moniepoint MFB',
    'account_number' => '1234567890',
    'account_name' => 'SuperAjo Platform',
    'whatsapp_number' => '2349013685802'
];
foreach ($defaults as $key => $val) {
    mysqli_query($conn, "INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('$key', '$val')");
}

echo "<h3 style='color:green;'>✅ Database upgraded successfully!</h3>";
echo "<p><a href='index.php'>Go to Homepage</a> | <a href='superadmin.php'>Go to Super Admin</a></p>";
?>
