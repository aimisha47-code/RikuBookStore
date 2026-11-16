<?php
session_start();
include 'db_connect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $role_selection = $_POST['role_selection']; // 'User' or 'Admin'

    $sql = "SELECT * FROM users WHERE email = '$email' AND role = '$role_selection'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['initials'] = $user['initials'];
            
            // Streak Logic
            $today = date('Y-m-d');
            $current_month = date('Y-m');
            $last_login_date = $user['last_login_date'];
            $login_streak = (int)$user['login_streak'];
            $protection_used_month = $user['protection_used_month'];
            $loyalty_points = (int)$user['loyalty_points'];

            if ($last_login_date !== $today) {
                if ($last_login_date === date('Y-m-d', strtotime('-1 day'))) {
                    // Consecutive day
                    $login_streak++;
                    $loyalty_points += 10;
                } else if ($last_login_date === date('Y-m-d', strtotime('-2 days')) && $protection_used_month !== $current_month) {
                    // Use protection (missed exactly 1 day)
                    $protection_used_month = $current_month;
                    $login_streak++;
                    $loyalty_points += 10;
                } else {
                    // Streak broken or first login
                    $login_streak = 1;
                    $loyalty_points += 10;
                }
                
                $protect_val = $protection_used_month ? "'$protection_used_month'" : "NULL";
                $update_sql = "UPDATE users SET login_streak=$login_streak, last_login_date='$today', protection_used_month=$protect_val, loyalty_points=$loyalty_points WHERE id=" . $user['id'];
                $conn->query($update_sql);
            }

            if ($user['role'] === 'Admin') {
                header("Location: admin_home.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Account not found or role mismatch.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RikuBookstore - Access Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body class="flex items-center justify-center min-h-screen p-4 body-gradient">

    <!-- Concept Split Container -->
    <div class="w-full max-w-4xl min-h-[580px] grid md:grid-cols-12 bg-gradient-to-br from-stone-50 to-stone-100 rounded-3xl overflow-hidden login-box p-3 gap-3">
        
        <!-- Left Side: Form Section (7 Columns) -->
        <div class="md:col-span-7 flex flex-col justify-between p-6 sm:p-10">
            
            <!-- Branding Header -->
            <div class="flex items-center space-x-2">
                <img src="image/logo.png" alt="Riku Bookstore" class="h-14 object-contain bg-transparent">
            </div>

            <!-- Core Content Form -->
            <div class="my-auto py-6">
                <h2 class="text-3xl font-normal text-stone-800 tracking-wide mb-1 font-['Playfair_Display']">Welcome Back</h2>
                <p class="text-xs text-stone-500 font-medium tracking-wide mb-6">Enter your details to access your dashboard</p>

                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 text-red-700 p-3 rounded-xl text-xs mb-5 border border-red-100 flex items-center space-x-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-500 mb-1.5 ml-1">Email Address</label>
                        <input type="email" name="email" required class="w-full px-4 py-3 bg-stone-200/40 border border-transparent rounded-xl text-sm focus:outline-none focus:border-amber-700/50 focus:bg-white transition" placeholder="name@domain.com">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-500 mb-1.5 ml-1">Security Key</label>
                        <input type="password" name="password" required class="w-full px-4 py-3 bg-stone-200/40 border border-transparent rounded-xl text-sm focus:outline-none focus:border-amber-700/50 focus:bg-white transition" placeholder="••••••••">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-500 mb-2 ml-1">Access Level</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer">
                                <input type="radio" name="role_selection" value="User" class="peer sr-only" checked>
                                <div class="text-center py-2.5 border border-stone-200 rounded-xl text-xs font-semibold text-stone-500 peer-checked:border-amber-800 peer-checked:bg-amber-50/60 peer-checked:text-amber-900 transition">
                                    <i class="fa-regular fa-user mr-1"></i> User
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="role_selection" value="Admin" class="peer sr-only">
                                <div class="text-center py-2.5 border border-stone-200 rounded-xl text-xs font-semibold text-stone-500 peer-checked:border-stone-800 peer-checked:bg-stone-900 peer-checked:text-white transition">
                                    <i class="fa-solid fa-shield-halved mr-1"></i> Admin
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="w-full concept-btn text-white text-xs py-3.5 rounded-xl font-bold tracking-widest uppercase transition-all duration-300 mt-2">
                        Log In
                    </button>
                </form>
            </div>

            <!-- Footer Meta Links -->
            <div class="flex items-center justify-between border-t border-stone-200/60 pt-4 text-xs">
                <p class="text-stone-500">
                    New collector? <a href="register.php" class="text-amber-800 font-bold hover:underline ml-0.5">Register</a>
                </p>
                <a href="term.php" class="text-stone-400 hover:text-stone-600 transition">Terms & Privacy</a>
            </div>
        </div>

        <!-- Right Side: Graphic Visual Showcase Panel (5 Columns) -->
        <div class="hidden md:col-span-5 md:block relative rounded-2xl overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-t from-stone-900/40 via-stone-900/10 to-transparent z-10"></div>
            <img src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?q=80&w=800&auto=format&fit=crop" 
                 alt="Library Space" 
                 class="absolute inset-0 w-full h-full object-cover transform scale-105 group-hover:scale-100 transition-transform duration-700">
            <div class="absolute bottom-6 left-6 right-6 z-20 text-white">
                <span class="text-[10px] uppercase font-bold tracking-widest opacity-80 bg-stone-900/30 backdrop-blur-md px-2.5 py-1 rounded-full">Curated Collection</span>
                <p class="font-['Playfair_Display'] text-xl font-normal mt-2 leading-relaxed">"A room without books is like a body without a soul."</p>
            </div>
        </div>

    </div>

</body>
</html>