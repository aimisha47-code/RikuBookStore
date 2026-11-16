<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$user_session = [
    'name' => $_SESSION['name'] ?? 'Admin',
    'initials' => $_SESSION['initials'] ?? 'A',
    'role' => $_SESSION['role'] ?? 'Admin',
];

// Get statistics
$stats = [
    'total_users' => 0,
    'total_books' => 0,
    'total_orders' => 0,
    'total_revenue' => 0
];

$user_res = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='User'");
if($user_res) $stats['total_users'] = $user_res->fetch_assoc()['count'];

$book_res = $conn->query("SELECT COUNT(*) as count FROM books");
if($book_res) $stats['total_books'] = $book_res->fetch_assoc()['count'];

$order_res = $conn->query("SELECT COUNT(*) as count, SUM(total_amount) as revenue FROM orders");
if($order_res) {
    $row = $order_res->fetch_assoc();
    $stats['total_orders'] = $row['count'] ?? 0;
    $stats['total_revenue'] = $row['revenue'] ?? 0;
}

// Recent orders
$recent_orders = [];
$r_order_res = $conn->query("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC LIMIT 5");
if ($r_order_res->num_rows > 0) {
    while($row = $r_order_res->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RikuStore - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin_home.css">
</head>
<body class="flex flex-col min-h-screen">

    <nav class="bg-stone-900 border-b border-stone-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center">
                    <div class="flex items-center space-x-3 text-white font-bold text-2xl tracking-tight">
                        <i class="fa-solid fa-shield-halved text-amber-500"></i>
                        <span class="serif-title font-semibold italic text-2xl">RikuStore Admin</span>
                    </div>
                    <div class="hidden md:flex items-center space-x-1 text-sm ml-6">
                        <a href="admin_home.php" class="px-3 py-2 rounded-lg text-amber-400 bg-amber-500/10 border border-amber-500/20 flex items-center gap-2">
                            <i class="fa-solid fa-gauge-high text-xs"></i> Dashboard
                        </a>
                        <a href="admin_books.php" class="px-3 py-2 rounded-lg text-stone-400 hover:text-white hover:bg-stone-800 transition flex items-center gap-2">
                            <i class="fa-solid fa-book-open text-xs"></i> Books
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="flex items-center space-x-3 border-l pl-5 border-stone-700">
                        <div class="text-right hidden md:block">
                            <p class="text-xs text-stone-400 font-medium"><?php echo $user_session['role']; ?></p>
                            <p class="text-sm font-semibold text-white"><?php echo $user_session['name']; ?></p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-stone-800 flex items-center justify-center text-stone-300 font-semibold border border-stone-700">
                          <?php echo $user_session['initials']; ?>
                        </div>
                        <a href="logout.php" class="text-stone-400 hover:text-red-500 transition ml-2" title="Logout">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex-grow w-full">
        <div class="mb-8">
            <h1 class="serif-title text-3xl font-normal text-white mb-2">Control Center</h1>
            <p class="text-stone-400 text-sm">System administration and overview matrix.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="bg-stone-900 border border-stone-800 rounded-xl p-5">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs text-stone-400 font-bold uppercase tracking-wider mb-1">Total Users</p>
                        <h3 class="text-2xl font-mono font-bold text-white"><?php echo $stats['total_users']; ?></h3>
                    </div>
                    <div class="w-8 h-8 rounded-lg bg-stone-800 flex items-center justify-center text-stone-400">
                        <i class="fa-solid fa-users text-sm"></i>
                    </div>
                </div>
            </div>
            <div class="bg-stone-900 border border-stone-800 rounded-xl p-5">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs text-stone-400 font-bold uppercase tracking-wider mb-1">Inventory</p>
                        <h3 class="text-2xl font-mono font-bold text-white"><?php echo $stats['total_books']; ?></h3>
                    </div>
                    <div class="w-8 h-8 rounded-lg bg-stone-800 flex items-center justify-center text-stone-400">
                        <i class="fa-solid fa-book text-sm"></i>
                    </div>
                </div>
            </div>
            <div class="bg-stone-900 border border-stone-800 rounded-xl p-5">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs text-stone-400 font-bold uppercase tracking-wider mb-1">Total Orders</p>
                        <h3 class="text-2xl font-mono font-bold text-white"><?php echo $stats['total_orders']; ?></h3>
                    </div>
                    <div class="w-8 h-8 rounded-lg bg-stone-800 flex items-center justify-center text-stone-400">
                        <i class="fa-solid fa-box text-sm"></i>
                    </div>
                </div>
            </div>
            <div class="bg-stone-900 border border-stone-800 rounded-xl p-5">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs text-stone-400 font-bold uppercase tracking-wider mb-1">Revenue</p>
                        <h3 class="text-2xl font-mono font-bold text-amber-500">RM<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                    </div>
                    <div class="w-8 h-8 rounded-lg bg-stone-800 flex items-center justify-center text-amber-500">
                        <i class="fa-solid fa-coins text-sm"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-6">
            <a href="admin_books.php"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-stone-900 font-bold rounded-xl text-sm transition shadow-lg shadow-amber-500/20 hover:-translate-y-0.5">
                <i class="fa-solid fa-book-open"></i> Manage Book Inventory
            </a>
        </div>

        <div class="bg-stone-900 border border-stone-800 rounded-xl p-6">
            <h2 class="serif-title text-xl font-normal text-white mb-6 border-b border-stone-800 pb-3">Recent Transactions</h2>
            <?php if (empty($recent_orders)): ?>
                <p class="text-stone-500 text-sm">No transactions found in the database.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-stone-400 font-mono text-xs border-b border-stone-800">
                                <th class="pb-3 pr-4 font-normal uppercase tracking-wider">Order ID</th>
                                <th class="pb-3 pr-4 font-normal uppercase tracking-wider">Customer</th>
                                <th class="pb-3 pr-4 font-normal uppercase tracking-wider">Date</th>
                                <th class="pb-3 pr-4 font-normal uppercase tracking-wider">Status</th>
                                <th class="pb-3 font-normal uppercase tracking-wider text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-800">
                            <?php foreach($recent_orders as $o): ?>
                                <tr class="hover:bg-stone-800/50 transition">
                                    <td class="py-4 pr-4 font-mono text-white"><?php echo $o['order_id']; ?></td>
                                    <td class="py-4 pr-4 text-stone-300"><?php echo htmlspecialchars($o['customer_name']); ?></td>
                                    <td class="py-4 pr-4 text-stone-400 text-[11px]"><?php echo $o['order_date']; ?></td>
                                    <td class="py-4 pr-4">
                                        <span class="px-2 py-1 rounded text-[10px] font-medium border <?php echo $o['status_color']; ?> bg-opacity-20 border-opacity-30">
                                            <?php echo $o['status']; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 text-right font-mono font-bold text-white">RM<?php echo number_format($o['total_amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</body>
</html>
