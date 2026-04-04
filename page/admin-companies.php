<?php
// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    header("Location: index.php");
    exit();
}

// Получаем список компаний
$companies = $link->query("SELECT * FROM companies ORDER BY name ASC");

// Получаем список учебных заведений
$institutions = $link->query("SELECT * FROM educational_institutions ORDER BY name ASC");
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <div class="flex justify-between items-center mb-6">
            <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100">
                Управление компаниями и ВУЗами
            </h2>
            <a href="index.php?page=admin-dashboard" class="btn bg-gray-500 text-white px-4 py-2 rounded-lg">
                <i class="bi bi-arrow-left"></i> Назад
            </a>
        </div>

        <!-- Статистика -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="stat-card text-center">
                <div class="stat-icon bg-blue-100 text-blue-600 mx-auto">
                    <i class="bi bi-building text-xl"></i>
                </div>
                <div class="stat-number"><?= $companies->num_rows ?></div>
                <div class="stat-label">Компаний в базе</div>
            </div>

            <div class="stat-card text-center">
                <div class="stat-icon bg-green-100 text-green-600 mx-auto">
                    <i class="bi bi-book text-xl"></i>
                </div>
                <div class="stat-number"><?= $institutions->num_rows ?></div>
                <div class="stat-label">Учебных заведений</div>
            </div>
        </div>

        <!-- Таблица компаний -->
        <div class="stat-card mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">Компании-работодатели</h3>
                <button onclick="openAddCompanyModal()" class="btn bg-color-brands text-white px-4 py-2 rounded-lg">
                    <i class="bi bi-plus-circle"></i> Добавить компанию
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-neutral dark:border-dark-neutral-border">
                            <th class="text-left p-3">Компания</th>
                            <th class="text-left p-3">Отрасль</th>
                            <th class="text-left p-3">Локация</th>
                            <th class="text-left p-3">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($company = $companies->fetch_assoc()): ?>
                            <tr class="border-b border-neutral dark:border-dark-neutral-border">
                                <td class="p-3">
                                    <div>
                                        <p class="font-medium"><?= htmlspecialchars($company['name']) ?></p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($company['description']) ?></p>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <span class="badge badge-primary"><?= $company['industry'] ?></span>
                                </td>
                                <td class="p-3">
                                    <?= $company['location'] ?>
                                </td>
                                <td class="p-3">
                                    <div class="flex gap-2 flex-wrap">
                                        <button onclick="viewCompanyConnections(<?= $company['id'] ?>, '<?= htmlspecialchars(addslashes($company['name'])) ?>')"
                                            class="btn bg-purple-500 text-white px-3 py-1 rounded text-sm whitespace-nowrap">
                                            <i class="bi bi-eye"></i> Профессии
                                        </button>
                                        <button onclick="editCompany(<?= $company['id'] ?>)"
                                            class="btn bg-blue-500 text-white px-3 py-1 rounded text-sm whitespace-nowrap">
                                            <i class="bi bi-pencil"></i> Редактировать
                                        </button>
                                        <a href="event_user/admin_companies_handler.php?delete_company=<?= $company['id'] ?>"
                                            class="btn bg-red-500 text-white px-3 py-1 rounded text-sm whitespace-nowrap"
                                            onclick="return confirm('Удалить компанию <?= htmlspecialchars(addslashes($company['name'])) ?>?')">
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

        <!-- Таблица учебных заведений -->
        <div class="stat-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">Учебные заведения</h3>
                <button onclick="openAddInstitutionModal()" class="btn bg-color-brands text-white px-4 py-2 rounded-lg">
                    <i class="bi bi-plus-circle"></i> Добавить ВУЗ/СУЗ
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-neutral dark:border-dark-neutral-border">
                            <th class="text-left p-3">Учебное заведение</th>
                            <th class="text-left p-3">Тип</th>
                            <th class="text-left p-3">Локация</th>
                            <th class="text-left p-3">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($institution = $institutions->fetch_assoc()): ?>
                            <tr class="border-b border-neutral dark:border-dark-neutral-border">
                                <td class="p-3">
                                    <div>
                                        <p class="font-medium"><?= htmlspecialchars($institution['name']) ?></p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($institution['description']) ?></p>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <span class="badge <?= $institution['type'] === 'ВУЗ' ? 'badge-success' : 'badge-primary' ?>">
                                        <?= $institution['type'] ?>
                                    </span>
                                </td>
                                <td class="p-3">
                                    <?= $institution['location'] ?>
                                </td>
                                <td class="p-3">
                                    <div class="flex gap-2 flex-wrap">
                                        <button onclick="viewInstitutionConnections(<?= $institution['id'] ?>, '<?= htmlspecialchars(addslashes($institution['name'])) ?>')"
                                            class="btn bg-purple-500 text-white px-3 py-1 rounded text-sm whitespace-nowrap">
                                            <i class="bi bi-eye"></i> Профессии
                                        </button>
                                        <button onclick="editInstitution(<?= $institution['id'] ?>)"
                                            class="btn bg-blue-500 text-white px-3 py-1 rounded text-sm whitespace-nowrap">
                                            <i class="bi bi-pencil"></i> Редактировать
                                        </button>
                                        <a href="event_user/admin_companies_handler.php?delete_institution=<?= $institution['id'] ?>"
                                            class="btn bg-red-500 text-white px-3 py-1 rounded text-sm whitespace-nowrap"
                                            onclick="return confirm('Удалить учебное заведение <?= htmlspecialchars(addslashes($institution['name'])) ?>?')">
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

