<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDbConnection();

$stmt = $conn->prepare("
    SELECT 
        u.username,
        COUNT(g.id) as total_games,
        SUM(g.score) as total_score,
        AVG(g.completion_time) as avg_time,
        MAX(g.score) as best_score
    FROM users u
    LEFT JOIN games g ON u.id = g.user_id
    GROUP BY u.id
    HAVING total_games > 0
    ORDER BY total_score DESC
    LIMIT 50
");
$stmt->execute();
$leaderboard = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("
    SELECT 
        u.username,
        g.score,
        g.completion_time,
        g.moves_used,
        g.star_rating,
        g.difficulty,
        g.completed_at
    FROM games g
    JOIN users u ON g.user_id = u.id
    ORDER BY g.score DESC
    LIMIT 10
");
$stmt->execute();
$top_games = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("
    SELECT 
        u.username,
        g.completion_time,
        g.difficulty,
        g.grid_size
    FROM games g
    JOIN users u ON g.user_id = u.id
    WHERE g.difficulty = 'hard'
    ORDER BY g.completion_time ASC
    LIMIT 10
");
$stmt->execute();
$fastest = $stmt->get_result();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Santa's Workshop</title>
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
            padding: 20px;
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
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            position: relative;
        }
        
        .header h1 {
            color: #dc2626;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
        }
        
        .back-btn:hover {
            background: #b91c1c;
        }
        
        .tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .tab {
            padding: 15px 30px;
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
        }
        
        .tab:hover {
            transform: translateY(-2px);
        }
        
        .leaderboard-section {
            display: none;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .leaderboard-section.active {
            display: block;
        }
        
        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .leaderboard-table th,
        .leaderboard-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .leaderboard-table th {
            background: #f9fafb;
            font-weight: bold;
            color: #374151;
            position: sticky;
            top: 0;
        }
        
        .leaderboard-table tr:hover {
            background: #f9fafb;
        }
        
        .rank {
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .rank-1 {
            color: #f59e0b;
            font-size: 1.5em;
        }
        
        .rank-2 {
            color: #9ca3af;
            font-size: 1.4em;
        }
        
        .rank-3 {
            color: #d97706;
            font-size: 1.3em;
        }
        
        .medal {
            font-size: 1.5em;
            margin-right: 10px;
        }
        
        .player-name {
            font-weight: bold;
            color: #1f2937;
        }
        
        .current-user {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
        }
        
        .stat-highlight {
            color: #dc2626;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="home.php" class="back-btn">← Home</a>
            <h1>🏆 Leaderboard 🏆</h1>
            <p>Top players in Santa's Workshop!</p>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('overall')">🌟 Overall</div>
            <div class="tab" onclick="showTab('top-games')">🎯 Top Games</div>
            <div class="tab" onclick="showTab('fastest')">⚡ Fastest</div>
        </div>
        
        <div id="overall" class="leaderboard-section active">
            <h2 style="color: #dc2626; margin-bottom: 20px; font-size: 1.8em; text-align: center;">
                🌟 Overall Rankings
            </h2>
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Player</th>
                        <th>Games Played</th>
                        <th>Total Score</th>
                        <th>Avg Time</th>
                        <th>Best Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    $leaderboard->data_seek(0);
                    while ($player = $leaderboard->fetch_assoc()): 
                        $isCurrentUser = ($player['username'] === $user['username']);
                    ?>
                    <tr class="<?php echo $isCurrentUser ? 'current-user' : ''; ?>">
                        <td>
                            <?php if ($rank <= 3): ?>
                                <span class="medal">
                                    <?php 
                                    echo $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : '🥉'); 
                                    ?>
                                </span>
                            <?php endif; ?>
                            <span class="rank rank-<?php echo $rank; ?>">
                                #<?php echo $rank; ?>
                            </span>
                        </td>
                        <td class="player-name">
                            <?php echo htmlspecialchars($player['username']); ?>
                            <?php if ($isCurrentUser): ?>
                                <span style="color: #059669;">⭐ (You)</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $player['total_games']; ?></td>
                        <td class="stat-highlight"><?php echo number_format($player['total_score']); ?></td>
                        <td><?php echo gmdate("i:s", $player['avg_time']); ?></td>
                        <td><?php echo number_format($player['best_score']); ?></td>
                    </tr>
                    <?php 
                    $rank++;
                    endwhile; 
                    ?>
                </tbody>
            </table>
        </div>
        
        <div id="top-games" class="leaderboard-section">
            <h2 style="color: #dc2626; margin-bottom: 20px; font-size: 1.8em; text-align: center;">
                🎯 Top Games of All Time
            </h2>
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Player</th>
                        <th>Score</th>
                        <th>Time</th>
                        <th>Moves</th>
                        <th>Difficulty</th>
                        <th>Stars</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    while ($game = $top_games->fetch_assoc()): 
                    ?>
                    <tr>
                        <td>
                            <?php if ($rank <= 3): ?>
                                <span class="medal">
                                    <?php 
                                    echo $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : '🥉'); 
                                    ?>
                                </span>
                            <?php endif; ?>
                            <span class="rank">#<?php echo $rank; ?></span>
                        </td>
                        <td class="player-name"><?php echo htmlspecialchars($game['username']); ?></td>
                        <td class="stat-highlight"><?php echo number_format($game['score']); ?></td>
                        <td><?php echo gmdate("i:s", $game['completion_time']); ?></td>
                        <td><?php echo $game['moves_used']; ?></td>
                        <td><?php echo ucfirst($game['difficulty']); ?></td>
                        <td><?php echo str_repeat('⭐', $game['star_rating']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($game['completed_at'])); ?></td>
                    </tr>
                    <?php 
                    $rank++;
                    endwhile; 
                    ?>
                </tbody>
            </table>
        </div>
        
        <div id="fastest" class="leaderboard-section">
            <h2 style="color: #dc2626; margin-bottom: 20px; font-size: 1.8em; text-align: center;">
                ⚡ Fastest Hard Mode Completions
            </h2>
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Player</th>
                        <th>Time</th>
                        <th>Grid Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    while ($game = $fastest->fetch_assoc()): 
                    ?>
                    <tr>
                        <td>
                            <?php if ($rank <= 3): ?>
                                <span class="medal">
                                    <?php 
                                    echo $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : '🥉'); 
                                    ?>
                                </span>
                            <?php endif; ?>
                            <span class="rank">#<?php echo $rank; ?></span>
                        </td>
                        <td class="player-name"><?php echo htmlspecialchars($game['username']); ?></td>
                        <td class="stat-highlight"><?php echo gmdate("i:s", $game['completion_time']); ?></td>
                        <td><?php echo $game['grid_size']; ?>×<?php echo $game['grid_size']; ?></td>
                    </tr>
                    <?php 
                    $rank++;
                    endwhile; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.leaderboard-section').forEach(section => section.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tabName).classList.add('active');
        }
        
        function createSnowflake() {
            const snowflake = document.createElement('div');
            snowflake.classList.add('snowflake');
            snowflake.textContent = '❄';
            snowflake.style.left = Math.random() * 100 + '%';
            snowflake.style.animationDuration = Math.random() * 3 + 2 + 's';
            snowflake.style.fontSize = Math.random() * 10 + 10 + 'px';
            document.body.appendChild(snowflake);
            
            setTimeout(() => snowflake.remove(), 5000);
        }
        
        setInterval(createSnowflake, 200);
    </script>
</body>
</html>