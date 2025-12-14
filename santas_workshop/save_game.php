<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $difficulty = $_POST['difficulty'];
    $grid_size = intval($_POST['grid_size']);
    $completion_time = intval($_POST['completion_time']);
    $moves = intval($_POST['moves']);
    $score = intval($_POST['score']);
    $stars = intval($_POST['stars']);
    
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO games (user_id, difficulty, grid_size, completion_time, moves_used, score, star_rating) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isiiiii", $user_id, $difficulty, $grid_size, $completion_time, $moves, $score, $stars);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM games WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_games = $result['total'];
    $stmt->close();
    
    if ($total_games == 1) {
        checkAndUnlockAchievement($conn, $user_id, 'first_victory');
    }
    
    if ($completion_time < 60) {
        checkAndUnlockAchievement($conn, $user_id, 'speed_demon');
    }
    
    if ($stars == 3) {
        checkAndUnlockAchievement($conn, $user_id, 'perfect_game');
    }
    
    if ($total_games >= 10) {
        checkAndUnlockAchievement($conn, $user_id, 'marathon_master');
    }
    
    if ($total_games >= 50) {
        checkAndUnlockAchievement($conn, $user_id, 'toy_master');
    }
    
    if ($total_games >= 100) {
        checkAndUnlockAchievement($conn, $user_id, 'workshop_legend');
    }
    
    $stmt = $conn->prepare("SELECT SUM(score) as total_score FROM games WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_score = $result['total_score'];
    $stmt->close();
    
    if ($total_score >= 10000) {
        checkAndUnlockGift($conn, $user_id, 'golden_bell');
    }
    
    if ($total_games >= 5) {
        checkAndUnlockGift($conn, $user_id, 'snow_globe');
    }
    
    if ($total_games >= 10) {
        checkAndUnlockGift($conn, $user_id, 'candy_cane');
    }
    
    if ($difficulty == 'hard') {
        checkAndUnlockGift($conn, $user_id, 'reindeer_toy');
    }
    
    if ($total_games >= 20) {
        checkAndUnlockGift($conn, $user_id, 'christmas_tree');
    }
    
    if ($total_games >= 30) {
        checkAndUnlockGift($conn, $user_id, 'sleigh_model');
    }
    
    $stmt = $conn->prepare("
        UPDATE story_progress 
        SET current_chapter = LEAST(current_chapter + 1, 10),
            chapters_completed = chapters_completed + 1
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    $conn->close();
    
    echo json_encode(['success' => true]);
}

function checkAndUnlockAchievement($conn, $user_id, $achievement_code) {
    $stmt = $conn->prepare("SELECT id FROM achievements WHERE code = ?");
    $stmt->bind_param("s", $achievement_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($achievement = $result->fetch_assoc()) {
        $achievement_id = $achievement['id'];
        
        $stmt2 = $conn->prepare("
            SELECT id FROM user_achievements 
            WHERE user_id = ? AND achievement_id = ?
        ");
        $stmt2->bind_param("ii", $user_id, $achievement_id);
        $stmt2->execute();
        $exists = $stmt2->get_result()->num_rows > 0;
        $stmt2->close();
        
        if (!$exists) {
            $stmt3 = $conn->prepare("
                INSERT INTO user_achievements (user_id, achievement_id) 
                VALUES (?, ?)
            ");
            $stmt3->bind_param("ii", $user_id, $achievement_id);
            $stmt3->execute();
            $stmt3->close();
        }
    }
    
    $stmt->close();
}

function checkAndUnlockGift($conn, $user_id, $gift_code) {
    $stmt = $conn->prepare("SELECT id FROM gifts WHERE code = ?");
    $stmt->bind_param("s", $gift_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($gift = $result->fetch_assoc()) {
        $gift_id = $gift['id'];
        
        $stmt2 = $conn->prepare("
            SELECT id FROM user_gifts 
            WHERE user_id = ? AND gift_id = ?
        ");
        $stmt2->bind_param("ii", $user_id, $gift_id);
        $stmt2->execute();
        $exists = $stmt2->get_result()->num_rows > 0;
        $stmt2->close();
        
        if (!$exists) {
            $stmt3 = $conn->prepare("
                INSERT INTO user_gifts (user_id, gift_id) 
                VALUES (?, ?)
            ");
            $stmt3->bind_param("ii", $user_id, $gift_id);
            $stmt3->execute();
            $stmt3->close();
        }
    }
    
    $stmt->close();
}
?>