<!-- Модальное окно компании -->
<div id="companyModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 15px; border-radius: 8px; width: 95%; max-width: 400px; margin: 20px auto; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="font-size: 1.1rem; font-weight: 600;" id="companyModalTitle">Добавить компанию</h3>
            <button onclick="closeModal('companyModal')" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; padding: 5px;">×</button>
        </div>

        <form method="POST" action="event_user/admin_companies_handler.php" id="companyForm">
            <input type="hidden" name="company_id" id="companyId">

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Название:</label>
                <input type="text" name="name" id="companyName" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" required>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Отрасль:</label>
                <input type="text" name="industry" id="companyIndustry" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" required>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Описание:</label>
                <textarea name="description" id="companyDescription" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; min-height: 60px; font-size: 0.9rem;" required></textarea>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Веб-сайт:</label>
                <input type="url" name="website" id="companyWebsite" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Локация:</label>
                <input type="text" name="location" id="companyLocation" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" required>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Сотрудники:</label>
                <input type="text" name="employee_count" id="companyEmployees" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Изображение:</label>
                <input type="text" name="image_url" id="companyImage" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" placeholder="assets/images/companies/default.png">
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 15px;">
                <button type="button" onclick="closeModal('companyModal')" style="padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">Отмена</button>
                <button type="submit" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;" id="companySubmitBtn">Добавить</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно учебного заведения -->
<div id="institutionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 15px; border-radius: 8px; width: 95%; max-width: 400px; margin: 20px auto; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="font-size: 1.1rem; font-weight: 600;" id="institutionModalTitle">Добавить учебное заведение</h3>
            <button onclick="closeModal('institutionModal')" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; padding: 5px;">×</button>
        </div>

        <form method="POST" action="event_user/admin_companies_handler.php" id="institutionForm">
            <input type="hidden" name="institution_id" id="institutionId">

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Название:</label>
                <input type="text" name="name" id="institutionName" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" required>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Тип:</label>
                <select name="type" id="institutionType" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" required>
                    <option value="ВУЗ">ВУЗ</option>
                    <option value="СУЗ">СУЗ</option>
                </select>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Локация:</label>
                <input type="text" name="location" id="institutionLocation" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" required>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Описание:</label>
                <textarea name="description" id="institutionDescription" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; min-height: 60px; font-size: 0.9rem;" required></textarea>
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Веб-сайт:</label>
                <input type="url" name="website" id="institutionWebsite" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Email:</label>
                <input type="email" name="contact_email" id="institutionEmail" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Телефон:</label>
                <input type="text" name="phone" id="institutionPhone" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 3px; font-weight: 500; font-size: 0.9rem;">Изображение:</label>
                <input type="text" name="image_url" id="institutionImage" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;" placeholder="assets/images/institutions/default.png">
            </div>

            <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 15px;">
                <button type="button" onclick="closeModal('institutionModal')" style="padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">Отмена</button>
                <button type="submit" style="padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem;" id="institutionSubmitBtn">Добавить</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно просмотра связей компании -->
