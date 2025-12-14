<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDbConnection();

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_puzzles,
        COALESCE(SUM(score), 0) as total_score,
        COALESCE(AVG(completion_time), 0) as avg_time
    FROM games 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("
    SELECT current_chapter, chapters_completed 
    FROM story_progress 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$story = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("
    SELECT DATE(completed_at) as game_date 
    FROM games 
    WHERE user_id = ? 
    ORDER BY completed_at DESC 
    LIMIT 7
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$recent_days = $stmt->get_result();
$unique_days = [];
while ($row = $recent_days->fetch_assoc()) {
    $unique_days[$row['game_date']] = true;
}
$current_streak = count($unique_days);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Santa's Workshop - Home</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #8b5cf6 100%);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }
        
        .snowflake {
            position: fixed;
            top: -10px;
            color: white;
            font-size: 1em;
            animation: fall linear infinite;
            opacity: 0.8;
            pointer-events: none;
            z-index: 1;
        }
        
        @keyframes fall {
            to {
                transform: translateY(110vh) rotate(360deg);
            }
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 10;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        
        .header h1 {
            color: #dc2626;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .welcome {
            color: #059669;
            font-size: 1.3em;
            font-weight: bold;
        }
        
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: #dc2626;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .stat-card .icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 2em;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #6b7280;
            font-size: 0.9em;
        }
        
        .main-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        .action-card.primary {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            grid-column: span 2;
        }
        
        .action-card .icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        .action-card h3 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }
        
        .action-card p {
            opacity: 0.8;
        }
        
        .story-progress {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .story-progress h3 {
            color: #dc2626;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        
        .progress-bar {
            background: #e5e7eb;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #059669 0%, #10b981 100%);
            height: 100%;
            transition: width 0.5s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .workshop-scene {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 150px;
            pointer-events: none;
            z-index: 5;
        }
        
        .elf {
            position: absolute;
            font-size: 2em;
            animation: elfWork 3s ease-in-out infinite;
        }
        
        @keyframes elfWork {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="logout.php" class="logout-btn">Logout</a>
        
        <div class="header">
            <h1>🎅 Santa's Workshop 🎄</h1>
            <p class="welcome">Welcome back, <?php echo htmlspecialchars($user['username']); ?>! ⭐</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">🎁</div>
                <div class="value"><?php echo $stats['total_puzzles']; ?></div>
                <div class="label">Puzzles Solved</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">⭐</div>
                <div class="value"><?php echo number_format($stats['total_score']); ?></div>
                <div class="label">Total Score</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">🔥</div>
                <div class="value"><?php echo $current_streak; ?></div>
                <div class="label">Day Streak</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">⏱️</div>
                <div class="value"><?php echo $stats['avg_time'] > 0 ? round($stats['avg_time']) . 's' : '--'; ?></div>
                <div class="label">Avg Time</div>
            </div>
        </div>
        
        <div class="main-actions">
            <a href="difficulty.php" class="action-card primary">
                <div class="icon">🎮</div>
                <h3>START GAME</h3>
                <p>Begin your puzzle adventure!</p>
            </a>
            
            <a href="leaderboard.php" class="action-card">
                <div class="icon">🏆</div>
                <h3>LEADERBOARD</h3>
                <p>See top players</p>
            </a>
            
            <a href="progress.php" class="action-card">
                <div class="icon">📊</div>
                <h3>MY PROGRESS</h3>
                <p>View your stats</p>
            </a>
        </div>
        
        <div class="story-progress">
            <h3>📖 Story Mode Progress</h3>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo ($story['chapters_completed'] / 10 * 100); ?>%">
                    Chapter <?php echo $story['current_chapter']; ?> / 10
                </div>
            </div>
            <p style="color: #6b7280; margin-top: 10px;">
                You've completed <?php echo $story['chapters_completed']; ?> chapters of Santa's Workshop story!
            </p>
        </div>
    </div>
    
    <div class="workshop-scene">
        <div class="elf" style="left: 10%; bottom: 20px;">🧝</div>
        <div class="elf" style="left: 30%; bottom: 20px; animation-delay: 1s;">🧝‍♀️</div>
        <div class="elf" style="left: 50%; bottom: 20px; animation-delay: 0.5s;">🧝</div>
        <div class="elf" style="left: 70%; bottom: 20px; animation-delay: 1.5s;">🧝‍♀️</div>
        <div class="elf" style="left: 90%; bottom: 20px; animation-delay: 2s;">🧝</div>
    </div>
    
    <script>
        function createSnowflake() {
            const snowflake = document.createElement('div');
            snowflake.classList.add('snowflake');
            snowflake.textContent = '❄';
            snowflake.style.left = Math.random() * 100 + '%';
            snowflake.style.animationDuration = Math.random() * 3 + 2 + 's';
            snowflake.style.fontSize = Math.random() * 10 + 10 + 'px';
            document.body.appendChild(snowflake);
            
            setTimeout(() => {
                snowflake.remove();
            }, 5000);
        }
        
        setInterval(createSnowflake, 200);
    </script>
</body>
</html>