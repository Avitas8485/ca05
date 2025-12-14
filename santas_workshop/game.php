<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : 'medium';
$gridSize = isset($_GET['size']) ? intval($_GET['size']) : 4;

$conn = getDbConnection();

$stmt = $conn->prepare("SELECT powerup_type, quantity FROM user_powerups WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$powerups = [];
while ($row = $result->fetch_assoc()) {
    $powerups[$row['powerup_type']] = $row['quantity'];
}
$stmt->close();

$stmt = $conn->prepare("SELECT current_chapter FROM story_progress WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$story = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conn->close();

$storyTexts = [
    1 => "Welcome to Santa's Workshop! The elves need your help organizing the toy room.",
    2 => "The reindeer are getting restless. Help arrange their stables!",
    3 => "Mrs. Claus needs the kitchen organized for Christmas cookie baking.",
    4 => "The gift wrapping station is in chaos! Time to sort it out.",
    5 => "Santa's sleigh needs its parts organized before the big night!",
    6 => "The North Pole mail room is overflowing with letters!",
    7 => "Help organize the elf dormitory before bedtime!",
    8 => "The toy testing lab needs your puzzle-solving skills!",
    9 => "Almost Christmas! Help organize the loading dock.",
    10 => "Final challenge: Organize Santa's command center for Christmas Eve!"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Santa's Workshop - Game</title>
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
            padding: 10px;
            overflow-x: hidden;
        }
        
        .snowflake {
            position: fixed;
            top: -10px;
            color: white;
            font-size: 1em;
            animation: fall linear infinite;
            opacity: 0.6;
            pointer-events: none;
        }
        
        @keyframes fall {
            to {
                transform: translateY(110vh) rotate(360deg);
            }
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header-bar {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat .label {
            color: #6b7280;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .stat .value {
            font-size: 1.5em;
            font-weight: bold;
            color: #dc2626;
        }
        
        .hint-btn, .pause-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .hint-btn {
            background: #10b981;
            color: white;
        }
        
        .hint-btn:hover {
            background: #059669;
        }
        
        .pause-btn {
            background: #f59e0b;
            color: white;
        }
        
        .pause-btn:hover {
            background: #d97706;
        }
        
        .game-area {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }
        
        .puzzle-grid {
            display: grid;
            gap: 5px;
            max-width: 600px;
            margin: 0 auto 20px;
            background: #e5e7eb;
            padding: 5px;
            border-radius: 10px;
        }
        
        .tile {
            aspect-ratio: 1;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            font-weight: bold;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            user-select: none;
        }
        
        .tile:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        
        .tile.empty {
            background: #f3f4f6;
            cursor: default;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .tile.empty:hover {
            transform: none;
        }
        
        .tile.movable {
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.03); }
        }
        
        .progress-bar-container {
            background: #e5e7eb;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 0.5s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .bottom-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .powerups-bar {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .powerup {
            padding: 15px 25px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            background: white;
        }
        
        .powerup:hover {
            border-color: #dc2626;
            transform: translateY(-2px);
        }
        
        .powerup.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .powerup .icon {
            font-size: 2em;
            margin-bottom: 5px;
        }
        
        .powerup .count {
            font-weight: bold;
            color: #dc2626;
        }
        
        .story-box {
            background: #fef3c7;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #f59e0b;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .story-box h4 {
            color: #92400e;
            margin-bottom: 10px;
        }
        
        .story-box p {
            color: #78350f;
            line-height: 1.6;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1em;
        }
        
        .btn-primary {
            background: #dc2626;
            color: white;
        }
        
        .btn-primary:hover {
            background: #b91c1c;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .modal-content h2 {
            color: #dc2626;
            margin-bottom: 20px;
            font-size: 2em;
        }
        
        .modal-content p {
            color: #4b5563;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #dc2626;
            position: absolute;
            animation: confetti-fall 3s linear;
        }
        
        @keyframes confetti-fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-bar">
            <div class="stat">
                <div class="label">⏱️ Timer</div>
                <div class="value" id="timer">00:00</div>
            </div>
            
            <div class="stat">
                <div class="label">🔢 Moves</div>
                <div class="value" id="moves">0</div>
            </div>
            
            <div class="stat">
                <div class="label">⭐ Score</div>
                <div class="value" id="score">0</div>
            </div>
            
            <button class="hint-btn" onclick="useHint()">❄️ Hint</button>
            <button class="pause-btn" onclick="pauseGame()">⏸️ Pause</button>
        </div>
        
        <div class="game-area">
            <div class="puzzle-grid" id="puzzleGrid"></div>
            
            <div class="progress-bar-container">
                <div class="progress-bar" id="progressBar">0% Complete</div>
            </div>
        </div>
        
        <div class="bottom-section">
            <div class="powerups-bar">
                <div class="powerup" onclick="usePowerup('hint')">
                    <div class="icon">❄️</div>
                    <div>Hint</div>
                    <div class="count"><?php echo $powerups['hint'] ?? 0; ?></div>
                </div>
                
                <div class="powerup" onclick="usePowerup('shuffle')">
                    <div class="icon">🎁</div>
                    <div>Shuffle</div>
                    <div class="count"><?php echo $powerups['shuffle'] ?? 0; ?></div>
                </div>
                
                <div class="powerup" onclick="usePowerup('auto_solve')">
                    <div class="icon">⭐</div>
                    <div>Auto</div>
                    <div class="count"><?php echo $powerups['auto_solve'] ?? 0; ?></div>
                </div>
                
                <div class="powerup" onclick="usePowerup('undo')">
                    <div class="icon">↩️</div>
                    <div>Undo</div>
                    <div class="count"><?php echo $powerups['undo'] ?? 0; ?></div>
                </div>
            </div>
            
            <div class="story-box">
                <h4>📖 Chapter <?php echo $story['current_chapter']; ?></h4>
                <p><?php echo $storyTexts[$story['current_chapter']] ?? 'Continue your adventure!'; ?></p>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="shufflePuzzle()">🔄 Shuffle</button>
                <button class="btn btn-secondary" onclick="quitGame()">❌ Quit</button>
            </div>
        </div>
    </div>
    
    <div class="modal" id="completionModal">
        <div class="modal-content">
            <h2>🎉 Puzzle Completed! 🎉</h2>
            <p id="completionTime"></p>
            <p id="completionMoves"></p>
            <p id="completionScore"></p>
            <p id="starRating"></p>
            <br>
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="playAgain()">Play Again</button>
                <button class="btn btn-secondary" onclick="goHome()">Home</button>
            </div>
        </div>
    </div>
    
    <script>
        const GRID_SIZE = <?php echo $gridSize; ?>;
        const DIFFICULTY = '<?php echo $difficulty; ?>';
        const USER_ID = <?php echo $user['id']; ?>;
        
        let tiles = [];
        let emptyPos = { row: 0, col: 0 };
        let moves = 0;
        let score = 0;
        let startTime = Date.now();
        let timerInterval;
        let moveHistory = [];
        
        const tileEmojis = ['🎁', '🎄', '⛄', '🎅', '🔔', '⭐', '🦌', '🧦', '🍪', '🎀', 
                            '🕯️', '🎺', '🎻', '🎪', '🎭', '🎨', '🧸', '🚂', '🏠', '❄️',
                            '✨', '🌟', '💫', '🎊', '🎉'];
        
        function initGame() {
            createPuzzle();
            shufflePuzzle();
            startTimer();
            updateDisplay();
        }
        
        function createPuzzle() {
            tiles = [];
            let num = 1;
            
            for (let i = 0; i < GRID_SIZE; i++) {
                tiles[i] = [];
                for (let j = 0; j < GRID_SIZE; j++) {
                    if (i === GRID_SIZE - 1 && j === GRID_SIZE - 1) {
                        tiles[i][j] = 0;
                        emptyPos = { row: i, col: j };
                    } else {
                        tiles[i][j] = num++;
                    }
                }
            }
        }
        
        function shufflePuzzle() {
            for (let i = 0; i < GRID_SIZE * GRID_SIZE * 50; i++) {
                const movable = getMovableTiles();
                if (movable.length > 0) {
                    const randomTile = movable[Math.floor(Math.random() * movable.length)];
                    swapTiles(randomTile.row, randomTile.col, false);
                }
            }
            moves = 0;
            score = 1000;
            moveHistory = [];
            updateDisplay();
        }
        
        function renderGrid() {
            const grid = document.getElementById('puzzleGrid');
            grid.style.gridTemplateColumns = `repeat(${GRID_SIZE}, 1fr)`;
            grid.innerHTML = '';
            
            const movable = getMovableTiles();
            
            for (let i = 0; i < GRID_SIZE; i++) {
                for (let j = 0; j < GRID_SIZE; j++) {
                    const tile = document.createElement('div');
                    tile.className = 'tile';
                    
                    if (tiles[i][j] === 0) {
                        tile.classList.add('empty');
                    } else {
                        const emoji = tileEmojis[(tiles[i][j] - 1) % tileEmojis.length];
                        tile.innerHTML = `<span style="font-size: 0.7em;">${emoji}</span><br>${tiles[i][j]}`;
                        tile.onclick = () => moveTile(i, j);
                        
                        const isMovable = movable.some(m => m.row === i && m.col === j);
                        if (isMovable) {
                            tile.classList.add('movable');
                        }
                    }
                    
                    grid.appendChild(tile);
                }
            }
        }
        
        function getMovableTiles() {
            const movable = [];
            const directions = [
                { row: -1, col: 0 },
                { row: 1, col: 0 },
                { row: 0, col: -1 },
                { row: 0, col: 1 }
            ];
            
            for (const dir of directions) {
                const newRow = emptyPos.row + dir.row;
                const newCol = emptyPos.col + dir.col;
                
                if (newRow >= 0 && newRow < GRID_SIZE && newCol >= 0 && newCol < GRID_SIZE) {
                    movable.push({ row: newRow, col: newCol });
                }
            }
            
            return movable;
        }
        
        function moveTile(row, col) {
            const movable = getMovableTiles();
            const canMove = movable.some(m => m.row === row && m.col === col);
            
            if (canMove) {
                swapTiles(row, col, true);
            }
        }
        
        function swapTiles(row, col, countMove) {
            moveHistory.push({
                tiles: JSON.parse(JSON.stringify(tiles)),
                emptyPos: { ...emptyPos },
                moves: moves,
                score: score
            });
            
            tiles[emptyPos.row][emptyPos.col] = tiles[row][col];
            tiles[row][col] = 0;
            emptyPos = { row, col };
            
            if (countMove) {
                moves++;
                score = Math.max(0, score - 5);
                updateDisplay();
                
                if (checkWin()) {
                    setTimeout(() => completeGame(), 300);
                }
            }
        }
        
        function checkWin() {
            let expected = 1;
            for (let i = 0; i < GRID_SIZE; i++) {
                for (let j = 0; j < GRID_SIZE; j++) {
                    if (i === GRID_SIZE - 1 && j === GRID_SIZE - 1) {
                        return tiles[i][j] === 0;
                    }
                    if (tiles[i][j] !== expected++) {
                        return false;
                    }
                }
            }
            return true;
        }
        
        function completeGame() {
            clearInterval(timerInterval);
            
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const timeBonus = Math.max(0, 500 - elapsed);
            const moveBonus = Math.max(0, 300 - moves * 2);
            const finalScore = score + timeBonus + moveBonus;
            
            let stars = 1;
            if (finalScore > 800) stars = 3;
            else if (finalScore > 600) stars = 2;
            
            document.getElementById('completionTime').textContent = `Time: ${formatTime(elapsed)}`;
            document.getElementById('completionMoves').textContent = `Moves: ${moves}`;
            document.getElementById('completionScore').textContent = `Score: ${finalScore}`;
            document.getElementById('starRating').textContent = '⭐'.repeat(stars);
            
            createConfetti();
            document.getElementById('completionModal').classList.add('active');
            
            saveGame(elapsed, moves, finalScore, stars);
        }
        
        function saveGame(time, moves, score, stars) {
            const formData = new FormData();
            formData.append('user_id', USER_ID);
            formData.append('difficulty', DIFFICULTY);
            formData.append('grid_size', GRID_SIZE);
            formData.append('completion_time', time);
            formData.append('moves', moves);
            formData.append('score', score);
            formData.append('stars', stars);
            
            fetch('save_game.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Game saved successfully:', data);
            })
            .catch(error => {
                console.error('Error saving game:', error);
            });
        }
        
        function createConfetti() {
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.top = '-10px';
                    confetti.style.background = ['#dc2626', '#10b981', '#f59e0b', '#3b82f6'][Math.floor(Math.random() * 4)];
                    document.body.appendChild(confetti);
                    setTimeout(() => confetti.remove(), 3000);
                }, i * 30);
            }
        }
        
        function startTimer() {
            timerInterval = setInterval(() => {
                const elapsed = Math.floor((Date.now() - startTime) / 1000);
                document.getElementById('timer').textContent = formatTime(elapsed);
            }, 1000);
        }
        
        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        
        function updateDisplay() {
            document.getElementById('moves').textContent = moves;
            document.getElementById('score').textContent = score;
            renderGrid();
            updateProgress();
        }
        
        function updateProgress() {
            let correct = 0;
            let total = GRID_SIZE * GRID_SIZE - 1;
            let expected = 1;
            
            for (let i = 0; i < GRID_SIZE; i++) {
                for (let j = 0; j < GRID_SIZE; j++) {
                    if (i === GRID_SIZE - 1 && j === GRID_SIZE - 1) continue;
                    if (tiles[i][j] === expected) correct++;
                    expected++;
                }
            }
            
            const percent = Math.floor((correct / total) * 100);
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressBar').textContent = percent + '% Complete';
        }
        
        function useHint() {
            alert('💡 Hint: Try to solve one row or column at a time, starting from the top-left!');
            score = Math.max(0, score - 50);
            updateDisplay();
        }
        
        function usePowerup(type) {
            if (type === 'hint') {
                useHint();
            } else if (type === 'shuffle') {
                if (confirm('Shuffle the puzzle? This will reset your progress.')) {
                    shufflePuzzle();
                }
            } else if (type === 'undo' && moveHistory.length > 0) {
                const prev = moveHistory.pop();
                tiles = prev.tiles;
                emptyPos = prev.emptyPos;
                moves = prev.moves;
                score = prev.score;
                updateDisplay();
            }
        }
        
        function pauseGame() {
            if (confirm('Pause game? Timer will continue running.')) {
                
            }
        }
        
        function quitGame() {
            if (confirm('Are you sure you want to quit? Progress will be lost.')) {
                window.location.href = 'difficulty.php';
            }
        }
        
        function playAgain() {
            window.location.href = 'difficulty.php';
        }
        
        function goHome() {
            window.location.href = 'home.php';
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
        
        setInterval(createSnowflake, 300);
        initGame();
    </script>
</body>
</html>