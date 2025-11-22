<?php
// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    header("Location: index.php");
    exit();
}

// Получаем список профессий
$professions = $link->query("
    SELECT p.*, COUNT(r.id) as recommendations_count
    FROM professions p
    LEFT JOIN recommendations r ON p.id = r.profession_id
    GROUP BY p.id
    ORDER BY p.title ASC
");

// Получаем компании и учебные заведения для связей
$companies = $link->query("SELECT * FROM companies ORDER BY name ASC");
$institutions = $link->query("SELECT * FROM educational_institutions ORDER BY name ASC");

// Получаем существующие связи для модального окна
$profession_companies = [];
$profession_institutions = [];

if (isset($_GET['profession_id'])) {
    $prof_id = $_GET['profession_id'];
    $profession_companies = $link->query("
        SELECT pc.*, c.name as company_name 
        FROM profession_companies pc 
        LEFT JOIN companies c ON pc.company_id = c.id 
        WHERE pc.profession_id = $prof_id
    ");

    $profession_institutions = $link->query("
        SELECT pi.*, i.name as institution_name 
        FROM profession_institutions pi 
        LEFT JOIN educational_institutions i ON pi.institution_id = i.id 
        WHERE pi.profession_id = $prof_id
    ");
}
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <div class="flex justify-between items-center mb-6">
            <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100">
                Управление профессиями
            </h2>
            <a href="index.php?page=admin-dashboard" class="btn bg-gray-500 text-white px-4 py-2 rounded-lg">
                <i class="bi bi-arrow-left"></i> Назад
            </a>
        </div>

        <!-- Статистика профессий -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="stat-card text-center">
                <div class="stat-icon bg-blue-100 text-blue-600 mx-auto">
                    <i class="bi bi-briefcase text-xl"></i>
                </div>
                <div class="stat-number"><?= $professions->num_rows ?></div>
                <div class="stat-label">Всего профессий</div>
            </div>

            <?php
            $tech_count = $link->query("SELECT COUNT(*) as count FROM professions WHERE category = 'техническая'")->fetch_assoc()['count'];
            $human_count = $link->query("SELECT COUNT(*) as count FROM professions WHERE category = 'гуманитарная'")->fetch_assoc()['count'];
            $creative_count = $link->query("SELECT COUNT(*) as count FROM professions WHERE category = 'творческая'")->fetch_assoc()['count'];
            ?>

            <div class="stat-card text-center">
                <div class="stat-icon bg-green-100 text-green-600 mx-auto">
                    <i class="bi bi-cpu text-xl"></i>
                </div>
                <div class="stat-number"><?= $tech_count ?></div>
                <div class="stat-label">Технические</div>
            </div>

            <div class="stat-card text-center">
                <div class="stat-icon bg-purple-100 text-purple-600 mx-auto">
                    <i class="bi bi-people text-xl"></i>
                </div>
                <div class="stat-number"><?= $human_count ?></div>
                <div class="stat-label">Гуманитарные</div>
            </div>

            <div class="stat-card text-center">
                <div class="stat-icon bg-orange-100 text-orange-600 mx-auto">
                    <i class="bi bi-palette text-xl"></i>
                </div>
                <div class="stat-number"><?= $creative_count ?></div>
                <div class="stat-label">Творческие</div>
            </div>
        </div>

        <!-- Таблица профессий -->
        <div class="stat-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">Каталог профессий</h3>
                <button onclick="openAddProfessionModal()" class="btn bg-color-brands text-white px-4 py-2 rounded-lg">
                    <i class="bi bi-plus-circle"></i> Добавить профессию
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-neutral dark:border-dark-neutral-border">
                            <th class="text-left p-3">Профессия</th>
                            <th class="text-left p-3">Категория</th>
                            <th class="text-left p-3">Востребованность</th>
                            <th class="text-left p-3">Рекомендации</th>
                            <th class="text-left p-3">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($prof = $professions->fetch_assoc()): ?>
                            <tr class="border-b border-neutral dark:border-dark-neutral-border">
                                <td class="p-3">
                                    <div>
                                        <p class="font-medium"><?= htmlspecialchars($prof['title']) ?></p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($prof['description']) ?></p>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <span class="badge 
                                    <?= $prof['category'] === 'техническая' ? 'badge-primary' : ($prof['category'] === 'гуманитарная' ? 'badge-success' : ($prof['category'] === 'творческая' ? 'badge-warning' : 'badge-primary')) ?>">
                                        <?= $prof['category'] ?>
                                    </span>
                                </td>
                                <td class="p-3">
                                    <span class="badge 
                                    <?= $prof['demand_level'] === 'высокий' ? 'badge-success' : ($prof['demand_level'] === 'средний' ? 'badge-warning' : 'badge-danger') ?>">
                                        <?= $prof['demand_level'] ?>
                                    </span>
                                </td>
                                <td class="p-3">
                                    <span class="font-semibold"><?= $prof['recommendations_count'] ?></span>
                                </td>
                                <td class="p-3">
                                    <div class="flex gap-2">
                                        <a href="index.php?page=profession-detail&id=<?= $prof['id'] ?>"
                                            class="btn bg-blue-500 text-white px-3 py-1 rounded text-sm">
                                            <i class="bi bi-eye"></i> Просмотр
                                        </a>
                                        <button onclick="manageProfessionConnections(<?= $prof['id'] ?>, '<?= htmlspecialchars(addslashes($prof['title'])) ?>')"
                                            class="btn bg-purple-500 text-white px-3 py-1 rounded text-sm">
                                            <i class="bi bi-link"></i> Связи
                                        </button>
                                        <button onclick="editProfession(<?= $prof['id'] ?>)"
                                            class="btn bg-green-500 text-white px-3 py-1 rounded text-sm">
                                            <i class="bi bi-pencil"></i> Редактировать
                                        </button>
                                        <a href="event_user/admin_professions_handler.php?delete_profession=<?= $prof['id'] ?>"
                                            class="btn bg-red-500 text-white px-3 py-1 rounded text-sm"
                                            onclick="return confirm('Удалить профессию <?= htmlspecialchars(addslashes($prof['title'])) ?>?')">
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

<!-- Модальное окно управления связями профессии -->
<div id="professionConnectionsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 15px; border-radius: 8px; width: 95%; max-width: 800px; margin: 20px auto; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="font-size: 1.1rem; font-weight: 600;" id="connectionsModalTitle">Управление связями профессии</h3>
            <button onclick="closeModal('professionConnectionsModal')" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; padding: 5px;">×</button>
        </div>

        <div id="connectionsContent">
            <!-- Контент будет загружаться через AJAX -->
        </div>
    </div>
</div>

<!-- Модальное окно профессии -->
<div id="professionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 15px; border-radius: 8px; width: 95%; max-width: 450px; margin: 20px auto; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="font-size: 1.1rem; font-weight: 600;" id="professionModalTitle">Добавить профессию</h3>
            <button onclick="closeModal('professionModal')" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; padding: 5px;">×</button>
        </div>

        <form method="POST" action="event_user/admin_professions_handler.php" id="professionForm">
            <input type="hidden" name="profession_id" id="professionId">

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Название:</label>
                <input type="text" name="title" id="professionTitle" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" required>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Описание:</label>
                <textarea name="description" id="professionDescription" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; min-height: 60px; font-size: 0.9rem;" required></textarea>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Необходимые навыки:</label>
                <textarea name="required_skills" id="professionSkills" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; min-height: 60px; font-size: 0.9rem;" required></textarea>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Зарплата:</label>
                <input type="text" name="salary_range" id="professionSalary" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" placeholder="50 000 - 100 000 руб." required>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Уровень образования:</label>
                <select name="education_level" id="professionEducation" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" required>
                    <option value="среднее">Среднее</option>
                    <option value="среднее-специальное">Среднее специальное</option>
                    <option value="бакалавриат" selected>Бакалавриат</option>
                    <option value="магистратура">Магистратура</option>
                    <option value="аспирантура">Аспирантура</option>
                </select>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Востребованность:</label>
                <select name="demand_level" id="professionDemand" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" required>
                    <option value="низкий">Низкий</option>
                    <option value="средний" selected>Средний</option>
                    <option value="высокий">Высокий</option>
                </select>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Категория:</label>
                <select name="category" id="professionCategory" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" required>
                    <option value="техническая">Техническая</option>
                    <option value="гуманитарная">Гуманитарная</option>
                    <option value="творческая">Творческая</option>
                    <option value="научная">Научная</option>
                    <option value="бизнес">Бизнес</option>
                </select>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Обязанности:</label>
                <textarea name="responsibilities" id="professionResponsibilities" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; min-height: 60px; font-size: 0.9rem;"></textarea>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Карьерный рост:</label>
                <textarea name="career_growth" id="professionCareer" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; min-height: 60px; font-size: 0.9rem;"></textarea>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Перспективы трудоустройства:</label>
                <textarea name="employment_prospects" id="professionProspects" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; min-height: 60px; font-size: 0.9rem;"></textarea>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Рекомендуемые курсы:</label>
                <textarea name="related_courses" id="professionCourses" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; min-height: 60px; font-size: 0.9rem;"></textarea>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Изображение:</label>
                <input type="text" name="image_url" id="professionImage" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" placeholder="assets/images/professions/default.png">
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 15px;">
                <button type="button" onclick="closeModal('professionModal')" style="padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">Отмена</button>
                <button type="submit" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;" id="professionSubmitBtn">Добавить</button>
            </div>
        </form>
    </div>
</div>

<script>
// Глобальные переменные для управления вкладками
let currentActiveTab = 'companies-tab';
let currentProfessionId = null;

// Управление связями профессии
function manageProfessionConnections(professionId, professionTitle) {
    currentProfessionId = professionId;
    document.getElementById('connectionsModalTitle').textContent = 'Управление связями: ' + professionTitle;

    // Загружаем контент через AJAX
    fetch(`event_user/get_profession_connections.php?id=${professionId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('connectionsContent').innerHTML = html;
            document.getElementById('professionConnectionsModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Активируем сохраненную вкладку после загрузки
            setTimeout(() => {
                activateTab(currentActiveTab);
            }, 100);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка загрузки данных связей');
        });
}

// Функция переключения вкладок
function switchTab(tabName) {
    currentActiveTab = tabName;
    
    // Скрыть все вкладки
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Убрать активный класс со всех кнопок
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.style.borderBottom = 'none';
        button.classList.remove('active');
    });
    
    // Показать выбранную вкладку
    const activeTab = document.getElementById(tabName);
    if (activeTab) {
        activeTab.style.display = 'block';
    }
    
    // Добавить активный класс к выбранной кнопке
    const activeButton = document.querySelector(`[onclick="switchTab('${tabName}')"]`);
    if (activeButton) {
        activeButton.style.borderBottom = '2px solid #3b82f6';
        activeButton.classList.add('active');
    }
}

// Активация конкретной вкладки
function activateTab(tabName) {
    const button = document.querySelector(`[onclick="switchTab('${tabName}')"]`);
    if (button) {
        button.click();
    }
}

// Добавление связи с компанией
function addCompanyConnection(professionId) {
    const companyId = document.getElementById('companySelect').value;
    const positionName = document.getElementById('positionName').value;
    const experienceLevel = document.getElementById('experienceLevel').value;
    
    if (!companyId || !positionName) {
        alert('Заполните все обязательные поля');
        return;
    }
    
    const formData = new FormData();
    formData.append('add_company_connection', '1');
    formData.append('profession_id', professionId);
    formData.append('company_id', companyId);
    formData.append('position_name', positionName);
    formData.append('experience_level', experienceLevel);
    
    fetch('event_user/profession_connections.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Сохраняем текущую вкладку
            const activeTab = currentActiveTab;
            // Обновляем содержимое модального окна
            manageProfessionConnections(professionId, document.getElementById('connectionsModalTitle').textContent.replace('Управление связями: ', ''));
            // Восстанавливаем активную вкладку
            setTimeout(() => {
                activateTab(activeTab);
            }, 150);
            alert(data.message || 'Связь успешно добавлена');
        } else {
            alert(data.error || 'Ошибка при добавлении связи');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка при добавлении связи');
    });
}

// Добавление связи с учебным заведением
function addInstitutionConnection(professionId) {
    const institutionId = document.getElementById('institutionSelect').value;
    const programName = document.getElementById('programName').value;
    const duration = document.getElementById('programDuration').value;
    const cost = document.getElementById('programCost').value;
    
    if (!institutionId || !programName) {
        alert('Заполните все обязательные поля');
        return;
    }
    
    const formData = new FormData();
    formData.append('add_institution_connection', '1');
    formData.append('profession_id', professionId);
    formData.append('institution_id', institutionId);
    formData.append('program_name', programName);
    formData.append('duration', duration);
    formData.append('cost', cost);
    
    fetch('event_user/profession_connections.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Сохраняем текущую вкладку как "учебные заведения"
            currentActiveTab = 'institutions-tab';
            // Обновляем содержимое модального окна
            manageProfessionConnections(professionId, document.getElementById('connectionsModalTitle').textContent.replace('Управление связями: ', ''));
            alert(data.message || 'Связь успешно добавлена');
        } else {
            alert(data.error || 'Ошибка при добавлении связи');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка при добавлении связи');
    });
}

// Профессии
function openAddProfessionModal() {
    document.getElementById('professionModalTitle').textContent = 'Добавить профессию';
    document.getElementById('professionSubmitBtn').textContent = 'Добавить';
    document.getElementById('professionId').value = '';
    document.getElementById('professionForm').reset();
    document.getElementById('professionModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function editProfession(professionId) {
    // AJAX запрос для получения данных профессии
    fetch(`event_user/get_profession_data.php?id=${professionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('professionModalTitle').textContent = 'Редактировать профессию';
                document.getElementById('professionSubmitBtn').textContent = 'Сохранить';
                document.getElementById('professionId').value = data.id;
                document.getElementById('professionTitle').value = data.title;
                document.getElementById('professionDescription').value = data.description;
                document.getElementById('professionSkills').value = data.required_skills;
                document.getElementById('professionSalary').value = data.salary_range;
                document.getElementById('professionEducation').value = data.education_level;
                document.getElementById('professionDemand').value = data.demand_level;
                document.getElementById('professionCategory').value = data.category;

                // Заполняем дополнительные поля если есть
                if (data.details) {
                    document.getElementById('professionResponsibilities').value = data.details.responsibilities || '';
                    document.getElementById('professionCareer').value = data.details.career_growth || '';
                    document.getElementById('professionProspects').value = data.details.employment_prospects || '';
                    document.getElementById('professionCourses').value = data.details.related_courses || '';
                    document.getElementById('professionImage').value = data.details.image_url || '';
                }

                document.getElementById('professionModal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка загрузки данных профессии');
        });
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
    // Сбрасываем активную вкладку при закрытии
    if (modalId === 'professionConnectionsModal') {
        currentActiveTab = 'companies-tab';
    }
}

// Закрытие модальных окон
document.getElementById('professionModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal('professionModal');
});

document.getElementById('professionConnectionsModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal('professionConnectionsModal');
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('professionModal').style.display === 'flex') closeModal('professionModal');
        if (document.getElementById('professionConnectionsModal').style.display === 'flex') closeModal('professionConnectionsModal');
    }
});
</script>