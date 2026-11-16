<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $seconds = (int)($data['seconds'] ?? 0);
    
    if ($seconds > 0 && $seconds <= 60) { // Max 60 seconds per heartbeat to prevent abuse
        $user_id = (int)$_SESSION['user_id'];
        $today = date('Y-m-d');
        
        $sql = "SELECT today_reading_seconds, reading_streak, last_read_date FROM users WHERE id = $user_id";
        $res = $conn->query($sql);
        
        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();
            
            $current_seconds = (int)$user['today_reading_seconds'];
            
            // Check if we need to reset the daily seconds because it's a new day
            if (!isset($_SESSION['last_reading_date']) || $_SESSION['last_reading_date'] !== $today) {
                $current_seconds = 0;
            }
            $_SESSION['last_reading_date'] = $today;
            
            $new_seconds = $current_seconds + $seconds;
            $reading_streak = (int)$user['reading_streak'];
            $last_read_date = $user['last_read_date'];
            
            // Goal is 10 minutes (600 seconds)
            $goal_reached_now = false;
            if ($new_seconds >= 600 && $last_read_date !== $today) {
                if ($last_read_date === date('Y-m-d', strtotime('-1 day'))) {
                    $reading_streak++;
                } else {
                    $reading_streak = 1;
                }
                $last_read_date = $today;
                $goal_reached_now = true;
            }
            
            $last_read_sql = $last_read_date ? "'$last_read_date'" : "NULL";
            $update_sql = "UPDATE users SET today_reading_seconds = $new_seconds, reading_streak = $reading_streak, last_read_date = $last_read_sql WHERE id = $user_id";
            $conn->query($update_sql);
            
            echo json_encode([
                'success' => true, 
                'seconds' => $new_seconds, 
                'streak' => $reading_streak,
                'goal_reached' => $goal_reached_now
            ]);
        }
    } else {
        echo json_encode(['error' => 'Invalid seconds data']);
    }
}
?>
