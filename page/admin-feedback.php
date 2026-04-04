<?php
// Проверка прав администратора
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'администратор') {
    header("Location: index.php");
    exit();
}

$message = '';
$message_type = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read'])) {
        $id = intval($_POST['id']);
        $link->query("UPDATE feedback SET status = 'read' WHERE id = $id");
        $message = "Сообщение отмечено как прочитанное";
        $message_type = "success";
    }
    
    if (isset($_POST['mark_replied'])) {
        $id = intval($_POST['id']);
        $admin_id = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'];
        $link->query("UPDATE feedback SET status = 'replied', responded_by = $admin_id, responded_at = NOW() WHERE id = $id");
        $message = "Сообщение отмечено как отвеченное";
        $message_type = "success";
    }
    
    if (isset($_POST['send_response'])) {
        $id = intval($_POST['id']);
        $response = $link->real_escape_string($_POST['response']);
        $admin_id = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'];
        $link->query("UPDATE feedback SET admin_response = '$response', status = 'replied', responded_by = $admin_id, responded_at = NOW() WHERE id = $id");
        $message = "Ответ отправлен пользователю";
        $message_type = "success";
    }
    
    if (isset($_POST['delete'])) {
        $id = intval($_POST['id']);
        $link->query("DELETE FROM feedback WHERE id = $id");
        $message = "Сообщение удалено";
        $message_type = "success";
    }
}

// Фильтрация
$status_filter = $_GET['status'] ?? 'all';
$status_where = '';
if ($status_filter !== 'all') {
    $status_where = "WHERE status = '$status_filter'";
}

