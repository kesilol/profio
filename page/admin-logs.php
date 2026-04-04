<?php
// Проверка прав доступа
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'администратор') {
    header("Location: index.php?page=sign-in");
    exit();
}

require_once(__DIR__ . '/../event_user/admin_logs_handler.php');

// Получаем список типов действий и целей для фильтров
$actionTypes = getActionTypes($link);
$targetTypes = getTargetTypes($link);
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
            Журнал действий администратора
        </h2>
        
        <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
            <div class="flex items-center gap-x-1">
                <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                <a class="capitalize" href="index.php">Главная</a>
            </div>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <span class="capitalize text-color-brands">Журнал действий</span>
        </div>

        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
            
            <!-- Фильтры в реальном времени -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Действие</label>
                    <select id="filterAction" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2" onchange="applyFiltersInstant()">
                        <option value="">Все действия</option>
                        <?php foreach ($actionTypes as $action): ?>
                            <option value="<?= htmlspecialchars($action) ?>"><?= htmlspecialchars($action) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Тип цели</label>
                    <select id="filterTargetType" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2" onchange="applyFiltersInstant()">
                        <option value="">Все типы</option>
                        <?php foreach ($targetTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Дата от</label>
                    <input type="date" id="filterDateFrom" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2" onchange="applyFiltersInstant()">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Дата до</label>
                    <input type="date" id="filterDateTo" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2" onchange="applyFiltersInstant()">
                </div>
            </div>
            
            <!-- Кнопки управления -->
            <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                <div class="flex gap-2">
                    <button onclick="exportLogs()" class="text-sm bg-green-500 text-white px-3 py-1.5 rounded-lg hover:bg-green-600 transition">
                        <i class="bi bi-download"></i> Экспорт CSV
                    </button>
                    <button onclick="clearOldLogs()" class="text-sm bg-red-500 text-white px-3 py-1.5 rounded-lg hover:bg-red-600 transition">
                        <i class="bi bi-trash"></i> Очистить старые
                    </button>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="text-sm text-gray-500">
                        <i class="bi bi-database"></i> Всего: <span id="logsCount" class="font-semibold">0</span>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-500">
                        <i class="bi bi-arrow-repeat"></i>
                        <span>Автообновление</span>
                        <input type="checkbox" id="autoRefresh" class="rounded" checked>
                    </label>
                </div>
            </div>
            
            <!-- Таблица логов (суженная) -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Время</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Администратор</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Действие</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Объект</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Детали</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500">
                                <i class="bi bi-hourglass-split text-2xl"></i>
                                <p class="mt-2">Загрузка...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Пагинация -->
            <div class="flex justify-between items-center mt-4">
                <button onclick="prevPage()" id="prevBtn" class="px-3 py-1.5 border rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition text-sm" disabled>
                    <i class="bi bi-chevron-left"></i> Назад
                </button>
                <div class="flex items-center gap-3">
                    <span id="pageInfo" class="text-sm text-gray-500">Страница 1</span>
                    <select id="perPage" class="text-sm border rounded-lg px-2 py-1 bg-white dark:bg-gray-800" onchange="changePerPage()">
                        <option value="20">20 записей</option>
                        <option value="50" selected>50 записей</option>
                        <option value="100">100 записей</option>
                    </select>
                </div>
                <button onclick="nextPage()" id="nextBtn" class="px-3 py-1.5 border rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition text-sm" disabled>
                    Вперед <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</main>

<script>
let currentPage = 1;
let totalPages = 1;
let currentFilters = {};
let perPage = 50;
let autoRefreshInterval = null;

// Маппинг типов целей для понятного отображения (русские названия)
const targetTypeNames = {
    'user': 'Пользователь',
    'profession': 'Профессия',
    'company': 'Компания',
    'institution': 'Учебное заведение',
    'test': 'Тест',
    'admin_logs': 'Логи',
    'question': 'Вопрос',
    'answer': 'Ответ',
    'profession_company': 'Связь профессии с компанией',
    'profession_institution': 'Связь профессии с учебным заведением'
};

// Маппинг действий для иконок
const actionIcons = {
    'Создание': 'bi-plus-circle',
    'Обновление': 'bi-pencil-square',
    'Удаление': 'bi-trash',
    'Блокировка': 'bi-lock',
    'Разблокировка': 'bi-unlock',
    'Очистка': 'bi-eraser'
};

function loadLogs() {
    const params = new URLSearchParams({
        get_logs: 1,
        page: currentPage,
        limit: perPage
    });
    
    if (currentFilters.action) params.append('action', currentFilters.action);
    if (currentFilters.target_type) params.append('target_type', currentFilters.target_type);
    if (currentFilters.date_from) params.append('date_from', currentFilters.date_from);
    if (currentFilters.date_to) params.append('date_to', currentFilters.date_to);
    
    fetch(`event_user/admin_logs_handler.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderLogs(data.logs);
                document.getElementById('logsCount').innerText = data.total;
                totalPages = data.total_pages;
                updatePagination();
            } else {
                showError('Ошибка загрузки: ' + (data.error || 'Неизвестная ошибка'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Ошибка соединения с сервером');
        });
}

function showError(message) {
    document.getElementById('logsTableBody').innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-8 text-red-500">
                <i class="bi bi-exclamation-triangle text-2xl"></i>
                <p class="mt-2">${message}</p>
            </td>
        </tr>
    `;
}

function renderLogs(logs) {
    if (!logs || logs.length === 0) {
        document.getElementById('logsTableBody').innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-8 text-gray-500">
                    <i class="bi bi-inbox text-2xl"></i>
                    <p class="mt-2">Нет записей</p>
                </td>
            </tr>
        `;
        return;
    }
    
    const html = logs.map(log => {
        // Определяем иконку для действия
        let actionIcon = 'bi-info-circle';
        if (log.action.includes('Создание')) actionIcon = 'bi-plus-circle text-green-500';
        else if (log.action.includes('Обновление') || log.action.includes('Редактирование')) actionIcon = 'bi-pencil-square text-blue-500';
        else if (log.action.includes('Удаление')) actionIcon = 'bi-trash text-red-500';
        else if (log.action.includes('Блокировка')) actionIcon = 'bi-lock text-orange-500';
        else if (log.action.includes('Разблокировка')) actionIcon = 'bi-unlock text-green-500';
        else if (log.action.includes('Очистка')) actionIcon = 'bi-eraser text-purple-500';
        
        // Определяем тип объекта
        const targetName = targetTypeNames[log.target_type] || log.target_type || 'Система';
        
        // Извлекаем из деталей название (email, имя и т.д.)
        let targetDisplay = '';
        if (log.details) {
            // Пытаемся извлечь название из деталей для разных типов объектов
            let patterns = [
                /профессия\s+['"]?([^'"]+?)['"]?\s*[→-]/i,
                /компания\s+['"]?([^'"]+?)['"]?\s*[→-]/i,
                /пользователь:\s*([^.\n]+)/i,
                /тест\s+['"]?([^'"]+?)['"]?/i,
                /учебное заведение\s+['"]?([^'"]+?)['"]?/i,
                /вопрос:\s*['"]?([^'"]+?)['"]?(?:\s*[→-]|\s*\(|$)/i,
                /ответ:\s*['"]?([^'"]+?)['"]?(?:\s*[→-]|\s*\(|$)/i
            ];
            
            for (let pattern of patterns) {
                const match = log.details.match(pattern);
                if (match) {
                    targetDisplay = match[1];
                    break;
                }
            }
            
            if (!targetDisplay && log.target_id) {
                targetDisplay = `ID: ${log.target_id}`;
            }
        } else if (log.target_id) {
            targetDisplay = `ID: ${log.target_id}`;
        }
        
        // Обрезаем длинные названия
        if (targetDisplay && targetDisplay.length > 50) {
            targetDisplay = targetDisplay.substring(0, 47) + '...';
        }
        
        return `
            <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                    ${formatDateTime(log.created_at)}
                </td>
                <td class="px-3 py-2">
                    <div class="font-medium text-sm">${escapeHtml(log.admin_name || 'Неизвестно')}</div>
                    <div class="text-xs text-gray-500">${escapeHtml(log.admin_email || '')}</div>
                </td>
                <td class="px-3 py-2">
                    <div class="flex items-center gap-1.5">
                        <i class="bi ${actionIcon} text-sm"></i>
                        <span class="text-sm">${escapeHtml(log.action)}</span>
                    </div>
                </td>
                <td class="px-3 py-2">
                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        ${escapeHtml(targetName)}
                    </span>
                    ${targetDisplay ? `<div class="text-xs text-gray-500 mt-1 max-w-[200px] truncate" title="${escapeHtml(targetDisplay)}">${escapeHtml(targetDisplay)}</div>` : ''}
                </td>
                <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-400">
                    <div class="max-w-md break-words" title="${escapeHtml(log.details || '')}">
                        ${escapeHtml(log.details ? log.details.substring(0, 100) : '-')}
                        ${log.details && log.details.length > 100 ? '...' : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    document.getElementById('logsTableBody').innerHTML = html;
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    
    // Если сегодня, показываем только время
    if (days === 0) {
        return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    }
    // Если вчера
    if (days === 1) {
        return `Вчера ${date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })}`;
    }
    // Если в этом году
    if (date.getFullYear() === now.getFullYear()) {
        return date.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
    }
    // Полная дата
    return date.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function applyFiltersInstant() {
    currentFilters = {
        action: document.getElementById('filterAction').value,
        target_type: document.getElementById('filterTargetType').value,
        date_from: document.getElementById('filterDateFrom').value,
        date_to: document.getElementById('filterDateTo').value
    };
    currentPage = 1;
    loadLogs();
}

// Добавляем обработчики для фильтров
document.getElementById('filterAction').addEventListener('change', applyFiltersInstant);
document.getElementById('filterTargetType').addEventListener('change', applyFiltersInstant);
document.getElementById('filterDateFrom').addEventListener('change', applyFiltersInstant);
document.getElementById('filterDateTo').addEventListener('change', applyFiltersInstant);

function changePerPage() {
    perPage = parseInt(document.getElementById('perPage').value);
    currentPage = 1;
    loadLogs();
}

function updatePagination() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const pageInfo = document.getElementById('pageInfo');
    
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;
    pageInfo.innerText = `Страница ${currentPage} из ${totalPages || 1}`;
}

function prevPage() {
    if (currentPage > 1) {
        currentPage--;
        loadLogs();
    }
}

function nextPage() {
    if (currentPage < totalPages) {
        currentPage++;
        loadLogs();
    }
}

function exportLogs() {
    const filters = {
        action: document.getElementById('filterAction').value,
        target_type: document.getElementById('filterTargetType').value,
        date_from: document.getElementById('filterDateFrom').value,
        date_to: document.getElementById('filterDateTo').value
    };
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'event_user/admin_logs_handler.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'export_csv';
    form.appendChild(actionInput);
    
    const filtersInput = document.createElement('input');
    filtersInput.type = 'hidden';
    filtersInput.name = 'filters';
    filtersInput.value = JSON.stringify(filters);
    form.appendChild(filtersInput);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function clearOldLogs() {
    const days = prompt('Удалить логи старше скольки дней? (по умолчанию 90)', '90');
    if (days && confirm(`Удалить все логи старше ${days} дней? Это действие нельзя отменить.`)) {
        fetch('event_user/admin_logs_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=clear_logs&days=${days}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                loadLogs();
            } else {
                alert('Ошибка: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка при очистке логов');
        });
    }
}

// Автообновление
function setupAutoRefresh() {
    const autoRefreshCheckbox = document.getElementById('autoRefresh');
    
    function startAutoRefresh() {
        if (autoRefreshInterval) clearInterval(autoRefreshInterval);
        autoRefreshInterval = setInterval(() => {
            if (autoRefreshCheckbox.checked) {
                loadLogs();
            }
        }, 30000);
    }
    
    autoRefreshCheckbox.addEventListener('change', () => {
        if (autoRefreshCheckbox.checked) {
            startAutoRefresh();
        } else {
            if (autoRefreshInterval) clearInterval(autoRefreshInterval);
        }
    });
    
    startAutoRefresh();
}

// Загружаем логи при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    loadLogs();
    setupAutoRefresh();
});
</script>

<style>
/* Дополнительные стили для таблицы */
#logsTableBody tr {
    transition: background-color 0.2s ease;
}

/* Стиль для скролла */
.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Для длинных текстов */
.max-w-md {
    max-width: 400px;
}

.break-words {
    word-break: break-word;
}

.truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>