<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$user_session = [
    'name'     => $_SESSION['name']     ?? 'Admin',
    'initials' => $_SESSION['initials'] ?? 'A',
    'role'     => $_SESSION['role']     ?? 'Admin',
];

$message      = '';
$message_type = '';

// ─── Handle POST actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── ADD BOOK ─────────────────────────────────────────────────────────────
    if ($action === 'add') {
        $title           = trim($_POST['title']           ?? '');
        $sub_title       = trim($_POST['sub_title']       ?? '');
        $author          = trim($_POST['author']          ?? '');
        $isbn            = trim($_POST['isbn']            ?? '');
        $genre           = trim($_POST['genre']           ?? '');
        $price           = (float)($_POST['price']        ?? 0);
        $stock_quantity  = (int)($_POST['stock_quantity'] ?? 0);
        $theme_gradient  = trim($_POST['theme_gradient']  ?? 'from-stone-900 to-stone-950');
        $border_color    = trim($_POST['border_color']    ?? 'border-stone-700');
        $icon            = trim($_POST['icon']            ?? 'fa-book');
        $description     = trim($_POST['description']     ?? '');
        $release_date    = trim($_POST['release_date']    ?? '');

        // Handle cover image upload
        $cover_image = '';
        if (!empty($_FILES['cover_image']['name'])) {
            $ext      = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $filename    = 'image/' . uniqid('book_') . '.' . $ext;
                $upload_path = __DIR__ . '/' . $filename;
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                    $cover_image = $filename;
                }
            }
        }

        $stmt = $conn->prepare("INSERT INTO books (title, sub_title, author, isbn, genre, price, stock_quantity, theme_gradient, border_color, icon, cover_image, description, release_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssdssssss", $title, $sub_title, $author, $isbn, $genre, $price, $stock_quantity, $theme_gradient, $border_color, $icon, $cover_image, $description, $release_date);

        if ($stmt->execute()) {
            $message      = "Book \"" . htmlspecialchars($title) . "\" added successfully.";
            $message_type = 'success';
        } else {
            $message      = "Error adding book: " . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }

    // ── EDIT BOOK ─────────────────────────────────────────────────────────────
    if ($action === 'edit') {
        $book_id         = (int)($_POST['book_id']        ?? 0);
        $title           = trim($_POST['title']           ?? '');
        $sub_title       = trim($_POST['sub_title']       ?? '');
        $author          = trim($_POST['author']          ?? '');
        $isbn            = trim($_POST['isbn']            ?? '');
        $genre           = trim($_POST['genre']           ?? '');
        $price           = (float)($_POST['price']        ?? 0);
        $stock_quantity  = (int)($_POST['stock_quantity'] ?? 0);
        $theme_gradient  = trim($_POST['theme_gradient']  ?? '');
        $border_color    = trim($_POST['border_color']    ?? '');
        $icon            = trim($_POST['icon']            ?? 'fa-book');
        $description     = trim($_POST['description']     ?? '');
        $release_date    = trim($_POST['release_date']    ?? '');
        $existing_cover  = trim($_POST['existing_cover']  ?? '');

        // Handle cover image upload
        $cover_image = $existing_cover;
        if (!empty($_FILES['cover_image']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $filename    = 'image/' . uniqid('book_') . '.' . $ext;
                $upload_path = __DIR__ . '/' . $filename;
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                    // Delete old image if it exists
                    if ($existing_cover && file_exists(__DIR__ . '/' . $existing_cover)) {
                        @unlink(__DIR__ . '/' . $existing_cover);
                    }
                    $cover_image = $filename;
                }
            }
        }

        $stmt = $conn->prepare("UPDATE books SET title=?, sub_title=?, author=?, isbn=?, genre=?, price=?, stock_quantity=?, theme_gradient=?, border_color=?, icon=?, cover_image=?, description=?, release_date=? WHERE book_id=?");
        $stmt->bind_param("ssssssdssssssl", $title, $sub_title, $author, $isbn, $genre, $price, $stock_quantity, $theme_gradient, $border_color, $icon, $cover_image, $description, $release_date, $book_id);

        if ($stmt->execute()) {
            $message      = "Book \"" . htmlspecialchars($title) . "\" updated successfully.";
            $message_type = 'success';
        } else {
            $message      = "Error updating book: " . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }

    // ── DELETE BOOK ───────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $book_id = (int)($_POST['book_id'] ?? 0);
        // Fetch cover image to delete the file
        $res = $conn->query("SELECT title, cover_image FROM books WHERE book_id = $book_id");
        if ($res && $row = $res->fetch_assoc()) {
            $deleted_title = $row['title'];
            if ($row['cover_image'] && file_exists(__DIR__ . '/' . $row['cover_image'])) {
                @unlink(__DIR__ . '/' . $row['cover_image']);
            }
        }
        $stmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
        $stmt->bind_param("i", $book_id);
        if ($stmt->execute()) {
            $message      = "Book \"" . htmlspecialchars($deleted_title ?? '') . "\" deleted.";
            $message_type = 'success';
        } else {
            $message      = "Error deleting book: " . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// ─── Fetch all books ──────────────────────────────────────────────────────────
$search       = trim($_GET['search'] ?? '');
$genre_filter = trim($_GET['genre']  ?? '');
$sort         = trim($_GET['sort']   ?? 'book_id');
$order        = strtoupper(trim($_GET['order'] ?? 'DESC'));

$allowed_sorts  = ['book_id', 'title', 'author', 'genre', 'price', 'stock_quantity'];
$allowed_orders = ['ASC', 'DESC'];
if (!in_array($sort, $allowed_sorts))   $sort  = 'book_id';
if (!in_array($order, $allowed_orders)) $order = 'DESC';

$where_clauses = [];
$bind_types    = '';
$bind_values   = [];

if ($search !== '') {
    $where_clauses[] = "(title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $s = "%$search%";
    $bind_types  .= 'sss';
    $bind_values  = array_merge($bind_values, [$s, $s, $s]);
}
if ($genre_filter !== '') {
    $where_clauses[] = "genre = ?";
    $bind_types  .= 's';
    $bind_values[] = $genre_filter;
}

$where_sql = $where_clauses ? ('WHERE ' . implode(' AND ', $where_clauses)) : '';
$sql       = "SELECT * FROM books $where_sql ORDER BY $sort $order";
$stmt      = $conn->prepare($sql);
if ($bind_types) {
    $refs = [];
    foreach ($bind_values as $k => $v) $refs[$k] = &$bind_values[$k];
    $stmt->bind_param($bind_types, ...$refs);
}
$stmt->execute();
$books_result = $stmt->get_result();
$books        = $books_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Genres for dropdowns
$genres_res = $conn->query("SELECT DISTINCT genre FROM books ORDER BY genre");
$genres     = [];
while ($g = $genres_res->fetch_assoc()) $genres[] = $g['genre'];

$total_books = count($books);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RikuStore Admin — Book Inventory</title>
    <meta name="description" content="Admin panel for managing the RikuStore book inventory. Add, edit, and delete books.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin_books.css">
</head>
<body class="flex flex-col min-h-screen bg-stone-950">

<!-- ════════════════════ NAVBAR ════════════════════ -->
<nav class="bg-stone-900 border-b border-stone-800 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex items-center space-x-8">
                <div class="flex items-center space-x-3 text-white font-bold text-2xl tracking-tight">
                    <i class="fa-solid fa-shield-halved text-amber-500"></i>
                    <span class="serif-title font-semibold italic text-2xl">RikuStore Admin</span>
                </div>
                <div class="hidden md:flex items-center space-x-1 text-sm">
                    <a href="admin_home.php" class="px-3 py-2 rounded-lg text-stone-400 hover:text-white hover:bg-stone-800 transition flex items-center gap-2">
                        <i class="fa-solid fa-gauge-high text-xs"></i> Dashboard
                    </a>
                    <a href="admin_books.php" class="px-3 py-2 rounded-lg text-amber-400 bg-amber-500/10 border border-amber-500/20 flex items-center gap-2">
                        <i class="fa-solid fa-book-open text-xs"></i> Books
                    </a>
                </div>
            </div>
            <div class="flex items-center space-x-6">
                <div class="flex items-center space-x-3 border-l pl-5 border-stone-700">
                    <div class="text-right hidden md:block">
                        <p class="text-xs text-stone-400 font-medium"><?php echo $user_session['role']; ?></p>
                        <p class="text-sm font-semibold text-white"><?php echo htmlspecialchars($user_session['name']); ?></p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-stone-800 flex items-center justify-center text-stone-300 font-semibold border border-stone-700">
                        <?php echo htmlspecialchars($user_session['initials']); ?>
                    </div>
                    <a href="logout.php" class="text-stone-400 hover:text-red-500 transition ml-2" title="Logout">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- ════════════════════ MAIN ════════════════════ -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex-grow w-full">

    <!-- Header row -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="serif-title text-3xl font-normal text-white mb-1">Book Inventory</h1>
            <p class="text-stone-400 text-sm">Manage all <span class="text-amber-400 font-semibold font-mono"><?php echo $total_books; ?></span> books in the store.</p>
        </div>
        <button id="btn-open-add" onclick="openAddModal()"
            class="flex items-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-stone-900 font-bold rounded-xl text-sm transition-all shadow-lg shadow-amber-500/20 hover:shadow-amber-400/30 hover:-translate-y-0.5">
            <i class="fa-solid fa-plus"></i> Add New Book
        </button>
    </div>

    <!-- Flash message -->
    <?php if ($message): ?>
    <div id="flash-msg" class="mb-6 flex items-center gap-3 px-5 py-4 rounded-xl border text-sm font-medium
        <?php echo $message_type === 'success'
            ? 'bg-emerald-950 border-emerald-700 text-emerald-300'
            : 'bg-red-950 border-red-700 text-red-300'; ?>">
        <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <?php echo $message; ?>
        <button onclick="document.getElementById('flash-msg').remove()" class="ml-auto opacity-60 hover:opacity-100"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <?php endif; ?>

    <!-- Filter / Search bar -->
    <form method="GET" class="bg-stone-900 border border-stone-800 rounded-xl p-4 mb-6 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-400 mb-1.5">Search</label>
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-stone-500 text-xs"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Title, author, ISBN…"
                    class="w-full pl-9 pr-4 py-2 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white placeholder-stone-500 focus:outline-none focus:border-amber-500 transition">
            </div>
        </div>
        <div class="min-w-[160px]">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-400 mb-1.5">Genre</label>
            <select name="genre" class="w-full px-3 py-2 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500 transition">
                <option value="">All Genres</option>
                <?php foreach ($genres as $g): ?>
                    <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $genre_filter === $g ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($g); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="min-w-[140px]">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-400 mb-1.5">Sort By</label>
            <select name="sort" class="w-full px-3 py-2 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500 transition">
                <option value="book_id"        <?php echo $sort === 'book_id'        ? 'selected' : ''; ?>>ID</option>
                <option value="title"          <?php echo $sort === 'title'          ? 'selected' : ''; ?>>Title</option>
                <option value="author"         <?php echo $sort === 'author'         ? 'selected' : ''; ?>>Author</option>
                <option value="price"          <?php echo $sort === 'price'          ? 'selected' : ''; ?>>Price</option>
                <option value="stock_quantity" <?php echo $sort === 'stock_quantity' ? 'selected' : ''; ?>>Stock</option>
            </select>
        </div>
        <div class="min-w-[110px]">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-stone-400 mb-1.5">Order</label>
            <select name="order" class="w-full px-3 py-2 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500 transition">
                <option value="ASC"  <?php echo $order === 'ASC'  ? 'selected' : ''; ?>>Ascending</option>
                <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
            </select>
        </div>
        <div class="flex gap-2 pb-0.5">
            <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-stone-900 font-bold rounded-lg text-sm transition">
                <i class="fa-solid fa-filter mr-1"></i> Filter
            </button>
            <a href="admin_books.php" class="px-4 py-2 bg-stone-700 hover:bg-stone-600 text-stone-300 font-medium rounded-lg text-sm transition">
                Reset
            </a>
        </div>
    </form>

    <!-- Book table -->
    <div class="bg-stone-900 border border-stone-800 rounded-xl overflow-hidden">
        <?php if (empty($books)): ?>
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <i class="fa-solid fa-book-open text-4xl text-stone-700 mb-4"></i>
                <p class="text-stone-400 text-sm">No books found.</p>
                <button onclick="openAddModal()" class="mt-4 text-amber-400 text-sm hover:underline">Add the first book →</button>
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-stone-400 font-mono text-[11px] border-b border-stone-800 bg-stone-900/80">
                        <th class="px-5 py-4 font-normal uppercase tracking-wider w-12">#</th>
                        <th class="px-4 py-4 font-normal uppercase tracking-wider">Cover</th>
                        <th class="px-4 py-4 font-normal uppercase tracking-wider">Title / Author</th>
                        <th class="px-4 py-4 font-normal uppercase tracking-wider">Genre</th>
                        <th class="px-4 py-4 font-normal uppercase tracking-wider">ISBN</th>
                        <th class="px-4 py-4 font-normal uppercase tracking-wider text-right">Price</th>
                        <th class="px-4 py-4 font-normal uppercase tracking-wider text-center">Stock</th>
                        <th class="px-5 py-4 font-normal uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-800">
                    <?php foreach ($books as $b): ?>
                    <tr class="hover:bg-stone-800/50 transition group">
                        <td class="px-5 py-4 font-mono text-stone-500 text-xs"><?php echo $b['book_id']; ?></td>
                        <td class="px-4 py-4">
                            <?php if ($b['cover_image'] && file_exists(__DIR__ . '/' . $b['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($b['cover_image']); ?>" alt="cover"
                                    class="w-10 h-14 object-cover rounded-md border border-stone-700 shadow">
                            <?php else: ?>
                                <div class="w-10 h-14 rounded-md border border-stone-700 bg-stone-800 flex items-center justify-center text-stone-600">
                                    <i class="fa-solid <?php echo htmlspecialchars($b['icon']); ?> text-xs"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 max-w-[220px]">
                            <p class="text-white font-semibold leading-tight truncate"><?php echo htmlspecialchars($b['title']); ?></p>
                            <p class="text-stone-400 text-xs mt-0.5 truncate"><?php echo htmlspecialchars($b['author']); ?></p>
                        </td>
                        <td class="px-4 py-4">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-stone-800 border border-stone-700 text-stone-300">
                                <?php echo htmlspecialchars($b['genre']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 font-mono text-stone-400 text-xs"><?php echo htmlspecialchars($b['isbn']); ?></td>
                        <td class="px-4 py-4 text-right font-mono font-bold text-amber-400">RM<?php echo number_format($b['price'], 2); ?></td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-2 py-1 rounded text-xs font-mono font-bold <?php echo $b['stock_quantity'] <= 3 ? 'text-red-400 bg-red-950 border border-red-800' : 'text-emerald-400 bg-emerald-950 border border-emerald-800'; ?>">
                                <?php echo $b['stock_quantity']; ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-2 opacity-70 group-hover:opacity-100 transition">
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($b)); ?>)"
                                    class="px-3 py-1.5 bg-stone-700 hover:bg-amber-500 hover:text-stone-900 text-stone-300 rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <button onclick="openDeleteModal(<?php echo $b['book_id']; ?>, '<?php echo htmlspecialchars(addslashes($b['title'])); ?>')"
                                    class="px-3 py-1.5 bg-stone-700 hover:bg-red-600 text-stone-300 hover:text-white rounded-lg text-xs font-semibold transition flex items-center gap-1.5">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</main>

<!-- ══════════════════ ADD MODAL ══════════════════ -->
<div id="modal-add" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="modal-backdrop absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeAddModal()"></div>
    <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-stone-900 border border-stone-700 rounded-2xl shadow-2xl">
        <div class="sticky top-0 bg-stone-900 border-b border-stone-800 px-6 py-5 flex items-center justify-between z-10">
            <h2 class="serif-title text-xl text-white font-normal">Add New Book</h2>
            <button onclick="closeAddModal()" class="text-stone-500 hover:text-white transition"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            <input type="hidden" name="action" value="add">
            <?php echo renderBookFormFields(); ?>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 py-2.5 bg-amber-500 hover:bg-amber-400 text-stone-900 font-bold rounded-xl text-sm transition">
                    <i class="fa-solid fa-plus mr-2"></i>Add Book
                </button>
                <button type="button" onclick="closeAddModal()" class="px-5 py-2.5 bg-stone-700 hover:bg-stone-600 text-stone-300 font-medium rounded-xl text-sm transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════ EDIT MODAL ══════════════════ -->
<div id="modal-edit" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="modal-backdrop absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeEditModal()"></div>
    <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-stone-900 border border-stone-700 rounded-2xl shadow-2xl">
        <div class="sticky top-0 bg-stone-900 border-b border-stone-800 px-6 py-5 flex items-center justify-between z-10">
            <h2 class="serif-title text-xl text-white font-normal">Edit Book</h2>
            <button onclick="closeEditModal()" class="text-stone-500 hover:text-white transition"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-5" id="edit-form">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="book_id" id="edit-book_id">
            <input type="hidden" name="existing_cover" id="edit-existing_cover">
            <?php echo renderBookFormFields('edit'); ?>
            <!-- Current cover preview -->
            <div id="edit-cover-preview" class="hidden">
                <label class="block text-xs font-bold uppercase tracking-wider text-stone-400 mb-2">Current Cover</label>
                <img id="edit-cover-img" src="" alt="current cover" class="h-24 w-auto rounded-lg border border-stone-700 object-cover">
                <p class="text-stone-500 text-xs mt-1">Upload a new file above to replace it.</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 py-2.5 bg-amber-500 hover:bg-amber-400 text-stone-900 font-bold rounded-xl text-sm transition">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>Save Changes
                </button>
                <button type="button" onclick="closeEditModal()" class="px-5 py-2.5 bg-stone-700 hover:bg-stone-600 text-stone-300 font-medium rounded-xl text-sm transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════ DELETE MODAL ══════════════════ -->
<div id="modal-delete" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <div class="modal-backdrop absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
    <div class="relative w-full max-w-sm bg-stone-900 border border-red-900/50 rounded-2xl shadow-2xl p-6 text-center">
        <div class="w-14 h-14 rounded-full bg-red-950 border border-red-800 flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-triangle-exclamation text-red-400 text-xl"></i>
        </div>
        <h2 class="text-white font-bold text-lg mb-2">Delete Book?</h2>
        <p class="text-stone-400 text-sm mb-6">You are about to permanently delete <span id="delete-book-title" class="text-white font-semibold"></span>. This cannot be undone.</p>
        <form method="POST" id="delete-form">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="book_id" id="delete-book-id">
            <div class="flex gap-3">
                <button type="submit" class="flex-1 py-2.5 bg-red-600 hover:bg-red-500 text-white font-bold rounded-xl text-sm transition">
                    <i class="fa-solid fa-trash mr-2"></i>Delete
                </button>
                <button type="button" onclick="closeDeleteModal()" class="flex-1 py-2.5 bg-stone-700 hover:bg-stone-600 text-stone-300 font-medium rounded-xl text-sm transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Modal helpers ─────────────────────────────────────────────────────────────
function openAddModal() {
    document.getElementById('modal-add').classList.replace('hidden', 'flex');
    document.body.classList.add('overflow-hidden');
}
function closeAddModal() {
    document.getElementById('modal-add').classList.replace('flex', 'hidden');
    document.body.classList.remove('overflow-hidden');
}
function closeEditModal() {
    document.getElementById('modal-edit').classList.replace('flex', 'hidden');
    document.body.classList.remove('overflow-hidden');
}
function closeDeleteModal() {
    document.getElementById('modal-delete').classList.replace('flex', 'hidden');
    document.body.classList.remove('overflow-hidden');
}

// ── Open Edit Modal ──────────────────────────────────────────────────────────
function openEditModal(book) {
    const fields = ['book_id', 'title', 'sub_title', 'author', 'isbn', 'genre',
                    'price', 'stock_quantity', 'theme_gradient', 'border_color',
                    'icon', 'description', 'release_date'];

    fields.forEach(f => {
        const el = document.getElementById('edit-' + f);
        if (el) el.value = book[f] ?? '';
    });

    document.getElementById('edit-existing_cover').value = book['cover_image'] ?? '';

    const preview = document.getElementById('edit-cover-preview');
    const img     = document.getElementById('edit-cover-img');
    if (book['cover_image']) {
        img.src = book['cover_image'];
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }

    document.getElementById('modal-edit').classList.replace('hidden', 'flex');
    document.body.classList.add('overflow-hidden');
}

// ── Open Delete Modal ─────────────────────────────────────────────────────────
function openDeleteModal(id, title) {
    document.getElementById('delete-book-id').value    = id;
    document.getElementById('delete-book-title').textContent = '"' + title + '"';
    document.getElementById('modal-delete').classList.replace('hidden', 'flex');
    document.body.classList.add('overflow-hidden');
}

// Auto-dismiss flash message after 4 s
setTimeout(() => {
    const el = document.getElementById('flash-msg');
    if (el) el.remove();
}, 4000);

// ESC to close modals
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeAddModal(); closeEditModal(); closeDeleteModal();
    }
});
</script>
</body>
</html>