// Получаем сообщения
$messages = $link->query("
    SELECT f.*, u.name as user_name, a.name as admin_name
    FROM feedback f
    LEFT JOIN users u ON f.user_id = u.id
    LEFT JOIN users a ON f.responded_by = a.id
    $status_where
    ORDER BY 
        CASE WHEN f.status = 'new' THEN 1 ELSE 2 END,
        f.created_at DESC
");

// Статистика
$stats = $link->query("
    SELECT 
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
        SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
        SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) as unregistered_count,
        COUNT(*) as total
    FROM feedback
")->fetch_assoc();
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <!-- Заголовок -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-2">
                    Обратная связь
                </h2>
                <p class="text-gray-500">Управление сообщениями от пользователей</p>
            </div>
            <div class="flex gap-3">
                <div class="bg-red-100 dark:bg-red-900 px-3 py-2 rounded-lg text-center">
                    <div class="text-sm text-red-600 dark:text-red-400">Новых</div>
                    <div class="text-xl font-bold"><?= $stats['new_count'] ?? 0 ?></div>
                </div>
                <div class="bg-yellow-100 dark:bg-yellow-900 px-3 py-2 rounded-lg text-center">
                    <div class="text-sm text-yellow-600 dark:text-yellow-400">Неавториз.</div>
                    <div class="text-xl font-bold"><?= $stats['unregistered_count'] ?? 0 ?></div>
                </div>
                <div class="bg-green-100 dark:bg-green-900 px-3 py-2 rounded-lg text-center">
                    <div class="text-sm text-green-600 dark:text-green-400">Отвечено</div>
                    <div class="text-xl font-bold"><?= $stats['replied_count'] ?? 0 ?></div>
                </div>
                <div class="bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-lg text-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Всего</div>
                    <div class="text-xl font-bold"><?= $stats['total'] ?? 0 ?></div>
                </div>
            </div>
        </div>

        <!-- Сообщение об успехе/ошибке -->
        <?php if ($message): ?>
            <div class="mb-4 p-3 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Фильтры -->
        <div class="flex gap-3 mb-6 flex-wrap">
            <a href="?page=admin-feedback&status=all" 
               class="px-4 py-2 rounded-lg <?= $status_filter === 'all' ? 'bg-color-brands text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300' ?>">
                Все
            </a>
            <a href="?page=admin-feedback&status=new" 
               class="px-4 py-2 rounded-lg <?= $status_filter === 'new' ? 'bg-color-brands text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300' ?>">
                Новые
                <?php if (($stats['new_count'] ?? 0) > 0): ?>
                    <span class="ml-1 px-1.5 py-0.5 text-xs bg-red-500 text-white rounded-full"><?= $stats['new_count'] ?></span>
                <?php endif; ?>
            </a>
            <a href="?page=admin-feedback&status=read" 
               class="px-4 py-2 rounded-lg <?= $status_filter === 'read' ? 'bg-color-brands text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300' ?>">
                Прочитанные
            </a>
            <a href="?page=admin-feedback&status=replied" 
               class="px-4 py-2 rounded-lg <?= $status_filter === 'replied' ? 'bg-color-brands text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300' ?>">
                Отвеченные
            </a>
        </div>

        <!-- Список сообщений -->
        <div class="space-y-4">
            <?php if ($messages && $messages->num_rows > 0): ?>
                <?php while($msg = $messages->fetch_assoc()): 
                    $is_unregistered = is_null($msg['user_id']);
                ?>
                    <div class="rounded-2xl border <?= $msg['status'] === 'new' ? 'border-red-300 bg-red-50 dark:bg-red-950/20' : 'border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg' ?> p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <div class="flex items-center gap-3 mb-2 flex-wrap">
                                    <h3 class="font-bold text-lg"><?= htmlspecialchars($msg['name']) ?></h3>
                                    
                                    <?php if ($msg['status'] === 'new'): ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-600">Новое</span>
                                    <?php elseif ($msg['status'] === 'replied'): ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-600">Отвечено</span>
                                    <?php elseif ($msg['status'] === 'read'): ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">Прочитано</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_unregistered): ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                            <i class="bi bi-person"></i> Неавторизованный
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                            <i class="bi bi-check-circle"></i> Авторизован
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="text-sm text-gray-500">
                                    <i class="bi bi-envelope mr-1"></i> <?= htmlspecialchars($msg['email']) ?>
                                    <?php if ($msg['user_name']): ?>
                                        <span class="mx-2">•</span>
                                        <i class="bi bi-person-circle mr-1"></i> <?= htmlspecialchars($msg['user_name']) ?>
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    <i class="bi bi-clock mr-1"></i>
                                    <?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?>
                                </p>
                            </div>
                            
                            <div class="flex gap-2">
                                <?php if ($msg['status'] === 'new'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                                        <button type="submit" name="mark_read" class="btn btn-sm bg-blue-500 text-white px-3 py-1 rounded-lg text-sm">
                                            <i class="bi bi-check2"></i> Прочитано
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" class="inline" onsubmit="return confirm('Удалить это сообщение?')">
                                    <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                                    <button type="submit" name="delete" class="btn btn-sm bg-red-500 text-white px-3 py-1 rounded-lg text-sm">
                                        <i class="bi bi-trash"></i> Удалить
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                <?= nl2br(htmlspecialchars($msg['message'])) ?>
                            </p>
                        </div>
                        
                        <?php if ($msg['admin_response']): ?>
                            <div class="mt-4 p-4 bg-green-50 dark:bg-green-950/20 rounded-lg border-l-4 border-green-500">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="bi bi-reply-all-fill text-green-600"></i>
                                    <span class="font-semibold text-green-700 dark:text-green-400">Ответ администратора:</span>
                                    <?php if ($msg['admin_name']): ?>
                                        <span class="text-xs text-gray-500">(<?= htmlspecialchars($msg['admin_name']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                    <?= nl2br(htmlspecialchars($msg['admin_response'])) ?>
                                </p>
                                <?php if ($msg['responded_at']): ?>
                                    <p class="text-xs text-gray-400 mt-2">
                                        <?= date('d.m.Y H:i', strtotime($msg['responded_at'])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Действия в зависимости от типа пользователя -->
                        <?php if ($msg['status'] !== 'replied'): ?>
                            <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                                
                                <?php if ($is_unregistered): ?>
                                    <!-- Для неавторизованных - только кнопка копирования email и отметка -->
                                    <div class="bg-yellow-50 dark:bg-yellow-950/20 p-3 rounded-lg">
                                        <div class="flex flex-col gap-3">
                                            <p class="text-sm text-yellow-800 dark:text-yellow-300">
                                                <i class="bi bi-info-circle"></i>
                                                Пользователь не авторизован. Скопируйте email и ответьте вручную.
                                            </p>
                                            <div class="flex gap-2">
                                                <button type="button" onclick="copyToClipboard('<?= $msg['email'] ?>')" 
                                                        class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-4 py-2 rounded-lg text-sm font-medium transition inline-flex items-center gap-2">
                                                    <i class="bi bi-copy"></i> Скопировать email
                                                </button>
                                                
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                                                    <button type="submit" name="mark_replied" 
                                                            class="bg-green-100 hover:bg-green-200 text-green-800 px-4 py-2 rounded-lg text-sm font-medium transition inline-flex items-center gap-2">
                                                        <i class="bi bi-check2-circle"></i> Отметить как отвеченное
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                <?php else: ?>
                                    <!-- Для авторизованных - ответ в системе -->
                                    <div class="bg-blue-50 dark:bg-blue-950/20 p-3 rounded-lg">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="bi bi-person-check-fill text-blue-600 dark:text-blue-400"></i>
                                            <span class="text-sm text-blue-800 dark:text-blue-300 font-medium">Авторизованный пользователь</span>
                                        </div>
                                        <details class="mt-0">
                                            <summary class="cursor-pointer text-color-brands font-semibold hover:opacity-80">
                                                <i class="bi bi-reply"></i> Ответить (ответ увидит пользователь в профиле)
                                            </summary>
                                            <form method="POST" class="mt-3">
                                                <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                                                <textarea name="response" rows="3" required 
                                                          class="w-full px-4 py-2 border border-neutral rounded-lg bg-white dark:bg-dark-neutral-bg focus:ring-2 focus:ring-color-brands focus:border-transparent"
                                                          placeholder="Введите ваш ответ..."></textarea>
                                                <button type="submit" name="send_response" 
                                                        class="mt-2 bg-color-brands hover:bg-opacity-90 text-white px-5 py-2 rounded-lg font-medium transition flex items-center gap-2">
                                                    <i class="bi bi-send"></i>
                                                    Отправить ответ
                                                </button>
                                            </form>
                                        </details>
                                    </div>
                                <?php endif; ?>
                                
                            </div>
                        <?php else: ?>
                            <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-2 text-green-600">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span class="text-sm">
                                        <?php if ($is_unregistered): ?>
                                            Отмечено как отвеченное
                                        <?php else: ?>
                                            Ответ отправлен пользователю
                                        <?php endif; ?>
                                    </span>
                                    <span class="text-xs text-gray-400 ml-2">
                                        <?= date('d.m.Y H:i', strtotime($msg['responded_at'])) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-12 bg-neutral-bg dark:bg-dark-neutral-bg rounded-2xl">
                    <i class="bi bi-inbox text-5xl text-gray-400 mb-3"></i>
                    <p class="text-gray-500">Нет сообщений</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function copyToClipboard(text) {
    // Создаем временный input
    const input = document.createElement('input');
    input.value = text;
    document.body.appendChild(input);
    input.select();
    input.setSelectionRange(0, 99999); // Для мобильных устройств
    
    try {
        document.execCommand('copy');
        // Показываем уведомление
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
        toast.innerHTML = '<i class="bi bi-check-circle me-2"></i>Email скопирован: ' + text;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    } catch (err) {
        // Если execCommand не работает, пробуем clipboard API
        navigator.clipboard.writeText(text).then(function() {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            toast.innerHTML = '<i class="bi bi-check-circle me-2"></i>Email скопирован: ' + text;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        }).catch(function() {
            alert('Не удалось скопировать email. Скопируйте вручную: ' + text);
        });
    }
    
    // Удаляем временный input
    document.body.removeChild(input);
}
</script>