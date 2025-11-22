<?php if ($plans->num_rows > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <?php while ($plan = $plans->fetch_assoc()): ?>
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-normal font-semibold text-gray-1100 dark:text-gray-dark-1100">
                        <?php echo htmlspecialchars($plan['title']); ?>
                    </h3>
                    <div class="dropdown dropdown-end">
                        <label class="cursor-pointer dropdown-label" tabindex="0">
                            <img src="assets/images/icons/icon-toggle.svg" alt="Меню">
                        </label>
                        <ul class="dropdown-content" tabindex="0">
                            <div class="relative menu rounded-box dropdown-shadow p-3 bg-neutral-bg mt-2 min-w-[150px] dark:bg-dark-neutral-bg">
                                <li>
                                    <a href="index.php?page=plan&action=view&id=<?php echo $plan['id']; ?>"
                                       class="flex items-center gap-2 p-2 hover:bg-gray-100 dark:hover:bg-gray-dark-100 rounded-lg">
                                        <i class="bi bi-eye text-gray-500"></i>
                                        <span>Просмотр</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="index.php?page=plan&action=edit&id=<?php echo $plan['id']; ?>"
                                       class="flex items-center gap-2 p-2 hover:bg-gray-100 dark:hover:bg-gray-dark-100 rounded-lg">
                                        <i class="bi bi-pencil text-gray-500"></i>
                                        <span>Редактировать</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#" 
                                       onclick="return confirmDeletePlan('<?php echo addslashes(htmlspecialchars($plan['title'])); ?>', <?php echo $plan['id']; ?>)"
                                       class="flex items-center gap-2 p-2 hover:bg-gray-100 dark:hover:bg-gray-dark-100 rounded-lg text-red-500">
                                        <i class="bi bi-trash"></i>
                                        <span>Удалить</span>
                                    </a>
                                </li>
                            </div>
                        </ul>
                    </div>
                </div>
               
                <p class="text-gray-500 dark:text-gray-dark-500 text-sm mb-4 line-clamp-2">
                    <?php echo htmlspecialchars($plan['description']); ?>
                </p>
               
                <!-- Прогресс -->
                <div class="mb-4">
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span class="text-gray-500 dark:text-gray-dark-500">Прогресс</span>
                        <span class="font-semibold text-gray-1100 dark:text-gray-dark-1100">
                            <?php echo $plan['progress_percentage']; ?>%
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-dark-200">
                        <div class="bg-color-brands h-2 rounded-full"
                             style="width: <?php echo $plan['progress_percentage']; ?>%"></div>
                    </div>
                </div>
               
                <!-- Статистика -->
                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-dark-500 mb-4">
                    <span><?php echo $plan['completed_tasks']; ?>/<?php echo $plan['total_tasks']; ?> задач</span>
                    <?php if ($plan['deadline']): ?>
                        <span><?php echo date('d.m.Y', strtotime($plan['deadline'])); ?></span>
                    <?php endif; ?>
                </div>
               
                <a href="index.php?page=plan&action=view&id=<?php echo $plan['id']; ?>"
                   class="btn bg-color-brands text-white w-full text-center">
                    Открыть план
                </a>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Кнопка создания нового плана (только когда есть планы) -->
    <div class="text-center">
        <a href="index.php?page=plan&action=create" class="btn bg-color-brands text-white px-8">
            <i class="bi bi-plus-lg mr-2"></i>
            Создать новый план
        </a>
    </div>
<?php else: ?>
    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-12 text-center">
        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-dark-100 grid place-items-center mx-auto mb-4">
            <i class="bi bi-kanban text-2xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-2">
            Планы развития отсутствуют
        </h3>
        <p class="text-gray-500 dark:text-gray-dark-500 mb-6">
            Создайте свой первый план развития для достижения профессиональных целей
        </p>
        <a href="index.php?page=plan&action=create" class="btn bg-color-brands text-white px-6">
            Создать план
        </a>
    </div>
<?php endif; ?>

<script>
function confirmDeletePlan(planTitle, planId) {
    if (confirm('Вы уверены, что хотите удалить план \"' + planTitle + '\"?')) {
        // Создаем скрытую форму для отправки
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?page=plan';
        
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = 'delete_plan';
        deleteInput.value = '1';
        form.appendChild(deleteInput);
        
        const planIdInput = document.createElement('input');
        planIdInput.type = 'hidden';
        planIdInput.name = 'plan_id';
        planIdInput.value = planId;
        form.appendChild(planIdInput);
        
        document.body.appendChild(form);
        form.submit();
    }
    return false;
}
</script>