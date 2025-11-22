<div class="max-w-4xl mx-auto">
    <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
        <form method="POST" class="space-y-6">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="update_plan" value="1">
            <?php else: ?>
                <input type="hidden" name="create_plan" value="1">
            <?php endif; ?>
            
            <!-- Основная информация -->
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-dark-500 mb-2">
                        Название плана *
                    </label>
                    <input type="text" name="title" 
                           value="<?php echo htmlspecialchars($plan['title'] ?? ''); ?>"
                           class="w-full px-4 py-3 rounded-xl border border-neutral dark:border-dark-neutral-border bg-white dark:bg-dark-neutral-bg focus:outline-none focus:ring-2 focus:ring-color-brands"
                           required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-dark-500 mb-2">
                        Описание
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-3 rounded-xl border border-neutral dark:border-dark-neutral-border bg-white dark:bg-dark-neutral-bg focus:outline-none focus:ring-2 focus:ring-color-brands"><?php echo htmlspecialchars($plan['description'] ?? ''); ?></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-dark-500 mb-2">
                        Цели и задачи
                    </label>
                    <textarea name="goals" rows="4"
                              class="w-full px-4 py-3 rounded-xl border border-neutral dark:border-dark-neutral-border bg-white dark:bg-dark-neutral-bg focus:outline-none focus:ring-2 focus:ring-color-brands"><?php echo htmlspecialchars($plan['goals'] ?? ''); ?></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-dark-500 mb-2">
                            Дата завершения
                        </label>
                        <input type="date" name="deadline" 
                               value="<?php echo $plan['deadline'] ?? ''; ?>"
                               class="w-full px-4 py-3 rounded-xl border border-neutral dark:border-dark-neutral-border bg-white dark:bg-dark-neutral-bg focus:outline-none focus:ring-2 focus:ring-color-brands">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-dark-500 mb-2">
                            Связанные профессии
                        </label>
                        <select name="profession_ids[]" multiple
                                class="w-full px-4 py-3 rounded-xl border border-neutral dark:border-dark-neutral-border bg-white dark:bg-dark-neutral-bg focus:outline-none focus:ring-2 focus:ring-color-brands h-32">
                            <?php foreach ($professions as $profession): ?>
                                <option value="<?php echo $profession['id']; ?>"
                                    <?php if (isset($plan['professions'])): ?>
                                        <?php foreach ($plan['professions'] as $plan_prof): ?>
                                            <?php if ($plan_prof['id'] == $profession['id']) echo 'selected'; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>>
                                    <?php echo htmlspecialchars($profession['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Для выбора нескольких профессий удерживайте Ctrl</p>
                    </div>
                </div>
            </div>
            
            <!-- Кнопки -->
            <div class="flex items-center justify-between pt-6 border-t border-neutral dark:border-dark-neutral-border">
                <a href="index.php?page=plan" class="btn border border-neutral text-gray-500 dark:border-dark-neutral-border dark:text-gray-dark-500 px-6">
                    Отмена
                </a>
                <button type="submit" class="btn bg-color-brands text-white px-8">
                    <?php echo $action === 'edit' ? 'Обновить план' : 'Создать план'; ?>
                </button>
            </div>
        </form>
    </div>
</div>