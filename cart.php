<?php
session_start();
include 'db_connect.php';
include 'toyyibpay_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$message = "";
$error_message = "";

// Fetch user's profile data (address + loyalty points)
$user_address_res = $conn->query("SELECT address, loyalty_points FROM users WHERE id = $user_id");
$user_address_row = $user_address_res->fetch_assoc();
$user_address     = $user_address_row['address'] ?? '';
$user_points      = (int)($user_address_row['loyalty_points'] ?? 0);

// Points conversion: 100 pts = RM1.00
define('POINTS_PER_RM', 100);

// ─── AJAX: Apply Points ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_points') {
    header('Content-Type: application/json');

    $points_to_use = (int)($_POST['points'] ?? 0);

    // Fresh balance from DB
    $pts_res = $conn->query("SELECT loyalty_points FROM users WHERE id = $user_id");
    $pts_row = $pts_res->fetch_assoc();
    $balance = (int)($pts_row['loyalty_points'] ?? 0);

    if ($points_to_use <= 0) {
        echo json_encode(['valid' => false, 'message' => 'Please enter a valid number of points.']);
        exit();
    }
    if ($points_to_use > $balance) {
        echo json_encode(['valid' => false, 'message' => 'You only have ' . $balance . ' points available.']);
        exit();
    }

    // Cap: cannot redeem more RM than the current order total
    $sub_res2 = $conn->query("SELECT SUM(b.price * c.quantity) AS subtotal FROM cart c JOIN books b ON c.book_id = b.book_id WHERE c.user_id = $user_id");
    $sub_row2 = $sub_res2->fetch_assoc();
    $current_subtotal2 = (float)($sub_row2['subtotal'] ?? 0);
    $current_shipping2 = $current_subtotal2 > 0 ? 5.00 : 0.00;
    $order_total       = max(0, $current_subtotal2 + $current_shipping2);
    $max_pts           = (int)floor($order_total * POINTS_PER_RM);

    if ($points_to_use > $max_pts) {
        $points_to_use = $max_pts; // silently cap
    }
    if ($points_to_use <= 0) {
        echo json_encode(['valid' => false, 'message' => 'No redeemable points for this order amount.']);
        exit();
    }

    $pts_discount = round($points_to_use / POINTS_PER_RM, 2);

    $_SESSION['applied_points'] = [
        'points'   => $points_to_use,
        'discount' => $pts_discount,
    ];

    echo json_encode([
        'valid'    => true,
        'message'  => $points_to_use . ' points applied — you save RM' . number_format($pts_discount, 2) . '.',
        'points'   => $points_to_use,
        'discount' => $pts_discount,
        'balance'  => $balance,
    ]);
    exit();
}

// ─── AJAX: Remove Points ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_points') {
    header('Content-Type: application/json');
    unset($_SESSION['applied_points']);
    echo json_encode(['success' => true]);
    exit();
}

