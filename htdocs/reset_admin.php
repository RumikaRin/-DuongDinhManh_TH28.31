<?php
require 'dbconnect.php';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
$sql = "UPDATE users SET mk_tv = ? WHERE ten_tv = 'admin'";
$stmt = $ketnoi->prepare($sql);
$stmt->bind_param('s', $hash);
if ($stmt->execute()) {
    echo "Password updated successfully.";
} else {
    echo "Error updating password.";
}
$stmt->close();
?>
