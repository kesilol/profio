<?php
// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    header("Location: index.php");
    exit();
}

// Обработка действий
if (isset($_GET['action'])) {
    require_once('../event_user/admin_actions.php');
}

// Получаем список пользователей
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

$query .= " ORDER BY created_at DESC";

$stmt = $link->prepare($query);
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <div class="flex justify-between items-center mb-6">
            <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100">
                Управление пользователями
            </h2>
            <a href="index.php?page=admin-dashboard" class="btn bg-gray-500 text-white px-4 py-2 rounded-lg">
                <i class="bi bi-arrow-left"></i> Назад
            </a>
        </div>

        <!-- Фильтры и поиск -->
        <div class="stat-card mb-6">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <input type="hidden" name="page" value="admin-users">

                <div class="flex-1">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Поиск по имени или email..."
                        class="w-full p-3 border border-neutral rounded-lg bg-white dark:bg-dark-neutral-bg dark:border-dark-neutral-border">
                </div>

                <div>
                    <select name="role" class="p-3 border border-neutral rounded-lg bg-white dark:bg-dark-neutral-bg dark:border-dark-neutral-border">
                        <option value="">Все роли</option>
                        <option value="студент" <?= $role_filter === 'студент' ? 'selected' : '' ?>>Студенты</option>
                        <option value="куратор" <?= $role_filter === 'куратор' ? 'selected' : '' ?>>Кураторы</option>
                        <option value="администратор" <?= $role_filter === 'администратор' ? 'selected' : '' ?>>Администраторы</option>
                    </select>
                </div>

                <button type="submit" class="btn bg-color-brands text-white px-4 py-2 rounded-lg">
                    <i class="bi bi-search"></i> Поиск
                </button>

                <a href="index.php?page=admin-users" class="btn bg-gray-500 text-white px-4 py-2 rounded-lg">
                    <i class="bi bi-arrow-clockwise"></i> Сбросить
                </a>
            </form>
        </div>

        <!-- Таблица пользователей -->
        <div class="stat-card">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-neutral dark:border-dark-neutral-border">
                            <th class="text-left p-3">Пользователь</th>
                            <th class="text-left p-3">Роль</th>
                            <th class="text-left p-3">Статус</th>
                            <th class="text-left p-3">Дата регистрации</th>
                            <th class="text-left p-3">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr class="border-b border-neutral dark:border-dark-neutral-border">
                                <td class="p-3">
                                    <div>
                                        <p class="font-medium"><?= htmlspecialchars($user['name']) ?></p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <span class="badge <?= $user['role'] === 'администратор' ? 'badge-warning' : ($user['role'] === 'куратор' ? 'badge-success' : 'badge-primary') ?>">
                                        <?= $user['role'] ?>
                                    </span>
                                </td>
                                <td class="p-3">
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge badge-success">Активен</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Заблокирован</span>
                                        <?php if ($user['block_reason']): ?>
                                            <br><small class="text-gray-500 text-xs"><?= htmlspecialchars($user['block_reason']) ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3">
                                    <?= date('d.m.Y H:i', strtotime($user['created_at'])) ?>
                                </td>
                                <td class="p-3">
                                    <div class="flex gap-2">
                                        <?php if ($user['is_active']): ?>
                                            <button onclick="blockUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')"
                                                class="btn bg-red-500 text-white px-3 py-1 rounded text-sm">
                                                <i class="bi bi-lock"></i> Заблокировать
                                            </button>
                                        <?php else: ?>
                                            <a href="event_user/admin_actions.php?action=unblock&id=<?= $user['id'] ?>"
                                                class="btn bg-green-500 text-white px-3 py-1 rounded text-sm">
                                                <i class="bi bi-unlock"></i> Разблокировать
                                            </a>
                                        <?php endif; ?>

                                        <button onclick="resetPassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')"
                                            class="btn bg-blue-500 text-white px-3 py-1 rounded text-sm">
                                            <i class="bi bi-key"></i> Сбросить пароль
                                        </button>

                                        <!-- НОВАЯ КНОПКА УДАЛЕНИЯ -->
                                        <?php if ($user['role'] !== 'администратор'): ?>
                                            <button onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')"
                                                class="btn bg-red-600 text-white px-3 py-1 rounded text-sm">
                                                <i class="bi bi-trash"></i> Удалить
                                            </button>
                                        <?php endif; ?>
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

<!-- Модальное окно блокировки -->
<div id="blockUserModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 20px; border-radius: 10px; width: 90%; max-width: 500px; margin: 20px auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 1.25rem; font-weight: 600;">Блокировка пользователя</h3>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; padding: 5px;">×</button>
        </div>

        <form id="blockUserForm" method="POST" action="event_user/admin_actions.php">
            <input type="hidden" name="user_id" id="blockUserId">

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Пользователь:</label>
                <p id="blockUserName" style="font-weight: 600; padding: 10px; background: #f3f4f6; border-radius: 5px; margin: 0;"></p>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Причина блокировки:</label>
                <select name="block_reason" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 5px;" required>
                    <option value="">Выберите причину</option>
                    <option value="Нарушение правил платформы">Нарушение правил платформы</option>
                    <option value="Подозрительная активность">Подозрительная активность</option>
                    <option value="Неадекватное поведение">Неадекватное поведение</option>
                    <option value="Другая причина">Другая причина</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Комментарий:</label>
                <textarea name="block_comment" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 5px; min-height: 80px;" placeholder="Дополнительная информация..."></textarea>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeModal()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer;">Отмена</button>
                <button type="submit" name="block_user" style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 5px; cursor: pointer;">Заблокировать</button>
            </div>
        </form>
    </div>
</div>

<script>
    function deleteUser(userId, userName) {
        if (confirm(`Вы уверены, что хотите удалить пользователя "${userName}"? Это действие нельзя отменить.`)) {
            window.location.href = `event_user/admin_actions.php?action=delete_user&id=${userId}`;
        }
    }

    function blockUser(userId, userName) {
        console.log('Opening modal for user:', userId, userName); // Для отладки
        document.getElementById('blockUserId').value = userId;
        document.getElementById('blockUserName').textContent = userName;
        document.getElementById('blockUserModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        console.log('Closing modal'); // Для отладки
        document.getElementById('blockUserModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function resetPassword(userId, userName) {
        if (confirm(`Сбросить пароль пользователя "${userName}"? Новый пароль будет: "password123"`)) {
            window.location.href = `event_user/admin_actions.php?action=reset_password&id=${userId}`;
        }
    }

    // Закрытие модального окна по клику на фон
    document.getElementById('blockUserModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Закрытие по ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    // Проверяем, загрузилась ли страница
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded, modal should be hidden');
    });
</script>