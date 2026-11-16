<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Get book details
$sql = "SELECT * FROM books WHERE book_id = $book_id";
$res = $conn->query($sql);
if ($res && $res->num_rows > 0) {
    $book = $res->fetch_assoc();
} else {
    // Fallback if book not found
    $book = [
        'title' => 'Digital Prototype Manuscript',
        'author' => 'System Architect',
        'genre' => 'Testing'
    ];
}

// Define dynamic book content
$book_title = $book['title'] ?? '';
$chapter_title = "Chapter One";
$paragraphs = [];

if (strpos($book_title, 'Garden of Evening Mists') !== false) {
    $chapter_title = "Chapter One: The Mountain and the Mist";
    $paragraphs = [
        "On the mountain there is a garden. It is a garden of shadows and soft, green hills, built by a man who once served the Emperor of Japan. My sister Yun Hong and I had dreamed of such a place during the long, dark years of the war.",
        "The Cameron Highlands were cool, a welcome relief from the stifling heat of the plains. As I stood at the edge of the tea plantation, the mist swirled around my knees like silk. Yugiri, it was called—the garden of evening mists. It was a place where time seemed to slow, caught in the transition between day and night.",
        "I had come to find Nakamura Aritomo. He was said to be the greatest gardener in Malaya, a man of quiet intensity and hidden depths. I needed him to help me build a memorial for Yun Hong, a garden that would capture her spirit and keep it safe from the ghosts that haunted my sleep."
    ];
} elseif (strpos($book_title, 'Billion Dollar Whale') !== false) {
    $chapter_title = "Chapter One: The Deck of the Yacht";
    $paragraphs = [
        "In the summer of 2012, a young Malaysian man named Jho Low stood on the deck of a superyacht in the Mediterranean. Around him, Hollywood stars and international models toasted with champagne. None of them knew that the money paying for this luxury had been diverted from a sovereign wealth fund.",
        "The night was warm and the music was loud. Jho Low smiled, sipping a carbonated drink, his round face glowing under the disco lights. To the people around him, he was a mysterious billionaire from Penang with connections to the highest levels of government. In reality, he was orchestrating one of the largest financial frauds in history.",
        "This is the story of how a young man from Malaysia manipulated global banks, Hollywood actors, and prime ministers, creating an illusion of wealth that eventually came crashing down around him."
    ];
} elseif (strpos($book_title, 'Kampung Boy') !== false) {
    $chapter_title = "Chapter One: Life in the Valley";
    $paragraphs = [
        "I was born in a house built on stilts, surrounded by rubber trees and coconut palms in the heart of Perak. In the afternoons, my friends and I would run down to the river, ignoring the warnings of our parents about crocodiles and water spirits.",
        "Life in the kampung was simple and free. We did not have televisions or video games, but we had the entire valley as our playground. We would catch fighting fish in the paddy fields, climb durian trees, and listen to the village elders tell stories of ghosts under the moonlight.",
        "This comic record traces the memories of those golden days, the warmth of the village community, and the sounds of laughter that echoed through the hills before the modern world arrived."
    ];
} elseif (strpos($book_title, 'Weight of Our Sky') !== false) {
    $chapter_title = "Chapter One: Counting to Three";
    $paragraphs = [
        "The Djinn inside me counts to three. One, two, three. If I don't tap my foot, the sky will fall. The street is filled with the smell of satay and burning fuel, but underneath it, there is a tension that feels like a coiled spring waiting to snap on this warm May evening in Kuala Lumpur.",
        "My mother is somewhere in the city, working at the hospital. I can hear the distant sounds of shouting and sirens, but I force myself to focus on my feet. One, two, three. Tap. One, two, three. Tap. If I stop, the fire will consume us all.",
        "As the shadows grow longer over Jalan Tunku Abdul Rahman, I realize that the world I knew is changing forever, and survival means running through a storm of fire and memory."
    ];
} elseif (strpos($book_title, 'Database System Concepts') !== false) {
    $chapter_title = "Chapter One: Introduction to DBMS";
    $paragraphs = [
        "A database-management system (DBMS) is a collection of interrelated data and a set of programs to access those data. The primary goal of a DBMS is to provide a way to store and retrieve database information that is both convenient and efficient.",
        "Database systems are designed to manage large bodies of information. Management of data involves both defining structures for storage of information and providing mechanisms for the manipulation of information.",
        "In this chapter, we discuss the historical development of database systems, their architecture, data models, and the fundamental differences between file-processing systems and modern database engines used in enterprise setups."
    ];
} elseif (strpos($book_title, 'Eloquent JavaScript') !== false) {
    $chapter_title = "Chapter One: Values, Types, and Operators";
    $paragraphs = [
        "Inside the computer's world, there is only data. You can read data, modify data, create new data—but anything that isn't data simply does not exist. All this data is represented as a long sequence of bits.",
        "JavaScript is the language of the web, a tool that brings life and interactivity to static web pages. It is a language that is both flexible and powerful, allowing developers to write everything from small scripts to large-scale applications.",
        "We will start by examining the basic building blocks of the language: numbers, strings, booleans, and the operators that allow us to combine and manipulate these values into meaningful logic."
    ];
} elseif (strpos($book_title, 'Customer Experience Analytics') !== false) {
    $chapter_title = "Chapter One: The Analytics Imperative";
    $paragraphs = [
        "In today's hyper-connected marketplace, every customer interaction leaves a trail of data. Customer Experience Analytics (CEA) is the discipline of capturing, analysing, and acting on that data in real time — transforming raw signals into the strategic intelligence that separates leading companies from the rest.",
        "Through a series of case studies drawn from retail, financial services, and telecommunications, this chapter demonstrates how CEA reshapes the relationship between a business and its customers. Rather than treating the customer as a passive recipient of a product, CEA positions the customer as an active partner whose behaviour continuously informs and improves the company's offerings.",
        "The architecture that powers CEA combines advanced analytics engines, streaming data pipelines, and a business values model. Together, these components allow organisations to detect shifting preferences, anticipate churn, and deliver personalised experiences at scale — turning customer data into a sustainable competitive advantage."
    ];
} elseif (strpos($book_title, 'Solution-focused Business Coaching') !== false) {
    $chapter_title = "Chapter One: The Solution-Focused Mindset";
    $paragraphs = [
        "Solution-focused coaching begins with a deceptively simple premise: instead of spending the majority of the session analysing the problem in depth, coach and coachee invest their energy in constructing a vivid, concrete picture of the desired future. This shift in orientation — from problem to solution — is both philosophically elegant and practically powerful.",
        "In individual coaching, the technique manifests as a series of carefully calibrated questions. What would be different if the problem were already solved? What resources have helped you in similar situations before? What is already working, however slightly? Each question nudges the coachee toward resourcefulness rather than helplessness, building momentum from the very first conversation.",
        "When applied to teams, solution-focused methods create a shared language for progress. Members who might otherwise talk past each other begin to describe the future they want in precise, behavioural terms. This book provides coaches and HR professionals with the central tools, question banks, and workshop formats needed to facilitate these conversations with confidence."
    ];
} elseif (strpos($book_title, 'Handbook on Blockchain') !== false) {
    $chapter_title = "Chapter One: Foundations of Blockchain Technology";
    $paragraphs = [
        "At its core, a blockchain is a distributed ledger — an append-only sequence of blocks, each containing a cryptographically secured record of transactions. Unlike a traditional database administered by a central authority, a blockchain is maintained collectively by a network of participants who reach consensus on the validity of new entries without the need to trust any single party.",
        "The technology traces its origins to Satoshi Nakamoto's 2008 white paper, which introduced Bitcoin as a peer-to-peer electronic cash system. Since then, the underlying principles have evolved far beyond digital currency. Ethereum generalised the concept by introducing programmable smart contracts, enabling developers to encode complex business logic directly onto the chain and execute it autonomously.",
        "This handbook aims to serve as a one-stop reference, curating survey and expository contributions from leading researchers in computer science, engineering, finance, and social science. It covers a comprehensive range of topics — from consensus algorithms and cryptographic primitives to enterprise applications, regulatory considerations, and the emerging decentralised finance ecosystem."
    ];
} elseif (strpos($book_title, 'Normal People') !== false) {
    $chapter_title = "Chapter One: January";
    $paragraphs = [
        "Connell and Marianne grew up in the same small town in the west of Ireland. He is popular and well-liked at school; she is gawky and isolated. But when Connell comes to collect his mother from her job at Marianne's house, a strange thing begins to happen — a connection forms between them, careful and electric, the kind that neither knows quite how to name.",
        "They never discuss what happens between them when no one is watching. Marianne understands this. She is not the kind of girl who gets to have things in the open, things that belong to her in the ordinary way. She is too strange, too solitary, and she knows it. So she takes what she can get.",
        "At school he passes her in the corridor without acknowledgment, and she does the same. But alone, in the warmth of her house, something loosens in both of them — words come easier, laughter arrives unbidden — and they are, each of them, more themselves than they ever manage to be anywhere else."
    ];
} elseif (strpos($book_title, 'Twilight') !== false) {
    $chapter_title = "Chapter One: First Sight";
    $paragraphs = [
        "My mother drove me to the airport with the windows rolled down. It was seventy-five degrees in Phoenix, the sky a perfect, cloudless blue. I was wearing my favourite shirt — sleeveless, white eyelet lace — and I was sweating already. I would not see such a sky again for a long time. I was going to Forks, the rainiest town in the Olympic Peninsula of Washington State.",
        "The small, white house at the end of a long, unpaved road was exactly as I remembered it. The green shutters, the porch with the swing, the old Chevy truck in the drive. Charlie, my father, was waiting at the door, looking just as I had left him three years before — perhaps a little greyer at the temples, a little more creased around the eyes.",
        "At school the next morning, I noticed him immediately in the cafeteria — the table of five students who were so beautiful, so breathtakingly pale, that they seemed to glow against the grey Washington light. He was the most beautiful of all of them. And when he turned and saw me looking, his golden eyes held mine for one long, strange moment, and I felt something cold move through me that had nothing to do with the rain."
    ];
} elseif (strpos($book_title, 'Me Before You') !== false) {
    $chapter_title = "Chapter One: Louisa";
    $paragraphs = [
        "I had worked at The Buttered Bun for six years and I knew the menu so well I could recite it backwards in my sleep. Thomas Trant always had the full English with extra black pudding. Kev and Angelo from the builders' yard took a baguette each, one ham and one cheese, and two large teas. I liked the regulars. I liked knowing that everything in my day would unfold roughly as I expected it to.",
        "When Frank called me into the back office, I still thought it was about the new coffee machine. It took me a moment — quite a long moment, if I am honest — to understand that the café was closing. That it had been losing money for months. That from the end of the week, I no longer had a job.",
        "That was how I came to be sitting in front of my laptop two weeks later, following a link my sister Treena had sent me about a position as a carer for a local family in our village. I had no qualifications, no experience, and no particular inclination toward caring for anyone. But rent was due, and I was thirty-six pounds overdrawn, so I put on my best interview blouse and rang the bell at Granta House — and that was when everything changed."
    ];
} elseif (strpos($book_title, 'I Know a Place') !== false) {
    $chapter_title = "Rest Stop: You Are Here";
    $paragraphs = [
        "There are locations in this world where the light does not seem to reach. You have passed them before — at the edge of a motorway, behind the glow of a petrol station sign that flickers once, twice, and then holds steady with an effort that seems almost deliberate. The kind of place where the fluorescent hum is the loudest sound and the attendant behind the counter does not look up when the door opens.",
        "Marcy had been driving for six hours when she saw the sign. REST STOP — 1 MILE. She was not tired, not exactly. It was something else, something that pressed behind her eyes and made the painted lines in the road seem to pulse. She told herself she only needed to stretch her legs. She told herself she would be back on the road in ten minutes.",
        "The parking lot held three cars. None of them showed any signs of recent arrival — no warmth in their hoods, no dust disturbed. The bathrooms were at the far end of a covered walkway, and as Marcy walked toward them, she became aware of a sound she could not identify. Not mechanical. Not wind. Something older than both."
    ];
} elseif (strpos($book_title, 'A Fig for All the Devils') !== false) {
    $chapter_title = "Chapter One: The End of Something";
    $paragraphs = [
        "Sonny Reyes had given up on a lot of things by the time he turned twenty-three. He had given up on his mother calling back. He had given up on the landlord fixing the boiler. He had given up on the idea that things were going to get better in any measurable way — not out of pessimism, exactly, but out of a kind of arithmetic that felt as honest as anything he had ever learned.",
        "He was sitting on the edge of the overpass at half past midnight when the figure appeared beside him. Not from any direction he could name. Simply there, the way a word sometimes arrives fully formed, without the sensation of having been constructed. The figure wore a coat the colour of November. It did not look at him but addressed him by name.",
        "Death, as it turned out, was not interested in Sonny's particular despair. Death was interested in his schedule. There was a great deal of work to be done, Death explained — a backlog had accumulated — and Sonny, who had demonstrated a singular talent for being present at the wrong moment, was, as a result, ideally qualified. It was, all things considered, the first job offer Sonny had ever received that he did not immediately want to refuse."
    ];
} elseif (strpos($book_title, 'She Waits Where Shadows Gather') !== false) {
    $chapter_title = "Chapter One: Parents Should Pass Down Stories, Not Spirits";
    $paragraphs = [
        "Avery Tam did not believe in ghosts. She had spent eleven years building that disbelief into something structural, something load-bearing — the way an engineer calculates stress tolerances. She had a methodology for it. When a floorboard settled, she identified the cause. When a mirror seemed to hold her reflection a half-second too long, she blinked and looked again. It was always fine. It was always just the light.",
        "The house in Batangas had been in Carlos's family for four generations. He had not spoken of it often, and when he did, it was with the particular brevity of someone describing a place they have not quite decided to remember. The caretaker, he mentioned once, had not been found for the last two years. The family had assumed she had retired. They had not followed up.",
        "Their flight landed at two in the afternoon, and by the time the car turned onto the unpaved track that led through the provincial trees, the sky had already gone the colour of a bruise. The house was larger than Avery had imagined. Every window was covered from the inside. From the front gate, at a distance of thirty metres, she could see the mirrors — shrouded in cloth so dark it seemed to absorb what remained of the light."
    ];
} elseif (strpos($book_title, 'It Ends with Us') !== false) {
    $chapter_title = "Chapter One: The Rooftop";
    $paragraphs = [
        "I stood at the edge of the roof, looking down at the streets of Boston. The air was cold, but my anger kept me warm. I had just given a eulogy for a father I hated, a man who had abused my mother for as long as I could remember, and yet everyone in that church had looked at me as if I was the one who was broken.",
        "It was then that I heard the door open behind me. I didn't turn around. I didn't want anyone to see the tears on my face, or the dark thoughts in my eyes. But when I heard the sound of someone kicking a patio chair across the gravel, I realized I wasn't alone. A man in a dark coat was pacing near the ledge, looking even more furious than I felt.",
        "His name was Ryle Kincaid. He was a neurosurgeon, a man whose life was built on control and precision. In the hours that followed, under the stars and above the noise of the city, we shared our naked truths—the secrets we would never tell anyone else, the moments that defined our scars."
    ];
} elseif (strpos($book_title, 'The Summer I Turned Pretty') !== false) {
    $chapter_title = "Chapter One: The Road to Cousins";
    $paragraphs = [
        "Some summers are just destined to be pretty. For as long as I can remember, my life has been measured in summers. Everything good, everything magical, happens between June and August. The rest of the year is just a waiting game, a long, grey countdown to Cousins Beach.",
        "My mother was driving, her hands tapping the steering wheel to some old folk song on the radio. My brother Steven was asleep in the passenger seat, his mouth slightly open. I was in the back, staring out the window at the highway signs, feeling the heat rise from the asphalt. We were almost there.",
        "When we pulled up to the big white house on the hill, Susannah was already standing on the porch, her arms wide. Behind her were Conrad and Jeremiah. I saw Conrad first, standing in the shadow of the roof, looking taller, his hair longer, his eyes dark and guarded. I felt a sudden, familiar flutter in my chest, the realization that this summer would be different from all the rest."
    ];
} else {

    // Fallback Lorem Ipsum
    $chapter_title = "Chapter One: The Prototype";
    $paragraphs = [
        "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.",
        "Curabitur pretium tincidunt lacus. Nulla gravida orci a odio. Nullam varius, turpis et commodo pharetra, est eros bibendum elit, nec luctus magna felis sollicitudin mauris. Integer in mauris eu nibh euismod gravida. Duis ac tellus et risus vulputate vehicula.",
        "Aliquam faucibus, elit ut dictum aliquet, felis nisl adipiscing sapien, sed malesuada diam lacus eget erat. Cras mollis scelerisque nunc. Nullam arcu. Aliquam consequat. Curabitur augue lorem, dapibus quis, laoreet et, pretium ac, nisi."
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading: <?php echo htmlspecialchars($book['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .book-content p {
            margin-bottom: 1.5rem;
            line-height: 1.8;
            font-size: 1.125rem;
            color: #333;
        }
    </style>
</head>
<body class="bg-[#f4f1ea] text-stone-800 min-h-screen flex flex-col font-serif">

    <!-- Top Reading Bar -->
    <div class="bg-white/80 backdrop-blur border-b border-stone-200 sticky top-0 z-50 px-6 py-3 flex justify-between items-center shadow-sm">
        <a href="bookshelf.php" class="text-stone-500 hover:text-stone-900 transition flex items-center gap-2 font-sans text-sm font-semibold">
            <i class="fa-solid fa-arrow-left"></i> Back to Bookshelf
        </a>
        <div class="text-center">
            <h1 class="font-bold text-stone-900 leading-tight"><?php echo htmlspecialchars($book['title']); ?></h1>
            <p class="text-xs text-stone-500 font-sans tracking-widest uppercase"><?php echo htmlspecialchars($book['author']); ?></p>
        </div>
        <div class="flex items-center gap-3 font-sans text-sm">
            <!-- Active Indicator -->
            <div id="status-indicator" class="flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 rounded-full border border-green-200 text-xs font-bold transition-all duration-300">
                <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div> Active Reading
            </div>
            <a href="streak.php" class="text-amber-700 hover:text-amber-900 font-bold px-3 py-1 border border-amber-200 bg-amber-50 rounded transition">
                <i class="fa-solid fa-fire text-amber-500 mr-1"></i> Streaks
            </a>
        </div>
    </div>

    <!-- Reading Content -->
    <main class="max-w-3xl mx-auto px-6 py-16 flex-grow w-full book-content font-['Playfair_Display']">
        <h2 class="text-4xl text-center mb-16 italic"><?php echo htmlspecialchars($chapter_title); ?></h2>
        
        <?php foreach ($paragraphs as $index => $para): ?>
            <?php if ($index === 0): ?>
                <p>
                    <span class="text-6xl float-left mr-3 mt-2 leading-none font-bold text-stone-900">
                        <?php echo htmlspecialchars(mb_substr($para, 0, 1)); ?>
                    </span>
                    <?php echo htmlspecialchars(mb_substr($para, 1)); ?>
                </p>
            <?php else: ?>
                <p><?php echo htmlspecialchars($para); ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <p class="text-center text-stone-400 mt-12 italic">* End of excerpt *</p>
    </main>

    <!-- Tracking Script -->
    <script>
        let isIdle = false;
        let idleTimeout;
        let readingInterval;
        let activeSeconds = 0;
        
        const IDLE_TIME = 30000; // 30 seconds
        const HEARTBEAT_INTERVAL = 10000; // 10 seconds
        const statusIndicator = document.getElementById('status-indicator');

        function resetIdleTimer() {
            if (isIdle) {
                isIdle = false;
                startReading();
                statusIndicator.className = "flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 rounded-full border border-green-200 text-xs font-bold transition-all duration-300";
                statusIndicator.innerHTML = '<div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div> Active Reading';
            }
            
            clearTimeout(idleTimeout);
            idleTimeout = setTimeout(() => {
                isIdle = true;
                pauseReading();
                statusIndicator.className = "flex items-center gap-1.5 px-3 py-1 bg-stone-100 text-stone-500 rounded-full border border-stone-200 text-xs font-bold transition-all duration-300";
                statusIndicator.innerHTML = '<div class="w-2 h-2 rounded-full bg-stone-400"></div> Idle/Paused';
            }, IDLE_TIME);
        }

        function sendHeartbeat(seconds) {
            fetch('track_reading.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ seconds: seconds })
            }).then(response => response.json())
              .then(data => {
                  console.log("Reading logged:", data);
              }).catch(err => console.error("Error logging reading:", err));
        }

        function startReading() {
            clearInterval(readingInterval);
            readingInterval = setInterval(() => {
                if (!isIdle) {
                    sendHeartbeat(HEARTBEAT_INTERVAL / 1000);
                }
            }, HEARTBEAT_INTERVAL);
        }

        function pauseReading() {
            clearInterval(readingInterval);
        }

        // Event listeners for activity
        ['mousemove', 'keydown', 'scroll', 'click', 'touchstart'].forEach(evt => {
            document.addEventListener(evt, resetIdleTimer, {passive: true});
        });

        // Initialize
        resetIdleTimer();
        startReading();
    </script>
</body>
</html>
