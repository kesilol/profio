<?php
// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=sign-in");
    exit();
}

// Получаем ID теста
$test_id = $_GET['id'] ?? 0;

// Получаем информацию о тесте
$test = $link->query("
    SELECT t.*, tt.name as type_name 
    FROM tests t 
    LEFT JOIN test_types tt ON t.test_type_id = tt.id 
    WHERE t.id = '$test_id'
")->fetch_assoc();

if (!$test) {
    echo "<script>alert('Тест не найден!'); window.location.href = 'index.php?page=tests';</script>";
    exit();
}

// Получаем вопросы для теста
$questions = $link->query("
    SELECT q.*, GROUP_CONCAT(a.id) as answer_ids, 
           GROUP_CONCAT(a.answer_text) as answer_texts, 
           GROUP_CONCAT(a.score_value) as score_values,
           GROUP_CONCAT(a.answer_order) as answer_orders
    FROM questions q 
    LEFT JOIN answers a ON q.id = a.question_id 
    WHERE q.test_id = '$test_id' 
    GROUP BY q.id 
    ORDER BY q.question_order
");

// Проверяем, есть ли вопросы у теста
$has_questions = $questions->num_rows > 0;
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
            <?php echo $test['title']; ?>
        </h2>

        <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
            <div class="flex items-center gap-x-1">
                <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                <a class="capitalize" href="index.php">Главная</a>
            </div>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <a class="capitalize" href="index.php?page=tests">Тестирование</a>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <span class="capitalize text-color-brands"><?php echo $test['title']; ?></span>
        </div>

        <!-- Описание теста -->
        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 mb-6">
            <p class="text-gray-500 dark:text-gray-dark-500 mb-4">
                <?php echo $test['description']; ?>
            </p>
            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-dark-500">
                <span>Вопросов: <?php echo $test['questions_count']; ?></span>
                <span>Тип: <?php echo $test['type_name']; ?></span>
                <span>Время: ~10-15 минут</span>
            </div>
        </div>

        <?php if (!$has_questions): ?>
            <!-- Заглушка для теста без вопросов -->
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-8 text-center">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-yellow-100 grid place-items-center">
                    <img src="assets/images/icons/icon-clock.svg" alt="В разработке" class="w-10 h-10">
                </div>
                <h3 class="text-xl font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-3">
                    Тест в разработке
                </h3>
                <p class="text-gray-500 dark:text-gray-dark-500 mb-6 max-w-md mx-auto">
                    Извините, этот тест находится в стадии разработки. Наша команда работает над добавлением вопросов.
                    Пожалуйста, попробуйте другие доступные тесты.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="index.php?page=tests"
                        class="btn bg-color-brands text-white px-6 py-3 hover:bg-color-brands/90 transition-colors">
                        Вернуться к тестам
                    </a>
                    <a href="index.php"
                        class="btn border border-gray-300 text-gray-700 px-6 py-3 hover:bg-gray-50 transition-colors dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                        На главную
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Форма теста (только если есть вопросы) -->
            <form id="testForm" action="../event_user/submit_test.php" method="POST">
                <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">

                <div class="space-y-6 py-2">
                    <?php
                    $question_num = 1;
                    while ($question = $questions->fetch_assoc()):
                        $answer_ids = explode(',', $question['answer_ids']);
                        $answer_texts = explode(',', $question['answer_texts']);
                        $score_values = explode(',', $question['score_values']);
                    ?>
                        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 question-block">
                            <h3 class="text-normal font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                                Вопрос <?php echo $question_num; ?>: <?php echo $question['question_text']; ?>
                            </h3>

                            <div class="space-y-3">
                                <?php for ($i = 0; $i < count($answer_ids); $i++): ?>
                                    <label class="flex items-center gap-3 p-3 rounded-lg border border-neutral dark:border-dark-neutral-border hover:bg-gray-50 dark:hover:bg-gray-dark-50 cursor-pointer transition-colors">
                                        <input type="radio" name="question_<?php echo $question['id']; ?>"
                                            value="<?php echo $answer_ids[$i]; ?>"
                                            class="radio radio-primary" required>
                                        <span class="text-gray-1100 dark:text-gray-dark-1100">
                                            <?php echo $answer_texts[$i]; ?>
                                        </span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php $question_num++; ?>
                    <?php endwhile; ?>
                </div>

                <!-- Кнопка отправки -->
                <div class="mt-6 md:mt-12 text-center">
                    <button type="submit" class="btn bg-color-brands text-white px-4 md:px-8 py-3 text-base md:text-lg hover:bg-color-brands/90 transition-colors w-full md:w-auto mobile-btn">
                        Завершить тест и получить результаты
                    </button>
                </div>
            </form>

            <script>
                // Валидация формы
                document.getElementById('testForm').addEventListener('submit', function(e) {
                    const questionBlocks = document.querySelectorAll('.question-block');
                    let allAnswered = true;

                    questionBlocks.forEach(block => {
                        const radios = block.querySelectorAll('input[type="radio"]');
                        const answered = Array.from(radios).some(radio => radio.checked);
                        if (!answered) {
                            allAnswered = false;
                            block.style.borderColor = 'red';
                        } else {
                            block.style.borderColor = '';
                        }
                    });

                    if (!allAnswered) {
                        e.preventDefault();
                        alert('Пожалуйста, ответьте на все вопросы перед завершением теста.');
                    }
                });
            </script>
        <?php endif; ?>
    </div>
</main>