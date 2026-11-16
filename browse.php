<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Cart count
$cart_count_sql = "SELECT SUM(quantity) as count FROM cart WHERE user_id = $user_id";
$cart_count_res = $conn->query($cart_count_sql);
$cart_count_row = $cart_count_res->fetch_assoc();
$cart_count = $cart_count_row['count'] ?? 0;

$user_session = [
    'name'       => $_SESSION['name']     ?? 'Guest',
    'initials'   => $_SESSION['initials'] ?? 'G',
    'role'       => $_SESSION['role']     ?? 'Customer',
    'cart_count' => $cart_count
];

// Get requested genre slug
$genre_slug = isset($_GET['genre']) ? trim($_GET['genre']) : '';

// Fetch genre metadata
$genre_info = null;
if ($genre_slug !== '') {
    $safe_slug  = $conn->real_escape_string($genre_slug);
    $genre_meta = $conn->query("SELECT * FROM genres WHERE slug = '$safe_slug' LIMIT 1");
    if ($genre_meta && $genre_meta->num_rows > 0) {
        $genre_info = $genre_meta->fetch_assoc();
    }
}

// If no valid genre found, redirect back
if (!$genre_info) {
    header("Location: genre.php");
    exit();
}

// Fetch books whose `genre` column matches the genre slug
// (books table stores genre as slug value e.g. 'Fiction', 'Non-Fiction', etc.)
$genre_slug_safe = $conn->real_escape_string($genre_info['slug']);
$books_result = $conn->query("SELECT * FROM books WHERE genre = '$genre_slug_safe' ORDER BY title ASC");
$books = [];
if ($books_result && $books_result->num_rows > 0) {
    while ($row = $books_result->fetch_assoc()) {
        $books[] = $row;
    }
}

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RikuStore – <?php echo htmlspecialchars($genre_info['name']); ?> Books</title>
    <meta name="description" content="Browse all <?php echo htmlspecialchars($genre_info['name']); ?> books available at RikuStore.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .serif-title { font-family: 'Playfair Display', serif; }
        .book-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .book-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px rgba(0,0,0,0.12); }
        .book-cover-img { transition: transform 0.4s ease; }
        .book-card:hover .book-cover-img { transform: scale(1.04); }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .book-card { animation: fadeInUp 0.4s ease both; }
        .genre-hero {
            background: linear-gradient(135deg, #1c1917 0%, #292524 60%, #1c1917 100%);
        }
        .spine-line {
            background: repeating-linear-gradient(
                to right,
                rgba(255,255,255,0.04) 0px,
                rgba(255,255,255,0.04) 1px,
                transparent 1px,
                transparent 40px
            );
        }
    </style>
</head>
<body class="flex flex-col min-h-screen text-stone-800 bg-stone-50">

    <!-- Navigation -->
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
                        <a href="genre.php" class="text-amber-800 font-semibold border-b-2 border-amber-800 px-1 py-2 transition">Genres</a>
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
                            <p class="text-xs text-stone-400 font-medium"><?php echo htmlspecialchars($user_session['role']); ?></p>
                            <p class="text-sm font-semibold text-stone-800"><?php echo htmlspecialchars($user_session['name']); ?></p>
                        </div>
                        <a href="profile.php" class="w-10 h-10 rounded-full bg-stone-100 flex items-center justify-center text-stone-700 font-semibold border border-stone-200 hover:bg-stone-200 transition">
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

    <!-- Genre Hero Banner -->
    <div class="genre-hero spine-line text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 flex items-center gap-8">
            <!-- Genre Book Spine Icon -->
            <div class="hidden sm:flex w-24 h-32 bg-gradient-to-b <?php echo htmlspecialchars($genre_info['bg_gradient']); ?> rounded-r-lg shadow-2xl items-center justify-center border-l-4 <?php echo htmlspecialchars($genre_info['border_color']); ?> shrink-0">
                <i class="fa-solid <?php echo htmlspecialchars($genre_info['icon']); ?> text-white/60 text-3xl"></i>
            </div>
            <div class="flex-1">
                <!-- Breadcrumb -->
                <nav class="flex items-center gap-2 text-stone-400 text-xs mb-4 font-mono">
                    <a href="genre.php" class="hover:text-amber-400 transition flex items-center gap-1">
                        <i class="fa-solid fa-layer-group text-[10px]"></i> Genre Shelves
                    </a>
                    <i class="fa-solid fa-chevron-right text-[8px] text-stone-600"></i>
                    <span class="text-stone-300"><?php echo htmlspecialchars($genre_info['name']); ?></span>
                </nav>
                <h1 class="serif-title text-4xl md:text-5xl font-normal mb-2"><?php echo htmlspecialchars($genre_info['name']); ?></h1>
                <p class="text-stone-400 text-sm max-w-xl leading-relaxed"><?php echo htmlspecialchars($genre_info['description']); ?></p>
                <div class="flex items-center gap-4 mt-5">
                    <span class="inline-flex items-center gap-1.5 bg-white/10 border border-white/20 text-white text-xs font-mono px-3 py-1.5 rounded-full">
                        <i class="fa-solid fa-books text-amber-400 text-[10px]"></i>
                        <?php echo count($books); ?> book<?php echo count($books) !== 1 ? 's' : ''; ?> found
                    </span>
                    <a href="genre.php" class="text-stone-400 hover:text-amber-400 text-xs font-medium transition flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Genres
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Books Grid -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 flex-grow w-full">

        <?php if (count($books) === 0): ?>
        <!-- Empty State -->
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="w-24 h-24 rounded-full bg-stone-100 flex items-center justify-center mb-6">
                <i class="fa-solid fa-book-open text-stone-300 text-4xl"></i>
            </div>
            <h2 class="serif-title text-2xl text-stone-700 mb-2">No books found</h2>
            <p class="text-stone-400 text-sm max-w-sm">There are currently no books shelved under <strong><?php echo htmlspecialchars($genre_info['name']); ?></strong>. Check back soon!</p>
            <a href="genre.php" class="mt-8 inline-flex items-center gap-2 bg-amber-700 hover:bg-amber-800 text-white text-sm font-semibold px-6 py-3 rounded-full transition shadow-md">
                <i class="fa-solid fa-arrow-left"></i> Browse Other Genres
            </a>
        </div>

        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <?php foreach ($books as $i => $book): ?>
            <div class="book-card bg-white rounded-2xl border border-stone-200 overflow-hidden shadow-sm group"
                 style="animation-delay: <?php echo $i * 60; ?>ms">

                <!-- Cover -->
                <div class="relative h-60 bg-gradient-to-br <?php echo htmlspecialchars($book['theme_gradient']); ?> overflow-hidden border-b border-stone-100">
                    <?php if (!empty($book['cover_image'])): ?>
                        <img src="<?php echo htmlspecialchars($book['cover_image']); ?>"
                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                             class="book-cover-img absolute inset-0 w-full h-full object-cover">
                    <?php else: ?>
                        <i class="fa-solid <?php echo htmlspecialchars($book['icon']); ?> text-white/10 text-7xl absolute bottom-4 right-4"></i>
                        <div class="absolute inset-0 p-5 flex flex-col justify-between">
                            <h3 class="serif-title text-white text-lg font-semibold leading-snug"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="text-white/60 text-xs uppercase tracking-widest font-bold"><?php echo htmlspecialchars($book['author']); ?></p>
                        </div>
                    <?php endif; ?>
                    <!-- Genre badge overlay -->
                    <div class="absolute top-3 left-3">
                        <span class="bg-black/50 backdrop-blur-sm text-white/90 text-[10px] font-mono px-2.5 py-1 rounded-full uppercase tracking-wider border border-white/10">
                            <?php echo htmlspecialchars($book['genre']); ?>
                        </span>
                    </div>
                </div>

                <!-- Info -->
                <div class="p-5 flex flex-col flex-1">
                    <h2 class="serif-title text-stone-900 text-base font-semibold leading-snug mb-1 group-hover:text-amber-800 transition">
                        <?php echo htmlspecialchars($book['title']); ?>
                    </h2>
                    <p class="text-stone-400 text-xs mb-3"><?php echo htmlspecialchars($book['author']); ?></p>

                    <?php if (!empty($book['description'])): ?>
                    <p class="text-stone-500 text-xs leading-relaxed mb-4 line-clamp-3">
                        <?php echo htmlspecialchars($book['description']); ?>
                    </p>
                    <?php endif; ?>

                    <div class="mt-auto flex items-center justify-between pt-4 border-t border-stone-100">
                        <span class="text-amber-800 font-bold text-base">RM<?php echo number_format($book['price'], 2); ?></span>
                        <?php if ($book['stock_quantity'] > 0): ?>
                            <span class="text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full font-semibold">In Stock</span>
                        <?php else: ?>
                            <span class="text-[10px] text-red-600 bg-red-50 border border-red-200 px-2 py-0.5 rounded-full font-semibold">Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2 mt-4">
                        <?php if ($book['stock_quantity'] > 0): ?>
                        <form action="index.php" method="POST" class="flex-1">
                            <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                            <button type="submit" id="add-to-cart-<?php echo $book['book_id']; ?>"
                                    class="w-full bg-amber-700 hover:bg-amber-800 text-white text-xs font-bold py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-cart-plus"></i> Add to Cart
                            </button>
                        </form>
                        <?php endif; ?>
                        <a href="read.php?id=<?php echo $book['book_id']; ?>"
                           id="read-btn-<?php echo $book['book_id']; ?>"
                           class="<?php echo $book['stock_quantity'] > 0 ? 'flex-none' : 'flex-1'; ?> bg-stone-800 hover:bg-stone-900 text-white text-xs font-bold py-2.5 px-3 rounded-xl transition flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-book-open"></i><?php echo $book['stock_quantity'] > 0 ? '' : ' Read'; ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </main>

    <footer class="bg-stone-950 text-stone-400 text-xs py-5 px-6 border-t border-stone-800 font-mono mt-auto">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row justify-between items-center space-y-3 lg:space-y-0">
            <div class="flex flex-wrap justify-center lg:justify-start gap-x-6 gap-y-2 text-[11px]">
                <span class="flex items-center text-stone-300"><i class="fa-solid fa-circle text-[6px] text-emerald-500 mr-2"></i> Apache/XAMPP Pipeline Online</span>
                <span>Genre Filter: <?php echo htmlspecialchars($genre_info['name']); ?></span>
                <span>Results: <?php echo count($books); ?> records found</span>
            </div>
            <div class="text-[11px] text-stone-500">RikuStore Dynamic PHP Model Framework &copy; 2026</div>
        </div>
    </footer>

</body>
</html>
