CREATE DATABASE IF NOT EXISTS rikustore_db;
USE rikustore_db;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('User', 'Admin') DEFAULT 'User',
    `initials` VARCHAR(5) NOT NULL,
    `joined_date` VARCHAR(50) NOT NULL,
    `address` TEXT,
    `loyalty_points` INT DEFAULT 0,
    `login_streak` INT DEFAULT 0,
    `last_login_date` DATE NULL,
    `reading_streak` INT DEFAULT 0,
    `last_read_date` DATE NULL,
    `today_reading_seconds` INT DEFAULT 0,
    `protection_used_month` VARCHAR(7) NULL
);

CREATE TABLE `user_badges` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `badge_name` VARCHAR(100) NOT NULL,
    `earned_date` VARCHAR(50) NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `books` (
    `book_id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `sub_title` VARCHAR(255) NOT NULL,
    `author` VARCHAR(100) NOT NULL,
    `isbn` VARCHAR(20) NOT NULL,
    `genre` VARCHAR(50) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `stock_quantity` INT NOT NULL DEFAULT 0,
    `theme_gradient` VARCHAR(100) NOT NULL,
    `border_color` VARCHAR(50) NOT NULL,
    `icon` VARCHAR(50) NOT NULL,
    `cover_image` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `release_date` VARCHAR(50) NULL
);

CREATE TABLE `genres` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `count` INT NOT NULL DEFAULT 0,
    `description` TEXT NOT NULL,
    `bg_gradient` VARCHAR(100) NOT NULL,
    `border_color` VARCHAR(50) NOT NULL,
    `icon` VARCHAR(50) NOT NULL
);

CREATE TABLE `cart` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `book_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`book_id`) REFERENCES `books`(`book_id`) ON DELETE CASCADE
);

CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `order_date` VARCHAR(50) NOT NULL,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `status_color` VARCHAR(100) NOT NULL,
    `items` TEXT NOT NULL,
    `shipping_address` TEXT NULL,
    `voucher_code` VARCHAR(50) NULL,
    `discount_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `points_used` INT NOT NULL DEFAULT 0,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `vouchers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `discount_type` ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
    `discount_value` DECIMAL(10, 2) NOT NULL,
    `min_order` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `max_uses` INT NULL,
    `used_count` INT NOT NULL DEFAULT 0,
    `expiry_date` DATE NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1
);

-- Sample voucher codes
INSERT INTO `vouchers` (`code`, `discount_type`, `discount_value`, `min_order`, `max_uses`, `expiry_date`, `is_active`) VALUES
('SAVE10',    'percent', 10.00,  0.00,  NULL, NULL,         1),
('RM5OFF',    'fixed',    5.00,  0.00,  100,  NULL,         1),
('WELCOME20', 'percent', 20.00, 50.00,  50,   '2027-12-31', 1),
('BOOKS15',   'percent', 15.00, 80.00,  NULL, '2027-06-30', 1);