// ─── Handle functional server POST requests ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'remove' && isset($_POST['book_id'])) {
        $remove_id = (int)$_POST['book_id'];
        $conn->query("DELETE FROM cart WHERE user_id = $user_id AND book_id = $remove_id");
        // Clear points on item removal (subtotal changes)
        unset($_SESSION['applied_points']);
        $message = "Item removed successfully from your dynamic session.";
    } elseif ($_POST['action'] === 'clear') {
        $conn->query("DELETE FROM cart WHERE user_id = $user_id");
        unset($_SESSION['applied_points']);
        $message = "All queue entries successfully purged.";
    } elseif ($_POST['action'] === 'checkout') {
        $shipping_address = isset($_POST['shipping_address']) ? $conn->real_escape_string(trim($_POST['shipping_address'])) : '';
        if (empty($shipping_address)) {
            $error_message = "Shipping address is required to proceed with checkout.";
        } else {
            // Save to profile if checkbox checked
            if (isset($_POST['save_profile_address']) && $_POST['save_profile_address'] === '1') {
                $conn->query("UPDATE users SET address = '$shipping_address' WHERE id = $user_id");
                $user_address = $shipping_address;
            }

            // Create an order in DB
            $cart_sql = "SELECT c.quantity, b.title, b.price FROM cart c JOIN books b ON c.book_id = b.book_id WHERE c.user_id = $user_id";
            $cart_res = $conn->query($cart_sql);
            if ($cart_res->num_rows > 0) {
                $total_amount = 0;
                $items_str = [];
                while($row = $cart_res->fetch_assoc()) {
                    $total_amount += $row['price'] * $row['quantity'];
                    $items_str[] = $row['title'] . " (" . $row['quantity'] . ")";
                }
                $shipping = $total_amount > 0 ? 5.00 : 0.00;
                $total_amount += $shipping;

                // Apply points discount
                $applied_points_co = $_SESSION['applied_points'] ?? null;
                $points_used    = 0;
                $points_discount_co = 0;
                if ($applied_points_co) {
                    $points_used        = (int)$applied_points_co['points'];
                    $points_discount_co = round($points_used / POINTS_PER_RM, 2);
                    $total_amount       = max(0, $total_amount - $points_discount_co);
                }

                $items_text   = implode(", ", $items_str);
                $order_id_str = "ORD-" . date('Y') . "-" . rand(1000, 9999);
                $order_date   = date("F j, Y");

                // Insert order
                $insert_order = "INSERT INTO orders (order_id, user_id, order_date, total_amount, status, status_color, items, shipping_address, discount_amount, points_used)
                    VALUES ('$order_id_str', $user_id, '$order_date', $total_amount, 'Pending Payment', 'bg-amber-100 text-amber-800 border-amber-200', '$items_text', '$shipping_address', $points_discount_co, $points_used)";

                if ($conn->query($insert_order)) {
                    // Deduct redeemed points from user balance
                    if ($points_used > 0) {
                        $conn->query("UPDATE users SET loyalty_points = GREATEST(0, loyalty_points - $points_used) WHERE id = $user_id");
                        unset($_SESSION['applied_points']);
                    }

                    // Get payor details
                    $user_sql  = "SELECT name, email FROM users WHERE id = $user_id";
                    $user_res  = $conn->query($user_sql);
                    $user_data = $user_res->fetch_assoc();

                    $billName        = "Order " . $order_id_str;
                    $billDescription = "Purchase of: " . $items_text;
                    if (strlen($billDescription) > 100) {
                        $billDescription = substr($billDescription, 0, 97) . "...";
                    }
                    $billAmount = (int)round($total_amount * 100);

                    // Construct return and callback URLs dynamically
                    $protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                    $host       = $_SERVER['HTTP_HOST'];
                    $requestUri = $_SERVER['REQUEST_URI'];
                    $dirPath    = dirname($requestUri);
                    if ($dirPath === '/' || $dirPath === '\\') {
                        $dirPath = '';
                    }

                    $baseUrl         = $protocol . $host . $dirPath;
                    $billReturnUrl   = $baseUrl . "/payment_return.php";
                    $billCallbackUrl = $baseUrl . "/payment_callback.php";

                    $categoryCode = TOYYIBPAY_CATEGORY_CODE;
                    if ($categoryCode === 'your_category_code_here') {
                        $ch_cat = curl_init();
                        curl_setopt($ch_cat, CURLOPT_POST, 1);
                        curl_setopt($ch_cat, CURLOPT_URL, TOYYIBPAY_URL . 'index.php/api/createCategory');
                        curl_setopt($ch_cat, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch_cat, CURLOPT_POSTFIELDS, http_build_query([
                            'userSecretKey'  => TOYYIBPAY_SECRET_KEY,
                            'catname'        => 'RikuStore Bookstore',
                            'catdescription' => 'Bookstore transactions'
                        ]));
                        $res_cat = curl_exec($ch_cat);
                        curl_close($ch_cat);

                        $cat_response = json_decode($res_cat, true);
                        if (isset($cat_response[0]['categoryCode'])) {
                            $categoryCode   = $cat_response[0]['categoryCode'];
                            $config_content = "<?php\n" .
                                              "// ToyyibPay Dev Credentials (Sandbox/Staging)\n" .
                                              "define('TOYYIBPAY_SECRET_KEY', '" . TOYYIBPAY_SECRET_KEY . "');\n" .
                                              "define('TOYYIBPAY_CATEGORY_CODE', '" . $categoryCode . "');\n" .
                                              "define('TOYYIBPAY_URL', 'https://dev.toyyibpay.com/');\n" .
                                              "?>";
                            file_put_contents('toyyibpay_config.php', $config_content);
                        } elseif (isset($cat_response[0]['CategoryCode'])) {
                            $categoryCode   = $cat_response[0]['CategoryCode'];
                            $config_content = "<?php\n" .
                                              "// ToyyibPay Dev Credentials (Sandbox/Staging)\n" .
                                              "define('TOYYIBPAY_SECRET_KEY', '" . TOYYIBPAY_SECRET_KEY . "');\n" .
                                              "define('TOYYIBPAY_CATEGORY_CODE', '" . $categoryCode . "');\n" .
                                              "define('TOYYIBPAY_URL', 'https://dev.toyyibpay.com/');\n" .
                                              "?>";
                            file_put_contents('toyyibpay_config.php', $config_content);
                        } else {
                            $error_message = "Failed to auto-create category. Gateway Response: " . htmlspecialchars($res_cat);
                        }
                    }

                    if (empty($error_message)) {
                        $toyyibpay_data = array(
                            'userSecretKey'            => TOYYIBPAY_SECRET_KEY,
                            'categoryCode'             => $categoryCode,
                            'billName'                 => $billName,
                            'billDescription'          => $billDescription,
                            'billPriceSetting'         => 0,
                            'billPayorInfo'            => 1,
                            'billAmount'               => $billAmount,
                            'billReturnUrl'            => $billReturnUrl,
                            'billCallbackUrl'          => $billCallbackUrl,
                            'billExternalReferenceNo'  => $order_id_str,
                            'billTo'                   => $user_data['name'],
                            'billEmail'                => $user_data['email'],
                            'billPhone'                => '0123456789',
                            'billSplitPayment'         => 0,
                            'billSplitPaymentArgs'     => '',
                            'billPaymentChannel'       => 0,
                            'billContentEmail'         => 'Thank you for your order!',
                            'billChargeToCustomer'     => 1
                        );

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_URL, TOYYIBPAY_URL . 'index.php/api/createBill');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($toyyibpay_data));
                        $result = curl_exec($ch);
                        $err    = curl_error($ch);
                        curl_close($ch);

                        if ($err) {
                            $error_message = "Error connecting to ToyyibPay: " . $err;
                        } else {
                            $response = json_decode($result, true);
                            if (isset($response[0]['BillCode'])) {
                                $billCode = $response[0]['BillCode'];
                                $conn->query("DELETE FROM cart WHERE user_id = $user_id");
                                header("Location: " . TOYYIBPAY_URL . $billCode);
                                exit();
                            } else {
                                $error_message = "Failed to create payment bill. Gateway Response: " . htmlspecialchars($result);
                            }
                        }
                    }
                } else {
                    $error_message = "Failed to record order details in database.";
                }
            }
        }
    }
}

