<?php
session_start();


$moneyValues = [
    0.01, 1, 5, 10, 25, 50, 75, 100, 200, 300, 400, 500, 750,
    1000, 5000, 10000, 25000, 50000, 75000, 100000, 200000, 
    300000, 400000, 500000, 750000, 1000000
];


$roundStructure = [6, 5, 4, 3, 2, 1, 1, 1, 1];


if (!isset($_SESSION['game']) || isset($_POST['reset'])) {
    $shuffled = $moneyValues;
    shuffle($shuffled);
    $_SESSION['game'] = [
        'cases' => array_combine(range(1, 26), $shuffled),
        'playerCase' => null,
        'openedCases' => [],
        'round' => 0,
        'casesToOpen' => 0,
        'state' => 'selecting',
        'lastOpened' => null,
        'lastValue' => null,
        'finalResult' => null,
        'dealAmount' => null
    ];
}

$game = &$_SESSION['game'];


if ($game['state'] === 'selecting' && isset($_POST['selectCase'])) {
    $game['playerCase'] = (int)$_POST['selectCase'];
    $game['state'] = 'playing';
    $game['round'] = 0;
    $game['casesToOpen'] = $roundStructure[0];
    $game['lastOpened'] = null;
}


if ($game['state'] === 'playing' && isset($_POST['openCase'])) {
    $caseNum = (int)$_POST['openCase'];
    if ($caseNum !== $game['playerCase'] && !in_array($caseNum, $game['openedCases'])) {
        $game['openedCases'][] = $caseNum;
        $game['lastOpened'] = $caseNum;
        $game['lastValue'] = $game['cases'][$caseNum];
        $game['casesToOpen']--;
        
        if ($game['casesToOpen'] <= 0) {
            
            $remainingCases = array_diff(range(1, 26), $game['openedCases'], [$game['playerCase']]);
            if (count($remainingCases) === 1) {
                $game['state'] = 'final';
            } else {
                $game['state'] = 'offer';
            }
        }
    }
}


if ($game['state'] === 'offer') {
    if (isset($_POST['deal'])) {
        $game['dealAmount'] = calculateBankerOffer($game);
        $game['finalResult'] = 'deal';
        $game['state'] = 'gameover';
    } elseif (isset($_POST['nodeal'])) {
        $game['round']++;
        $game['casesToOpen'] = $roundStructure[min($game['round'], count($roundStructure) - 1)];
        $game['state'] = 'playing';
        $game['lastOpened'] = null;
    }
}


if ($game['state'] === 'final') {
    if (isset($_POST['keepCase'])) {
        $game['finalResult'] = 'kept';
        $game['state'] = 'gameover';
    } elseif (isset($_POST['switchCase'])) {
        $remainingCase = array_values(array_diff(range(1, 26), $game['openedCases'], [$game['playerCase']]))[0];
        $game['playerCase'] = $remainingCase;
        $game['finalResult'] = 'switched';
        $game['state'] = 'gameover';
    }
}


function calculateBankerOffer($game) {
    $remainingValues = [];
    foreach ($game['cases'] as $num => $val) {
        if ($num === $game['playerCase'] || !in_array($num, $game['openedCases'])) {
            $remainingValues[] = $val;
        }
    }
    $avg = array_sum($remainingValues) / count($remainingValues);
    
    
    $casesOpened = count($game['openedCases']);
    $totalOpenable = 25; 
    $percentageOpened = $casesOpened / $totalOpenable;
    
    
    
    $baseMultiplier = 0.2 + ($percentageOpened * 0.7);
    
    
    $max = max($remainingValues);
    $min = min($remainingValues);
    $spread = $max - $min;
    $riskFactor = $spread / $max; 
    
    
    $riskAdjustment = 1.0 - ($riskFactor * 0.3);
    
    $finalMultiplier = $baseMultiplier * $riskAdjustment;
    $finalMultiplier = min($finalMultiplier, 1.1); 
    
    return round($avg * $finalMultiplier);
}


function formatMoney($amount) {
    if ($amount < 1) return '$' . number_format($amount, 2);
    return '$' . number_format($amount);
}


