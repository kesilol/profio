<?php
session_start();
require('../connect.php');

// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    die('Access denied');
}

if (!isset($_GET['id'])) {
    die('ID компании не указан');
}

$company_id = intval($_GET['id']);

// Получаем данные компании
$company_stmt = $link->prepare("SELECT * FROM companies WHERE id = ?");
$company_stmt->bind_param("i", $company_id);
$company_stmt->execute();
$company = $company_stmt->get_result()->fetch_assoc();

if (!$company) {
    die('Компания не найдена');
}

// Получаем связанные профессии
$professions_stmt = $link->prepare("
    SELECT p.*, pc.position_name, pc.experience_level
    FROM profession_companies pc
    LEFT JOIN professions p ON pc.profession_id = p.id
    WHERE pc.company_id = ?
    ORDER BY p.title ASC
");
$professions_stmt->bind_param("i", $company_id);
$professions_stmt->execute();
$professions_result = $professions_stmt->get_result();
$professions = [];
while($row = $professions_result->fetch_assoc()) {
    $professions[] = $row;
}
?>

<div class="company-connections">
    <h4 style="margin-bottom: 15px;">Профессии связанные с компанией "<?= htmlspecialchars($company['name']) ?>"</h4>
    
    <?php if (count($professions) > 0): ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Профессия</th>
                    <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Должность</th>
                    <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Уровень</th>
                    <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Категория</th>
                    <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($professions as $profession): ?>
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd;">
                        <strong><?= htmlspecialchars($profession['title']) ?></strong>
                        <br><small style="color: #666;"><?= htmlspecialchars($profession['description']) ?></small>
                    </td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($profession['position_name']) ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd;">
                        <span style="padding: 2px 6px; background: #e9ecef; border-radius: 3px; font-size: 0.8rem;">
                            <?= $profession['experience_level'] ?>
                        </span>
                    </td>
                    <td style="padding: 10px; border: 1px solid #ddd;">
                        <span style="padding: 2px 6px; background: #dee2e6; border-radius: 3px; font-size: 0.8rem;">
                            <?= $profession['category'] ?>
                        </span>
                    </td>
                    <td style="padding: 10px; border: 1px solid #ddd;">
                        <a href="index.php?page=profession-detail&id=<?= $profession['id'] ?>" 
                           style="color: #3b82f6; text-decoration: none; margin-right: 8px;">Просмотр</a>
                        <a href="event_user/profession_connections.php?delete_company_connection=<?= $profession['id'] ?>&company_id=<?= $company_id ?>" 
                           onclick="return confirm('Удалить связь?')" 
                           style="color: #dc3545; text-decoration: none;">Удалить</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-radius: 4px;">
            <strong>Всего профессий:</strong> <?= count($professions) ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #6c757d;">
            <p>Нет связанных профессий</p>
            <p><small>Добавьте связи через управление профессиями</small></p>
        </div>
    <?php endif; ?>
</div>