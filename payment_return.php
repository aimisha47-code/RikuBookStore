<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$status_id = isset($_GET['status_id']) ? $_GET['status_id'] : '';
$billcode = isset($_GET['billcode']) ? $conn->real_escape_string($_GET['billcode']) : '';
$order_id = isset($_GET['order_id']) ? $conn->real_escape_string($_GET['order_id']) : '';

$status_msg = "Unknown Status";
$status_type = "unknown"; // success, pending, failed
$total_amount = 0;
$order_date = "";
$items = "";

if (!empty($order_id)) {
    // Retrieve order info
    $order_sql = "SELECT * FROM orders WHERE order_id = '$order_id' AND user_id = $user_id";
    $order_res = $conn->query($order_sql);
    if ($order_res && $order_res->num_rows > 0) {
        $order_data = $order_res->fetch_assoc();
        $total_amount = $order_data['total_amount'];
        $order_date = $order_data['order_date'];
        $items = $order_data['items'];
        
        if ($status_id === '1') {
            // Success - Update Status if not already updated by callback
            $conn->query("UPDATE orders SET status = 'Paid', status_color = 'bg-emerald-100 text-emerald-800 border-emerald-200' WHERE order_id = '$order_id'");
            $status_msg = "Payment Successful";
            $status_type = "success";
        } elseif ($status_id === '2') {
            // Pending
            $conn->query("UPDATE orders SET status = 'Pending Control', status_color = 'bg-amber-100 text-amber-800 border-amber-200' WHERE order_id = '$order_id'");
            $status_msg = "Payment Pending";
            $status_type = "pending";
        } else {
            // Failed / Cancelled
            $conn->query("UPDATE orders SET status = 'Failed', status_color = 'bg-red-100 text-red-800 border-red-200' WHERE order_id = '$order_id'");
            $status_msg = "Payment Failed";
            $status_type = "failed";
        }
    }
}

// Get Cart Count
$cart_count_sql = "SELECT SUM(quantity) as count FROM cart WHERE user_id = $user_id";
$cart_count_res = $conn->query($cart_count_sql);
$cart_count_row = $cart_count_res->fetch_assoc();
$cart_count = $cart_count_row['count'] ?? 0;

