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

// Get new releases (where release_date is NOT NULL)
$new_releases = [];
$releases_sql = "SELECT * FROM books WHERE release_date IS NOT NULL";
$releases_res = $conn->query($releases_sql);
if ($releases_res->num_rows > 0) {
    while($row = $releases_res->fetch_assoc()) {
        $new_releases[] = $row;
    }
}

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RikuStore - New Releases</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/newrelease.css">
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
                        <a href="bookshelf.php" class="<?php echo ($current_page == 'bookshelf.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">Bookshelf</a>
                        <a href="genre.php" class="<?php echo ($current_page == 'genre.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">Genres</a>
                        <a href="newrelease.php" class="<?php echo ($current_page == 'newrelease.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">New Releases</a>
                        <a href="streak.php" class="<?php echo ($current_page == 'streak.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">Streaks</a>
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

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 flex-grow w-full">
        <div class="mb-12 text-center lg:text-left">
            <span class="text-xs uppercase tracking-widest text-amber-800 font-bold bg-amber-100 px-2.5 py-1 rounded">Fresh In Stock</span>
            <h1 class="serif-title text-4xl font-normal text-stone-900 mb-3 mt-3">Newly Released Additions</h1>
            <p class="text-stone-500 text-sm max-w-xl">Fresh database drops and newly published theoretical materials curated for the current catalog block.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php foreach ($new_releases as $book): ?>
                <div class="bg-white border border-stone-200 rounded-2xl p-6 flex release-card relative overflow-hidden">
                    <div class="w-28 h-40 rounded shadow-md relative shrink-0 overflow-hidden select-none <?php echo empty($book['cover_image']) ? 'bg-gradient-to-b ' . htmlspecialchars($book['theme_gradient']) . ' p-4 flex flex-col justify-between border-l-4 ' . htmlspecialchars($book['border_color']) : ''; ?>">
                        <?php if (!empty($book['cover_image'])): ?>
                            <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="" class="w-full h-full object-cover">
                            <div class="absolute inset-0 spine-pattern"></div>
                        <?php else: ?>
                            <div class="absolute inset-0 spine-pattern"></div>
                            <i class="fa-solid <?php echo htmlspecialchars($book['icon']); ?> text-white/10 text-4xl self-end"></i>
                            <span class="text-[8px] tracking-widest font-mono text-stone-300 uppercase block mt-auto">NEW</span>
                        <?php endif; ?>
                    </div>
                    <div class="ml-6 flex flex-col justify-between py-1 z-10 flex-grow">
                        <div>
                            <span class="text-[10px] font-mono bg-stone-100 text-stone-600 px-2 py-0.5 rounded-md border border-stone-200">Arrived: <?php echo $book['release_date']; ?></span>
                            <h3 class="serif-title text-lg font-normal text-stone-900 mt-2 line-clamp-1"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="text-xs text-stone-400 mt-0.5">By <?php echo htmlspecialchars($book['author']); ?></p>
                            <p class="text-xs text-stone-500 mt-2 line-clamp-2 leading-relaxed" title="<?php echo htmlspecialchars($book['description']); ?>">
                                <?php echo htmlspecialchars($book['description'] ?? $book['sub_title']); ?>
                            </p>
                        </div>
                        <div class="flex items-center justify-between mt-4">
                            <span class="text-base font-bold text-stone-900">RM<?php echo number_format($book['price'], 2); ?></span>
                            <form action="index.php" method="POST">
                                <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                <button type="submit" class="bg-stone-900 hover:bg-amber-800 text-white text-[11px] px-3 py-2 rounded-lg font-semibold tracking-wide transition">Add To Cart</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer class="bg-stone-950 text-stone-400 text-xs py-5 px-6 border-t border-stone-800 font-mono mt-auto">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row justify-between items-center space-y-3 lg:space-y-0">
            <div class="flex flex-wrap justify-center lg:justify-start gap-x-6 gap-y-2 text-[11px]">
                <span class="flex items-center text-stone-300"><i class="fa-solid fa-circle text-[6px] text-emerald-500 mr-2"></i> Hot-Deployment Pipeline Active</span>
                <span>Interval: June 2026 Dynamic Drops</span>
            </div>
            <div class="text-[11px] text-stone-500">RikuStore Dynamic PHP Model Framework &copy; 2026</div>
        </div>
    </footer>

</body>
</html>