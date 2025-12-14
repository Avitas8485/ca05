<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'angonyani1');
define('DB_PASS', 'angonyani1');
define('DB_NAME', 'angonyani1');


$alreadySetup = false;
try {
    $testConn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$testConn->connect_error) {
        
        $result = $testConn->query("SHOW TABLES LIKE 'users'");
        if ($result && $result->num_rows > 0) {
            $alreadySetup = true;
        }
        $testConn->close();
    }
} catch (Exception $e) {
    
}


if ($alreadySetup) {
    header('Location: login.php');
    exit();
}


$setupComplete = false;
$setupErrors = [];

if (true) { 
    ob_start();
    $result = setupDatabase();
    ob_end_clean();
    $setupComplete = $result['success'];
    $setupErrors = $result['errors'];
}

function setupDatabase() {
    $errors = [];
    $success = true;
    
    try {
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
        
        if ($conn->connect_error) {
            return ['success' => false, 'errors' => ['Connection failed: ' . $conn->connect_error]];
        }
        
        
        $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
        $conn->query($sql);
        
        
        $conn->select_db(DB_NAME);
        
        
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            INDEX idx_username (username),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);
        
        
        $sql = "CREATE TABLE IF NOT EXISTS games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            difficulty VARCHAR(20) NOT NULL,
            grid_size INT NOT NULL,
            completion_time INT NOT NULL,
            moves_used INT NOT NULL,
            score INT NOT NULL,
            star_rating INT NOT NULL,
            completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_difficulty (difficulty),
            INDEX idx_score (score DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);
        
        
        $sql = "CREATE TABLE IF NOT EXISTS achievements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            icon VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);
        
        
        $sql = "CREATE TABLE IF NOT EXISTS user_achievements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            achievement_id INT NOT NULL,
            unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_achievement (user_id, achievement_id),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);
        
        
        $sql = "CREATE TABLE IF NOT EXISTS gifts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            icon VARCHAR(100) NOT NULL,
            unlock_requirement VARCHAR(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);
        
        
        $sql = "CREATE TABLE IF NOT EXISTS user_gifts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            gift_id INT NOT NULL,
            unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (gift_id) REFERENCES gifts(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_gift (user_id, gift_id),
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);
        
        
        $sql = "CREATE TABLE IF NOT EXISTS story_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNIQUE NOT NULL,
            current_chapter INT DEFAULT 1,
            chapters_completed INT DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);
        
        
        $sql = "CREATE TABLE IF NOT EXISTS user_powerups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            powerup_type VARCHAR(50) NOT NULL,
            quantity INT DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_powerup (user_id, powerup_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);
        
        
        $achievements = [
            ['speed_demon', 'Speed Demon', 'Complete a puzzle in under 1 minute', '⚡'],
            ['efficient_solver', 'Efficient Solver', 'Complete a puzzle in minimum moves', '🎯'],
            ['marathon_master', 'Marathon Master', 'Complete 10 puzzles in one session', '🏃'],
            ['perfect_game', 'Perfect Game', 'Achieve a 3-star rating', '⭐'],
            ['first_victory', 'First Victory', 'Complete your first puzzle', '🎉'],
            ['toy_master', 'Toy Master', 'Complete 50 puzzles', '🎁'],
            ['workshop_legend', 'Workshop Legend', 'Complete 100 puzzles', '👑'],
            ['streak_keeper', 'Streak Keeper', 'Play 7 days in a row', '🔥']
        ];
        
        foreach ($achievements as $ach) {
            $stmt = $conn->prepare("INSERT IGNORE INTO achievements (code, name, description, icon) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $ach[0], $ach[1], $ach[2], $ach[3]);
            $stmt->execute();
            $stmt->close();
        }
        
        
        $gifts = [
            ['snow_globe', 'Magic Snow Globe', 'A beautiful snow globe from Santa', '🔮', 'Complete 5 puzzles'],
            ['golden_bell', 'Golden Bell', 'Rings with holiday cheer', '🔔', 'Achieve 10,000 total score'],
            ['candy_cane', 'Giant Candy Cane', 'Sweet reward for sweet solving', '🍭', 'Complete 10 puzzles'],
            ['reindeer_toy', 'Reindeer Toy', 'Rudolph approves!', '🦌', 'Complete a Hard puzzle'],
            ['christmas_tree', 'Mini Christmas Tree', 'Festive decoration', '🎄', 'Complete 20 puzzles'],
            ['santa_hat', 'Santa Hat', 'Wear it with pride', '🎅', 'Achieve 3 stars 5 times'],
            ['sleigh_model', 'Sleigh Model', 'Exact replica of Santa\'s sleigh', '🛷', 'Complete 30 puzzles'],
            ['magic_wand', 'Elf Magic Wand', 'Grants special powers', '✨', 'Unlock all achievements']
        ];
        
        foreach ($gifts as $gift) {
            $stmt = $conn->prepare("INSERT IGNORE INTO gifts (code, name, description, icon, unlock_requirement) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $gift[0], $gift[1], $gift[2], $gift[3], $gift[4]);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        $success = false;
    }
    
    return ['success' => $success, 'errors' => $errors];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setting Up Santa's Workshop...</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        
        .loading-container {
            text-align: center;
            color: white;
        }
        
        .workshop-icon {
            font-size: 120px;
            margin-bottom: 30px;
            animation: bounce 2s infinite;
        }
        
        h1 {
            font-size: 3em;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .loading-text {
            font-size: 1.5em;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        
        .progress-bar {
            width: 400px;
            height: 8px;
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
            margin: 0 auto;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #dc2626, #ef4444, #dc2626);
            background-size: 200% 100%;
            animation: progress 2s ease-in-out forwards, shimmer 1.5s infinite;
            border-radius: 10px;
        }
        
        .snowflakes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        
        .snowflake {
            position: absolute;
            top: -10px;
            color: white;
            font-size: 1.5em;
            animation: fall linear infinite;
            opacity: 0.8;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes progress {
            from { width: 0%; }
            to { width: 100%; }
        }
        
        @keyframes shimmer {
            0% { background-position: 0% 0%; }
            100% { background-position: 200% 0%; }
        }
        
        @keyframes fall {
            to { transform: translateY(100vh); }
        }
        
        .status-message {
            margin-top: 30px;
            font-size: 1.1em;
            opacity: 0.8;
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.9);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body>
    <div class="snowflakes">
        <?php for($i = 0; $i < 20; $i++): ?>
            <div class="snowflake" style="left: <?= rand(0, 100) ?>%; animation-duration: <?= rand(3, 8) ?>s; animation-delay: <?= rand(0, 5) ?>s;">❄</div>
        <?php endfor; ?>
    </div>
    
    <div class="loading-container">
        <div class="workshop-icon">🎅</div>
        <h1>Santa's Workshop</h1>
        <div class="loading-text">Setting up your magical experience...</div>
        
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
        
        <?php if ($setupComplete): ?>
            <div class="status-message">✨ Workshop ready! Redirecting to login...</div>
            <script>
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 2000);
            </script>
        <?php elseif (!empty($setupErrors)): ?>
            <div class="error-message">
                <strong>⚠️ Setup Error:</strong><br>
                <?php echo htmlspecialchars(implode(', ', $setupErrors)); ?><br>
                <small>Please check your database configuration</small>
            </div>
        <?php else: ?>
            <div class="status-message">🎄 Preparing the workshop...</div>
        <?php endif; ?>
    </div>
</body>
</html>
