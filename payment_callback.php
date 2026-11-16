<?php
include 'db_connect.php';

// ToyyibPay sends POST data on payment completion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status_id = isset($_POST['status_id']) ? $_POST['status_id'] : '';
    $billcode = isset($_POST['billcode']) ? $conn->real_escape_string($_POST['billcode']) : '';
    $order_id = isset($_POST['order_id']) ? $conn->real_escape_string($_POST['order_id']) : '';
    
    if ($status_id === '1') {
        // Success
        $sql = "UPDATE orders SET status = 'Paid', status_color = 'bg-emerald-100 text-emerald-800 border-emerald-200' WHERE order_id = '$order_id'";
        $conn->query($sql);
    } elseif ($status_id === '2') {
        // Pending
        $sql = "UPDATE orders SET status = 'Pending Control', status_color = 'bg-amber-100 text-amber-800 border-amber-200' WHERE order_id = '$order_id'";
        $conn->query($sql);
    } else {
        // Failed
        $sql = "UPDATE orders SET status = 'Failed', status_color = 'bg-red-100 text-red-800 border-red-200' WHERE order_id = '$order_id'";
        $conn->query($sql);
    }
    
    // ToyyibPay expects a response to confirm receipt of callback
    echo "OK";
    exit();
}
?>
