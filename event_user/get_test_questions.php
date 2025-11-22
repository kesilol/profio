<?php
session_start();
require('../connect.php');

// Ничего не кэшируем
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    die('Access denied');
}

if (!isset($_GET['id'])) {
    die('ID теста не указан');
}

$test_id = intval($_GET['id']);

// Получаем данные теста
$test_stmt = $link->prepare("SELECT * FROM tests WHERE id = ?");
$test_stmt->bind_param("i", $test_id);
$test_stmt->execute();
$test = $test_stmt->get_result()->fetch_assoc();

if (!$test) {
    die('Тест не найден');
}

// Получаем вопросы теста
$questions_stmt = $link->prepare("SELECT * FROM questions WHERE test_id = ? ORDER BY question_order ASC");
$questions_stmt->bind_param("i", $test_id);
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();
$questions = [];

while ($row = $questions_result->fetch_assoc()) {
    $questions[] = $row;
}

// Для каждого вопроса получаем ответы
foreach ($questions as &$question) {
    $answers_stmt = $link->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY answer_order ASC");
    $answers_stmt->bind_param("i", $question['id']);
    $answers_stmt->execute();
    $answers_result = $answers_stmt->get_result();
    $question['answers'] = [];
    while ($answer = $answers_result->fetch_assoc()) {
        $question['answers'][] = $answer;
    }
}
?>

<div class="test-questions-management" id="questionsContainer">
    <!-- Простая форма добавления вопроса -->
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <h4 style="margin-bottom: 15px;">Добавить вопрос</h4>

        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Текст вопроса:</label>
            <textarea id="newQuestionText" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; min-height: 80px;" placeholder="Введите текст вопроса"></textarea>
        </div>
        <button type="button" class="add-question-btn"
            onclick="addQuestion(<?= $test_id ?>)"
            style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">
            Добавить вопрос
        </button>
    </div>

    <!-- Форма добавления ответа к выбранному вопросу -->
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <h4 style="margin-bottom: 15px;">Добавить ответ к вопросу</h4>

        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; margin-bottom: 10px;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Вопрос:</label>
                <select id="questionSelect" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Выберите вопрос</option>
                    <?php foreach ($questions as $question): ?>
                        <option value="<?= $question['id'] ?>"><?= htmlspecialchars(mb_substr($question['question_text'], 0, 50)) ?>...</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Текст ответа:</label>
                <input type="text" id="newAnswerText" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Текст ответа">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Баллы:</label>
                <input type="number" id="newAnswerScore" value="0" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
        </div>
        <button type="button" class="add-answer-btn"
            onclick="addAnswer(<?= $test_id ?>)"
            style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">
            Добавить ответ
        </button>
    </div>

    <!-- Список существующих вопросов -->
    <div id="questionsList">
        <h4 style="margin-bottom: 15px;">Вопросы теста (<span id="questionsCount"><?= count($questions) ?></span>)</h4>

        <?php if (count($questions) > 0): ?>
            <div id="questionsItems">
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question-item" style="border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; padding: 15px; background: white;" id="question-<?= $question['id'] ?>">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                            <div style="flex-grow: 1;">
                                <h5 style="margin: 0 0 8px 0; font-weight: 600; color: #2d3748;">
                                    Вопрос <?= $index + 1 ?>: <?= htmlspecialchars($question['question_text']) ?>
                                </h5>
                                <small style="color: #666;">ID: <?= $question['id'] ?> | Тип: <?= $question['question_type'] ?></small>
                            </div>
                            <div>
                                <a href="event_user/test_questions_handler.php?delete_question=<?= $question['id'] ?>&test_id=<?= $test_id ?>"
                                    onclick="return confirm('Удалить вопрос и все ответы?')"
                                    style="color: #dc3545; text-decoration: none; padding: 5px 10px; background: #f8f9fa; border-radius: 3px;">
                                    Удалить
                                </a>
                            </div>
                        </div>
                        <!-- Список ответов -->
                        <div>
                            <h6 style="margin: 0 0 10px 0; color: #4a5568;">Ответы:</h6>
                            <?php if (count($question['answers']) > 0): ?>
                                <div style="display: grid; gap: 8px;">
                                    <?php foreach ($question['answers'] as $answer): ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #f8f9fa; border-radius: 4px;">
                                            <div style="flex-grow: 1;">
                                                <?= htmlspecialchars($answer['answer_text']) ?>
                                            </div>
                                            <div style="color: #666; margin-right: 15px;">
                                                Баллы: <?= $answer['score_value'] ?>
                                            </div>
                                            <div>
                                                <a href="event_user/test_questions_handler.php?delete_answer=<?= $answer['id'] ?>&test_id=<?= $test_id ?>"
                                                    onclick="return confirm('Удалить ответ?')"
                                                    style="color: #dc3545; text-decoration: none;">
                                                    Удалить
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p style="color: #6c757d; font-style: italic; padding: 10px;">Нет ответов</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #6c757d; background: #f8f9fa; border-radius: 5px;">
                <p>В этом тесте пока нет вопросов</p>
                <p><small>Добавьте первый вопрос используя форму выше</small></p>
            </div>
        <?php endif; ?>
    </div>
</div>