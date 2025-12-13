<?php
session_start();
require('connect.php');

// Если пользователь уже авторизован, перенаправляем
if (isset($_SESSION['user'])) {
    header("Location: index.php?page=tests");
    exit();
}

// Используем тест Климова (ID=1)
$test_id = 1;
$test = $link->query("SELECT * FROM tests WHERE id = '$test_id'")->fetch_assoc();

if (!$test) {
    header("Location: landing.php");
    exit();
}

// Получаем ВСЕ вопросы теста Климова
$questions = $link->query("
    SELECT q.*, GROUP_CONCAT(a.id) as answer_ids, 
           GROUP_CONCAT(a.answer_text) as answer_texts,
           GROUP_CONCAT(a.score_value) as score_values
    FROM questions q 
    LEFT JOIN answers a ON q.id = a.question_id 
    WHERE q.test_id = '$test_id' 
    GROUP BY q.id 
    ORDER BY q.question_order
");

if ($questions->num_rows == 0) {
    header("Location: index.php?page=sign-up");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест Климова - Profio</title>
    <link rel="stylesheet" href="assets/styles/style.min.css">
    <link rel="stylesheet" href="assets/styles/tailwind.min.css">
    <link rel="icon" href="assets/images/icons/favicon.svg" type="image/svg+xml" sizes="16x16">
    <style>
        .logo-square {
            background: #7364db !important;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <!-- Упрощенный хедер -->
    <header class="bg-white dark:bg-gray-800 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a class="flex items-center gap-3" href="landing.php">
                        <div class="logo-square rounded flex items-center justify-center" style="width: 32px; height: 32px;">
                            <span class="text-white font-bold text-sm">P</span>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white text-lg">Profio</span>
                    </a>
                </div>
                <div class="text-sm text-gray-500">
                    Вопросов: <?php echo $questions->num_rows; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                <?php echo $test['title']; ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                <?php echo $test['description']; ?>
            </p>
        </div>

        <form action="event_user/onboarding_test.php" method="POST">
            <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">

            <div class="space-y-6">
                <?php
                $question_num = 1;
                while ($question = $questions->fetch_assoc()):
                    $answer_ids = explode(',', $question['answer_ids']);
                    $answer_texts = explode(',', $question['answer_texts']);
                    $score_values = explode(',', $question['score_values']);
                ?>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4">
                            Вопрос <?php echo $question_num; ?>: <?php echo $question['question_text']; ?>
                        </h3>

                        <div class="space-y-3">
                            <?php for ($i = 0; $i < count($answer_ids); $i++): ?>
                                <label class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <input type="radio" name="question_<?php echo $question['id']; ?>"
                                        value="<?php echo $answer_ids[$i]; ?>"
                                        class="radio radio-primary mr-3" required>
                                    <span class="text-gray-700 dark:text-gray-300"><?php echo $answer_texts[$i]; ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php $question_num++; ?>
                <?php endwhile; ?>
            </div>

            <div class="mt-8 space-y-4">
                <button type="submit" class="btn bg-color-brands text-white w-full py-3 text-sm sm:text-base hover:bg-color-brands/90 whitespace-normal break-words leading-tight px-4 flex items-center justify-center text-center h-auto min-h-[48px]">
                    Завершить тестирование и посмотреть результаты
                </button>
                
                <div class="text-center">
                    <a href="index.php?page=sign-up" class="text-color-brands hover:text-color-brands/80 underline text-sm">
                        Пропустить тест и сразу создать аккаунт
                    </a>
                </div>

                <div class="text-center">
                    <a href="landing.php" class="text-gray-500 hover:text-gray-700 text-sm">
                        ← На главную
                    </a>
                </div>
            </div>
        </form>
    </main>
</body>

</html>