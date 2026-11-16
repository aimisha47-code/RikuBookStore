<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION['role'] ?? '') === 'Admin') {
    header("Location: admin_home.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $book_id = (int)$_POST['book_id'];
    $check_cart = "SELECT * FROM cart WHERE user_id = $user_id AND book_id = $book_id";
    $res = $conn->query($check_cart);
    if ($res->num_rows > 0) {
        $conn->query("UPDATE cart SET quantity = quantity + 1 WHERE user_id = $user_id AND book_id = $book_id");
    } else {
        $conn->query("INSERT INTO cart (user_id, book_id, quantity) VALUES ($user_id, $book_id, 1)");
    }
    header("Location: index.php");
    exit();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RikuStore - Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/index.css">
    
    <style>
        .aesthetic-hero {
            background-color: #1a1d1a;
            background-image: 
                radial-gradient(circle at 80% 30%, rgba(235, 200, 130, 0.14) 0%, transparent 55%),
                radial-gradient(circle at 15% 70%, rgba(235, 220, 185, 0.05) 0%, transparent 45%),
                linear-gradient(rgba(235, 220, 185, 0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(235, 220, 185, 0.025) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 28px 28px, 28px 28px;
        }
        .aesthetic-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            opacity: 0.04;
            pointer-events: none;
            background-image: 
                linear-gradient(45deg, rgba(235, 220, 185, 0.4) 1px, transparent 1px),
                linear-gradient(-45deg, rgba(235, 220, 185, 0.4) 1px, transparent 1px);
            background-size: 100px 100px;
        }

        /* Quote card cycling animation */
        .quote-card {
            animation: quoteFloat 6s ease-in-out infinite;
        }
        .quote-card:nth-child(2) { animation-delay: 2s; }
        .quote-card:nth-child(3) { animation-delay: 4s; }

        @keyframes quoteFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-6px); }
        }

        /* Cycling quotes */
        .quotes-rotator .quote-slide {
            display: none;
            animation: fadeInQuote 0.8s ease;
        }
        .quotes-rotator .quote-slide.active {
            display: block;
        }
        @keyframes fadeInQuote {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
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
                        <a href="newrelease.php" class="<?php echo ($current_page == 'new-releases.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">New Releases</a>
                        <a href="streak.php" class="<?php echo ($current_page == 'streak.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">Streaks</a>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="relative hidden sm:block">
                        <input type="text" placeholder="Search title, author..." class="w-72 pl-10 pr-4 py-2.5 bg-stone-50 border border-stone-200 rounded-lg text-sm focus:outline-none focus:border-amber-700 transition">
                        <div class="absolute left-3.5 top-3.5 text-stone-400"><i class="fa-solid fa-magnifying-glass text-xs"></i></div>
                    </div>
                    <a href="cart.php" class="relative text-stone-700 hover:text-amber-800 transition p-1">
                        <i class="fa-solid fa-bag-shopping text-xl"></i>
                        <span class="absolute -top-1 -right-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-amber-800 rounded-full"><?php echo $user_session['cart_count']; ?></span>
                    </a>
                    <div class="flex items-center space-x-3 border-l pl-5 border-stone-200">
                        <div class="text-right hidden md:block">
                            <p class="text-xs text-stone-400 font-medium"><?php echo $user_session['role']; ?></p>
                            <a href="profile.php" class="text-sm font-semibold text-stone-800 hover:text-amber-800 transition"><?php echo $user_session['name']; ?></a>
                        </div>
                        <a href="profile.php" class="w-10 h-10 rounded-full <?php echo ($current_page == 'profile.php') ? 'bg-amber-800 text-white ring-2 ring-amber-600' : 'bg-stone-100 text-stone-700 hover:bg-stone-200'; ?> flex items-center justify-center font-semibold border border-stone-200 transition">
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

    <!-- Hero Banner -->
    <header class="aesthetic-hero text-stone-100 py-20 px-4 relative overflow-hidden border-b border-stone-800/40">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row items-center gap-12 relative z-10">

            <!-- LEFT: Text -->
            <div class="flex-1 text-center lg:text-left">
                <span class="inline-flex items-center gap-2 text-[11px] uppercase tracking-widest text-amber-400/90 font-bold bg-amber-400/10 px-3 py-1.5 rounded-md border border-amber-400/20">
                    <i class="fa-solid fa-book-open text-amber-400"></i> Welcome to Riku Bookstore
                </span>
                <h1 class="serif-title text-4xl sm:text-5xl lg:text-[3.4rem] font-normal mt-5 mb-5 leading-[1.15] tracking-tight">
                    Every page holds<br>a world worth <span class="italic text-amber-300">exploring.</span>
                </h1>
                <p class="text-stone-400 text-sm sm:text-base mb-8 max-w-lg leading-relaxed">
                    Discover stories, wisdom, and wonder — hand-picked from the shelves of Riku Bookstore. Your next favourite book is already waiting.
                </p>

            </div>

            <!-- RIGHT: Rotating Book Quotes Panel -->
            <div class="hidden lg:flex flex-col gap-4 w-[380px] shrink-0">

                <!-- Quote rotator card -->
                <div class="bg-white/5 border border-amber-300/20 backdrop-blur-sm rounded-2xl p-6 shadow-xl relative overflow-hidden quote-card">
                    <i class="fa-solid fa-quote-left text-amber-400/20 text-5xl absolute -top-1 -left-1"></i>
                    <div class="quotes-rotator min-h-[90px]" id="quoteRotator">
                        <div class="quote-slide active">
                            <p class="text-stone-200 text-sm leading-relaxed italic">&ldquo;A reader lives a thousand lives before he dies. The man who never reads lives only one.&rdquo;</p>
                            <span class="block mt-3 text-amber-400 text-xs font-semibold tracking-wide">&mdash; George R.R. Martin</span>
                        </div>
                        <div class="quote-slide">
                            <p class="text-stone-200 text-sm leading-relaxed italic">&ldquo;Not all those who wander are lost.&rdquo;</p>
                            <span class="block mt-3 text-amber-400 text-xs font-semibold tracking-wide">&mdash; J.R.R. Tolkien, The Fellowship of the Ring</span>
                        </div>
                        <div class="quote-slide">
                            <p class="text-stone-200 text-sm leading-relaxed italic">&ldquo;So many books, so little time.&rdquo;</p>
                            <span class="block mt-3 text-amber-400 text-xs font-semibold tracking-wide">&mdash; Frank Zappa</span>
                        </div>
                        <div class="quote-slide">
                            <p class="text-stone-200 text-sm leading-relaxed italic">&ldquo;That's the thing about books. They let you travel without moving your feet.&rdquo;</p>
                            <span class="block mt-3 text-amber-400 text-xs font-semibold tracking-wide">&mdash; Jhumpa Lahiri</span>
                        </div>
                        <div class="quote-slide">
                            <p class="text-stone-200 text-sm leading-relaxed italic">&ldquo;The more that you read, the more things you will know. The more that you learn, the more places you'll go.&rdquo;</p>
                            <span class="block mt-3 text-amber-400 text-xs font-semibold tracking-wide">&mdash; Dr. Seuss</span>
                        </div>
                    </div>
                    <!-- dot indicators -->
                    <div class="flex gap-1.5 mt-5" id="quoteDots"></div>
                </div>

                <!-- Two small stat chips -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 flex items-center gap-3">
                        <span class="w-9 h-9 rounded-full bg-amber-400/15 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-books text-amber-400 text-sm"></i>
                        </span>
                        <div>
                            <p class="text-stone-400 text-[10px] uppercase tracking-wider">In Catalog</p>
                            <p class="text-stone-100 font-bold text-sm"><?php echo count($books_inventory); ?> Titles</p>
                        </div>
                    </div>
                    <div class="bg-white/5 border border-white/10 rounded-xl px-4 py-3 flex items-center gap-3">
                        <span class="w-9 h-9 rounded-full bg-amber-400/15 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-star text-amber-400 text-sm"></i>
                        </span>
                        <div>
                            <p class="text-stone-400 text-[10px] uppercase tracking-wider">Genres</p>
                            <p class="text-stone-100 font-bold text-sm">Multi-Genre</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </header>

    <script>
    (function(){
        const slides = document.querySelectorAll('#quoteRotator .quote-slide');
        const dotsEl = document.getElementById('quoteDots');
        let cur = 0;

        // Build dots
        slides.forEach((_, i) => {
            const d = document.createElement('button');
            d.className = 'w-1.5 h-1.5 rounded-full transition-all duration-300 ' + (i === 0 ? 'bg-amber-400 w-4' : 'bg-white/20');
            d.addEventListener('click', () => show(i));
            dotsEl.appendChild(d);
        });

        function show(n) {
            slides[cur].classList.remove('active');
            dotsEl.children[cur].className = 'w-1.5 h-1.5 rounded-full transition-all duration-300 bg-white/20';
            cur = n % slides.length;
            slides[cur].classList.add('active');
            dotsEl.children[cur].className = 'w-4 h-1.5 rounded-full transition-all duration-300 bg-amber-400';
        }

        setInterval(() => show(cur + 1), 4500);
    })();
    </script>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 flex-grow">
        <div class="flex flex-wrap items-center justify-between border-b border-stone-200 pb-5 mb-10">
            <h2 class="serif-title text-3xl font-normal text-stone-900">Curated Bookstore Catalog</h2>
            <div class="text-xs text-stone-400 font-medium">Active Index optimization parameter: <span class="bg-stone-100 text-stone-600 px-2 py-1 rounded font-mono font-bold">IDX_Books_Genre</span></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php foreach ($books_inventory as $book): ?>
                <?php $is_out_of_stock = ($book['stock_quantity'] === 0); ?>
                <div class="bg-white rounded-xl border border-stone-200 p-4 book-card flex flex-col justify-between">
                    <div>
                        <div class="h-60 w-full rounded shadow-md relative overflow-hidden mb-5 <?php echo $is_out_of_stock ? 'opacity-50' : ''; ?>">
                            <?php if (!empty($book['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover">
                                <div class="absolute inset-0 book-spine-shadow"></div>
                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br <?php echo $book['theme_gradient']; ?> p-4 flex flex-col justify-between border-l-4 <?php echo $book['border_color']; ?>">
                                    <div class="absolute inset-0 book-spine-shadow"></div>
                                    <i class="fa-solid <?php echo $book['icon']; ?> text-stone-100/10 text-6xl absolute right-2 bottom-2"></i>
                                    <span class="text-[9px] tracking-widest text-stone-300 uppercase font-bold"><?php echo $is_out_of_stock ? 'Out of Stock' : 'Available Copy'; ?></span>
                                    <h3 class="serif-title text-base text-stone-100 leading-snug mt-1"><?php echo htmlspecialchars($book['sub_title']); ?></h3>
                                    <p class="text-[10px] text-stone-400 mt-auto"><?php echo htmlspecialchars($book['author']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <span class="text-[11px] font-bold text-amber-800 tracking-wider uppercase bg-amber-50 px-2 py-0.5 rounded border border-amber-200/50"><?php echo htmlspecialchars($book['genre']); ?></span>
                        <h4 class="font-bold text-stone-900 text-base mt-2 line-clamp-1 <?php echo $is_out_of_stock ? 'text-stone-400 line-through' : ''; ?>"><?php echo htmlspecialchars($book['title']); ?></h4>
                        <p class="text-xs text-stone-400 mt-0.5">By <?php echo htmlspecialchars($book['author']); ?></p>
                        <p class="text-xs text-stone-500 mt-2 line-clamp-3 leading-relaxed" title="<?php echo htmlspecialchars($book['description']); ?>">
                            <?php echo htmlspecialchars($book['description'] ?? $book['sub_title']); ?>
                        </p>
                        <p class="text-[10px] text-stone-400 mt-2 font-mono">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-stone-100 flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-xs text-stone-400 font-medium">Price Tag</span>
                            <span class="text-xl font-bold <?php echo $is_out_of_stock ? 'text-stone-400' : 'text-stone-900'; ?>">RM<?php echo number_format($book['price'], 2); ?></span>
                        </div>
                        <?php if ($is_out_of_stock): ?>
                            <button disabled class="bg-stone-200 text-stone-400 text-xs px-4 py-2.5 rounded-lg font-semibold cursor-not-allowed">Restocking</button>
                        <?php else: ?>
                            <form action="" method="POST">
                                <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                <button type="submit" class="bg-stone-900 hover:bg-amber-800 text-white text-xs px-4 py-2.5 rounded-lg font-semibold tracking-wide transition flex items-center space-x-2 shadow-sm">
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    <span>Add to Cart</span>
                                </button>
                            </form>
                        <?php endif; ?>
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