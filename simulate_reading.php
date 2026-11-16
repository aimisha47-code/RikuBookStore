<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Fetch current reading streak
$sql = "SELECT reading_streak FROM users WHERE id = $user_id";
$res = $conn->query($sql);
$user = $res->fetch_assoc();

$reading_streak = (int)$user['reading_streak'];
$reading_streak++; // Simulate a successful day of reading

// Update database
$update_sql = "UPDATE users SET reading_streak=$reading_streak, last_read_date=CURDATE() WHERE id=$user_id";
$conn->query($update_sql);

// Redirect back with success message
header("Location: streak.php?reading_simulated=1");
exit();
?>
