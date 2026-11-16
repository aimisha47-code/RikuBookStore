<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RikuBookstore - Terms & Conditions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css"> <!-- Reuses your theme configuration -->
</head>
<body class="flex items-center justify-center min-h-screen p-4 body-gradient">

    <!-- Card Container -->
    <div class="w-full max-w-3xl bg-gradient-to-br from-stone-50 to-stone-100 rounded-3xl overflow-hidden login-box p-6 sm:p-10 border border-stone-200/50 shadow-2xl">
        
        <!-- Header Branding Section -->
        <div class="text-center mb-8 border-b border-stone-200/60 pb-6">
            <img src="image/logo.png" alt="Riku Bookstore" class="h-16 mx-auto object-contain bg-transparent mb-3">
            <h1 class="text-3xl font-normal text-stone-800 tracking-wide font-['Playfair_Display']">Terms & Conditions</h1>
            <p class="text-xs text-stone-500 font-medium tracking-wide mt-1">Last updated: June 2026</p>
        </div>

        <!-- Scrollable Terms Body Content -->
        <div class="space-y-6 text-stone-600 text-sm max-h-[380px] overflow-y-auto pr-2 scrollbar-thin">
            <section>
                <h3 class="text-stone-800 font-semibold mb-2 font-['Playfair_Display'] text-base">1. Acceptance of Terms</h3>
                <p class="leading-relaxed text-stone-500 text-xs">
                    Welcome to RikuBookstore. By accessing or initializing an entry registry on our network portal, you implicitly agree to follow, respect, and be governed by these operational guidelines and community rules.
                </p>
            </section>

            <section>
                <h3 class="text-stone-800 font-semibold mb-2 font-['Playfair_Display'] text-base">2. Account Responsibility & Security Keys</h3>
                <p class="leading-relaxed text-stone-500 text-xs">
                    When you create an account, you receive a secure digital identity block. You are completely accountable for locking down your specific system security key. Any activity logged via your system initials or profile will be tracked directly back to your entry registry.
                </p>
            </section>

            <section>
                <h3 class="text-stone-800 font-semibold mb-2 font-['Playfair_Display'] text-base">3. Use of Curated Content</h3>
                <p class="leading-relaxed text-stone-500 text-xs">
                    All digital book representations, covers, metadata assets, logos, and custom interface designs present inside RikuBookstore are the protected architectural assets of the platform. Scraping or running automated pipelines against our catalogs without permission is restricted.
                </p>
            </section>

            <section>
                <h3 class="text-stone-800 font-semibold mb-2 font-['Playfair_Display'] text-base">4. Privacy Framework</h3>
                <p class="leading-relaxed text-stone-500 text-xs">
                    Your standard telemetry data (Full Name, Encrypted Credentials, System Initials, and Activity Logs) is tightly enclosed within our secure server layer. We do not transfer your personal digital data to independent third-party tracking companies.
                </p>
            </section>
        </div>

        <!-- Action / Navigation Footer -->
        <div class="mt-8 pt-5 border-t border-stone-200/60 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-stone-500 text-center sm:text-left">
                Questions? Return to the portal setup interface.
            </p>
            <div class="flex space-x-3 w-full sm:w-auto">
                <a href="javascript:history.back()" class="text-center w-full sm:w-auto px-5 py-2.5 bg-stone-200 hover:bg-stone-300/80 text-stone-700 text-xs font-bold tracking-wider rounded-xl transition duration-200">
                    Go Back
                </a>
                <a href="register.php" class="text-center w-full sm:w-auto px-6 py-2.5 concept-btn text-white text-xs font-bold tracking-wider rounded-xl transition duration-200">
                    Accept & Register
                </a>
            </div>
        </div>

    </div>

    <!-- Scrollbar Visual Consistency Styling -->
    <style>
        .scrollbar-thin::-webkit-scrollbar { width: 5px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #d6d3d1; border-radius: 99px; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #a78b71; }
    </style>

</body>
</html>