<?php
session_start();
require('../connect.php');
require_once(__DIR__ . '/admin_logs_handler.php');

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if ($_SESSION['user']['role'] !== 'администратор') {
    die('Access denied');
}

if (!isset($_GET['id'])) {
    die('ID теста не указан');
}

$test_id = intval($_GET['id']);

$test_stmt = $link->prepare("SELECT * FROM tests WHERE id = ?");
$test_stmt->bind_param("i", $test_id);
$test_stmt->execute();
$test = $test_stmt->get_result()->fetch_assoc();

if (!$test) {
    die('Тест не найден');
}

// Определяем тип теста
$is_mbti_test = ($test['test_type_id'] == 4); // 4 - это ID типа mbti из test_types

$questions_stmt = $link->prepare("SELECT * FROM questions WHERE test_id = ? ORDER BY question_order ASC");
$questions_stmt->bind_param("i", $test_id);
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();
$questions = [];

while ($row = $questions_result->fetch_assoc()) {
    $answers_stmt = $link->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY answer_order ASC");
    $answers_stmt->bind_param("i", $row['id']);
    $answers_stmt->execute();
    $answers_result = $answers_stmt->get_result();
    $row['answers'] = [];
    while ($answer = $answers_result->fetch_assoc()) {
        $row['answers'][] = $answer;
    }
    $questions[] = $row;
}
?>