<div id="companyConnectionsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; padding: 15px; border-radius: 8px; width: 95%; max-width: 800px; margin: 20px auto; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="font-size: 1.1rem; font-weight: 600;" id="companyConnectionsTitle">Связанные профессии</h3>
            <button onclick="closeModal('companyConnectionsModal')" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; padding: 5px;">×</button>
        </div>

        <div id="companyConnectionsContent">
            <!-- Контент будет загружаться через AJAX -->
        </div>
    </div>
</div>

<script>
    // Просмотр связей компании
    function viewCompanyConnections(companyId, companyName) {
        document.getElementById('companyConnectionsTitle').textContent = 'Профессии компании: ' + companyName;

        // Загружаем контент через AJAX
        fetch(`event_user/get_company_connections.php?id=${companyId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('companyConnectionsContent').innerHTML = html;
                document.getElementById('companyConnectionsModal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка загрузки данных связей');
            });
    }

    // Просмотр связей учебного заведения
    function viewInstitutionConnections(institutionId, institutionName) {
        document.getElementById('companyConnectionsTitle').textContent = 'Профессии учебного заведения: ' + institutionName;

        // Загружаем контент через AJAX
        fetch(`event_user/get_institution_connections.php?id=${institutionId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('companyConnectionsContent').innerHTML = html;
                document.getElementById('companyConnectionsModal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка загрузки данных связей');
            });
    }
</script>

<script>
    // Компании
    function openAddCompanyModal() {
        document.getElementById('companyModalTitle').textContent = 'Добавить компанию';
        document.getElementById('companySubmitBtn').textContent = 'Добавить';
        document.getElementById('companyId').value = '';
        document.getElementById('companyForm').reset();
        document.getElementById('companyModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function editCompany(companyId) {
        // AJAX запрос для получения данных компании
        fetch(`event_user/get_company_data.php?id=${companyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('companyModalTitle').textContent = 'Редактировать компанию';
                    document.getElementById('companySubmitBtn').textContent = 'Сохранить';
                    document.getElementById('companyId').value = data.id;
                    document.getElementById('companyName').value = data.name;
                    document.getElementById('companyIndustry').value = data.industry;
                    document.getElementById('companyDescription').value = data.description;
                    document.getElementById('companyWebsite').value = data.website;
                    document.getElementById('companyLocation').value = data.location;
                    document.getElementById('companyEmployees').value = data.employee_count;
                    document.getElementById('companyImage').value = data.image_url;
                    document.getElementById('companyModal').style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка загрузки данных компании');
            });
    }

    // Учебные заведения
    function openAddInstitutionModal() {
        document.getElementById('institutionModalTitle').textContent = 'Добавить учебное заведение';
        document.getElementById('institutionSubmitBtn').textContent = 'Добавить';
        document.getElementById('institutionId').value = '';
        document.getElementById('institutionForm').reset();
        document.getElementById('institutionModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function editInstitution(institutionId) {
        // AJAX запрос для получения данных учебного заведения
        fetch(`event_user/get_institution_data.php?id=${institutionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('institutionModalTitle').textContent = 'Редактировать учебное заведение';
                    document.getElementById('institutionSubmitBtn').textContent = 'Сохранить';
                    document.getElementById('institutionId').value = data.id;
                    document.getElementById('institutionName').value = data.name;
                    document.getElementById('institutionType').value = data.type;
                    document.getElementById('institutionLocation').value = data.location;
                    document.getElementById('institutionDescription').value = data.description;
                    document.getElementById('institutionWebsite').value = data.website;
                    document.getElementById('institutionEmail').value = data.contact_email;
                    document.getElementById('institutionPhone').value = data.phone;
                    document.getElementById('institutionImage').value = data.image_url;
                    document.getElementById('institutionModal').style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка загрузки данных учебного заведения');
            });
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Закрытие модальных окон
    document.getElementById('companyModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal('companyModal');
    });

    document.getElementById('institutionModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal('institutionModal');
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (document.getElementById('companyModal').style.display === 'flex') closeModal('companyModal');
            if (document.getElementById('institutionModal').style.display === 'flex') closeModal('institutionModal');
        }
    });
</script>