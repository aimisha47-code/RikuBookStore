<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$update_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_name = $conn->real_escape_string(trim($_POST['fullname']));
    $new_email = $conn->real_escape_string(trim($_POST['email']));
    $new_address = $conn->real_escape_string(trim($_POST['address']));
    
    $update_sql = "UPDATE users SET name='$new_name', email='$new_email', address='$new_address' WHERE id=$user_id";
    if ($conn->query($update_sql)) {
        $_SESSION['name'] = $new_name;
        $update_success = true;
    }
}

// Get user profile
$user_sql = "SELECT * FROM users WHERE id = $user_id";
$user_res = $conn->query($user_sql);
$user_data = $user_res->fetch_assoc();

// Get Cart Count
$cart_count_sql = "SELECT SUM(quantity) as count FROM cart WHERE user_id = $user_id";
$cart_count_res = $conn->query($cart_count_sql);
$cart_count_row = $cart_count_res->fetch_assoc();
$cart_count = $cart_count_row['count'] ?? 0;

$user_profile = [
    'user_id' => $user_data['id'],
    'name' => $user_data['name'],
    'email' => $user_data['email'],
    'role' => $user_data['role'],
    'initials' => $user_data['initials'],
    'joined_date' => $user_data['joined_date'],
    'cart_count' => $cart_count,
    'address' => $user_data['address']
];

// Get orders
$order_history = [];
$orders_sql = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY id DESC";
$orders_res = $conn->query($orders_sql);
if ($orders_res->num_rows > 0) {
    while($row = $orders_res->fetch_assoc()) {
        $order_history[] = $row;
    }
}

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RikuStore - My Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
</head>
<body class="flex flex-col min-h-screen text-stone-800">
<nav class="bg-white/90 backdrop-blur-md border-b border-stone-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <img src="image/logo.png" alt="Riku Bookstore" class="h-16 object-contain bg-transparent">
                    </a>
                    <div class="hidden md:flex space-x-8 ml-12 text-sm font-medium text-stone-600">
                        <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">Home</a>
                        <a href="genre.php" class="<?php echo ($current_page == 'genre.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">Genres</a>
                        <a href="newrelease.php" class="<?php echo ($current_page == 'newreleases.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">New Releases</a>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="cart.php" class="relative text-stone-700 hover:text-amber-800 transition p-1">
                        <i class="fa-solid fa-bag-shopping text-xl"></i>
                        <span class="absolute -top-1 -right-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-amber-800 rounded-full"><?php echo $user_profile['cart_count']; ?></span>
                    </a>
                    <div class="flex items-center space-x-3 border-l pl-5 border-stone-200">
                        <div class="text-right hidden md:block">
                            <p class="text-xs text-stone-400 font-medium"><?php echo $user_profile['role']; ?></p>
                            <p class="text-sm font-semibold text-stone-800"><?php echo $user_profile['name']; ?></p>
                        </div>
                        <a href="profile.php" class="w-10 h-10 rounded-full bg-amber-700 text-white flex items-center justify-center font-semibold border border-amber-800 cursor-pointer">
                            <?php echo $user_profile['initials']; ?>
                        </a>
                        <a href="logout.php" class="text-stone-400 hover:text-red-700 transition" title="Logout">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-grow w-full">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1 flex flex-col space-y-6">
                <div class="bg-white border border-stone-200 rounded-xl p-6 text-center shadow-sm">
                    <div class="w-20 h-20 mx-auto rounded-full bg-stone-100 border border-stone-200 text-stone-700 flex items-center justify-center text-2xl font-bold mb-4">
                        <?php echo $user_profile['initials']; ?>
                    </div>
                    <h2 class="font-bold text-lg text-stone-900"><?php echo htmlspecialchars($user_profile['name']); ?></h2>
                    <p class="text-sm text-stone-500 mb-2"><?php echo htmlspecialchars($user_profile['email']); ?></p>
                    <span class="inline-block text-[11px] font-bold uppercase tracking-wider bg-stone-100 text-stone-700 px-2.5 py-1 rounded-full border border-stone-200">
                        ID: <?php echo $user_profile['user_id']; ?> &bull; <?php echo $user_profile['role']; ?>
                    </span>
                    <div class="border-t border-stone-100 mt-4 pt-4 text-left text-xs text-stone-400">
                        <i class="fa-regular fa-calendar mr-1"></i> Member since <?php echo $user_profile['joined_date']; ?>
                    </div>
                </div>
            </div>
