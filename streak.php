<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

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

$user_data_sql = "SELECT login_streak, reading_streak, loyalty_points, protection_used_month, today_reading_seconds, last_read_date FROM users WHERE id = $user_id";
$user_data_res = $conn->query($user_data_sql);
$user_data = $user_data_res->fetch_assoc();

$login_streak = (int)($user_data['login_streak'] ?? 0);
$reading_streak = (int)($user_data['reading_streak'] ?? 0);
$total_loyalty_points = (int)($user_data['loyalty_points'] ?? 0);
$protection_active = (($user_data['protection_used_month'] ?? null) === date('Y-m'));

$today_reading_seconds = (int)($user_data['today_reading_seconds'] ?? 0);
if (!isset($_SESSION['last_reading_date']) || $_SESSION['last_reading_date'] !== date('Y-m-d')) {
    $today_reading_seconds = 0;
}
$reading_minutes = floor($today_reading_seconds / 60);
$reading_seconds_rem = $today_reading_seconds % 60;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RikuStore - Streaks & Rewards</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="flex flex-col min-h-screen text-stone-800 bg-stone-50">

    <nav class="bg-white/90 backdrop-blur-md border-b border-stone-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <img src="image/logo.png" alt="Riku Bookstore" class="h-16 object-contain bg-transparent">
                    </a>
                    <div class="hidden md:flex space-x-8 ml-12 text-sm font-medium text-stone-600">
                        <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">Home</a>
                        <a href="bookshelf.php" class="<?php echo ($current_page == 'bookshelf.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">Bookshelf</a>
                        <a href="genre.php" class="<?php echo ($current_page == 'genre.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">Genres</a>
                        <a href="newrelease.php" class="<?php echo ($current_page == 'newrelease.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">New Releases</a>
                        <a href="streak.php" class="<?php echo ($current_page == 'streak.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">Streaks</a>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="flex items-center space-x-2 bg-amber-50 px-3 py-1.5 rounded-full border border-amber-200">
                        <i class="fa-solid fa-coins text-amber-500"></i>
                        <span class="text-sm font-bold text-amber-800"><?php echo number_format($total_loyalty_points); ?> pts</span>
                    </div>
                    <a href="cart.php" class="relative text-stone-700 hover:text-amber-800 transition p-1">
                        <i class="fa-solid fa-bag-shopping text-xl"></i>
                        <span class="absolute -top-1 -right-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-amber-800 rounded-full"><?php echo $user_session['cart_count']; ?></span>
                    </a>
                    <div class="flex items-center space-x-3 border-l pl-5 border-stone-200">
                        <div class="text-right hidden md:block">
                            <p class="text-xs text-stone-400 font-medium"><?php echo htmlspecialchars($user_session['role']); ?></p>
                            <a href="profile.php" class="text-sm font-semibold text-stone-800 hover:text-amber-800 transition"><?php echo htmlspecialchars($user_session['name']); ?></a>
                        </div>
                        <a href="profile.php" class="w-10 h-10 rounded-full <?php echo ($current_page == 'profile.php') ? 'bg-amber-800 text-white ring-2 ring-amber-600' : 'bg-stone-100 text-stone-700 hover:bg-stone-200'; ?> flex items-center justify-center font-semibold border border-stone-200 transition">
                          <?php echo htmlspecialchars($user_session['initials']); ?>
                        </a>
                        <a href="logout.php" class="text-stone-400 hover:text-red-700 transition" title="Logout">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <header class="bg-stone-900 text-stone-100 py-16 px-4 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-amber-900/20 to-transparent"></div>
        <div class="max-w-7xl mx-auto relative z-10 flex flex-col items-center text-center">
            <h1 class="serif-title text-4xl sm:text-5xl font-normal mb-4">Streaks & Rewards</h1>
            <p class="text-stone-400 text-lg max-w-2xl">Claim your daily check-in, build your reading habits, and unlock exclusive badges, site credit, and loyalty points.</p>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex-grow w-full space-y-16">
        
        <!-- Daily Login Streak Section -->
        <section class="bg-white rounded-2xl border border-stone-200 p-8 shadow-sm">
            <div class="flex flex-col lg:flex-row gap-12">
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="serif-title text-2xl font-bold text-stone-900 flex items-center gap-3">
                            <i class="fa-solid fa-calendar-check text-amber-600"></i> Daily Check-in
                        </h2>
                        <?php if ($protection_active): ?>
                            <span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full border border-green-200">Protection Active</span>
                        <?php else: ?>
                            <span class="bg-red-100 text-red-800 text-xs font-bold px-3 py-1 rounded-full border border-red-200">Protection Used</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="bg-stone-50 border border-stone-200 rounded-xl p-6 flex flex-col items-center mb-8">
                        <p class="text-stone-500 mb-2 font-medium">Current Streak</p>
                        <div class="text-5xl font-black text-amber-700 mb-6 drop-shadow-sm flex items-baseline gap-2">
                            <?php echo $login_streak; ?> <span class="text-xl text-amber-900/60 font-medium">Days</span>
                        </div>
                        
                        <!-- 7-Day Visual Calendar -->
                        <div class="flex gap-2 sm:gap-4 mb-6">
                            <?php for($i = 1; $i <= 7; $i++): ?>
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center font-bold text-sm <?php echo $i <= $login_streak ? 'bg-amber-600 text-white shadow-md ring-2 ring-amber-200' : ($i == $login_streak + 1 ? 'bg-white border-2 border-amber-400 text-amber-700 border-dashed' : 'bg-stone-100 text-stone-400 border border-stone-200'); ?>">
                                        <?php if($i <= $login_streak): ?>
                                            <i class="fa-solid fa-check"></i>
                                        <?php else: ?>
                                            <?php echo $i; ?>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-[10px] uppercase font-bold text-stone-400">Day <?php echo $i; ?></span>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <button class="bg-stone-900 hover:bg-amber-800 text-white px-8 py-3 rounded-lg font-bold tracking-wide transition shadow-md w-full sm:w-auto">
                            Claim Today's Check-in
                        </button>
                    </div>

                    <!-- Next Milestone Progress -->
                    <div class="mb-6">
                        <div class="flex justify-between text-sm font-bold text-stone-700 mb-2">
                            <span>Progress to 7-Day Reward</span>
                            <span><?php echo $login_streak; ?> / 7</span>
                        </div>
                        <div class="h-3 w-full bg-stone-100 rounded-full overflow-hidden border border-stone-200">
                            <div class="h-full bg-amber-500 rounded-full transition-all duration-1000" style="width: <?php echo ($login_streak / 7) * 100; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3 text-xs text-stone-500 bg-blue-50 p-3 rounded-lg border border-blue-100">
                        <i class="fa-solid fa-shield-halved text-blue-500 text-lg"></i>
                        <p><strong>Streak Protection:</strong> You have 1 free missed day per month. You can also recover a broken streak using 500 loyalty points.</p>
                    </div>
                </div>

                <!-- Rewards List -->
                <div class="flex-1 lg:max-w-md bg-stone-50 p-6 rounded-xl border border-stone-200">
                    <h3 class="font-bold text-stone-800 mb-4 border-b border-stone-200 pb-2">Milestone Rewards</h3>
                    <ul class="space-y-4">
                        <li class="flex items-center justify-between p-3 bg-white rounded-lg border border-stone-200 <?php echo $login_streak >= 3 ? 'opacity-50 grayscale' : ''; ?>">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded bg-amber-100 flex items-center justify-center text-amber-700"><i class="fa-solid fa-coins"></i></div>
                                <div>
                                    <p class="font-bold text-sm text-stone-800">3-Day Streak</p>
                                    <p class="text-xs text-stone-500">50 Loyalty Points</p>
                                </div>
                            </div>
                            <?php if($login_streak >= 3): ?><i class="fa-solid fa-circle-check text-green-500"></i><?php endif; ?>
                        </li>
                        <li class="flex items-center justify-between p-3 bg-white rounded-lg border border-stone-200 <?php echo $login_streak >= 7 ? 'opacity-50 grayscale' : 'ring-1 ring-amber-400 shadow-sm'; ?>">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded bg-green-100 flex items-center justify-center text-green-700"><i class="fa-solid fa-money-bill-wave"></i></div>
                                <div>
                                    <p class="font-bold text-sm text-stone-800">7-Day Streak</p>
                                    <p class="text-xs text-stone-500">RM5 Site Credit</p>
                                </div>
                            </div>
                            <?php if($login_streak >= 7): ?><i class="fa-solid fa-circle-check text-green-500"></i><?php endif; ?>
                        </li>
                        <li class="flex items-center justify-between p-3 bg-white rounded-lg border border-stone-200">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded bg-blue-100 flex items-center justify-center text-blue-700"><i class="fa-solid fa-truck-fast"></i></div>
                                <div>
                                    <p class="font-bold text-sm text-stone-800">14-Day Streak</p>
                                    <p class="text-xs text-stone-500">Free Shipping Voucher</p>
                                </div>
                            </div>
                        </li>
                        <li class="flex items-center justify-between p-3 bg-white rounded-lg border border-stone-200">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded bg-purple-100 flex items-center justify-center text-purple-700"><i class="fa-solid fa-medal"></i></div>
                                <div>
                                    <p class="font-bold text-sm text-stone-800">30-Day Streak</p>
                                    <p class="text-xs text-stone-500">RM15 Credit + Badge</p>
                                </div>
                            </div>
                        </li>
                        <li class="flex items-center justify-between p-3 bg-white rounded-lg border border-stone-200">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded bg-rose-100 flex items-center justify-center text-rose-700"><i class="fa-solid fa-crown"></i></div>
                                <div>
                                    <p class="font-bold text-sm text-stone-800">60-Day Streak</p>
                                    <p class="text-xs text-stone-500">Premium Member Trial</p>
                                </div>
                            </div>
                        </li>
                        <li class="flex items-center justify-between p-3 bg-white rounded-lg border border-stone-200">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded bg-amber-200 flex items-center justify-center text-amber-900"><i class="fa-solid fa-trophy"></i></div>
                                <div>
                                    <p class="font-bold text-sm text-stone-800">100-Day Streak</p>
                                    <p class="text-xs text-stone-500">Special Badge + RM30 Credit</p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Reading Streaks Section -->
        <section class="bg-white rounded-2xl border border-stone-200 p-8 shadow-sm">
            <div class="flex flex-col lg:flex-row gap-12">
                <div class="flex-1 order-2 lg:order-1">
                    <h3 class="font-bold text-stone-800 mb-4 border-b border-stone-200 pb-2">Reading Milestones</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-stone-50 p-4 rounded-xl border border-stone-200 flex flex-col">
                            <span class="text-xs font-bold text-stone-400 uppercase tracking-wide mb-1">7 Days</span>
                            <span class="font-bold text-stone-800 mb-2">100 Points</span>
                            <div class="mt-auto h-1.5 w-full bg-stone-200 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 w-full"></div>
                            </div>
                        </div>
                        <div class="bg-stone-50 p-4 rounded-xl border border-stone-200 flex flex-col ring-1 ring-amber-400 shadow-sm relative overflow-hidden">
                            <div class="absolute top-0 right-0 bg-amber-400 text-amber-900 text-[9px] font-bold px-2 py-0.5 rounded-bl">NEXT</div>
                            <span class="text-xs font-bold text-stone-400 uppercase tracking-wide mb-1">14 Days</span>
                            <span class="font-bold text-stone-800 mb-2">Discount Coupon</span>
                            <div class="mt-auto h-1.5 w-full bg-stone-200 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500" style="width: <?php echo ($reading_streak / 14) * 100; ?>%"></div>
                            </div>
                        </div>
                        <div class="bg-stone-50 p-4 rounded-xl border border-stone-200 flex flex-col">
                            <span class="text-xs font-bold text-stone-400 uppercase tracking-wide mb-1">30 Days</span>
                            <span class="font-bold text-stone-800 mb-2">Free Shipping</span>
                            <div class="mt-auto h-1.5 w-full bg-stone-200 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 w-0"></div>
                            </div>
                        </div>
                        <div class="bg-stone-50 p-4 rounded-xl border border-stone-200 flex flex-col">
                            <span class="text-xs font-bold text-stone-400 uppercase tracking-wide mb-1">90 Days</span>
                            <span class="font-bold text-stone-800 mb-2">Exclusive Digital Book</span>
                            <div class="mt-auto h-1.5 w-full bg-stone-200 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 w-0"></div>
                            </div>
                        </div>
                        <div class="bg-stone-50 p-4 rounded-xl border border-stone-200 flex flex-col sm:col-span-2">
                            <span class="text-xs font-bold text-stone-400 uppercase tracking-wide mb-1">180 Days</span>
                            <span class="font-bold text-amber-700 flex items-center gap-2"><i class="fa-solid fa-gem text-amber-500"></i> VIP Reader Badge</span>
                        </div>
                    </div>
                </div>

                <div class="flex-1 order-1 lg:order-2">
                    <div class="flex items-center gap-3 mb-6">
                        <i class="fa-solid fa-book-open-reader text-3xl text-amber-700"></i>
                        <div>
                            <h2 class="serif-title text-2xl font-bold text-stone-900">Reading Streaks</h2>
                            <p class="text-stone-500 text-sm">Read books directly on the website to earn.</p>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center mb-6">
                        <p class="text-amber-800 font-bold mb-1">Daily Reading Goal</p>
                        <p class="text-sm text-amber-700/80 mb-4">Read for 10 minutes or 5 pages to extend streak</p>
                        
                        <div class="flex items-center justify-center gap-6 mb-4">
                            <div class="text-center">
                                <span class="block text-3xl font-black text-amber-600"><?php echo $reading_streak; ?></span>
                                <span class="text-xs font-bold text-stone-500 uppercase">Day Streak</span>
                            </div>
                            <div class="h-10 w-px bg-amber-200"></div>
                            <div class="text-center">
                                <span class="block text-3xl font-black text-stone-800"><?php echo $reading_minutes; ?><span class="text-lg">m</span> <?php echo str_pad($reading_seconds_rem, 2, '0', STR_PAD_LEFT); ?><span class="text-lg">s</span></span>
                                <span class="text-xs font-bold text-stone-500 uppercase">Today's Reading</span>
                            </div>
                        </div>
                        
                        <a href="bookshelf.php" class="inline-block bg-white text-amber-800 border border-amber-300 hover:bg-amber-100 px-6 py-2 rounded-lg font-bold transition shadow-sm text-sm">
                            Go to Bookshelf to Read
                        </a>
                    </div>
                    
                    <div class="text-xs text-stone-400 border border-stone-200 p-4 rounded-lg bg-stone-50">
                        <strong class="text-stone-600 flex items-center gap-1"><i class="fa-solid fa-circle-info"></i> Anti-Abuse System Active:</strong> We require active page interaction to track reading time. Idle sessions or automated reading manipulation will not count towards your goals.
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer class="bg-stone-950 text-stone-400 text-xs py-5 px-6 border-t border-stone-800 font-mono mt-auto">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row justify-between items-center space-y-3 lg:space-y-0">
            <div class="flex flex-wrap justify-center lg:justify-start gap-x-6 gap-y-2 text-[11px]">
                <span class="flex items-center text-stone-300"><i class="fa-solid fa-circle text-[6px] text-emerald-500 mr-2"></i> Apache/XAMPP Pipeline Online</span>
                <span>Active Isolation Engine: InnoDB</span>
                <span>Security Token: Session Cookie Checked</span>
            </div>
            <div class="text-[11px] text-stone-500">RikuStore Dynamic PHP Model Framework &copy; 2026</div>
        </div>
    </footer>

</body>
</html>
