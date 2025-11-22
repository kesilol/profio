<?php
// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    header("Location: index.php");
    exit();
}

// Получаем список тестов
$tests = $link->query("
    SELECT t.*, tt.name as type_name, 
           (SELECT COUNT(*) FROM questions q WHERE q.test_id = t.id) as questions_count
    FROM tests t
    LEFT JOIN test_types tt ON t.test_type_id = tt.id
    ORDER BY t.created_at DESC
");
// Получаем типы тестов
$test_types = $link->query("SELECT * FROM test_types");
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <div class="flex justify-between items-center mb-6">
            <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100">
                Управление тестами
            </h2>
            <a href="index.php?page=admin-dashboard" class="btn bg-gray-500 text-white px-4 py-2 rounded-lg">
                <i class="bi bi-arrow-left"></i> Назад
            </a>
        </div>

        <!-- Статистика тестов -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="stat-card text-center">
                <div class="stat-icon bg-blue-100 text-blue-600 mx-auto">
                    <i class="bi bi-clipboard-data text-xl"></i>
                </div>
                <div class="stat-number"><?= $tests->num_rows ?></div>
                <div class="stat-label">Всего тестов</div>
            </div>

            <?php
            $total_questions = $link->query("SELECT COUNT(*) as count FROM questions")->fetch_assoc()['count'];
            $total_results = $link->query("SELECT COUNT(*) as count FROM test_results")->fetch_assoc()['count'];
            ?>

            <div class="stat-card text-center">
                <div class="stat-icon bg-green-100 text-green-600 mx-auto">
                    <i class="bi bi-question-circle text-xl"></i>
                </div>
                <div class="stat-number"><?= $total_questions ?></div>
                <div class="stat-label">Всего вопросов</div>
            </div>

            <div class="stat-card text-center">
                <div class="stat-icon bg-purple-100 text-purple-600 mx-auto">
                    <i class="bi bi-check-circle text-xl"></i>
                </div>
                <div class="stat-number"><?= $total_results ?></div>
                <div class="stat-label">Пройдено тестов</div>
            </div>
        </div>

        <!-- Таблица тестов -->
        <div class="stat-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">Список тестов</h3>
                <button onclick="openAddTestModal()" class="btn bg-color-brands text-white px-4 py-2 rounded-lg">
                    <i class="bi bi-plus-circle"></i> Добавить тест
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-neutral dark:border-dark-neutral-border">
                            <th class="text-left p-3">Название теста</th>
                            <th class="text-left p-3">Тип</th>
                            <th class="text-left p-3">Вопросы</th>
                            <th class="text-left p-3">Дата создания</th>
                            <th class="text-left p-3">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($test = $tests->fetch_assoc()): ?>
                            <tr class="border-b border-neutral dark:border-dark-neutral-border" data-test-id="<?= $test['id'] ?>">
                                <td class="p-3">
                                    <div>
                                        <p class="font-medium"><?= htmlspecialchars($test['title']) ?></p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($test['description']) ?></p>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <span class="badge badge-primary"><?= $test['type_name'] ?? 'Пользовательский' ?></span>
                                </td>
                                <td class="p-3">
                                    <span class="font-semibold question-count"><?= $test['questions_count'] ?></span>
                                </td>
                                <td class="p-3">
                                    <?= date('d.m.Y', strtotime($test['created_at'])) ?>
                                </td>
                                <td class="p-3">
                                    <div class="flex gap-2">
                                        <a href="index.php?page=test&id=<?= $test['id'] ?>"
                                            class="btn bg-green-500 text-white px-3 py-1 rounded text-sm">
                                            <i class="bi bi-eye"></i> Просмотр
                                        </a>
                                        <button onclick="manageTestQuestions(<?= $test['id'] ?>, '<?= htmlspecialchars(addslashes($test['title'])) ?>')"
                                            class="btn bg-purple-500 text-white px-3 py-1 rounded text-sm">
                                            <i class="bi bi-question-circle"></i> Вопросы
                                        </button>
                                        <button onclick="editTest(<?= $test['id'] ?>)"
                                            class="btn bg-blue-500 text-white px-3 py-1 rounded text-sm">
                                            <i class="bi bi-pencil"></i> Редактировать
                                        </button>
                                        <a href="event_user/admin_tests_handler.php?delete_test=<?= $test['id'] ?>"
                                            class="btn bg-red-500 text-white px-3 py-1 rounded text-sm"
                                            onclick="return confirm('Удалить тест <?= htmlspecialchars(addslashes($test['title'])) ?>?')">
                                            <i class="bi bi-trash"></i> Удалить
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Модальное окно теста -->
<div id="testModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 15px; border-radius: 8px; width: 95%; max-width: 400px; margin: 20px auto; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="font-size: 1.1rem; font-weight: 600;" id="testModalTitle">Добавить тест</h3>
            <button onclick="closeModal('testModal')" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; padding: 5px;">×</button>
        </div>

        <form method="POST" action="event_user/admin_tests_handler.php" id="testForm">
            <input type="hidden" name="test_id" id="testId">

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Название теста:</label>
                <input type="text" name="title" id="testTitle" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" required>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Описание:</label>
                <textarea name="description" id="testDescription" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; min-height: 80px; font-size: 0.9rem;" required></textarea>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Тип теста:</label>
                <select name="test_type_id" id="testType" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" required>
                    <?php while ($type = $test_types->fetch_assoc()): ?>
                        <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 15px;">
                <button type="button" onclick="closeModal('testModal')" style="padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">Отмена</button>
                <button type="submit" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;" id="testSubmitBtn">Добавить</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно управления вопросами теста -->
<div id="testQuestionsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 15px; border-radius: 8px; width: 95%; max-width: 900px; margin: 20px auto; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="font-size: 1.1rem; font-weight: 600;" id="questionsModalTitle">Управление вопросами теста</h3>
            <button onclick="closeModal('testQuestionsModal')" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; padding: 5px;">×</button>
        </div>

        <div id="questionsContent">
            <!-- Контент будет загружаться через AJAX -->
        </div>
    </div>
</div>

<script>
    // Глобальные переменные
    let currentTestId = null;
    let questionsData = [];

    // Управление вопросами теста
function manageTestQuestions(testId, testTitle) {
    currentTestId = testId;
    document.getElementById('questionsModalTitle').textContent = 'Управление вопросами: ' + testTitle;
    
    // Очищаем предыдущие данные
    questionsData = [];
    
    // Загружаем контент через AJAX
    loadQuestionsContent(testId);
}

// Загрузка содержимого вопросов
function loadQuestionsContent(testId) {
    const timestamp = new Date().getTime();
    fetch(`event_user/get_test_questions.php?id=${testId}&t=${timestamp}&nocache=${Math.random()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            document.getElementById('questionsContent').innerHTML = html;
            document.getElementById('testQuestionsModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Загружаем актуальные данные вопросов
            refreshQuestionsData(testId);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка загрузки данных вопросов');
        });
}

    // Обновление данных вопросов
function refreshQuestionsData(testId) {
    fetch(`event_user/test_questions_handler.php?get_questions=1&test_id=${testId}&t=${new Date().getTime()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                questionsData = data.questions;
                updateQuestionsUI();
                updateQuestionSelect(); // ОБНОВЛЯЕМ ВЫПАДАЮЩИЙ СПИСОК
            }
        })
        .catch(error => {
            console.error('Error refreshing questions:', error);
        });
}
// Обновление выпадающего списка вопросов
function updateQuestionSelect() {
    const questionSelect = document.getElementById('questionSelect');
    if (!questionSelect) return;
    
    // Сохраняем текущее выбранное значение
    const currentValue = questionSelect.value;
    
    // Очищаем список
    questionSelect.innerHTML = '<option value="">Выберите вопрос</option>';
    
    // Добавляем все вопросы
    questionsData.forEach(question => {
        const option = document.createElement('option');
        option.value = question.id;
        option.textContent = question.question_text.length > 50 
            ? question.question_text.substring(0, 50) + '...' 
            : question.question_text;
        questionSelect.appendChild(option);
    });
    
    // Восстанавливаем выбранное значение, если оно еще существует
    if (currentValue && questionsData.some(q => q.id == currentValue)) {
        questionSelect.value = currentValue;
    }
}

    // Обновление UI вопросов
