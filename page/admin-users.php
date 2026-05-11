<?php
/** @var mysqli $link */
// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    header("Location: index.php");
    exit();
}

// Обработка действий
if (isset($_GET['action'])) {
    require_once('../event_user/admin_actions.php');
}

// Получаем список пользователей (начальная загрузка)
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

        <!-- Фильтры и поиск в реальном времени -->
<div class="stat-card mb-6">
    <div class="flex flex-col md:flex-row gap-4">
        <!-- Поиск (с live поиском) -->
        <div class="flex-1">
            <div class="bg-gray-100 flex rounded-xl py-3 px-4 dark:bg-gray-dark-100">
                <div class="flex-shrink-0">
                    <img src="assets/images/icons/icon-search-normal.svg" alt="Поиск" class="w-5 h-5">
                </div>
                <input class="input w-full bg-transparent outline-none pl-2 h-5 text-gray-800 dark:text-gray-dark-800 focus:!outline-none placeholder:text-gray-400 dark:placeholder:text-gray-dark-400 placeholder:font-semibold"
                    type="text" id="searchInput" placeholder="Поиск по имени или email...">
                <button type="button" id="clearSearch" class="flex-shrink-0 ml-1 hidden text-gray-400 hover:text-gray-600">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Фильтр по роли -->
        <div class="relative">
            <select id="roleFilter" class="bg-gray-100 rounded-xl py-3 px-4 pr-10 border-0 focus:ring-2 focus:ring-color-brands dark:bg-gray-dark-100 cursor-pointer appearance-none">
                <option value="">Все роли</option>
                <option value="обучающийся">Обучающиеся</option>
                <option value="куратор">Кураторы</option>
                <option value="администратор">Администраторы</option>
            </select>
        </div>

        <!-- Кнопка сброса -->
        <button id="resetBtn" class="bg-gray-100 hover:bg-gray-200 dark:bg-gray-dark-100 dark:hover:bg-gray-dark-200 rounded-xl py-3 px-5 transition-all duration-200 flex items-center gap-2 text-gray-700 dark:text-gray-dark-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Сбросить
        </button>
    </div>
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
                    <tbody id="usersTableBody">
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr class="border-b border-neutral dark:border-dark-neutral-border user-row" 
                                data-name="<?= htmlspecialchars(strtolower($user['name'])) ?>"
                                data-email="<?= htmlspecialchars(strtolower($user['email'])) ?>"
                                data-role="<?= $user['role'] ?>">
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
                                                class="btn bg-green-500 text-white px-3 py-1 rounded text-sm"
                                                onclick="return confirm('Разблокировать пользователя?')">
                                                <i class="bi bi-unlock"></i> Разблокировать
                                            </a>
                                        <?php endif; ?>

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
            
            <!-- Сообщение если нет результатов -->
            <div id="noResults" class="text-center py-8 text-gray-500" style="display: none;">
                <i class="bi bi-search text-4xl"></i>
                <p class="mt-2">Пользователи не найдены</p>
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
    // Получаем элементы
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const resetBtn = document.getElementById('resetBtn');
    const clearSearchBtn = document.getElementById('clearSearch');
    
    // Функция фильтрации таблицы
    function filterUsers() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const roleFilterValue = roleFilter.value;
        const rows = document.querySelectorAll('#usersTableBody .user-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const email = row.getAttribute('data-email') || '';
            const role = row.getAttribute('data-role') || '';
            
            const matchesSearch = searchTerm === '' || name.includes(searchTerm) || email.includes(searchTerm);
            const matchesRole = roleFilterValue === '' || role === roleFilterValue;
            
            if (matchesSearch && matchesRole) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Показываем/скрываем кнопку очистки
        if (searchTerm !== '') {
            clearSearchBtn.classList.remove('hidden');
        } else {
            clearSearchBtn.classList.add('hidden');
        }
        
        // Показываем сообщение если нет результатов
        const noResultsDiv = document.getElementById('noResults');
        if (visibleCount === 0) {
            noResultsDiv.style.display = 'block';
        } else {
            noResultsDiv.style.display = 'none';
        }
    }
    
    // Очистка поиска
    function clearSearch() {
        searchInput.value = '';
        filterUsers();
        searchInput.focus();
    }
    
    // Сброс всех фильтров
    function resetFilters() {
        searchInput.value = '';
        roleFilter.value = '';
        filterUsers();
        searchInput.focus();
    }
    
    // Обработчики событий
    searchInput.addEventListener('input', filterUsers);
    roleFilter.addEventListener('change', filterUsers);
    resetBtn.addEventListener('click', resetFilters);
    clearSearchBtn.addEventListener('click', clearSearch);
    
    // Функция удаления пользователя
    function deleteUser(userId, userName) {
        if (confirm(`Вы уверены, что хотите удалить пользователя "${userName}"? Это действие нельзя отменить.`)) {
            window.location.href = `event_user/admin_actions.php?action=delete_user&id=${userId}`;
        }
    }
    
    // Функция блокировки
    function blockUser(userId, userName) {
        document.getElementById('blockUserId').value = userId;
        document.getElementById('blockUserName').textContent = userName;
        document.getElementById('blockUserModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        document.getElementById('blockUserModal').style.display = 'none';
        document.body.style.overflow = 'auto';
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
    
    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        filterUsers();
    });
</script>

<style>
    /* Дополнительные стили для плавности */
    .user-row {
        transition: background-color 0.2s ease;
    }
    .user-row:hover {
        background-color: rgba(103, 58, 183, 0.05);
    }
</style>