<div class="lg:col-span-2 flex flex-col space-y-8">
                <?php if ($update_success): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-4 text-sm flex items-center space-x-3">
                        <i class="fa-solid fa-circle-check text-base text-emerald-600"></i>
                        <span><strong>Database Update Successful:</strong> Profile records have been updated in cache.</span>
                    </div>
                <?php endif; ?>

                <div class="bg-white border border-stone-200 rounded-xl p-6 shadow-sm">
                    <h3 class="serif-title text-2xl font-normal text-stone-900 mb-6 pb-2 border-b border-stone-100">Account Settings (Update Spec)</h3>
                    <form method="POST" action="profile.php" class="space-y-4">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-stone-500 mb-2">Full Name</label>
                                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user_profile['name']); ?>" required class="w-full px-3 py-2.5 bg-stone-50 border border-stone-200 rounded-lg text-sm focus:outline-none focus:border-amber-700 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-stone-500 mb-2">Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user_profile['email']); ?>" required class="w-full px-3 py-2.5 bg-stone-50 border border-stone-200 rounded-lg text-sm focus:outline-none focus:border-amber-700 transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-stone-500 mb-2">Shipping Address</label>
                                <textarea name="address" rows="5" required class="w-full px-3 py-2.5 bg-stone-50 border border-stone-200 rounded-lg text-sm focus:outline-none focus:border-amber-700 transition resize-none"><?php echo htmlspecialchars($user_profile['address']); ?></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="px-6 py-2.5 bg-amber-700 hover:bg-amber-800 text-white rounded-lg text-sm font-medium shadow-sm transition">
                                Save Profile
                            </button>
                        </div>
                    </form>
                </div>
<div class="bg-white border border-stone-200 rounded-xl p-6 shadow-sm">
                    <h3 class="serif-title text-2xl font-normal text-stone-900 mb-6 pb-2 border-b border-stone-100">Order History (Read Tracking)</h3>
                    <div class="space-y-4">
                        <?php foreach ($order_history as $order): ?>
                            <div class="border border-stone-200 rounded-lg p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-3 sm:space-y-0 bg-stone-50/50">
                                <div class="space-y-1">
                                    <div class="flex items-center space-x-3">
                                        <span class="font-mono text-sm font-bold text-stone-900"><?php echo $order['order_id']; ?></span>
                                        <span class="text-xs px-2 py-0.5 rounded border font-medium <?php echo $order['status_color']; ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-stone-500"><?php echo htmlspecialchars($order['items']); ?></p>
                                    <?php if (!empty($order['shipping_address'])): ?>
                                        <p class="text-[11px] text-stone-500 flex items-start mt-1">
                                            <i class="fa-solid fa-truck text-stone-400 mt-0.5 mr-1.5 shrink-0"></i>
                                            <span>Ship to: <?php echo htmlspecialchars($order['shipping_address']); ?></span>
                                        </p>
                                    <?php endif; ?>
                                    <p class="text-[11px] text-stone-400">Processed: <?php echo $order['order_date']; ?></p>
                                </div>
                                <div class="text-right w-full sm:w-auto border-t sm:border-0 pt-2 sm:pt-0">
                                    <span class="text-xs text-stone-400 block">Total Invoiced</span>
                                    <span class="text-base font-bold text-stone-900">RM<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <footer class="bg-stone-950 text-stone-400 text-xs py-5 px-6 border-t border-stone-800 font-mono mt-auto">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row justify-between items-center space-y-3 lg:space-y-0">
            <div class="flex flex-wrap justify-center lg:justify-start gap-x-6 gap-y-2 text-[11px]">
                <span class="flex items-center text-stone-300"><i class="fa-solid fa-circle text-[6px] text-emerald-500 mr-2"></i> Apache/XAMPP Pipeline Online</span>
                <span>User Context Lifecycle Model</span>
                <span>Cryptographic Vector: bcrypt(rounds=12)</span>
            </div>
            <div class="text-[11px] text-stone-500">RikuStore Dynamic PHP Model Framework &copy; 2026</div>
        </div>
    </footer>

</body>
</html>