$user_session = [
    'name' => $_SESSION['name'] ?? 'Guest',
    'initials' => $_SESSION['initials'] ?? 'G',
    'role' => $_SESSION['role'] ?? 'Customer',
    'cart_count' => $cart_count
];

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RikuStore - Payment Receipt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="flex flex-col min-h-screen text-stone-800 bg-stone-50/50">

    <nav class="bg-white/90 backdrop-blur-md border-b border-stone-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <img src="image/logo.png" alt="Riku Bookstore" class="h-16 object-contain bg-transparent">
                    </a>
                    <div class="hidden md:flex space-x-8 ml-12 text-sm font-medium text-stone-600">
                        <a href="index.php" class="hover:text-stone-900 px-1 py-2 transition">Home</a>
                        <a href="genre.php" class="hover:text-stone-900 px-1 py-2 transition">Genres</a> 
                        <a href="newrelease.php" class="hover:text-stone-900 px-1 py-2 transition">New Releases</a>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="cart.php" class="relative text-stone-700 hover:text-amber-800 transition p-1">
                        <i class="fa-solid fa-bag-shopping text-xl"></i>
                        <span class="absolute -top-1 -right-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-amber-800 rounded-full"><?php echo $user_session['cart_count']; ?></span>
                    </a>
                    <div class="flex items-center space-x-3 border-l pl-5 border-stone-200">
                        <div class="text-right hidden md:block">
                            <p class="text-xs text-stone-400 font-medium"><?php echo $user_session['role']; ?> Session</p>
                            <a href="profile.php" class="text-sm font-semibold text-stone-800 hover:text-amber-800 transition"><?php echo $user_session['name']; ?></a>
                        </div>
                        <a href="profile.php" class="w-10 h-10 rounded-full bg-stone-100 text-stone-700 hover:bg-stone-200 flex items-center justify-center font-semibold border border-stone-200 transition">
                          <?php echo $user_session['initials']; ?>
                        </a>
                        <a href="logout.php" class="text-stone-400 hover:text-red-700 transition" title="Logout">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-xl mx-auto px-4 py-16 flex-grow w-full flex flex-col justify-center">
        
        <div class="bg-white border border-stone-200 rounded-2xl p-8 shadow-sm text-center relative overflow-hidden">
            
            <!-- Dynamic Status Badge Indicator -->
            <div class="flex justify-center mb-6">
                <?php if ($status_type === 'success'): ?>
                    <div class="w-16 h-16 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center text-3xl animate-bounce">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                <?php elseif ($status_type === 'pending'): ?>
                    <div class="w-16 h-16 rounded-full bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center text-3xl animate-pulse">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                <?php else: ?>
                    <div class="w-16 h-16 rounded-full bg-red-50 border border-red-200 text-red-600 flex items-center justify-center text-3xl">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                <?php endif; ?>
            </div>

            <h1 class="serif-title text-2xl font-normal text-stone-900 mb-2"><?php echo htmlspecialchars($status_msg); ?></h1>
            <p class="text-xs text-stone-400 mb-6">
                <?php if ($status_type === 'success'): ?>
                    Your books have been successfully purchased and unlocked in your bookshelf.
                <?php elseif ($status_type === 'pending'): ?>
                    Your payment is currently being processed by ToyyibPay. Check back in a few minutes.
                <?php else: ?>
                    The payment transaction was cancelled or could not be completed. Please try again.
                <?php endif; ?>
            </p>

            <!-- Order Specs Receipt Table -->
            <?php if (!empty($order_id)): ?>
                <div class="bg-stone-50 border border-stone-200 rounded-xl p-5 mb-8 text-left space-y-3 font-mono text-xs">
                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-400">Order ID:</span>
                        <span class="font-bold text-stone-900"><?php echo htmlspecialchars($order_id); ?></span>
                    </div>
                    <?php if (!empty($billcode)): ?>
                        <div class="flex justify-between border-b border-stone-200 pb-2">
                            <span class="text-stone-400">ToyyibPay Ref:</span>
                            <span class="font-bold text-stone-900"><?php echo htmlspecialchars($billcode); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-400">Purchased Items:</span>
                        <span class="font-bold text-stone-900 truncate max-w-[200px]" title="<?php echo htmlspecialchars($items); ?>"><?php echo htmlspecialchars($items); ?></span>
                    </div>
                    <?php if (!empty($order_data['shipping_address'])): ?>
                        <div class="flex justify-between border-b border-stone-200 pb-2">
                            <span class="text-stone-400">Shipping Address:</span>
                            <span class="font-bold text-stone-900 truncate max-w-[200px]" title="<?php echo htmlspecialchars($order_data['shipping_address']); ?>"><?php echo htmlspecialchars($order_data['shipping_address']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-400">Total Amount:</span>
                        <span class="font-bold text-amber-800 text-sm">RM<?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-stone-400">Date:</span>
                        <span class="font-bold text-stone-900"><?php echo htmlspecialchars($order_date); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Navigation Actions -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <?php if ($status_type === 'success'): ?>
                    <a href="bookshelf.php" class="bg-stone-900 hover:bg-amber-800 text-white text-xs px-6 py-3 rounded-lg font-bold tracking-wide transition shadow-sm">
                        Go to Bookshelf
                    </a>
                <?php else: ?>
                    <a href="cart.php" class="bg-stone-900 hover:bg-amber-800 text-white text-xs px-6 py-3 rounded-lg font-bold tracking-wide transition shadow-sm">
                        Return to Cart
                    </a>
                <?php endif; ?>
                <a href="profile.php" class="bg-stone-100 hover:bg-stone-200 border border-stone-200 text-stone-700 text-xs px-6 py-3 rounded-lg font-bold tracking-wide transition">
                    View Orders
                </a>
            </div>

        </div>

    </main>

    <footer class="bg-stone-950 text-stone-400 text-xs py-5 px-6 border-t border-stone-800 font-mono mt-auto">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row justify-between items-center space-y-3 lg:space-y-0">
            <div class="flex flex-wrap justify-center lg:justify-start gap-x-6 gap-y-2 text-[11px]">
                <span class="flex items-center text-stone-300"><i class="fa-solid fa-circle text-[6px] text-emerald-500 mr-2"></i> Payment Registry System Online</span>
                <span>Sub-Systems: ToyyibPay Dev Router</span>
            </div>
            <div class="text-[11px] text-stone-500">RikuStore Dynamic PHP Model Framework &copy; 2026</div>
        </div>
    </footer>

</body>
</html>