function updateQuestionsUI() {
    const questionsList = document.getElementById('questionsList');
    if (!questionsList) return;
    
    const questionsCount = document.getElementById('questionsCount');
    const questionsItems = document.getElementById('questionsItems');
    
    if (questionsCount) {
        questionsCount.textContent = questionsData.length;
    }
    
    if (questionsData.length > 0) {
        let questionsHTML = '';
        questionsData.forEach((question, index) => {
            questionsHTML += `
                <div class="question-item" style="border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; padding: 15px; background: white;" id="question-${question.id}">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <div style="flex-grow: 1;">
                            <h5 style="margin: 0 0 8px 0; font-weight: 600; color: #2d3748;">
                                Вопрос ${index + 1}: ${escapeHtml(question.question_text)}
                            </h5>
                            <small style="color: #666;">ID: ${question.id} | Тип: ${question.question_type}</small>
                        </div>
                        <div>
                            <a href="event_user/test_questions_handler.php?delete_question=${question.id}&test_id=${currentTestId}"
                               onclick="return confirm('Удалить вопрос и все ответы?')"
                               style="color: #dc3545; text-decoration: none; padding: 5px 10px; background: #f8f9fa; border-radius: 3px;">
                                Удалить
                            </a>
                        </div>
                    </div>
                    <div>
                        <h6 style="margin: 0 0 10px 0; color: #4a5568;">Ответы:</h6>
                        ${question.answers && question.answers.length > 0 ? 
                            `<div style="display: grid; gap: 8px;">
                                ${question.answers.map(answer => `
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #f8f9fa; border-radius: 4px;">
                                        <div style="flex-grow: 1;">
                                            ${escapeHtml(answer.answer_text)}
                                        </div>
                                        <div style="color: #666; margin-right: 15px;">
                                            Баллы: ${answer.score_value}
                                        </div>
                                        <div>
                                            <a href="event_user/test_questions_handler.php?delete_answer=${answer.id}&test_id=${currentTestId}"
                                               onclick="return confirm('Удалить ответ?')"
                                               style="color: #dc3545; text-decoration: none;">
                                                Удалить
                                            </a>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>` : 
                            `<p style="color: #6c757d; font-style: italic; padding: 10px;">Нет ответов</p>`
                        }
                    </div>
                </div>
            `;
        });
        
        if (questionsItems) {
            questionsItems.innerHTML = questionsHTML;
        } else {
            // Если контейнер не существует, создаем его
            questionsList.innerHTML = `
                <h4 style="margin-bottom: 15px;">Вопросы теста (<span id="questionsCount">${questionsData.length}</span>)</h4>
                <div id="questionsItems">
                    ${questionsHTML}
                </div>
            `;
        }
    } else {
        questionsList.innerHTML = `
            <h4 style="margin-bottom: 15px;">Вопросы теста (<span id="questionsCount">0</span>)</h4>
            <div style="text-align: center; padding: 40px; color: #6c757d; background: #f8f9fa; border-radius: 5px;">
                <p>В этом тесте пока нет вопросов</p>
                <p><small>Добавьте первый вопрос используя форму выше</small></p>
            </div>
        `;
    }
}

    // Экранирование HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Добавление нового вопроса
function addQuestion(testId) {
    const questionText = document.getElementById('newQuestionText');
    if (!questionText) return;
    
    const text = questionText.value.trim();
    
    if (!text) {
        alert('Введите текст вопроса');
        return;
    }

    const formData = new FormData();
    formData.append('add_question', '1');
    formData.append('test_id', testId);
    formData.append('question_text', text);
    formData.append('question_type', 'одиночный');

    // Показываем индикатор загрузки
    const addBtn = document.querySelector('.add-question-btn');
    const originalText = addBtn.textContent;
    addBtn.textContent = 'Добавление...';
    addBtn.disabled = true;

    fetch('event_user/test_questions_handler.php', {
        method: 'POST',
        body: formData,
        headers: {
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Восстанавливаем кнопку
        addBtn.textContent = originalText;
        addBtn.disabled = false;

        if (data.success) {
            // Очищаем форму
            questionText.value = '';
            
            // Обновляем данные и UI внутри модального окна
            refreshQuestionsData(testId);
            
            alert('Вопрос успешно добавлен');
        } else {
            alert(data.error || 'Ошибка при добавлении вопроса');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        addBtn.textContent = originalText;
        addBtn.disabled = false;
        alert('Ошибка при добавлении вопроса');
    });
}

    // Добавление ответа к вопросу
    function addAnswer(testId) {
        const questionSelect = document.getElementById('questionSelect');
        const answerText = document.getElementById('newAnswerText');
        const scoreValue = document.getElementById('newAnswerScore');

        if (!questionSelect || !questionSelect.value) {
            alert('Выберите вопрос');
            return;
        }

        if (!answerText || !answerText.value.trim()) {
            alert('Введите текст ответа');
            return;
        }

        const formData = new FormData();
        formData.append('add_answer', '1');
        formData.append('question_id', questionSelect.value);
        formData.append('answer_text', answerText.value.trim());
        formData.append('score_value', scoreValue ? scoreValue.value : 0);

        // Показываем индикатор загрузки
        const addBtn = document.querySelector('.add-answer-btn');
        const originalText = addBtn.textContent;
        addBtn.textContent = 'Добавление...';
        addBtn.disabled = true;

        fetch('event_user/test_questions_handler.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'Cache-Control': 'no-cache'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Восстанавливаем кнопку
                addBtn.textContent = originalText;
                addBtn.disabled = false;

                if (data.success) {
                    // Очищаем форму
                    if (answerText) answerText.value = '';
                    if (scoreValue) scoreValue.value = '0';

                    // Обновляем данные и UI
                    refreshQuestionsData(testId);
                    alert('Ответ успешно добавлен');
                } else {
                    alert(data.error || 'Ошибка при добавлении ответа');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                addBtn.textContent = originalText;
                addBtn.disabled = false;
                alert('Ошибка при добавлении ответа');
            });
    }

    // Остальные функции остаются без изменений
    function openAddTestModal() {
        document.getElementById('testModalTitle').textContent = 'Добавить тест';
        document.getElementById('testSubmitBtn').textContent = 'Добавить';
        document.getElementById('testId').value = '';
        document.getElementById('testForm').reset();
        document.getElementById('testModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function editTest(testId) {
        fetch(`event_user/get_test_data.php?id=${testId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('testModalTitle').textContent = 'Редактировать тест';
                    document.getElementById('testSubmitBtn').textContent = 'Сохранить';
                    document.getElementById('testId').value = data.id;
                    document.getElementById('testTitle').value = data.title;
                    document.getElementById('testDescription').value = data.description;
                    document.getElementById('testType').value = data.test_type_id;
                    document.getElementById('testModal').style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка загрузки данных теста');
            });
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Закрытие модальных окон
    document.getElementById('testModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeModal('testModal');
    });

    document.getElementById('testQuestionsModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeModal('testQuestionsModal');
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (document.getElementById('testModal')?.style.display === 'flex') closeModal('testModal');
            if (document.getElementById('testQuestionsModal')?.style.display === 'flex') closeModal('testQuestionsModal');
        }
    });
    // Обновление данных в таблице тестов
    function updateTestsTable() {
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }

    // Альтернативный вариант - AJAX обновление только счетчика
    function updateTestQuestionCount(testId) {
        fetch(`event_user/get_test_stats.php?test_id=${testId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Находим ячейку с количеством вопросов для этого теста
                    const questionCountCells = document.querySelectorAll(`[data-test-id="${testId}"] .question-count`);
                    questionCountCells.forEach(cell => {
                        cell.textContent = data.questions_count;
                    });
                }
            })
            .catch(error => {
                console.error('Error updating question count:', error);
            });
    }
</script>