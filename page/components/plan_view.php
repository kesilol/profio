<div class="max-w-6xl mx-auto">
    <!-- Заголовок и действия -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-2xl font-bold text-gray-1100 dark:text-gray-dark-1100">
                <?php echo htmlspecialchars($plan['title']); ?>
            </h3>
            <?php if ($plan['deadline']): ?>
                <p class="text-gray-500 dark:text-gray-dark-500 mt-1">
                    До <?php echo date('d.m.Y', strtotime($plan['deadline'])); ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-3">
            <a href="index.php?page=plan&action=edit&id=<?php echo $plan['id']; ?>" 
               class="btn border border-neutral text-gray-500 dark:border-dark-neutral-border dark:text-gray-dark-500 px-4">
                <i class="bi bi-pencil mr-2"></i>Редактировать
            </a>
            <a href="index.php?page=plan" 
               class="btn bg-gray-100 text-gray-500 dark:bg-gray-dark-100 dark:text-gray-dark-500 px-4">
                <i class="bi bi-arrow-left mr-2"></i>Назад
            </a>
        </div>
    </div>
    
    <!-- Прогресс -->
    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100">Общий прогресс</h4>
            <span class="text-2xl font-bold text-color-brands"><?php echo $plan['progress_percentage']; ?>%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-dark-200">
            <div class="bg-color-brands h-4 rounded-full transition-all duration-500" 
                 style="width: <?php echo $plan['progress_percentage']; ?>%"></div>
        </div>
        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-dark-500 mt-2">
            <span>Выполнено <?php echo $plan['completed_tasks']; ?> из <?php echo $plan['total_tasks']; ?> задач</span>
            <span><?php echo $plan['completed_tasks']; ?> задач</span>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Основная информация -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Описание -->
            <?php if ($plan['description']): ?>
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <h4 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">Описание</h4>
                    <p class="text-gray-500 dark:text-gray-dark-500"><?php echo nl2br(htmlspecialchars($plan['description'])); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Цели -->
            <?php if ($plan['goals']): ?>
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <h4 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">Цели и задачи</h4>
                    <p class="text-gray-500 dark:text-gray-dark-500"><?php echo nl2br(htmlspecialchars($plan['goals'])); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Задачи -->
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h4 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100">Задачи плана</h4>
                    <button type="button" onclick="showAddTaskModal()" 
                            class="btn bg-color-brands text-white px-4 py-2">
                        <i class="bi bi-plus-lg mr-2"></i>Добавить задачу
                    </button>
                </div>
                
                <?php if (!empty($plan['tasks'])): ?>
                    <div class="space-y-4">
                        <?php foreach ($plan['tasks'] as $task): ?>
                            <div class="flex items-center gap-4 p-4 rounded-xl border border-neutral dark:border-dark-neutral-border">
                                <form method="POST" class="flex items-center gap-4 flex-1">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <input type="hidden" name="update_task_status" value="1">
                                    <input type="checkbox" name="is_completed" value="1" 
                                           <?php echo $task['is_completed'] ? 'checked' : ''; ?>
                                           onchange="this.form.submit()"
                                           class="w-5 h-5 text-color-brands rounded focus:ring-color-brands">
                                    
                                    <div class="flex-1">
                                        <p class="<?php echo $task['is_completed'] ? 'line-through text-gray-400' : 'text-gray-1100 dark:text-gray-dark-1100'; ?>">
                                            <?php echo htmlspecialchars($task['task_text']); ?>
                                        </p>
                                        <?php if ($task['deadline']): ?>
                                            <p class="text-sm text-gray-500 dark:text-gray-dark-500">
                                                До <?php echo date('d.m.Y', strtotime($task['deadline'])); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </form>
                                
                                <form method="POST">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <input type="hidden" name="delete_task" value="1">
                                    <button type="submit" 
                                            onclick="return confirm('Удалить эту задачу?')"
                                            class="text-red-500 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="bi bi-clipboard-check text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500 dark:text-gray-dark-500">Задачи пока не добавлены</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Боковая панель -->
        <div class="space-y-6">
            <!-- Связанные профессии -->
            <?php if (!empty($plan['professions'])): ?>
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                    <h4 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">Связанные профессии</h4>
                    <div class="space-y-3">
                        <?php foreach ($plan['professions'] as $profession): ?>
                            <a href="index.php?page=profession-detail&id=<?php echo $profession['id']; ?>"
                               class="flex items-center gap-3 p-3 rounded-xl border border-neutral dark:border-dark-neutral-border hover:bg-gray-50 dark:hover:bg-gray-dark-50 transition-colors">
                                <div class="w-10 h-10 rounded-lg bg-color-brands/10 grid place-items-center">
                                    <i class="bi bi-briefcase text-color-brands"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-1100 dark:text-gray-dark-1100">
                                    <?php echo htmlspecialchars($profession['title']); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Статистика -->
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                <h4 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">Статистика</h4>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-dark-500">Всего задач:</span>
                        <span class="font-semibold"><?php echo $plan['total_tasks']; ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-dark-500">Выполнено:</span>
                        <span class="font-semibold text-green-500"><?php echo $plan['completed_tasks']; ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-dark-500">Осталось:</span>
                        <span class="font-semibold"><?php echo $plan['total_tasks'] - $plan['completed_tasks']; ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-dark-500">Создан:</span>
                        <span class="font-semibold"><?php echo date('d.m.Y', strtotime($plan['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно добавления задачи -->
<div id="addTaskModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white dark:bg-dark-neutral-bg rounded-2xl p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold mb-4">Добавить задачу</h3>
        <form method="POST" id="addTaskForm">
            <input type="hidden" name="add_task" value="1">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-dark-500 mb-2">
                        Описание задачи *
                    </label>
                    <textarea name="task_text" rows="3" required
                              class="w-full px-4 py-3 rounded-xl border border-neutral dark:border-dark-neutral-border bg-white dark:bg-dark-neutral-bg focus:outline-none focus:ring-2 focus:ring-color-brands"
                              placeholder="Опишите задачу..." id="taskTextInput"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-dark-500 mb-2">
                        Срок выполнения
                    </label>
                    <input type="date" name="task_deadline"
                           min="<?php echo date('Y-m-d'); ?>"
                           class="w-full px-4 py-3 rounded-xl border border-neutral dark:border-dark-neutral-border bg-white dark:bg-dark-neutral-bg focus:outline-none focus:ring-2 focus:ring-color-brands"
                           id="taskDeadlineInput">
                    <?php if ($plan['deadline']): ?>
                        <p class="text-xs text-gray-400 mt-1">
                            Дата не может быть позже даты завершения плана: <?php echo date('d.m.Y', strtotime($plan['deadline'])); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="hideAddTaskModal()" 
                        class="btn border border-neutral text-gray-500 dark:border-dark-neutral-border dark:text-gray-dark-500">
                    Отмена
                </button>
                <button type="submit" class="btn bg-color-brands text-white">
                    Добавить
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddTaskModal() {
    // Очищаем поля формы при открытии модального окна
    document.getElementById('taskTextInput').value = '';
    document.getElementById('taskDeadlineInput').value = '';
    document.getElementById('addTaskModal').classList.remove('hidden');
}

function hideAddTaskModal() {
    document.getElementById('addTaskModal').classList.add('hidden');
}

// Валидация даты задачи перед отправкой
document.getElementById('addTaskForm').addEventListener('submit', function(e) {
    const taskDeadline = document.getElementById('taskDeadlineInput').value;
    const planDeadline = '<?php echo $plan['deadline'] ?? ''; ?>';
    const today = new Date().toISOString().split('T')[0];
    
    // Проверка, что дата не в прошлом
    if (taskDeadline && taskDeadline < today) {
        e.preventDefault();
        alert('Срок выполнения задачи не может быть в прошлом!');
        return false;
    }
    
    // Проверка, что дата задачи не позже даты плана (если дата плана указана)
    if (taskDeadline && planDeadline && taskDeadline > planDeadline) {
        e.preventDefault();
        alert('Срок выполнения задачи не может быть позже даты завершения плана (' + 
              new Date(planDeadline).toLocaleDateString('ru-RU') + ')');
        return false;
    }
    
    hideAddTaskModal();
    return true;
});

// Закрытие модального окна при клике вне его
document.getElementById('addTaskModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideAddTaskModal();
    }
});
</script>