<div class="test-questions-management">
    <style>
        .test-questions-management .btn-edit { background: #3b82f6; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin-right: 5px; }
        .test-questions-management .btn-delete { color: #dc3545; text-decoration: none; padding: 5px 10px; background: #f8f9fa; border-radius: 3px; }
        .test-questions-management .btn-delete:hover { background: #dc3545; color: white; }
        .test-questions-management .btn-edit:hover { background: #2563eb; }
        .test-questions-management .question-item { border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; padding: 15px; background: white; }
        .dark .test-questions-management .question-item { background: #1f2937; border-color: #374151; }
        .test-questions-management .answer-item { display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #f8f9fa; border-radius: 4px; margin-bottom: 5px; }
        .dark .test-questions-management .answer-item { background: #374151; }
    </style>

    <div id="questionsList">
        <h4 style="margin-bottom: 15px;">Вопросы теста "<?= htmlspecialchars($test['title']) ?>" (<span id="questionsCount"><?= count($questions) ?></span>)</h4>

        <?php if (count($questions) > 0): ?>
            <div id="questionsItems">
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question-item" id="question-<?= $question['id'] ?>">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                            <div style="flex-grow: 1;">
                                <h5 style="margin: 0 0 8px 0; font-weight: 600; color: #2d3748;">
                                    Вопрос <?= $index + 1 ?>: <?= htmlspecialchars($question['question_text']) ?>
                                </h5>
                                <small style="color: #666;">ID: <?= $question['id'] ?> | Тип: <?= $question['question_type'] ?></small>
                                <?php if ($is_mbti_test): ?>
                                    <br><small style="color: #4f46e5;">Вес MBTI: <?= $question['mbti_weight'] ?? '0.8' ?></small>
                                <?php endif; ?>
                            </div>
                            <div>
                                <button onclick="window.editQuestion(<?= $question['id'] ?>, '<?= htmlspecialchars(addslashes($question['question_text'])) ?>', <?= $is_mbti_test ? 'true' : 'false' ?>, <?= $question['mbti_weight'] ?? '0.8' ?>)"
                                    class="btn-edit">
                                    ✏️ Редактировать
                                </button>
                                <a href="event_user/test_questions_handler.php?delete_question=<?= $question['id'] ?>&test_id=<?= $test_id ?>"
                                    onclick="return confirm('Удалить вопрос и все ответы? Это действие нельзя отменить.')"
                                    class="btn-delete">
                                    🗑️ Удалить
                                </a>
                            </div>
                        </div>
                        <div>
                            <h6 style="margin: 0 0 10px 0; color: #4a5568;">Ответы:</h6>
                            <?php if (count($question['answers']) > 0): ?>
                                <div style="display: grid; gap: 8px;">
                                    <?php foreach ($question['answers'] as $answer): ?>
                                        <div class="answer-item">
                                            <div style="flex-grow: 1;">
                                                <?= htmlspecialchars($answer['answer_text']) ?>
                                                <div style="color: #666; font-size: 12px; margin-top: 4px;">
                                                    Баллы: <?= $answer['score_value'] ?>
                                                    <?php if ($is_mbti_test && $answer['mbti_dimension']): ?>
                                                        | Шкала MBTI: <?= $answer['mbti_dimension'] ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div>
                                                <button onclick="window.editAnswer(<?= $answer['id'] ?>, '<?= htmlspecialchars(addslashes($answer['answer_text'])) ?>', <?= $answer['score_value'] ?>, <?= $is_mbti_test ? 'true' : 'false' ?>, '<?= $answer['mbti_dimension'] ?? '' ?>')"
                                                    class="btn-edit">
                                                    ✏️
                                                </button>
                                                <a href="event_user/test_questions_handler.php?delete_answer=<?= $answer['id'] ?>&test_id=<?= $test_id ?>"
                                                    onclick="return confirm('Удалить ответ?')"
                                                    class="btn-delete"
                                                    style="margin-left: 5px;">
                                                    🗑️
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
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function editQuestion(questionId, questionText, isMbti = false, currentWeight = 0.8) {
    if (isMbti) {
        const newText = prompt('Редактировать вопрос:', questionText);
        if (newText && newText.trim() !== '') {
            const newWeight = prompt('Вес вопроса для MBTI (0.7, 0.8, 0.9):', currentWeight);
            if (newWeight !== null && !isNaN(parseFloat(newWeight)) && parseFloat(newWeight) >= 0.5 && parseFloat(newWeight) <= 1) {
                const formData = new FormData();
                formData.append('edit_question', '1');
                formData.append('question_id', questionId);
                formData.append('question_text', newText.trim());
                formData.append('question_type', 'одиночный');
                formData.append('mbti_weight', parseFloat(newWeight));
                
                fetch('../event_user/test_questions_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Вопрос успешно обновлен');
                        location.reload();
                    } else {
                        alert(data.error || 'Ошибка при обновлении вопроса');
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    alert('Ошибка при обновлении вопроса');
                });
            } else {
                alert('Введите корректный вес (от 0.5 до 1)');
            }
        }
    } else {
        const newText = prompt('Редактировать вопрос:', questionText);
        if (newText && newText.trim() !== '') {
            const formData = new FormData();
            formData.append('edit_question', '1');
            formData.append('question_id', questionId);
            formData.append('question_text', newText.trim());
            formData.append('question_type', 'одиночный');
            
            fetch('../event_user/test_questions_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Вопрос успешно обновлен');
                    location.reload();
                } else {
                    alert(data.error || 'Ошибка при обновлении вопроса');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Ошибка при обновлении вопроса');
            });
        }
    }
}

function editAnswer(answerId, answerText, scoreValue, isMbti = false, currentDimension = '') {
    const newText = prompt('Редактировать ответ:', answerText);
    if (newText && newText.trim() !== '') {
        const newScore = prompt('Баллы за ответ (число):', scoreValue);
        if (newScore !== null && !isNaN(parseInt(newScore))) {
            const formData = new FormData();
            formData.append('edit_answer', '1');
            formData.append('answer_id', answerId);
            formData.append('answer_text', newText.trim());
            formData.append('score_value', parseInt(newScore));
            
            if (isMbti) {
                const dimensions = ['IE', 'SN', 'TF', 'JP'];
                const dimensionOptions = dimensions.map(d => d === currentDimension ? `${d} (текущий)` : d).join(', ');
                const newDimension = prompt(`Шкала MBTI для этого ответа (${dimensionOptions}):`, currentDimension);
                if (newDimension && dimensions.includes(newDimension.toUpperCase())) {
                    formData.append('mbti_dimension', newDimension.toUpperCase());
                } else if (newDimension !== null && newDimension !== '') {
                    alert('Неверная шкала. Допустимые: IE, SN, TF, JP');
                    return;
                }
            }
            
            fetch('../event_user/test_questions_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Ответ успешно обновлен');
                    location.reload();
                } else {
                    alert(data.error || 'Ошибка при обновлении ответа');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Ошибка при обновлении ответа');
            });
        } else {
            alert('Введите корректное количество баллов');
        }
    }
}
</script>