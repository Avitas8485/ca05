<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDbConnection();

$stmt = $conn->prepare("SELECT current_chapter FROM story_progress WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$story = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Difficulty - Santa's Workshop</title>
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
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        .snowflake {
            position: fixed;
            top: -10px;
            color: white;
            font-size: 1em;
            animation: fall linear infinite;
            opacity: 0.8;
            pointer-events: none;
        }
        
        @keyframes fall {
            to {
                transform: translateY(110vh) rotate(360deg);
            }
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        
        .header h1 {
            color: #dc2626;
            font-size: 2.2em;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #6b7280;
            font-size: 1.1em;
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 12px 25px;
            background: rgba(255, 255, 255, 0.95);
            color: #dc2626;
            border: 2px solid #dc2626;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: #dc2626;
            color: white;
        }
        
        .difficulty-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .difficulty-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 35px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border: 3px solid transparent;
        }
        
        .difficulty-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }
        
        .difficulty-card.easy {
            border-color: #10b981;
        }
        
        .difficulty-card.easy:hover {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        }
        
        .difficulty-card.medium {
            border-color: #f59e0b;
        }
        
        .difficulty-card.medium:hover {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        }
        
        .difficulty-card.hard {
            border-color: #dc2626;
        }
        
        .difficulty-card.hard:hover {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        }
        
        .difficulty-card .icon {
            font-size: 4em;
            margin-bottom: 15px;
        }
        
        .difficulty-card h2 {
            font-size: 1.8em;
            margin-bottom: 10px;
            color: #1f2937;
        }
        
        .difficulty-card .subtitle {
            color: #6b7280;
            font-size: 1.1em;
            margin-bottom: 15px;
            font-style: italic;
        }
        
        .difficulty-card .grid-size {
            font-size: 1.3em;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 10px;
        }
        
        .difficulty-card .time {
            color: #059669;
            margin-bottom: 15px;
        }
        
        .difficulty-card .description {
            color: #4b5563;
            line-height: 1.6;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
        }
        
        .features {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .features h3 {
            color: #dc2626;
            font-size: 1.5em;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .feature-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .feature-item {
            padding: 15px;
            background: #f9fafb;
            border-radius: 10px;
            border-left: 4px solid #dc2626;
        }
        
        .feature-item .feature-icon {
            font-size: 1.5em;
            margin-right: 10px;
        }
        
        .feature-item .feature-text {
            color: #374151;
            font-weight: 600;
        }
        
        .story-indicator {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
            border: 2px solid #f59e0b;
        }
        
        .story-indicator .icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .story-indicator h4 {
            color: #92400e;
            margin-bottom: 5px;
        }
        
        .story-indicator p {
            color: #78350f;
        }
    </style>
</head>
<body>
    <a href="home.php" class="back-btn">← Back to Home</a>
    
    <div class="container">
        <div class="header">
            <h1>🎮 Choose Your Challenge 🎮</h1>
            <p>Select a difficulty level to begin your puzzle adventure!</p>
        </div>
        
        <div class="difficulty-grid">
            <div class="difficulty-card easy" onclick="startGame('easy', 3)">
                <div class="icon">🧝</div>
                <h2>EASY</h2>
                <div class="subtitle">Elf Apprentice</div>
                <div class="grid-size">3×3 Grid</div>
                <div class="time">⏱️ Est. 2-5 minutes</div>
                <div class="description">
                    Perfect for beginners! Master the basics of tile sliding with a smaller puzzle.
                </div>
            </div>
            
            <div class="difficulty-card medium" onclick="startGame('medium', 4)">
                <div class="icon">🎁</div>
                <h2>MEDIUM</h2>
                <div class="subtitle">Workshop Helper</div>
                <div class="grid-size">4×4 Grid</div>
                <div class="time">⏱️ Est. 5-15 minutes</div>
                <div class="description">
                    A balanced challenge! Test your strategy with more tiles and complex patterns.
                </div>
            </div>
            
            <div class="difficulty-card hard" onclick="startGame('hard', 5)">
                <div class="icon">🎅</div>
                <h2>HARD</h2>
                <div class="subtitle">Master Toymaker</div>
                <div class="grid-size">5×5 Grid</div>
                <div class="time">⏱️ Est. 15-30 minutes</div>
                <div class="description">
                    For puzzle masters only! An epic challenge that requires skill and patience.
                </div>
            </div>
        </div>
        
        <div class="features">
            <h3>✨ Game Features ✨</h3>
            <div class="feature-list">
                <div class="feature-item">
                    <span class="feature-icon">🎁</span>
                    <span class="feature-text">Unlock Rewards</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">⚡</span>
                    <span class="feature-text">Special Powerups</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">📖</span>
                    <span class="feature-text">Story Mode</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🏆</span>
                    <span class="feature-text">Achievements</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">⭐</span>
                    <span class="feature-text">Star Ratings</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🎯</span>
                    <span class="feature-text">Smart AI</span>
                </div>
            </div>
            
            <div class="story-indicator">
                <div class="icon">📖</div>
                <h4>Story Mode Active</h4>
                <p>Currently on Chapter <?php echo $story['current_chapter']; ?> - Complete puzzles to unlock the next chapter!</p>
            </div>
        </div>
    </div>
    
    <script>
        function startGame(difficulty, gridSize) {
            window.location.href = `game.php?difficulty=${difficulty}&size=${gridSize}`;
        }
        
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