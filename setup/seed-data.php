<?php
/**
 * Database Seed Data Generator
 * Online Quiz Competition Platform
 * 
 * Populates database with:
 * - 1 Admin account
 * - 10 Sample Student accounts
 * - 5 Quizzes across diverse categories
 * - 50 Comprehensive MCQs (10 per quiz, 4 options each)
 * - Completed sample attempts for leaderboard testing
 */

require_once __DIR__ . '/../config/database.php';

$isCLI = (php_sapi_name() === 'cli');

echo "=== SEEDING DATABASE ===\n";

try {
    $pdo = get_db_connection();

    // Disable foreign key checks for clean truncation if needed
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE `attempt_answers`;");
    $pdo->exec("TRUNCATE TABLE `attempts`;");
    $pdo->exec("TRUNCATE TABLE `options`;");
    $pdo->exec("TRUNCATE TABLE `questions`;");
    $pdo->exec("TRUNCATE TABLE `quizzes`;");
    $pdo->exec("TRUNCATE TABLE `users`;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "[✓] Tables truncated.\n";

    // 1. Create Admin
    $adminPass = password_hash('Admin@12345', PASSWORD_DEFAULT);
    $adminStmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
    $adminStmt->execute(['System Administrator', 'admin@quizplatform.com', $adminPass]);
    $adminId = $pdo->lastInsertId();
    echo "[✓] Admin created (admin@quizplatform.com / Admin@12345).\n";

    // 2. Create 10 Students
    $students = [
        ['Aarav Sharma', 'aarav@quiz.edu', 'Student@123'],
        ['Ananya Verma', 'ananya@quiz.edu', 'Student@123'],
        ['Rohan Patel', 'rohan@quiz.edu', 'Student@123'],
        ['Priya Iyer', 'priya@quiz.edu', 'Student@123'],
        ['Rahul Nair', 'rahul@quiz.edu', 'Student@123'],
        ['Sneha Gupta', 'sneha@quiz.edu', 'Student@123'],
        ['Vikram Singh', 'vikram@quiz.edu', 'Student@123'],
        ['Neha Joshi', 'neha@quiz.edu', 'Student@123'],
        ['Aditya Kumar', 'aditya@quiz.edu', 'Student@123'],
        ['Tanvi Deshmukh', 'tanvi@quiz.edu', 'Student@123']
    ];

    $studentIds = [];
    $studentStmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'student')");

    foreach ($students as $stu) {
        $pwdHash = password_hash($stu[2], PASSWORD_DEFAULT);
        $studentStmt->execute([$stu[0], $stu[1], $pwdHash]);
        $studentIds[] = $pdo->lastInsertId();
    }
    echo "[✓] 10 Sample students created (Password for all: Student@123).\n";

    // 3. Quizzes definitions
    $quizzesData = [
        [
            'title' => 'Modern Full-Stack Web Development',
            'description' => 'Test your proficiency in HTML5, CSS3, JavaScript ES6+, core backend workflows, and browser rendering engines.',
            'category' => 'Web Development',
            'difficulty' => 'medium',
            'duration_minutes' => 15,
            'status' => 'published',
            'questions' => [
                [
                    'q' => 'Which HTML5 semantic element is most suitable for encapsulating independent, self-contained syndicatable content like a blog post or news item?',
                    'marks' => 2,
                    'options' => [
                        ['text' => '<section>', 'correct' => 0],
                        ['text' => '<article>', 'correct' => 1],
                        ['text' => '<aside>', 'correct' => 0],
                        ['text' => '<div>', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'In modern CSS, which CSS Grid property provides a concise shorthand for defining both grid-template-rows and grid-template-columns simultaneously?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'grid-template', 'correct' => 1],
                        ['text' => 'grid-area', 'correct' => 0],
                        ['text' => 'grid-auto-flow', 'correct' => 0],
                        ['text' => 'grid-matrix', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What is the primary difference between let and var declarations in modern JavaScript?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'let has block scope while var has function scope', 'correct' => 1],
                        ['text' => 'var cannot be re-assigned, but let can be', 'correct' => 0],
                        ['text' => 'let variables are hoisted and initialized to undefined', 'correct' => 0],
                        ['text' => 'There is no difference; let is an alias for var', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'In core PHP, which superglobal array is populated when a form sends data using HTTP POST with enctype="multipart/form-data"?',
                    'marks' => 2,
                    'options' => [
                        ['text' => '$_REQUEST only', 'correct' => 0],
                        ['text' => '$_FILES and $_POST', 'correct' => 1],
                        ['text' => '$_ATTACHMENTS', 'correct' => 0],
                        ['text' => '$_SERVER["FILES"]', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Which HTTP response status code should be returned when a client makes an unauthenticated request to a protected endpoint?',
                    'marks' => 2,
                    'options' => [
                        ['text' => '400 Bad Request', 'correct' => 0],
                        ['text' => '401 Unauthorized', 'correct' => 1],
                        ['text' => '403 Forbidden', 'correct' => 0],
                        ['text' => '404 Not Found', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Which native JavaScript method converts a JSON string into a native JavaScript object?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'JSON.stringify()', 'correct' => 0],
                        ['text' => 'JSON.parse()', 'correct' => 1],
                        ['text' => 'JSON.toObject()', 'correct' => 0],
                        ['text' => 'Object.fromJSON()', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What is the essential mechanism used to prevent Cross-Site Request Forgery (CSRF) in web forms?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'Enabling HTTPS and SSL certificates', 'correct' => 0],
                        ['text' => 'Using unique, cryptographically secure anti-CSRF session tokens in forms', 'correct' => 1],
                        ['text' => 'Encoding all HTML entities in URL query strings', 'correct' => 0],
                        ['text' => 'Disabling JavaScript in client browsers', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'In the CSS box model, which property determines the space between the content boundary and the border?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'margin', 'correct' => 0],
                        ['text' => 'padding', 'correct' => 1],
                        ['text' => 'outline', 'correct' => 0],
                        ['text' => 'gap', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Which PHP function generates a secure cryptographic hash suitable for password storage?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'md5()', 'correct' => 0],
                        ['text' => 'sha1()', 'correct' => 0],
                        ['text' => 'password_hash()', 'correct' => 1],
                        ['text' => 'crypt_blowfish()', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What is the purpose of the SameSite attribute on HTTP cookies?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'It controls whether cookies are sent with cross-site requests to mitigate CSRF attacks', 'correct' => 1],
                        ['text' => 'It restricts the cookie to specific domain IP addresses only', 'correct' => 0],
                        ['text' => 'It prevents cookies from expiring when the browser closes', 'correct' => 0],
                        ['text' => 'It compresses cookie payloads across subdomains', 'correct' => 0],
                    ]
                ]
            ]
        ],
        [
            'title' => 'Core Python Programming Challenge',
            'description' => 'Assess your understanding of Python syntax, data structures, object-oriented concepts, and memory management.',
            'category' => 'Programming',
            'difficulty' => 'easy',
            'duration_minutes' => 10,
            'status' => 'published',
            'questions' => [
                [
                    'q' => 'Which of the following built-in data types in Python is immutable?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'list', 'correct' => 0],
                        ['text' => 'tuple', 'correct' => 1],
                        ['text' => 'dict', 'correct' => 0],
                        ['text' => 'set', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What will be the output of `bool([])` and `bool([0])` in Python?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'False and True', 'correct' => 1],
                        ['text' => 'False and False', 'correct' => 0],
                        ['text' => 'True and False', 'correct' => 0],
                        ['text' => 'True and True', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Which keyword is used in Python to define an anonymous or inline function?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'def', 'correct' => 0],
                        ['text' => 'lambda', 'correct' => 1],
                        ['text' => 'inline', 'correct' => 0],
                        ['text' => 'func', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What is the output of `type((1))` versus `type((1,))` in Python?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'int and tuple', 'correct' => 1],
                        ['text' => 'tuple and tuple', 'correct' => 0],
                        ['text' => 'int and int', 'correct' => 0],
                        ['text' => 'list and tuple', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Which statement correctly handles potential exceptions in Python?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'try / catch', 'correct' => 0],
                        ['text' => 'try / except', 'correct' => 1],
                        ['text' => 'do / rescue', 'correct' => 0],
                        ['text' => 'test / handle', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What does PEP 8 represent in the Python ecosystem?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'The Style Guide for Python Code', 'correct' => 1],
                        ['text' => 'The Package Index specification', 'correct' => 0],
                        ['text' => 'The Bytecode execution virtual machine', 'correct' => 0],
                        ['text' => 'The Python Extension Protocol', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Which built-in function returns both the index and the item during iteration in Python?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'range()', 'correct' => 0],
                        ['text' => 'enumerate()', 'correct' => 1],
                        ['text' => 'zip()', 'correct' => 0],
                        ['text' => 'map()', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What is the average time complexity of searching a key in a standard Python dictionary?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'O(1)', 'correct' => 1],
                        ['text' => 'O(n)', 'correct' => 0],
                        ['text' => 'O(log n)', 'correct' => 0],
                        ['text' => 'O(n log n)', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'In Python, what is the special method called automatically when an object instance is created?',
                    'marks' => 1,
                    'options' => [
                        ['text' => '__start__', 'correct' => 0],
                        ['text' => '__construct__', 'correct' => 0],
                        ['text' => '__init__', 'correct' => 1],
                        ['text' => '__newbie__', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What will `print("Hello" * 3)` output in Python?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'HelloHelloHello', 'correct' => 1],
                        ['text' => 'TypeError: Cannot multiply string', 'correct' => 0],
                        ['text' => 'Hello 3', 'correct' => 0],
                        ['text' => '["Hello", "Hello", "Hello"]', 'correct' => 0],
                    ]
                ]
            ]
        ],
        [
            'title' => 'Relational Databases & SQL Mastery',
            'description' => 'Demonstrate your knowledge of relational algebra, ACID properties, joins, indexing, and SQL queries.',
            'category' => 'Database Management',
            'difficulty' => 'medium',
            'duration_minutes' => 15,
            'status' => 'published',
            'questions' => [
                [
                    'q' => 'Which SQL clause is used to filter records resulting from an aggregate function like SUM or COUNT?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'WHERE', 'correct' => 0],
                        ['text' => 'HAVING', 'correct' => 1],
                        ['text' => 'GROUP FILTER', 'correct' => 0],
                        ['text' => 'ORDER BY', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'In database transactions, what does the "I" in ACID stand for?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'Integrity', 'correct' => 0],
                        ['text' => 'Isolation', 'correct' => 1],
                        ['text' => 'Indexability', 'correct' => 0],
                        ['text' => 'Immutability', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What type of JOIN returns all records from the left table and matched records from the right table?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'INNER JOIN', 'correct' => 0],
                        ['text' => 'LEFT OUTER JOIN', 'correct' => 1],
                        ['text' => 'FULL OUTER JOIN', 'correct' => 0],
                        ['text' => 'CROSS JOIN', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Which normal form eliminates partial dependency on a composite primary key?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'First Normal Form (1NF)', 'correct' => 0],
                        ['text' => 'Second Normal Form (2NF)', 'correct' => 1],
                        ['text' => 'Third Normal Form (3NF)', 'correct' => 0],
                        ['text' => 'Boyce-Codd Normal Form (BCNF)', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What is the primary operational advantage of creating a B-Tree index on a frequently queried column?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'It drastically reduces disk I/O and query lookup time from O(n) to O(log n)', 'correct' => 1],
                        ['text' => 'It automatically encrypts the column data on disk', 'correct' => 0],
                        ['text' => 'It speeds up INSERT operations by bypassing constraints', 'correct' => 0],
                        ['text' => 'It reduces the total table storage size on disk', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Which SQL command is used to remove all rows from a table quickly without logging individual row deletions?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'DELETE ALL FROM table', 'correct' => 0],
                        ['text' => 'DROP TABLE table', 'correct' => 0],
                        ['text' => 'TRUNCATE TABLE table', 'correct' => 1],
                        ['text' => 'REMOVE FROM table', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Which SQL constraint guarantees that values in a column are non-empty and unique across every row?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'CHECK CONSTRAINT', 'correct' => 0],
                        ['text' => 'PRIMARY KEY', 'correct' => 1],
                        ['text' => 'FOREIGN KEY', 'correct' => 0],
                        ['text' => 'DEFAULT', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'In MySQL InnoDB, what is the default transaction isolation level?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'READ UNCOMMITTED', 'correct' => 0],
                        ['text' => 'READ COMMITTED', 'correct' => 0],
                        ['text' => 'REPEATABLE READ', 'correct' => 1],
                        ['text' => 'SERIALIZABLE', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What is the role of a FOREIGN KEY constraint in relational databases?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'To enforce referential integrity between two related tables', 'correct' => 1],
                        ['text' => 'To establish external network connections across database servers', 'correct' => 0],
                        ['text' => 'To generate auto-incrementing serial IDs', 'correct' => 0],
                        ['text' => 'To automatically compress large binary blobs', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What is a SQL Injection vulnerability primarily caused by?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'Directly concatenating untrusted user input into dynamic SQL query strings', 'correct' => 1],
                        ['text' => 'Using MySQL instead of PostgreSQL', 'correct' => 0],
                        ['text' => 'Having too many indexes on a single table', 'correct' => 0],
                        ['text' => 'Running queries with multiple JOIN clauses', 'correct' => 0],
                    ]
                ]
            ]
        ],
        [
            'title' => 'Science, Inventions & Tech Pioneers',
            'description' => 'A captivating general knowledge quiz on landmark scientific discoveries, computing history, and pioneer innovators.',
            'category' => 'General Knowledge',
            'difficulty' => 'easy',
            'duration_minutes' => 10,
            'status' => 'published',
            'questions' => [
                [
                    'q' => 'Who is widely celebrated as the world\'s first computer programmer for writing an algorithm for Charles Babbage\'s Analytical Engine?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'Grace Hopper', 'correct' => 0],
                        ['text' => 'Ada Lovelace', 'correct' => 1],
                        ['text' => 'Alan Turing', 'correct' => 0],
                        ['text' => 'Katherine Johnson', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'In which year was the World Wide Web first introduced to the public by Tim Berners-Lee?',
                    'marks' => 1,
                    'options' => [
                        ['text' => '1983', 'correct' => 0],
                        ['text' => '1989', 'correct' => 0],
                        ['text' => '1991', 'correct' => 1],
                        ['text' => '1995', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Who created the Linux operating system kernel in 1991 while a student at the University of Helsinki?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'Richard Stallman', 'correct' => 0],
                        ['text' => 'Linus Torvalds', 'correct' => 1],
                        ['text' => 'Ken Thompson', 'correct' => 0],
                        ['text' => 'Dennis Ritchie', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Which scientist won Nobel Prizes in two different scientific fields (Physics and Chemistry)?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'Marie Curie', 'correct' => 1],
                        ['text' => 'Albert Einstein', 'correct' => 0],
                        ['text' => 'Niels Bohr', 'correct' => 0],
                        ['text' => 'Linus Pauling', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What does GPS stand for in satellite navigation technology?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'General Planetary System', 'correct' => 0],
                        ['text' => 'Global Positioning System', 'correct' => 1],
                        ['text' => 'Ground Point Synchronizer', 'correct' => 0],
                        ['text' => 'Geographic Path Solver', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Who co-created the C programming language and co-developed the UNIX operating system at Bell Labs?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'Dennis Ritchie', 'correct' => 1],
                        ['text' => 'Bjarne Stroustrup', 'correct' => 0],
                        ['text' => 'James Gosling', 'correct' => 0],
                        ['text' => 'Guido van Rossum', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What was the code name of the breakthrough machine developed at Bletchley Park to decrypt Enigma messages?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'The Bombe', 'correct' => 1],
                        ['text' => 'ENIAC', 'correct' => 0],
                        ['text' => 'Colossus', 'correct' => 0],
                        ['text' => 'UNIVAC', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Which company developed the Java programming language under the leadership of James Gosling?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'Sun Microsystems', 'correct' => 1],
                        ['text' => 'IBM', 'correct' => 0],
                        ['text' => 'Microsoft', 'correct' => 0],
                        ['text' => 'Apple', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Which subatomic particle was discovered by J.J. Thomson in 1897 using cathode ray tubes?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'Proton', 'correct' => 0],
                        ['text' => 'Neutron', 'correct' => 0],
                        ['text' => 'Electron', 'correct' => 1],
                        ['text' => 'Photon', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Who formulated the famous laws of planetary motion in astronomy in the early 17th century?',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'Johannes Kepler', 'correct' => 1],
                        ['text' => 'Galileo Galilei', 'correct' => 0],
                        ['text' => 'Nicolaus Copernicus', 'correct' => 0],
                        ['text' => 'Tycho Brahe', 'correct' => 0],
                    ]
                ]
            ]
        ],
        [
            'title' => 'OS, Architecture & Computer Networking',
            'description' => 'Test your core concepts in CPU scheduling, virtual memory, TCP/IP network protocol layers, and hardware architecture.',
            'category' => 'Computer Fundamentals',
            'difficulty' => 'hard',
            'duration_minutes' => 15,
            'status' => 'published',
            'questions' => [
                [
                    'q' => 'Which layer of the standard OSI model is responsible for reliable end-to-end data transmission, error recovery, and flow control?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'Data Link Layer', 'correct' => 0],
                        ['text' => 'Network Layer', 'correct' => 0],
                        ['text' => 'Transport Layer', 'correct' => 1],
                        ['text' => 'Session Layer', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What is the condition called when two or more concurrent processes are unable to proceed because each is waiting for the other to release a resource?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'Starvation', 'correct' => 0],
                        ['text' => 'Deadlock', 'correct' => 1],
                        ['text' => 'Race Condition', 'correct' => 0],
                        ['text' => 'Thrashing', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What is the phenomenon called when excessive paging causes the operating system to spend more time swapping pages than executing instructions?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'Thrashing', 'correct' => 1],
                        ['text' => 'Cache Invalidation', 'correct' => 0],
                        ['text' => 'Fragmentation', 'correct' => 0],
                        ['text' => 'Segmentation Fault', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What is the default subnet mask for a standard Class C IPv4 address network?',
                    'marks' => 2,
                    'options' => [
                        ['text' => '255.0.0.0', 'correct' => 0],
                        ['text' => '255.255.0.0', 'correct' => 0],
                        ['text' => '255.255.255.0', 'correct' => 1],
                        ['text' => '255.255.255.255', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'In computer architecture, what does the Program Counter (PC) register store?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'The memory address of the next instruction to be fetched and executed', 'correct' => 1],
                        ['text' => 'The count of total instructions executed so far', 'correct' => 0],
                        ['text' => 'The result of the latest Arithmetic Logic Unit (ALU) calculation', 'correct' => 0],
                        ['text' => 'The current stack top pointer address', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Which protocol is primarily used to dynamically assign IP addresses to client devices joining a local network?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'DNS', 'correct' => 0],
                        ['text' => 'DHCP', 'correct' => 1],
                        ['text' => 'ARP', 'correct' => 0],
                        ['text' => 'SNMP', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'Which CPU scheduling algorithm is preemptive and assigns a fixed time slice (quantum) to every process in the ready queue?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'First-Come, First-Served (FCFS)', 'correct' => 0],
                        ['text' => 'Shortest Job First (SJF)', 'correct' => 0],
                        ['text' => 'Round Robin (RR)', 'correct' => 1],
                        ['text' => 'Priority Non-Preemptive', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What is the size in bits of an IPv6 address compared to an IPv4 address?',
                    'marks' => 2,
                    'options' => [
                        ['text' => '64 bits vs 32 bits', 'correct' => 0],
                        ['text' => '128 bits vs 32 bits', 'correct' => 1],
                        ['text' => '256 bits vs 64 bits', 'correct' => 0],
                        ['text' => '512 bits vs 128 bits', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What hardware component resolves logical virtual memory addresses to physical RAM addresses using page tables?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'Memory Management Unit (MMU)', 'correct' => 1],
                        ['text' => 'Direct Memory Access Controller (DMA)', 'correct' => 0],
                        ['text' => 'Arithmetic Logic Unit (ALU)', 'correct' => 0],
                        ['text' => 'Northbridge Controller', 'correct' => 0],
                    ]
                ],
                [
                    'q' => 'What is the purpose of the Address Resolution Protocol (ARP) in local area networking?',
                    'marks' => 2,
                    'options' => [
                        ['text' => 'To map a known IPv4 network address to a physical MAC hardware address', 'correct' => 1],
                        ['text' => 'To translate domain names into IP addresses', 'correct' => 0],
                        ['text' => 'To establish encrypted TLS tunnels', 'correct' => 0],
                        ['text' => 'To route packets across different autonomous systems', 'correct' => 0],
                    ]
                ]
            ]
        ]
    ];

    $quizInsert = $pdo->prepare("INSERT INTO quizzes (title, description, category, difficulty, duration_minutes, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $qInsert = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, marks, question_order) VALUES (?, ?, ?, ?)");
    $optInsert = $pdo->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");

    $seededQuizIds = [];
    $allQuizQuestions = []; // quiz_id => list of question records with options

    foreach ($quizzesData as $qData) {
        $quizInsert->execute([
            $qData['title'],
            $qData['description'],
            $qData['category'],
            $qData['difficulty'],
            $qData['duration_minutes'],
            $qData['status'],
            $adminId
        ]);
        $quizId = $pdo->lastInsertId();
        $seededQuizIds[] = $quizId;
        $allQuizQuestions[$quizId] = [];

        $order = 1;
        foreach ($qData['questions'] as $q) {
            $qInsert->execute([$quizId, $q['q'], $q['marks'], $order++]);
            $questionId = $pdo->lastInsertId();

            $optList = [];
            foreach ($q['options'] as $opt) {
                $optInsert->execute([$questionId, $opt['text'], $opt['correct']]);
                $optId = $pdo->lastInsertId();
                $optList[] = [
                    'id' => $optId,
                    'text' => $opt['text'],
                    'is_correct' => $opt['correct']
                ];
            }

            $allQuizQuestions[$quizId][] = [
                'id' => $questionId,
                'marks' => $q['marks'],
                'options' => $optList
            ];
        }
    }
    echo "[✓] 5 Quizzes and 50 MCQ questions created with 4 options each.\n";

    // 4. Create realistic completed attempts for students to showcase leaderboard
    // Let's seed completed attempts for the Web Development quiz (index 0) and Python quiz (index 1)
    $webQuizId = $seededQuizIds[0];
    $pyQuizId = $seededQuizIds[1];

    $attemptInsert = $pdo->prepare("INSERT INTO attempts 
        (user_id, quiz_id, started_at, deadline_at, submitted_at, status, score, total_marks, percentage, time_taken_seconds) 
        VALUES (?, ?, ?, ?, ?, 'completed', ?, ?, ?, ?)");
    
    $answerInsert = $pdo->prepare("INSERT INTO attempt_answers 
        (attempt_id, question_id, selected_option_id, is_correct, marks_awarded) 
        VALUES (?, ?, ?, ?, ?)");

    // Seed attempts for 8 students on Web Development Quiz
    // Total marks: 20 (10 questions * 2 marks)
    $performanceProfile = [
        // [student_index, correct_count, seconds_taken]
        [0, 10, 412], // Aarav: 20/20 in 6m 52s (Rank 1 - Gold)
        [1, 9, 395],  // Ananya: 18/20 in 6m 35s (Rank 2 - Silver)
        [2, 9, 450],  // Rohan: 18/20 in 7m 30s (Rank 3 - Bronze)
        [3, 8, 510],  // Priya: 16/20 in 8m 30s
        [4, 8, 580],  // Rahul: 16/20 in 9m 40s
        [5, 7, 480],  // Sneha: 14/20 in 8m 00s
        [6, 6, 620],  // Vikram: 12/20 in 10m 20s
        [7, 5, 530],  // Neha: 10/20 in 8m 50s
    ];

    $webQuestions = $allQuizQuestions[$webQuizId];
    $webTotalMarks = array_sum(array_column($webQuestions, 'marks'));

    foreach ($performanceProfile as $perf) {
        $sIdx = $perf[0];
        $correctCount = $perf[1];
        $seconds = $perf[2];
        $userId = $studentIds[$sIdx];

        $now = time() - (86400 * rand(1, 3)) + rand(100, 3600);
        $startedAt = date('Y-m-d H:i:s', $now);
        $deadlineAt = date('Y-m-d H:i:s', $now + (15 * 60));
        $submittedAt = date('Y-m-d H:i:s', $now + $seconds);

        $score = $correctCount * 2;
        $pct = ($score / $webTotalMarks) * 100;

        $attemptInsert->execute([
            $userId,
            $webQuizId,
            $startedAt,
            $deadlineAt,
            $submittedAt,
            $score,
            $webTotalMarks,
            $pct,
            $seconds
        ]);
        $attemptId = $pdo->lastInsertId();

        // Populate answers
        $qIdx = 0;
        foreach ($webQuestions as $q) {
            $isCorrect = ($qIdx < $correctCount) ? 1 : 0;
            $selectedOpt = null;
            foreach ($q['options'] as $opt) {
                if ($isCorrect && $opt['is_correct'] == 1) {
                    $selectedOpt = $opt['id'];
                    break;
                } elseif (!$isCorrect && $opt['is_correct'] == 0) {
                    $selectedOpt = $opt['id'];
                    break;
                }
            }
            $awarded = $isCorrect ? $q['marks'] : 0.00;
            $answerInsert->execute([$attemptId, $q['id'], $selectedOpt, $isCorrect, $awarded]);
            $qIdx++;
        }
    }

    // Seed attempts for 5 students on Python Quiz
    // Total marks: 10 (10 questions * 1 mark)
    $pyQuestions = $allQuizQuestions[$pyQuizId];
    $pyTotalMarks = array_sum(array_column($pyQuestions, 'marks'));
    $pyProfile = [
        [1, 10, 240], // Ananya: 10/10 in 4m
        [0, 9, 210],  // Aarav: 9/10 in 3m 30s
        [4, 8, 300],  // Rahul: 8/10 in 5m
        [8, 7, 340],  // Aditya: 7/10 in 5m 40s
        [9, 6, 280],  // Tanvi: 6/10 in 4m 40s
    ];

    foreach ($pyProfile as $perf) {
        $sIdx = $perf[0];
        $correctCount = $perf[1];
        $seconds = $perf[2];
        $userId = $studentIds[$sIdx];

        $now = time() - (86400 * rand(1, 2)) + rand(100, 3600);
        $startedAt = date('Y-m-d H:i:s', $now);
        $deadlineAt = date('Y-m-d H:i:s', $now + (10 * 60));
        $submittedAt = date('Y-m-d H:i:s', $now + $seconds);

        $score = $correctCount * 1;
        $pct = ($score / $pyTotalMarks) * 100;

        $attemptInsert->execute([
            $userId,
            $pyQuizId,
            $startedAt,
            $deadlineAt,
            $submittedAt,
            $score,
            $pyTotalMarks,
            $pct,
            $seconds
        ]);
        $attemptId = $pdo->lastInsertId();

        $qIdx = 0;
        foreach ($pyQuestions as $q) {
            $isCorrect = ($qIdx < $correctCount) ? 1 : 0;
            $selectedOpt = null;
            foreach ($q['options'] as $opt) {
                if ($isCorrect && $opt['is_correct'] == 1) {
                    $selectedOpt = $opt['id'];
                    break;
                } elseif (!$isCorrect && $opt['is_correct'] == 0) {
                    $selectedOpt = $opt['id'];
                    break;
                }
            }
            $awarded = $isCorrect ? $q['marks'] : 0.00;
            $answerInsert->execute([$attemptId, $q['id'], $selectedOpt, $isCorrect, $awarded]);
            $qIdx++;
        }
    }

    echo "[✓] Realistic student attempts seeded for Web Dev and Python quizzes.\n";
    echo "=== SEEDING COMPLETED SUCCESSFULLY ===\n";

} catch (Exception $e) {
    echo "[✗] Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
