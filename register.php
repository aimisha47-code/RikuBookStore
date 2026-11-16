<?php
session_start();
include 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $check_sql = "SELECT * FROM users WHERE email = '$email'";
        if ($conn->query($check_sql)->num_rows > 0) {
            $error = "Email is already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $words = explode(" ", $name);
            $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
            $joined_date = date("F j, Y");

            $insert_sql = "INSERT INTO users (name, email, password, role, initials, joined_date, address) 
                           VALUES ('$name', '$email', '$hashed_password', 'User', '$initials', '$joined_date', '')";
            
            if ($conn->query($insert_sql) === TRUE) {
                $success = "Account created successfully. You can now login.";
            } else {
                $error = "Database Error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RikuBookstore - Create Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/register.css">
</head>
<body class="flex items-center justify-center min-h-screen p-4 body-gradient">

    <!-- Concept Split Container -->
    <div class="w-full max-w-4xl min-h-[580px] grid md:grid-cols-12 bg-gradient-to-br from-stone-50 to-stone-100 rounded-3xl overflow-hidden register-box p-3 gap-3">
        
        <!-- Left Side: Form Section (7 Columns) -->
        <div class="md:col-span-7 flex flex-col justify-between p-6 sm:p-10">
            
            <!-- Branding Header -->
            <div class="flex items-center space-x-2">
                <img src="image/logo.png" alt="Riku Bookstore" class="h-14 object-contain bg-transparent">
            </div>

            <!-- Core Content Form -->
            <div class="my-auto py-4">
                <h2 class="text-3xl font-normal text-stone-800 tracking-wide mb-1 font-['Playfair_Display']">Create Account</h2>
                <p class="text-xs text-stone-500 font-medium tracking-wide mb-5">Join our sanctuary and track your custom logs</p>

                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 text-red-700 p-3 rounded-xl text-xs mb-4 border border-red-100 flex items-center space-x-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="bg-emerald-50 text-emerald-800 p-3 rounded-xl text-xs mb-4 border border-emerald-100 flex items-center space-x-2">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST" class="space-y-3.5">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-500 mb-1.5 ml-1">Full Name</label>
                        <input type="text" name="name" required class="w-full px-4 py-3 bg-stone-200/40 border border-transparent rounded-xl text-sm focus:outline-none focus:border-amber-700/50 focus:bg-white transition" placeholder="John Doe">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-500 mb-1.5 ml-1">Email Address</label>
                        <input type="email" name="email" required class="w-full px-4 py-3 bg-stone-200/40 border border-transparent rounded-xl text-sm focus:outline-none focus:border-amber-700/50 focus:bg-white transition" placeholder="name@domain.com">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-500 mb-1.5 ml-1">Password</label>
                            <input type="password" name="password" required class="w-full px-4 py-3 bg-stone-200/40 border border-transparent rounded-xl text-sm focus:outline-none focus:border-amber-700/50 focus:bg-white transition" placeholder="••••••••">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-500 mb-1.5 ml-1">Confirm</label>
                            <input type="password" name="confirm_password" required class="w-full px-4 py-3 bg-stone-200/40 border border-transparent rounded-xl text-sm focus:outline-none focus:border-amber-700/50 focus:bg-white transition" placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="w-full concept-btn text-white text-xs py-3.5 rounded-xl font-bold tracking-widest uppercase transition-all duration-300 mt-3">
                        Register
                    </button>
                </form>
            </div>

            <!-- Footer Meta Links -->
            <div class="flex items-center justify-between border-t border-stone-200/60 pt-4 text-xs">
                <p class="text-stone-500">
                    Already registered? <a href="login.php" class="text-amber-800 font-bold hover:underline ml-0.5">Log in</a>
                </p>
                <a href="term.php" class="text-stone-400 hover:text-stone-600 transition">Terms & Privacy</a>
            </div>
        </div>

        <!-- Right Side: Graphic Visual Showcase Panel (5 Columns) -->
        <div class="hidden md:col-span-5 md:block relative rounded-2xl overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-t from-stone-900/40 via-stone-900/10 to-transparent z-10"></div>
            <img src="https://images.unsplash.com/photo-1521587760476-6c12a4b040da?q=80&w=800&auto=format&fit=crop" 
                 alt="Aesthetic Bookstore" 
                 class="absolute inset-0 w-full h-full object-cover transform scale-105 group-hover:scale-100 transition-transform duration-700">
            <div class="absolute bottom-6 left-6 right-6 z-20 text-white">
                <span class="text-[10px] uppercase font-bold tracking-widest opacity-80 bg-stone-900/30 backdrop-blur-md px-2.5 py-1 rounded-full">Knowledge Hub</span>
                <p class="font-['Playfair_Display'] text-xl font-normal mt-2 leading-relaxed">"Discover universes bound between pages."</p>
            </div>
        </div>

    </div>

</body>
</html>