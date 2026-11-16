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

// Get Books
$books_inventory = [];
$books_sql = "SELECT * FROM books";
$books_result = $conn->query($books_sql);
if ($books_result->num_rows > 0) {
    while($row = $books_result->fetch_assoc()) {
        $books_inventory[] = $row;
    }
}

$current_page = basename($_SERVER['SCRIPT_NAME']);
$books_per_shelf = 4;
$shelves = array_chunk($books_inventory, $books_per_shelf);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RikuStore - Digital Bookshelf</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/index.css">
    <style>
        .perspective-1000 {
            perspective: 1000px;
        }
        .rotate-y-12 {
            transform: rotateY(12deg);
        }
        .shelf-shadow {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
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

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 flex-grow w-full">
        <div class="text-center pb-12 mb-10">
            <h1 class="serif-title text-4xl md:text-5xl lg:text-6xl font-normal text-stone-900 mb-4 tracking-tight">Digital Bookshelf</h1>
            <div class="flex justify-center space-x-6 text-sm text-stone-400 font-medium">
            </div>
        </div>

        <div class="bookshelf-container space-y-24 sm:space-y-32">
            <?php foreach ($shelves as $shelf_books): ?>
            <div class="max-w-5xl mx-auto px-4">
                <div class="flex justify-center items-end gap-4 sm:gap-8 md:gap-12 pb-0 z-10 relative px-2 sm:px-8">
                    <?php foreach ($shelf_books as $book): ?>
                        <div class="group relative perspective-1000 w-28 sm:w-40 md:w-48 transition-transform duration-300 hover:-translate-y-4 cursor-pointer">
                            <!-- Book Cover -->
                            <div class="h-40 sm:h-60 md:h-72 w-full <?php echo empty($book['cover_image']) ? 'bg-gradient-to-br ' . htmlspecialchars($book['theme_gradient']) : ''; ?> rounded shadow-lg relative p-3 sm:p-4 flex flex-col justify-between border-l-[3px] sm:border-l-[5px] <?php echo htmlspecialchars($book['border_color']); ?> overflow-hidden transform-gpu transition-all duration-300 group-hover:shadow-2xl group-hover:rotate-y-12 group-hover:scale-105 before:content-[''] before:absolute before:inset-0 before:bg-gradient-to-r before:from-white/20 before:via-transparent before:to-black/20 z-20">
                                <?php if (!empty($book['cover_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="" class="absolute inset-0 w-full h-full object-cover -z-10 select-none">
                                <?php endif; ?>
                                <div class="absolute inset-0 book-spine-shadow"></div>
                                <?php if (empty($book['cover_image'])): ?>
                                    <i class="fa-solid <?php echo htmlspecialchars($book['icon']); ?> text-stone-100/10 text-4xl sm:text-6xl absolute right-2 bottom-2"></i>
                                    <h3 class="serif-title text-xs sm:text-base md:text-lg text-stone-100 leading-snug mt-1 shadow-sm font-semibold z-10"><?php echo htmlspecialchars($book['title']); ?></h3>
                                    <p class="text-[8px] sm:text-[10px] md:text-xs text-stone-200 mt-auto drop-shadow-md uppercase tracking-wider font-bold z-10"><?php echo htmlspecialchars($book['author']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Tooltip for Book details -->
                            <div class="absolute opacity-0 group-hover:opacity-100 transition-opacity duration-300 bottom-full mb-4 left-1/2 -translate-x-1/2 w-48 bg-stone-900 text-stone-100 text-xs p-3 rounded shadow-xl pointer-events-none z-50">
                                <p class="font-bold mb-1 text-sm"><?php echo htmlspecialchars($book['title']); ?></p>
                                <p class="text-stone-400 mb-2"><?php echo htmlspecialchars($book['author']); ?></p>
                                <div class="flex justify-between items-center mt-2 pt-2 border-t border-stone-700">
                                    <span class="font-bold text-amber-400">RM<?php echo number_format($book['price'], 2); ?></span>
                                    <span class="bg-stone-800 px-2 py-0.5 rounded text-[9px] uppercase"><?php echo htmlspecialchars($book['genre']); ?></span>
                                </div>
                            </div>
                            
                            <!-- Actions on hover -->
                            <?php if ($book['stock_quantity'] > 0): ?>
                            <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-all duration-300 z-30 translate-y-2 group-hover:translate-y-0 flex gap-1.5">
                                <form action="index.php" method="POST">
                                    <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                    <button type="submit" class="bg-amber-700 hover:bg-amber-800 text-white text-[10px] px-2.5 py-1.5 rounded-full font-bold shadow-lg whitespace-nowrap flex items-center gap-1">
                                        <i class="fa-solid fa-cart-plus"></i><span>Add</span>
                                    </button>
                                </form>
                                <a href="read.php?id=<?php echo $book['book_id']; ?>" class="bg-stone-800 hover:bg-stone-900 text-white text-[10px] px-2.5 py-1.5 rounded-full font-bold shadow-lg whitespace-nowrap flex items-center gap-1">
                                    <i class="fa-solid fa-book-open"></i><span>Read</span>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Shelf Base -->
                <div class="relative z-0 mt-[-2px]">
                    <!-- Top surface of the shelf -->
                    <div class="h-5 sm:h-6 bg-[#f1f1f1] rounded-sm shadow-inner border-b-4 border-stone-300 relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-b from-white/80 to-transparent"></div>
                        <div class="absolute top-0 left-0 right-0 h-[2px] bg-white/50"></div>
                    </div>
                    <!-- Front edge of the shelf -->
                    <div class="h-4 sm:h-5 bg-stone-200 shadow-xl mx-2 sm:mx-3 rounded-b-lg relative border-x border-stone-300/50">
                        <div class="absolute inset-0 bg-gradient-to-r from-stone-400/10 via-transparent to-stone-400/10"></div>
                    </div>
                    <!-- Shadow below shelf -->
                    <div class="h-12 bg-gradient-to-b from-stone-400/30 to-transparent mx-6 sm:mx-10 blur-xl rounded-full -mt-2"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
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