-- Insert dummy users (Password is 'password123' hashed with bcrypt)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `initials`, `joined_date`, `address`) VALUES
('Syafiq Aiman', 'syafiq.aiman@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', 'SA', 'April 14, 2026', 'No. 14, Jalan Sultan Ismail, Bukit Bintang, 50250 Kuala Lumpur, Malaysia'),
('Alex Rivera', 'alex@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', 'AR', 'January 10, 2026', '123 Tech Avenue, Silicon Valley'),
('Admin User', 'admin@rikustore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'AD', 'May 1, 2026', 'RikuStore HQ');

-- Insert genres
INSERT INTO `genres` (`name`, `slug`, `count`, `description`, `bg_gradient`, `border_color`, `icon`) VALUES
('Computer Science', 'Computer Science', 124, 'Relational database schemas, algorithmic complexity, systems architecture, and logic structures.', 'from-blue-900 to-indigo-950', 'border-blue-800', 'fa-server'),
('Web Development', 'Web Development', 86, 'Full-stack application paradigms, backend script compilation, server deployment, and dynamic layouts.', 'from-violet-900 to-fuchsia-950', 'border-violet-800', 'fa-terminal'),
('Non-Fiction', 'Non-Fiction', 42, 'Investigative journalism, historical narratives, business, and true crime chronicles.', 'from-emerald-900 to-stone-950', 'border-emerald-800', 'fa-money-bill-trend-up'),
('Classic Fiction', 'Fiction', 210, 'Timeless masterworks, historical narratives, literary prose, and creative script compositions.', 'from-red-900 to-rose-950', 'border-red-800', 'fa-feather'),
('Horror', 'Horror', 3, 'Spine-chilling tales of the supernatural, psychological dread, paranormal hauntings, and dark detours into the unknown.', 'from-gray-900 to-red-950', 'border-red-900', 'fa-skull'),
('Romance', 'Romance', 5, 'Heartwarming and heartbreaking love stories — from contemporary romance to paranormal passion and tearjerking relationships.', 'from-pink-900 to-rose-950', 'border-pink-800', 'fa-heart');

-- Insert books
INSERT INTO `books` (`title`, `sub_title`, `author`, `isbn`, `genre`, `price`, `stock_quantity`, `theme_gradient`, `border_color`, `icon`, `cover_image`, `description`, `release_date`) VALUES
('The Garden of Evening Mists', 'A masterpiece of love, art, and memory in Malaya', 'Tan Twan Eng', '978-1401311728', 'Fiction', 39.90, 8, 'from-teal-900 to-emerald-950', 'border-teal-800', 'fa-feather', 'image/garden_evening_mists.png', 'Winner of the Man Asian Literary Prize, this novel tells the story of Yun Ling Teoh, who seeks to build a garden in memory of her sister in the misty Cameron Highlands.', NULL),
('Billion Dollar Whale', 'The global heist that shook the financial world', 'Tom Wright & Bradley Hope', '978-1473663749', 'Non-Fiction', 79.90, 5, 'from-slate-900 to-emerald-950', 'border-emerald-800', 'fa-money-bill-trend-up', 'image/billion_dollar_whale.png', 'The definitive inside story of Jho Low and the 1MDB scandal, tracing a massive multi-billion-dollar heist that funded Hollywood films, luxury yachts, and grand political campaigns.', NULL),
('The Kampung Boy', 'A legendary graphic novel of Malaysian village life', 'Lat', '978-9830501062', 'Fiction', 15.00, 12, 'from-amber-900 to-stone-950', 'border-amber-800', 'fa-child-reaching', 'image/kampung_boy.png', 'The beloved autobiographical comic book depicting the childhood and simple life of a boy growing up in a traditional Perak kampung during the tin mining boom.', NULL),
('The Weight of Our Sky', 'A gripping story of love, survival, and music', 'Hanna Alkaf', '978-1534426092', 'Fiction', 35.00, 6, 'from-red-950 to-orange-950', 'border-red-900', 'fa-music', 'image/weight_of_our_sky.png', 'A music-loving teen with OCD fights to make her way through the violence of the 1969 race riots in Kuala Lumpur to find her mother.', NULL),
('Database System Concepts', 'The academic foundation of modern databases', 'Abraham Silberschatz', '978-0073523323', 'Computer Science', 150.00, 10, 'from-blue-900 to-indigo-950', 'border-blue-950', 'fa-server', 'image/database_system_concepts.png', 'A comprehensive, standard textbook used by computer science students in top Malaysian universities like UM and UTM to learn database concepts.', 'May 2026'),
('Eloquent JavaScript, 3rd Edition', 'A modern introduction to programming', 'Marijn Haverbeke', '978-1593279509', 'Web Development', 95.00, 4, 'from-yellow-950 to-stone-950', 'border-yellow-600', 'fa-terminal', 'image/eloquent_javascript.png', 'A globally popular guide to JavaScript, widely recommended in the Malaysian tech community for web developers from novice to advanced levels.', 'June 2026'),
('Customer Experience Analytics', 'The Key to Real-Time, Adaptive Customer Relationships', 'Arnoud Sathi', '978-1583473610', 'Non-Fiction', 119.00, 7, 'from-sky-900 to-indigo-950', 'border-sky-700', 'fa-chart-line', 'image/customer_experience_analytics.jpg', 'Through a series of case studies from various industries, this book explores how customer experience analytics (CEA) is reshaping business, helping companies gain competitive advantage and real-time relationships with customers.', NULL),
('Solution-focused Business Coaching', 'A Guide for Individual and Team Coaching', 'Jörg Middendorf', '978-3031077074', 'Non-Fiction', 105.00, 5, 'from-teal-800 to-emerald-950', 'border-teal-600', 'fa-people-group', 'image/solution_focused_coaching.jpg', 'This book presents the fundamentals and applications of solution-focused coaching for both individual and team settings, with central tools, methods, and a rich collection of coaching questions for practical use.', '2022'),
('Handbook on Blockchain', 'A Comprehensive Survey of Blockchain Technology', 'Duc A. Tran, My T. Thai, Bhaskar Krishnamachari', '978-3031070143', 'Computer Science', 185.00, 4, 'from-violet-900 to-purple-950', 'border-violet-700', 'fa-link', 'image/handbook_on_blockchain.jpg', 'This handbook serves as a one-stop reference for survey and expository contributions on blockchain technology, covering fundamentals, applications, and deep details across technology, business, cryptocurrency, and engineering.', '2022'),
('Normal People', 'A story about human connection and the complexity of love', 'Sally Rooney', '978-0571334650', 'Romance', 49.90, 10, 'from-yellow-700 to-amber-950', 'border-yellow-600', 'fa-heart', 'image/normal_people.jpg', 'Normal People by Sally Rooney is a poignant coming-of-age novel tracking the magnetic, evolving relationship between Marianne and Connell, spanning from their high school days in rural Ireland to university in Dublin.', '2018'),
('Twilight', 'Deeply seductive and extraordinarily suspenseful', 'Stephenie Meyer', '978-0316015844', 'Romance', 39.90, 15, 'from-stone-900 to-slate-950', 'border-stone-700', 'fa-moon', 'image/twilight.jpg', 'About three things Stephenie Meyer was absolutely positive. First, Edward was a vampire. Second, there was a part of him that thirsted for her blood. And third, she was unconditionally and irrevocably in love with him.', '2007'),
('Me Before You', 'The love story that captured over 20 million hearts', 'Jojo Moyes', '978-0143124542', 'Romance', 44.90, 8, 'from-pink-800 to-rose-950', 'border-pink-700', 'fa-heart-pulse', 'image/me_before_you.jpg', 'Louisa Clark is an ordinary girl living an extraordinarily ordinary life until she takes a job working for Will Traynor. A heartbreakingly romantic novel that asks: what do you do when making the person you love happy also means breaking your own heart?', '2012'),
('I Know a Place', 'Best Stop and Other Dark Detours', 'Nat Cassidy', '978-1960721174', 'Horror', 65.00, 6, 'from-gray-950 to-red-950', 'border-red-900', 'fa-skull', 'image/i_know_a_place.jpg', 'There are locations in this world where the light does not seem to reach. I Know a Place collects dark, suspenseful horror tales about the places we fear most — a gas station rest stop, a haunted kitchen, a yoga retreat in the desert — all linked by creeping dread.', '2026'),
('A Fig for All the Devils', 'A dark and suspenseful horror novel', 'C.S. Fries', '978-1958998007', 'Horror', 55.00, 8, 'from-stone-900 to-gray-950', 'border-stone-700', 'fa-ghost', 'image/a_fig_for_all_the_devils.jpg', 'An abused, grief-stricken, and impoverished Sonny has all but given up on life — until he meets Death, by way of the Grim Reaper. The Reaper grooms Sonny as his apprentice, training him in the ways of death and dying through suspenseful and horror-laced events, giving Sonny something he never intended: something to live for.', '2022'),
('She Waits Where Shadows Gather', 'Some inherit memories. Others inherit monsters.', 'Michelle Tang', '978-1728289892', 'Horror', 69.90, 7, 'from-red-950 to-stone-950', 'border-red-800', 'fa-eye', 'image/she_waits_where_shadows_gather.jpg', 'Avery and Carlos Tam have built their lives on logic, not legends. But when they travel to his ancestral home in the Philippines, darkness clings to every corner. The mirrors are shrouded. The housekeeper will not stay in the house alone. And no one will speak of the tragedies the family has seen. A brutal car crash traps them there — and the house begins to speak.', '2026'),
('It Ends with Us', 'Sometimes it is the one who loves you who hurts you the most', 'Colleen Hoover', '9781501110375', 'Romance', 49.90, 12, 'from-purple-900 to-pink-950', 'border-purple-700', 'fa-heart-crack', 'image/it_ends_with_us.jpg', 'Lily hasn\'t always had it easy, but she\'s never stopped her from working hard for the life she wants. When she feels a spark with a gorgeous neurosurgeon named Ryle Kincaid, everything seems too good to be true. But as questions about her new relationship swirl, so do thoughts of her first love — Atlas Corrigan — and a link to the past she left behind.', '2016'),
('The Summer I Turned Pretty', 'Some summers are just destined to be pretty', 'Jenny Han', '9781416968290', 'Romance', 42.90, 10, 'from-sky-800 to-teal-950', 'border-sky-600', 'fa-umbrella-beach', 'image/the_summer_i_turned_pretty.jpg', 'Belly measures her life in summers. Every good, magical thing happens between June and August. Winters are simply a time to count the weeks until next summer — a place away from the beach house, away from Susannah, and most importantly, away from Jeremiah and Conrad, the boys who have been her very best friends.', '2010');

-- Insert mock cart items for Syafiq Aiman (User 1)
INSERT INTO `cart` (`user_id`, `book_id`, `quantity`) VALUES
(1, 1, 1),
(1, 2, 1),
(1, 3, 1);

-- Insert mock orders for Syafiq Aiman (User 1)
INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `total_amount`, `status`, `status_color`, `items`, `shipping_address`) VALUES
('ORD-2026-8941', 1, 'May 28, 2026', 89.49, 'Shipped', 'bg-emerald-100 text-emerald-800 border-emerald-200', 'Fundamentals of SQL & Schema Design (1)', 'No. 14, Jalan Sultan Ismail, Bukit Bintang, 50250 Kuala Lumpur, Malaysia'),
('ORD-2026-7720', 1, 'June 02, 2026', 31.20, 'Pending Control', 'bg-amber-100 text-amber-800 border-amber-200', 'Eloquent JavaScript, 3rd Edition (1)', 'No. 14, Jalan Sultan Ismail, Bukit Bintang, 50250 Kuala Lumpur, Malaysia');