// ─── Get Cart Items ──────────────────────────────────────────────────────────
$cart_items = [];
$subtotal   = 0;
$cart_items_sql = "SELECT c.*, b.title, b.author, b.price, b.theme_gradient, b.border_color FROM cart c JOIN books b ON c.book_id = b.book_id WHERE c.user_id = $user_id";
$cart_items_res = $conn->query($cart_items_sql);
if ($cart_items_res->num_rows > 0) {
    while($row = $cart_items_res->fetch_assoc()) {
        $cart_items[] = $row;
        $subtotal += $row['price'] * $row['quantity'];
    }
}

$shipping = $subtotal > 0 ? 5.00 : 0.00;

// Recalculate points discount from session
$applied_points  = $_SESSION['applied_points'] ?? null;
$points_discount = 0;
if ($applied_points && $subtotal > 0) {
    $points_discount = round((int)$applied_points['points'] / POINTS_PER_RM, 2);
}

$total_price = max(0, $subtotal + $shipping - $points_discount);

$cart_count_sql = "SELECT SUM(quantity) as count FROM cart WHERE user_id = $user_id";
$cart_count_res = $conn->query($cart_count_sql);
$cart_count_row = $cart_count_res->fetch_assoc();
$cart_count     = $cart_count_row['count'] ?? 0;