function isValueInPlay($value, $game) {
    foreach ($game['openedCases'] as $caseNum) {
        if ($game['cases'][$caseNum] == $value) return false;
    }
    return true;
}


$lowValues = [0.01, 1, 5, 10, 25, 50, 75, 100, 200, 300, 400, 500, 750];

$highValues = [1000, 5000, 10000, 25000, 50000, 75000, 100000, 200000, 300000, 400000, 500000, 750000, 1000000];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deal or No Deal</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            color: #fff;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        header {
            text-align: center;
            padding: 20px;
            background: linear-gradient(90deg, #ffd700, #ff8c00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
        }
        header h1 { font-size: 2.5em; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        header p { font-size: 1.2em; opacity: 0.9; }
        .game-area {
            display: grid;
            grid-template-columns: 150px 1fr 150px;
            gap: 20px;
            margin-bottom: 20px;
        }
        .money-tree {
            background: rgba(0,0,0,0.4);
            border-radius: 10px;
            padding: 10px;
        }
        .money-tree h3 {
            text-align: center;
            margin-bottom: 10px;
            color: #ffd700;
            font-size: 0.9em;
        }
        .money-value {
            padding: 6px 10px;
            margin: 4px 0;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.85em;
            text-align: center;
            transition: all 0.3s ease;
        }
        .money-tree.low .money-value { background: linear-gradient(90deg, #1e3a5f, #2d5a87); }
        .money-tree.high .money-value { background: linear-gradient(90deg, #8b4513, #cd853f); }
        .money-value.struck {
            background: #333 !important;
            text-decoration: line-through;
            opacity: 0.4;
        }
        .briefcase-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 15px;
            padding: 20px;
            background: rgba(0,0,0,0.3);
            border-radius: 15px;
        }
        .briefcase {
            aspect-ratio: 1;
            border: none;
            border-radius: 10px;
            background: linear-gradient(145deg, #c9a227, #8b7355);
            cursor: pointer;
            font-size: 1.4em;
            font-weight: bold;
            color: #1a1a2e;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .briefcase:hover:not(.opened):not(.player-case) {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }
        .briefcase.opened {
            background: #333;
            cursor: default;
            opacity: 0.5;
        }
        .briefcase.player-case {
            background: linear-gradient(145deg, #ffd700, #ffaa00);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.6);
            cursor: default;
        }
        .briefcase.just-opened {
            animation: flipReveal 1s ease forwards;
        }
        @keyframes flipReveal {
            0% { transform: rotateY(0deg); }
            50% { transform: rotateY(90deg); background: #c9a227; }
            51% { background: #333; }
            100% { transform: rotateY(0deg); background: #333; opacity: 0.5; }
        }
        .revealed-value {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 0.6em;
            color: #ffd700;
        }
        .bottom-section {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 20px;
            background: rgba(0,0,0,0.4);
            padding: 20px;
            border-radius: 15px;
        }
        .player-case-display {
            text-align: center;
        }
        .player-case-display h3 { color: #ffd700; margin-bottom: 10px; }
        .player-case-display .big-case {
            width: 100px;
            height: 100px;
            background: linear-gradient(145deg, #ffd700, #ffaa00);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            font-weight: bold;
            color: #1a1a2e;
            margin: 0 auto;
            box-shadow: 0 0 30px rgba(255, 215, 0, 0.5);
        }
        .game-status {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .game-status h2 { color: #ffd700; margin-bottom: 10px; font-size: 1.5em; }
        .game-status p { font-size: 1.2em; opacity: 0.9; }
        .offer-screen, .gameover-screen, .final-screen {
            text-align: center;
            padding: 40px;
            background: rgba(0,0,0,0.5);
            border-radius: 20px;
            margin: 20px 0;
        }
        .offer-amount {
            font-size: 4em;
            color: #ffd700;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
            margin: 30px 0;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .btn {
            padding: 15px 40px;
            font-size: 1.3em;
            font-weight: bold;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn:hover { transform: scale(1.05); }
        .btn-deal {
            background: linear-gradient(145deg, #28a745, #1e7b34);
            color: white;
            box-shadow: 0 5px 20px rgba(40, 167, 69, 0.4);
        }
        .btn-nodeal {
            background: linear-gradient(145deg, #dc3545, #a71d2a);
            color: white;
            box-shadow: 0 5px 20px rgba(220, 53, 69, 0.4);
        }
        .btn-play {
            background: linear-gradient(145deg, #ffd700, #ffaa00);
            color: #1a1a2e;
            box-shadow: 0 5px 20px rgba(255, 215, 0, 0.4);
        }
        .welcome-screen {
            text-align: center;
            margin-bottom: 20px;
        }
        .welcome-screen h2 { color: #ffd700; font-size: 2em; margin-bottom: 10px; }
        .last-revealed {
            background: rgba(255, 215, 0, 0.2);
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            border: 2px solid #ffd700;
        }
        .last-revealed .value {
            font-size: 1.5em;
            color: #ffd700;
            font-weight: bold;
        }
        @media (max-width: 900px) {
            .game-area { grid-template-columns: 1fr; }
            .money-tree { display: none; }
            .bottom-section { grid-template-columns: 1fr; }
        }
        .money-trees-mobile {
            display: none;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        @media (max-width: 900px) {
            .money-trees-mobile { display: grid; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>DEAL OR NO DEAL</h1>
            <p>High-Stakes Negotiation</p>
        </header>

        <?php if ($game['state'] === 'selecting'): ?>
            <div class="welcome-screen">
                <h2>Choose Your Case</h2>
                <p>Select one of the 26 briefcases to keep as your own. This case will remain sealed until the end of the game.</p>
            </div>
            <div class="briefcase-grid">
                <?php for ($i = 1; $i <= 26; $i++): ?>
                    <form method="post" style="display:contents;">
                        <button type="submit" name="selectCase" value="<?= $i ?>" class="briefcase">
                            <?= $i ?>
                        </button>
                    </form>
                <?php endfor; ?>
            </div>

        <?php elseif ($game['state'] === 'playing'): ?>
            <div class="money-trees-mobile">
                <div class="money-tree low">
                    <h3>Low Values</h3>
                    <?php foreach ($lowValues as $val): ?>
                        <div class="money-value <?= !isValueInPlay($val, $game) ? 'struck' : '' ?>">
                            <?= formatMoney($val) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="money-tree high">
                    <h3>High Values</h3>
                    <?php foreach ($highValues as $val): ?>
                        <div class="money-value <?= !isValueInPlay($val, $game) ? 'struck' : '' ?>">
                            <?= formatMoney($val) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="game-area">
                <div class="money-tree low">
                    <h3>Low Values</h3>
                    <?php foreach ($lowValues as $val): ?>
                        <div class="money-value <?= !isValueInPlay($val, $game) ? 'struck' : '' ?>">
                            <?= formatMoney($val) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="briefcase-grid">
                    <?php for ($i = 1; $i <= 26; $i++): 
                        $isOpened = in_array($i, $game['openedCases']);
                        $isPlayerCase = $i === $game['playerCase'];
                        $justOpened = $game['lastOpened'] === $i;
                        $classes = 'briefcase';
                        if ($isOpened) $classes .= ' opened';
                        if ($isPlayerCase) $classes .= ' player-case';
                        if ($justOpened) $classes .= ' just-opened';
                    ?>
                        <?php if (!$isOpened && !$isPlayerCase): ?>
                            <form method="post" style="display:contents;">
                                <button type="submit" name="openCase" value="<?= $i ?>" class="<?= $classes ?>">
                                    <?= $i ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="<?= $classes ?>">
                                <?= $i ?>
                                <?php if ($isOpened): ?>
                                    <span class="revealed-value"><?= formatMoney($game['cases'][$i]) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <div class="money-tree high">
                    <h3>High Values</h3>
                    <?php foreach ($highValues as $val): ?>
                        <div class="money-value <?= !isValueInPlay($val, $game) ? 'struck' : '' ?>">
                            <?= formatMoney($val) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="bottom-section">
                <div class="player-case-display">
                    <h3>Your Case</h3>
                    <div class="big-case"><?= $game['playerCase'] ?></div>
                </div>
                <div class="game-status">
                    <h2>Round <?= $game['round'] + 1 ?></h2>
                    <p>Pick <?= $game['casesToOpen'] ?> briefcase<?= $game['casesToOpen'] > 1 ? 's' : '' ?> to open.</p>
                    <?php if ($game['lastOpened']): ?>
                        <div class="last-revealed">
                            Case #<?= $game['lastOpened'] ?> contained: 
                            <span class="value"><?= formatMoney($game['lastValue']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($game['state'] === 'offer'): ?>
            <?php $offer = calculateBankerOffer($game); ?>
            <div class="offer-screen">
                <h2>📞 The Banker is Calling...</h2>
                <p>The Banker has made you an offer:</p>
                <div class="offer-amount"><?= formatMoney($offer) ?></div>
                <p>Do you want to take the money, or keep playing?</p>
                <form method="post" style="display:inline;">
                    <button type="submit" name="deal" class="btn btn-deal">DEAL</button>
                </form>
                <form method="post" style="display:inline;">
                    <button type="submit" name="nodeal" class="btn btn-nodeal">NO DEAL</button>
                </form>
            </div>
            <div class="money-trees-mobile">
                <div class="money-tree low">
                    <h3>Low Values</h3>
                    <?php foreach ($lowValues as $val): ?>
                        <div class="money-value <?= !isValueInPlay($val, $game) ? 'struck' : '' ?>">
                            <?= formatMoney($val) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="money-tree high">
                    <h3>High Values</h3>
                    <?php foreach ($highValues as $val): ?>
                        <div class="money-value <?= !isValueInPlay($val, $game) ? 'struck' : '' ?>">
                            <?= formatMoney($val) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php elseif ($game['state'] === 'final'): ?>
            <?php 
            $remainingCase = array_values(array_diff(range(1, 26), $game['openedCases'], [$game['playerCase']]))[0];
            ?>
            <div class="final-screen">
                <h2>Final Decision!</h2>
                <p>Only two cases remain. Your case #<?= $game['playerCase'] ?> and case #<?= $remainingCase ?>.</p>
                <p>Do you want to keep your case or switch?</p>
                <form method="post" style="display:inline;">
                    <button type="submit" name="keepCase" class="btn btn-deal">KEEP Case #<?= $game['playerCase'] ?></button>
                </form>
                <form method="post" style="display:inline;">
                    <button type="submit" name="switchCase" class="btn btn-nodeal">SWITCH to Case #<?= $remainingCase ?></button>
                </form>
            </div>

        <?php elseif ($game['state'] === 'gameover'): ?>
            <div class="gameover-screen">
                <?php if ($game['finalResult'] === 'deal'): ?>
                    <h2>🎉 You Made a Deal!</h2>
                    <p>You walked away with:</p>
                    <div class="offer-amount"><?= formatMoney($game['dealAmount']) ?></div>
                    <p>Your case #<?= $game['playerCase'] ?> contained: <strong><?= formatMoney($game['cases'][$game['playerCase']]) ?></strong></p>
                    <?php if ($game['cases'][$game['playerCase']] > $game['dealAmount']): ?>
                        <p style="color: #dc3545;">You could have won more!</p>
                    <?php else: ?>
                        <p style="color: #28a745;">Great deal! You made the right choice!</p>
                    <?php endif; ?>
                <?php else: ?>
                    <h2>🎊 Game Over!</h2>
                    <p>You <?= $game['finalResult'] === 'kept' ? 'kept' : 'switched to' ?> case #<?= $game['playerCase'] ?>.</p>
                    <p>Your case contained:</p>
                    <div class="offer-amount"><?= formatMoney($game['cases'][$game['playerCase']]) ?></div>
                <?php endif; ?>
                <form method="post">
                    <button type="submit" name="reset" class="btn btn-play">Play Again</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>