<?php
// ─── Reusable form-fields renderer ───────────────────────────────────────────
function renderBookFormFields(string $prefix = ''): string {
    $p = $prefix ? $prefix . '-' : '';
    $genres_list = ['Fiction', 'Non-Fiction', 'Computer Science', 'Web Development', 'Horror', 'Romance'];

    ob_start(); ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-stone-400 mb-1.5">Title <span class="text-red-400">*</span></label>
            <input type="text" name="title" id="<?php echo $p; ?>title" required placeholder="Book title"
                class="w-full px-4 py-2.5 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white placeholder-stone-500 focus:outline-none focus:border-amber-500 transition">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-stone-400 mb-1.5">Sub-title</label>
            <input type="text" name="sub_title" id="<?php echo $p; ?>sub_title" placeholder="Short tagline"
                class="w-full px-4 py-2.5 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white placeholder-stone-500 focus:outline-none focus:border-amber-500 transition">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-stone-400 mb-1.5">Author <span class="text-red-400">*</span></label>
            <input type="text" name="author" id="<?php echo $p; ?>author" required placeholder="Author name"
                class="w-full px-4 py-2.5 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white placeholder-stone-500 focus:outline-none focus:border-amber-500 transition">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-stone-400 mb-1.5">ISBN <span class="text-red-400">*</span></label>
            <input type="text" name="isbn" id="<?php echo $p; ?>isbn" required placeholder="978-XXXXXXXXXX"
                class="w-full px-4 py-2.5 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white placeholder-stone-500 focus:outline-none focus:border-amber-500 transition">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-stone-400 mb-1.5">Genre <span class="text-red-400">*</span></label>
            <select name="genre" id="<?php echo $p; ?>genre" required
                class="w-full px-4 py-2.5 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500 transition">
                <option value="">Select genre…</option>
                <?php foreach ($genres_list as $g): ?>
                <option value="<?php echo htmlspecialchars($g); ?>"><?php echo htmlspecialchars($g); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-stone-400 mb-1.5">Release Date</label>
            <input type="text" name="release_date" id="<?php echo $p; ?>release_date" placeholder="e.g. 2024 or May 2026"
                class="w-full px-4 py-2.5 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white placeholder-stone-500 focus:outline-none focus:border-amber-500 transition">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-stone-400 mb-1.5">Price (RM) <span class="text-red-400">*</span></label>
            <input type="number" name="price" id="<?php echo $p; ?>price" required min="0" step="0.01" placeholder="0.00"
                class="w-full px-4 py-2.5 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white placeholder-stone-500 focus:outline-none focus:border-amber-500 transition">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-stone-400 mb-1.5">Stock Quantity <span class="text-red-400">*</span></label>
            <input type="number" name="stock_quantity" id="<?php echo $p; ?>stock_quantity" required min="0" placeholder="0"
                class="w-full px-4 py-2.5 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white placeholder-stone-500 focus:outline-none focus:border-amber-500 transition">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-stone-400 mb-1.5">Theme Gradient</label>
            <input type="text" name="theme_gradient" id="<?php echo $p; ?>theme_gradient" placeholder="from-stone-900 to-stone-950"
                class="w-full px-4 py-2.5 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white placeholder-stone-500 focus:outline-none focus:border-amber-500 transition">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-stone-400 mb-1.5">Border Color</label>
            <input type="text" name="border_color" id="<?php echo $p; ?>border_color" placeholder="border-stone-700"
                class="w-full px-4 py-2.5 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white placeholder-stone-500 focus:outline-none focus:border-amber-500 transition">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-stone-400 mb-1.5">Icon (Font Awesome class)</label>
            <input type="text" name="icon" id="<?php echo $p; ?>icon" placeholder="fa-book"
                class="w-full px-4 py-2.5 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white placeholder-stone-500 focus:outline-none focus:border-amber-500 transition">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-stone-400 mb-1.5">Cover Image</label>
            <input type="file" name="cover_image" accept="image/*"
                class="w-full text-xs text-stone-400 bg-stone-800 border border-stone-700 rounded-lg px-3 py-2.5 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-500 file:text-stone-900 hover:file:bg-amber-400 transition cursor-pointer">
        </div>
    </div>
    <div>
        <label class="block text-xs font-bold uppercase tracking-wider text-stone-400 mb-1.5">Description</label>
        <textarea name="description" id="<?php echo $p; ?>description" rows="3" placeholder="Short synopsis…"
            class="w-full px-4 py-2.5 bg-stone-800 border border-stone-700 rounded-lg text-sm text-white placeholder-stone-500 focus:outline-none focus:border-amber-500 transition resize-none"></textarea>
    </div>
    <?php
    return ob_get_clean();
}
?>