$user_session = [
    'name'       => $_SESSION['name'] ?? 'Guest',
    'initials'   => $_SESSION['initials'] ?? 'G',
    'role'       => $_SESSION['role'] ?? 'Customer',
    'cart_count' => $cart_count
];

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RikuStore - Shopping Cart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cart.css">
    <style>
        /* ── Discount row animation ── */
        .discount-row {
            overflow: hidden;
            transition: max-height 0.35s ease, opacity 0.3s ease;
            max-height: 0;
            opacity: 0;
        }
        .discount-row.visible {
            max-height: 4rem;
            opacity: 1;
        }

        @keyframes pulse-amber {
            0%   { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
            70%  { box-shadow: 0 0 0 8px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
        .pulse-success { animation: pulse-amber 0.6s ease; }

        /* ── Points Section ── */
        .points-section {
            border: 1px solid #fcd34d;
            border-radius: 0.625rem;
            padding: 0.875rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%);
        }
        .points-balance-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #92400e;
            color: #fef3c7;
            padding: 0.22rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.04em;
        }
        .points-slider {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 4px;
            border-radius: 9999px;
            background: #e7e5e4;
            outline: none;
            margin: 0.5rem 0;
            cursor: pointer;
        }
        .points-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #92400e;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(0,0,0,0.25);
            transition: transform 0.15s;
        }
        .points-slider::-webkit-slider-thumb:hover { transform: scale(1.2); }
        .points-slider::-moz-range-thumb {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #92400e;
            cursor: pointer;
            border: none;
            box-shadow: 0 1px 4px rgba(0,0,0,0.25);
        }
        .points-apply-btn {
            padding: 0.45rem 0.85rem;
            background: #92400e;
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.2s;
            font-family: 'Plus Jakarta Sans', sans-serif;
            white-space: nowrap;
        }
        .points-apply-btn:hover    { background: #78350f; }
        .points-apply-btn:disabled { background: #a8a29e; cursor: not-allowed; }

        .points-feedback {
            font-size: 0.68rem;
            font-weight: 600;
            margin-top: 0.35rem;
            min-height: 1rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .points-feedback.success { color: #065f46; }
        .points-feedback.error   { color: #991b1b; }

        .points-applied-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #fef3c7;
            border: 1px solid #f59e0b;
            color: #78350f;
            padding: 0.3rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.04em;
        }
        .pts-remove-btn {
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 0;
            font-size: 0.65rem;
            transition: color 0.15s;
            line-height: 1;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .pts-remove-btn:hover { color: #dc2626; }
        .points-discount-row { color: #92400e; }
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
                        <a href="genre.php" class="<?php echo ($current_page == 'genre.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">Genres</a>
                        <a href="newrelease.php" class="<?php echo ($current_page == 'newrelease.php') ? 'text-amber-800 font-semibold border-b-2 border-amber-800' : 'hover:text-stone-900'; ?> px-1 py-2 transition">New Releases</a>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="cart.php" class="relative text-amber-800 transition p-1">
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
        <div class="flex items-center justify-between border-b border-stone-200 pb-5 mb-10">
            <h1 class="serif-title text-3xl font-normal text-stone-900">Your Selection Cart</h1>
            <?php if (!empty($cart_items)): ?>
                <form action="cart.php" method="POST">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="text-xs font-semibold text-red-700 hover:text-red-900 transition flex items-center space-x-1">
                        <i class="fa-solid fa-trash-can"></i>
                        <span>Clear All Items</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-xl flex items-center space-x-2 font-medium">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl flex items-center space-x-2 font-medium">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="text-center py-20 bg-white border border-stone-200 rounded-2xl p-8 max-w-md mx-auto">
                <i class="fa-solid fa-basket-shopping text-4xl text-stone-300 mb-4 block"></i>
                <h2 class="font-bold text-stone-900 text-lg">Your cart is currently empty</h2>
                <p class="text-xs text-stone-500 mt-1 mb-6">Explore the curated collection to add books back into your dynamic active array scope.</p>
                <a href="index.php" class="inline-block bg-stone-900 hover:bg-amber-800 text-white text-xs px-5 py-3 rounded-lg font-semibold tracking-wide transition shadow-sm">Return to Catalog</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">

                <!-- ── Cart Items ── -->
                <div class="lg:col-span-2 space-y-4">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="bg-white border border-stone-200 rounded-xl p-4 flex items-center justify-between shadow-sm">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-16 bg-gradient-to-br <?php echo htmlspecialchars($item['theme_gradient']); ?> border-l-2 <?php echo htmlspecialchars($item['border_color']); ?> rounded shadow relative shrink-0 overflow-hidden">
                                    <div class="absolute inset-0 spine-shadow"></div>
                                </div>
                                <div>
                                    <h3 class="font-bold text-stone-900 text-sm line-clamp-1"><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <p class="text-xs text-stone-400 mt-0.5">By <?php echo htmlspecialchars($item['author']); ?></p>
                                    <span class="text-xs text-stone-500 block font-mono mt-1">Qty: <?php echo $item['quantity']; ?></span>
                                </div>
                            </div>
                            <div class="text-right flex flex-col items-end justify-between h-16">
                                <span class="font-mono font-bold text-stone-900 text-sm">RM<?php echo number_format($item['price'], 2); ?></span>
                                <form action="cart.php" method="POST">
                                    <input type="hidden" name="book_id" value="<?php echo $item['book_id']; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="text-[11px] font-medium text-stone-400 hover:text-red-700 transition">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- ── Checkout Panel ── -->
                <div class="bg-white border border-stone-200 rounded-xl p-6 shadow-sm" id="checkout-panel">
                    <h2 class="serif-title text-xl font-normal text-stone-900 border-b pb-4 mb-4">Checkout</h2>

                    <!-- Price Summary -->
                    <div class="space-y-3 text-xs font-medium border-b pb-4 mb-4">
                        <div class="flex justify-between text-stone-500">
                            <span>Subtotal Items</span>
                            <span class="font-mono text-stone-900">RM<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="flex justify-between text-stone-500">
                            <span>Calculated Shipping</span>
                            <span class="font-mono text-stone-900">RM<?php echo number_format($shipping, 2); ?></span>
                        </div>
                        <!-- Points Discount Row -->
                        <div class="discount-row <?php echo $points_discount > 0 ? 'visible' : ''; ?>" id="points-discount-row">
                            <div class="flex justify-between points-discount-row">
                                <span class="flex items-center gap-1">
                                    <i class="fa-solid fa-star text-[10px]"></i>
                                    Points Redeemed
                                </span>
                                <span class="font-mono font-bold" id="summary-points-discount">-RM<?php echo number_format($points_discount, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between items-baseline mb-5">
                        <span class="text-sm font-bold text-stone-900">Total Price</span>
                        <span class="text-2xl font-bold font-mono text-amber-800" id="summary-total">RM<?php echo number_format($total_price, 2); ?></span>
                    </div>

                    <!-- ── Points Redemption Section ── -->
                    <?php if ($user_points > 0): ?>
                    <div class="points-section" id="points-section">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-amber-900 flex items-center gap-1.5">
                                <i class="fa-solid fa-star"></i> Redeem Points
                            </p>
                            <span class="points-balance-chip">
                                <i class="fa-solid fa-coins text-[9px]"></i>
                                <span id="pts-balance-display"><?php echo number_format($user_points); ?></span> pts
                            </span>
                        </div>
                        <p class="text-[10px] text-amber-800 mb-0.5 font-medium">100 pts = RM1.00</p>

                        <!-- Applied state -->
                        <div id="pts-applied-state" class="<?php echo $applied_points ? '' : 'hidden'; ?>">
                            <div class="flex items-center justify-between mt-1">
                                <span class="points-applied-badge">
                                    <i class="fa-solid fa-star"></i>
                                    <span id="pts-applied-label"><?php echo $applied_points ? number_format($applied_points['points']) . ' pts applied — save RM' . number_format($applied_points['discount'], 2) : ''; ?></span>
                                </span>
                                <button type="button" id="pts-remove-btn" class="pts-remove-btn" title="Remove points">
                                    <i class="fa-solid fa-xmark"></i> Remove
                                </button>
                            </div>
                        </div>

                        <!-- Input state -->
                        <div id="pts-input-state" class="<?php echo $applied_points ? 'hidden' : ''; ?>">
                            <p class="text-[10px] text-amber-700 mb-1 font-medium">
                                Max redeemable: <span id="pts-max-label"><?php
                                    echo number_format(min($user_points, (int)floor($total_price * POINTS_PER_RM)));
                                ?></span> pts
                            </p>
                            <input type="range" class="points-slider" id="pts-slider"
                                min="0"
                                max="<?php echo min($user_points, (int)floor($total_price * POINTS_PER_RM)); ?>"
                                step="<?php echo POINTS_PER_RM; ?>"
                                value="0">
                            <div class="flex items-center gap-2 mt-1">
                                <div class="flex items-center flex-1 bg-white border border-amber-200 rounded-lg px-2 py-1.5 gap-1.5">
                                    <i class="fa-solid fa-coins text-amber-700 text-[10px]"></i>
                                    <input type="number" id="pts-input"
                                        min="0"
                                        max="<?php echo min($user_points, (int)floor($total_price * POINTS_PER_RM)); ?>"
                                        step="<?php echo POINTS_PER_RM; ?>"
                                        value="0"
                                        class="w-full text-xs font-bold text-stone-800 outline-none bg-transparent" style="min-width:0">
                                    <span class="text-[10px] text-stone-400 shrink-0">pts</span>
                                </div>
                                <span class="text-xs font-bold text-amber-800 shrink-0" id="pts-rm-preview">= RM0.00</span>
                                <button type="button" class="points-apply-btn" id="pts-apply-btn">Use</button>
                            </div>
                            <div class="points-feedback" id="pts-feedback" role="status" aria-live="polite"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ── Checkout Form ── -->
                    <form action="cart.php" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="checkout">

                        <div class="border-t border-stone-100 pt-4">
                            <label for="shipping_address" class="block text-xs font-bold uppercase tracking-wider text-stone-500 mb-2">Shipping Address</label>
                            <textarea id="shipping_address" name="shipping_address" rows="3" required
                                class="w-full px-3 py-2 bg-stone-50 border border-stone-200 rounded-lg text-xs focus:outline-none focus:border-amber-700 transition resize-none"
                                placeholder="Enter your full shipping address"><?php echo htmlspecialchars($user_address); ?></textarea>

                            <div class="flex items-center mt-2">
                                <input type="checkbox" id="save_profile_address" name="save_profile_address" value="1" checked
                                    class="w-3.5 h-3.5 text-amber-800 border-stone-300 rounded focus:ring-amber-500 cursor-pointer">
                                <label for="save_profile_address" class="ml-2 text-[10px] font-medium text-stone-500 cursor-pointer">Save address to profile</label>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-stone-900 hover:bg-amber-800 text-white text-xs py-3.5 rounded-lg font-bold tracking-wider uppercase transition shadow-sm">
                            Checkout Order
                        </button>
                    </form>
                </div>

            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-stone-950 text-stone-400 text-xs py-5 px-6 border-t border-stone-800 font-mono mt-auto">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row justify-between items-center space-y-3 lg:space-y-0">
            <div class="flex flex-wrap justify-center lg:justify-start gap-x-6 gap-y-2 text-[11px]">
                <span class="flex items-center text-stone-300"><i class="fa-solid fa-circle text-[6px] text-emerald-500 mr-2"></i> Session Registry Online</span>
                <span>Sub-Systems: Cart Queue Processing</span>
            </div>
            <div class="text-[11px] text-stone-500">RikuStore Dynamic PHP Model Framework &copy; 2026</div>
        </div>
    </footer>

<script>
(function () {
    const serverSubtotal   = <?php echo json_encode($subtotal); ?>;
    const serverShipping   = <?php echo json_encode($shipping); ?>;
    const serverUserPoints = <?php echo json_encode($user_points); ?>;
    const POINTS_PER_RM    = <?php echo POINTS_PER_RM; ?>;

    let pointsDiscount = <?php echo json_encode($points_discount); ?>;

    // ── Helpers ──
    function fmtRM(val) {
        return 'RM' + Math.abs(val).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function updateTotals() {
        const total  = Math.max(0, serverSubtotal + serverShipping - pointsDiscount);
        const totalEl = document.getElementById('summary-total');
        if (totalEl) totalEl.textContent = fmtRM(total);

        const pRow = document.getElementById('points-discount-row');
        const pAmt = document.getElementById('summary-points-discount');
        if (pRow && pAmt) {
            pAmt.textContent = '-' + fmtRM(pointsDiscount);
            pRow.classList.toggle('visible', pointsDiscount > 0);
        }

        // Recalculate max redeemable
        const maxPtsLabel = document.getElementById('pts-max-label');
        const ptsSlider   = document.getElementById('pts-slider');
        const ptsInput    = document.getElementById('pts-input');
        const newMax      = Math.min(serverUserPoints, Math.floor(Math.max(0, serverSubtotal + serverShipping) * POINTS_PER_RM));
        if (maxPtsLabel) maxPtsLabel.textContent = newMax.toLocaleString();
        if (ptsSlider)   { ptsSlider.max = newMax; if (+ptsSlider.value > newMax) ptsSlider.value = newMax; }
        if (ptsInput)    { ptsInput.max  = newMax; if (+ptsInput.value  > newMax) { ptsInput.value = newMax; syncSlider(); } }
    }

    function pulsePanel() {
        const panel = document.getElementById('checkout-panel');
        if (panel) {
            panel.classList.add('pulse-success');
            setTimeout(() => panel.classList.remove('pulse-success'), 700);
        }
    }

    // ── Points elements ──
    const pSlider     = document.getElementById('pts-slider');
    const pInput      = document.getElementById('pts-input');
    const pRmPreview  = document.getElementById('pts-rm-preview');
    const pApplyBtn   = document.getElementById('pts-apply-btn');
    const pRemoveBtn  = document.getElementById('pts-remove-btn');
    const pFeedback   = document.getElementById('pts-feedback');
    const pApplied    = document.getElementById('pts-applied-state');
    const pInputState = document.getElementById('pts-input-state');
    const pAppliedLbl = document.getElementById('pts-applied-label');

    if (!pSlider) return; // no points or empty cart

    function setPFeedback(msg, type) {
        if (!pFeedback) return;
        pFeedback.textContent = '';
        pFeedback.className = 'points-feedback ' + (type || '');
        if (msg) {
            const icon = document.createElement('i');
            icon.className = type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark';
            pFeedback.appendChild(icon);
            pFeedback.appendChild(document.createTextNode(' ' + msg));
        }
    }

    function syncSlider() {
        if (!pSlider || !pInput || !pRmPreview) return;
        const pts = Math.min(+pInput.value || 0, +pSlider.max);
        pSlider.value = pts;
        pRmPreview.textContent = '= RM' + (pts / POINTS_PER_RM).toFixed(2);
    }

    function showPtsApplied(pts, disc) {
        if (pApplied)    pApplied.classList.remove('hidden');
        if (pInputState) pInputState.classList.add('hidden');
        if (pAppliedLbl) pAppliedLbl.textContent = pts.toLocaleString() + ' pts applied — save RM' + disc.toFixed(2);
        pointsDiscount = disc;
        updateTotals();
        pulsePanel();
    }

    function showPtsInput() {
        if (pApplied)    pApplied.classList.add('hidden');
        if (pInputState) pInputState.classList.remove('hidden');
        if (pSlider)     pSlider.value = 0;
        if (pInput)      pInput.value  = 0;
        if (pRmPreview)  pRmPreview.textContent = '= RM0.00';
        setPFeedback('');
        pointsDiscount = 0;
        updateTotals();
    }

    pSlider.addEventListener('input', function () {
        if (pInput) pInput.value = pSlider.value;
        syncSlider();
    });

    pInput.addEventListener('input', function () {
        let v    = parseInt(pInput.value) || 0;
        const maxV = +pSlider.max;
        v = Math.min(Math.round(v / POINTS_PER_RM) * POINTS_PER_RM, maxV);
        pInput.value = v;
        syncSlider();
    });

    if (pApplyBtn) {
        pApplyBtn.addEventListener('click', function () {
            const pts = parseInt(pInput.value) || 0;
            if (pts <= 0) { setPFeedback('Move the slider to select points.', 'error'); return; }
            pApplyBtn.disabled = true;
            pApplyBtn.textContent = '...';
            setPFeedback('');
            const fd = new FormData();
            fd.append('action', 'apply_points');
            fd.append('points', pts);
            fetch('cart.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    pApplyBtn.disabled = false;
                    pApplyBtn.textContent = 'Use';
                    if (data.valid) {
                        setPFeedback(data.message, 'success');
                        setTimeout(() => showPtsApplied(data.points, data.discount), 600);
                    } else {
                        setPFeedback(data.message, 'error');
                    }
                })
                .catch(() => {
                    pApplyBtn.disabled = false;
                    pApplyBtn.textContent = 'Use';
                    setPFeedback('Network error. Please try again.', 'error');
                });
        });
    }

    if (pRemoveBtn) {
        pRemoveBtn.addEventListener('click', function () {
            const fd = new FormData();
            fd.append('action', 'remove_points');
            fetch('cart.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(() => showPtsInput())
                .catch(() => showPtsInput());
        });
    }

    updateTotals();
})();
</script>

</body>
</html>