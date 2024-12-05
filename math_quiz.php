<?php
session_start();

if (isset($_POST['start_game'])) {
    $level = $_POST['level'];
    $customMin = $level === 'custom' ? (int)$_POST['custom_min'] : null;
    $customMax = $level === 'custom' ? (int)$_POST['custom_max'] : null;

    $_SESSION['level'] = $level;
    $_SESSION['operator'] = $_POST['operator'];
    $_SESSION['total_questions'] = $_POST['num_questions'];
    $_SESSION['current_question'] = 1;
    $_SESSION['score'] = 0;
    $_SESSION['missed_questions'] = []; // Initialize missed questions
    $_SESSION['question'] = generateQuestion($level, $_SESSION['operator'], $customMin, $customMax);
    $_SESSION['remark'] = "";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

function generateQuestion($level, $operator, $customMin = null, $customMax = null) {
    if ($level === 'custom' && isset($customMin, $customMax)) {
        $num1 = rand($customMin, $customMax);
        $num2 = rand($customMin, $customMax);
    } else {
        $max = $level == 1 ? 10 : 100;
        $num1 = rand(1, $max);
        $num2 = rand(1, $max);
    }

    if ($operator == '/' && $num2 == 0) {
        $num2 = rand($customMin ?? 1, $customMax ?? $max);
    }

    $answer = eval("return $num1 $operator $num2;");
    $question = "$num1 $operator $num2";

    $choices = [$answer];
    $maxChoiceRange = ($customMax ?? $max) * 2;
    while (count($choices) < 4) {
        $fakeAnswer = rand(1, $maxChoiceRange);
        if (!in_array($fakeAnswer, $choices)) {
            $choices[] = $fakeAnswer;
        }
    }
    shuffle($choices);

    $_SESSION['answer'] = $answer; 
    $_SESSION['choices'] = $choices; 
    return $question;
}

if (isset($_POST['submit_answer'])) {
    $userAnswer = $_POST['answer'];
    if ($userAnswer == $_SESSION['answer']) {
        $_SESSION['score']++;
        $_SESSION['remark'] = "Correct! Well done!";
    } else {
        $_SESSION['remark'] = "Incorrect. The correct answer is " . $_SESSION['answer'];
        $_SESSION['missed_questions'][] = [
            'question' => $_SESSION['question'],
            'correct_answer' => $_SESSION['answer'],
            'user_answer' => $userAnswer
        ];
    }

    $_SESSION['current_question']++;
    if ($_SESSION['current_question'] <= $_SESSION['total_questions']) {
        $_SESSION['question'] = generateQuestion(
            $_SESSION['level'],
            $_SESSION['operator'],
            $_POST['custom_min'] ?? null,
            $_POST['custom_max'] ?? null
        );
    } else {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?game_over=1');
        exit();
    }
}

if (isset($_GET['game_over'])) {
    $score = $_SESSION['score'];
    $total = $_SESSION['total_questions'];
    session_write_close(); // Keep session data for review
    $resultMessage = "Game Over! You scored $score out of $total.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="math_quiz.css">
    <title>Math Quiz Game</title>
</head>
<body>
    <div class="container">
        <h1>Math Quiz Game</h1>

        <?php if (!isset($_SESSION['current_question']) && !isset($_GET['game_over'])): ?>
        <!-- Start Game Form -->
        <form method="post" action="">
            <label for="level">Select Difficulty Level:</label>
            <select name="level" id="level" required>
                <option value="1">Level 1 (1-10)</option>
                <option value="2">Level 2 (1-100)</option>
                <option value="custom">Custom Level</option>
            </select>

            <div id="custom-range-container">
                <div>
                    <label for="custom_min">Min:</label>
                    <input type="number" name="custom_min" id="custom_min" placeholder="Enter min value">
                </div>
                <div>
                    <label for="custom_max">Max:</label>
                    <input type="number" name="custom_max" id="custom_max" placeholder="Enter max value">
                </div>
            </div>

            <label for="operator">Choose Operator:</label>
            <select name="operator" id="operator" required>
                <option value="+">Addition (+)</option>
                <option value="-">Subtraction (-)</option>
                <option value="*">Multiplication (*)</option>
                <option value="/">Division (/)</option>
            </select>

            <label for="num_questions">Number of Questions:</label>
            <input type="number" name="num_questions" id="num_questions" placeholder="Enter number of questions" required>

            <button type="submit" name="start_game">Start Game</button>
        </form>
        <?php elseif (!isset($_GET['game_over'])): ?>
        <!-- Quiz In Progress -->
        <p>Question <?= $_SESSION['current_question'] ?> of <?= $_SESSION['total_questions'] ?>:</p>
        <p><strong><?= $_SESSION['question'] ?></strong></p>
        <form method="post" action="">
            <?php foreach ($_SESSION['choices'] as $choice): ?>
            <div class="choice-container">
                <input type="radio" name="answer" value="<?= $choice ?>" id="choice<?= $choice ?>" required>
                <label for="choice<?= $choice ?>"><?= $choice ?></label>
            </div>
            <?php endforeach; ?>
            <button type="submit" name="submit_answer">Submit Answer</button>
        </form>
        <p class="remark"><?= $_SESSION['remark'] ?></p>
        <?php else: ?>
        <!-- Game Over Section -->
        <div class="game-over">
            <p><?= $resultMessage ?></p>
            <a href="<?= $_SERVER['PHP_SELF'] ?>">Play Again</a>

            <?php if (!empty($_SESSION['missed_questions'])): ?>
            <div class="review-missed">
                <h2>Review Missed Questions:</h2>
                <ul>
                    <?php foreach ($_SESSION['missed_questions'] as $missed): ?>
                        <li>
                            <strong>Question:</strong> <?= $missed['question'] ?><br>
                            <strong>Your Answer:</strong> <span class="user-answer"><?= $missed['user_answer'] ?></span><br>
                            <strong>Correct Answer:</strong> <span class="correct-answer"><?= $missed['correct_answer'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('level').addEventListener('change', function () {
            const customRangeContainer = document.getElementById('custom-range-container');
            customRangeContainer.style.display = this.value === 'custom' ? 'block' : 'none';
        });
    </script>
</body>
</html>
