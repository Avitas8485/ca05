<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$conn = getDbConnection();

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_puzzles,
        SUM(completion_time) as total_time,
        AVG(completion_time) as avg_time,
        MAX(score) as best_score,
        AVG(score) as avg_score
    FROM games 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
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

$stmt = $conn->prepare("
    SELECT 
        difficulty,
        grid_size,
        completion_time,
        moves_used,
        score,
        star_rating,
        completed_at
    FROM games 
    WHERE user_id = ?
    ORDER BY completed_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$recent_games = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("
    SELECT a.code, a.name, a.description, a.icon,
           ua.unlocked_at
    FROM achievements a
    LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
    ORDER BY ua.unlocked_at DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$achievements = $stmt->get_result();
$stmt->close();

$stmt = $conn->prepare("
    SELECT g.code, g.name, g.description, g.icon, g.unlock_requirement,
           ug.unlocked_at
    FROM gifts g
    LEFT JOIN user_gifts ug ON g.id = ug.gift_id AND ug.user_id = ?
    ORDER BY ug.unlocked_at DESC
");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$gifts = $stmt->get_result();
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

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Progress - Santa's Workshop</title>
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
            font-size: 2.2em;
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
        
        .stats-overview {
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
        }
        
        .section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .section h2 {
            color: #dc2626;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        
        .games-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .games-table th,
        .games-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .games-table th {
            background: #f9fafb;
            font-weight: bold;
            color: #374151;
        }
        
        .games-table tr:hover {
            background: #f9fafb;
        }
        
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .achievement-card {
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
        }
        
        .achievement-card.unlocked {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-color: #10b981;
        }
        
        .achievement-card.locked {
            background: #f3f4f6;
            opacity: 0.6;
        }
        
        .achievement-card .icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .achievement-card .name {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .achievement-card .description {
            font-size: 0.9em;
            color: #6b7280;
        }
        
        .gifts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .gift-card {
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
        }
        
        .gift-card.unlocked {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-color: #f59e0b;
        }
        
        .gift-card.locked {
            background: #f3f4f6;
            opacity: 0.5;
        }
        
        .gift-card .icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .gift-card .name {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .gift-card .requirement {
            font-size: 0.85em;
            color: #6b7280;
            font-style: italic;
        }
        
        .story-section {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #dc2626;
        }
        
        .progress-bar-container {
            background: #e5e7eb;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            margin: 15px 0;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #dc2626 0%, #b91c1c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: width 0.5s;
        }
        
        .action-btns {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 1.1em;
            cursor: pointer;
            margin: 0 10px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 38, 38, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="home.php" class="back-btn">← Home</a>
            <h1>📊 My Progress 📊</h1>
            <p>Track your puzzle-solving journey!</p>
        </div>
        
        <div class="stats-overview">
            <div class="stat-card">
                <div class="icon">🎁</div>
                <div class="value"><?php echo $stats['total_puzzles']; ?></div>
                <div class="label">Total Puzzles</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">⏱️</div>
                <div class="value"><?php echo $stats['total_time'] > 0 ? gmdate("H:i:s", $stats['total_time']) : '--'; ?></div>
                <div class="label">Total Time</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">📈</div>
                <div class="value"><?php echo $stats['avg_time'] > 0 ? round($stats['avg_time']) . 's' : '--'; ?></div>
                <div class="label">Avg Time</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">🔥</div>
                <div class="value"><?php echo $current_streak; ?></div>
                <div class="label">Day Streak</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">⭐</div>
                <div class="value"><?php echo number_format($stats['best_score']); ?></div>
                <div class="label">Best Score</div>
            </div>
        </div>
        
        <div class="section">
            <h2>🎮 Recent Games</h2>
            <table class="games-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Difficulty</th>
                        <th>Time</th>
                        <th>Moves</th>
                        <th>Score</th>
                        <th>Stars</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($game = $recent_games->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($game['completed_at'])); ?></td>
                        <td><?php echo ucfirst($game['difficulty']) . ' (' . $game['grid_size'] . 'x' . $game['grid_size'] . ')'; ?></td>
                        <td><?php echo gmdate("i:s", $game['completion_time']); ?></td>
                        <td><?php echo $game['moves_used']; ?></td>
                        <td><?php echo number_format($game['score']); ?></td>
                        <td><?php echo str_repeat('⭐', $game['star_rating']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>🏆 Achievements</h2>
            <div class="achievements-grid">
                <?php while ($achievement = $achievements->fetch_assoc()): ?>
                <div class="achievement-card <?php echo $achievement['unlocked_at'] ? 'unlocked' : 'locked'; ?>">
                    <div class="icon"><?php echo $achievement['icon']; ?></div>
                    <div class="name"><?php echo htmlspecialchars($achievement['name']); ?></div>
                    <div class="description"><?php echo htmlspecialchars($achievement['description']); ?></div>
                    <?php if ($achievement['unlocked_at']): ?>
                        <div style="margin-top: 10px; color: #059669; font-weight: bold;">✓ Unlocked</div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <div class="section">
            <h2>🎁 Gift Collection</h2>
            <div class="gifts-grid">
                <?php while ($gift = $gifts->fetch_assoc()): ?>
                <div class="gift-card <?php echo $gift['unlocked_at'] ? 'unlocked' : 'locked'; ?>">
                    <div class="icon"><?php echo $gift['unlocked_at'] ? $gift['icon'] : '🎁'; ?></div>
                    <div class="name"><?php echo htmlspecialchars($gift['name']); ?></div>
                    <?php if ($gift['unlocked_at']): ?>
                        <div class="description" style="color: #059669; margin-top: 5px;">✓ Unlocked!</div>
                    <?php else: ?>
                        <div class="requirement"><?php echo htmlspecialchars($gift['unlock_requirement']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <div class="section story-section">
            <h2>📖 Story Mode Progress</h2>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo ($story['chapters_completed'] / 10 * 100); ?>%">
                    Chapter <?php echo $story['current_chapter']; ?> / 10
                </div>
            </div>
            <p style="color: #7f1d1d; text-align: center; margin-top: 10px;">
                You've completed <?php echo $story['chapters_completed']; ?> of 10 chapters in Santa's Workshop story!
            </p>
        </div>
        
        <div class="action-btns">
            <a href="difficulty.php" class="btn btn-primary">🎮 Play Now</a>
        </div>
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
            
            setTimeout(() => snowflake.remove(), 5000);
        }
        
        setInterval(createSnowflake, 200);
    </script>
</body>
</html>