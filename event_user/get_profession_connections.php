<?php
session_start();
require('../connect.php');

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    die('Access denied');
}

if (!isset($_GET['id'])) {
    die('ID профессии не указан');
}

$profession_id = intval($_GET['id']);

// Получаем данные профессии
$profession_stmt = $link->prepare("SELECT * FROM professions WHERE id = ?");
$profession_stmt->bind_param("i", $profession_id);
$profession_stmt->execute();
$profession = $profession_stmt->get_result()->fetch_assoc();

if (!$profession) {
    die('Профессия не найдена');
}

// Получаем компании для выпадающего списка
$companies_result = $link->query("SELECT * FROM companies ORDER BY name ASC");
$companies = [];
while($row = $companies_result->fetch_assoc()) {
    $companies[] = $row;
}

// Получаем связи с компаниями
$profession_companies_stmt = $link->prepare("
    SELECT pc.*, c.name as company_name 
    FROM profession_companies pc 
    LEFT JOIN companies c ON pc.company_id = c.id 
    WHERE pc.profession_id = ?
");
$profession_companies_stmt->bind_param("i", $profession_id);
$profession_companies_stmt->execute();
$profession_companies_result = $profession_companies_stmt->get_result();
$profession_companies = [];
while($row = $profession_companies_result->fetch_assoc()) {
    $profession_companies[] = $row;
}

// Получаем учебные заведения для выпадающего списка
$institutions_result = $link->query("SELECT * FROM educational_institutions ORDER BY name ASC");
$institutions = [];
while($row = $institutions_result->fetch_assoc()) {
    $institutions[] = $row;
}

// Получаем связи с учебными заведениями
$profession_institutions_stmt = $link->prepare("
    SELECT pi.*, i.name as institution_name 
    FROM profession_institutions pi 
    LEFT JOIN educational_institutions i ON pi.institution_id = i.id 
    WHERE pi.profession_id = ?
");
$profession_institutions_stmt->bind_param("i", $profession_id);
$profession_institutions_stmt->execute();
$profession_institutions_result = $profession_institutions_stmt->get_result();
$profession_institutions = [];
while($row = $profession_institutions_result->fetch_assoc()) {
    $profession_institutions[] = $row;
}
?>

<!-- HTML контент для модального окна -->
<div class="connections-tabs">
    <div class="tab-buttons" style="display: flex; border-bottom: 1px solid #ddd; margin-bottom: 15px;">
        <button class="tab-button active" onclick="switchTab('companies-tab')" style="padding: 10px 15px; border: none; background: none; cursor: pointer; border-bottom: 2px solid #3b82f6;">Компании</button>
        <button class="tab-button" onclick="switchTab('institutions-tab')" style="padding: 10px 15px; border: none; background: none; cursor: pointer;">Учебные заведения</button>
    </div>
    
    <div id="companies-tab" class="tab-content">
        <h4 style="margin-bottom: 15px;">Связи с компаниями</h4>
        
        <!-- Форма добавления новой связи -->
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <h5 style="margin-bottom: 10px;">Добавить связь с компанией</h5>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Компания:</label>
                    <select id="companySelect" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Выберите компанию</option>
                        <?php foreach($companies as $company): ?>
                            <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Уровень опыта:</label>
                    <select id="experienceLevel" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="стажер">Стажер</option>
                        <option value="junior">Junior</option>
                        <option value="middle">Middle</option>
                        <option value="senior">Senior</option>
                        <option value="lead">Lead</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Название должности:</label>
                <input type="text" id="positionName" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Например: Backend-разработчик">
            </div>
            <button onclick="addCompanyConnection(<?= $profession_id ?>)" style="padding: 8px 15px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">Добавить связь</button>
        </div>
        
        <!-- Список существующих связей -->
        <div>
            <h5 style="margin-bottom: 10px;">Существующие связи:</h5>
            <?php if (count($profession_companies) > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Компания</th>
                            <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Должность</th>
                            <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Уровень</th>
                            <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($profession_companies as $connection): ?>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #ddd;"><?= htmlspecialchars($connection['company_name']) ?></td>
                            <td style="padding: 8px; border: 1px solid #ddd;"><?= htmlspecialchars($connection['position_name']) ?></td>
                            <td style="padding: 8px; border: 1px solid #ddd;"><?= $connection['experience_level'] ?></td>
                            <td style="padding: 8px; border: 1px solid #ddd;">
                                <a href="event_user/profession_connections.php?delete_company_connection=<?= $connection['id'] ?>&profession_id=<?= $profession_id ?>" 
                                   onclick="return confirm('Удалить связь?')" 
                                   style="color: #dc3545; text-decoration: none;">Удалить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #6c757d;">Нет связей с компаниями</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="institutions-tab" class="tab-content" style="display: none;">
        <h4 style="margin-bottom: 15px;">Связи с учебными заведениями</h4>
        
        <!-- Форма добавления новой связи -->
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <h5 style="margin-bottom: 10px;">Добавить связь с учебным заведением</h5>
            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Учебное заведение:</label>
                <select id="institutionSelect" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Выберите учебное заведение</option>
                    <?php foreach($institutions as $institution): ?>
                        <option value="<?= $institution['id'] ?>"><?= htmlspecialchars($institution['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Название программы:</label>
                    <input type="text" id="programName" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Например: Программная инженерия">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Продолжительность:</label>
                    <input type="text" id="programDuration" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Например: 4 года">
                </div>
            </div>
            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Стоимость:</label>
                <input type="text" id="programCost" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Например: 300,000 руб./год">
            </div>
            <button onclick="addInstitutionConnection(<?= $profession_id ?>)" style="padding: 8px 15px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;">Добавить связь</button>
        </div>
        
        <!-- Список существующих связей -->
        <div>
            <h5 style="margin-bottom: 10px;">Существующие связи:</h5>
            <?php if (count($profession_institutions) > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Учебное заведение</th>
                            <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Программа</th>
                            <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Продолжительность</th>
                            <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Стоимость</th>
                            <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($profession_institutions as $connection): ?>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #ddd;"><?= htmlspecialchars($connection['institution_name']) ?></td>
                            <td style="padding: 8px; border: 1px solid #ddd;"><?= htmlspecialchars($connection['program_name']) ?></td>
                            <td style="padding: 8px; border: 1px solid #ddd;"><?= $connection['duration'] ?></td>
                            <td style="padding: 8px; border: 1px solid #ddd;"><?= $connection['cost'] ?></td>
                            <td style="padding: 8px; border: 1px solid #ddd;">
                                <a href="event_user/profession_connections.php?delete_institution_connection=<?= $connection['id'] ?>&profession_id=<?= $profession_id ?>" 
                                   onclick="return confirm('Удалить связь?')" 
                                   style="color: #dc3545; text-decoration: none;">Удалить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #6c757d;">Нет связей с учебными заведениями</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Скрыть все вкладки
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    
    // Убрать активный класс со всех кнопок
    document.querySelectorAll('.tab-button').forEach(button => {
        button.style.borderBottom = 'none';
    });
    
    // Показать выбранную вкладку
    document.getElementById(tabName).style.display = 'block';
    
    // Добавить активный класс к выбранной кнопке
    event.target.style.borderBottom = '2px solid #3b82f6';
}
</script>