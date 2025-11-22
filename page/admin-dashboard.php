<?php
// Проверка прав администратора
if ($_SESSION['user']['role'] !== 'администратор') {
    header("Location: index.php");
    exit();
}

// Получаем статистику для админки
$stats = $link->query("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'студент') as total_students,
        (SELECT COUNT(*) FROM users WHERE role = 'куратор') as total_curators,
        (SELECT COUNT(*) FROM test_results) as total_tests,
        (SELECT COUNT(*) FROM development_plans) as total_plans,
        (SELECT COUNT(*) FROM recommendations) as total_recommendations,
        (SELECT COUNT(*) FROM users WHERE is_active = 0) as blocked_users,
        (SELECT COUNT(*) FROM professions) as total_professions,
        (SELECT COUNT(*) FROM companies) as total_companies
")->fetch_assoc();

// Активность за сегодня
$today_activity = $link->query("
    SELECT COUNT(*) as count 
    FROM test_results 
    WHERE DATE(completed_at) = CURDATE()
")->fetch_assoc();

// Последние пользователи
$recent_users = $link->query("
    SELECT name, email, role, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <!-- Заголовок -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-2">
                    Административная панель
                </h2>
                <p class="text-gray-500">Обзор системы и управление контентом</p>
            </div>
            <div class="text-sm text-gray-500 bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-lg">
                <i class="bi bi-calendar3 mr-2"></i>
                <?= date('d.m.Y H:i') ?>
            </div>
        </div>

        <!-- Статистика - БЕЗ анимации -->
        <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-2 lg:grid-cols-4">
            <!-- Студенты -->
            <div class="stat-card">
                <div class="stat-icon bg-blue-100 text-blue-600">
                    <i class="bi bi-people text-xl"></i>
                </div>
                <div class="stat-number"><?= $stats['total_students'] ?></div>
                <div class="stat-label">Студентов</div>
            </div>

            <!-- Кураторы -->
            <div class="stat-card">
                <div class="stat-icon bg-green-100 text-green-600">
                    <i class="bi bi-person-badge text-xl"></i>
                </div>
                <div class="stat-number"><?= $stats['total_curators'] ?></div>
                <div class="stat-label">Кураторов</div>
            </div>

            <!-- Тесты -->
            <div class="stat-card">
                <div class="stat-icon bg-purple-100 text-purple-600">
                    <i class="bi bi-clipboard-data text-xl"></i>
                </div>
                <div class="stat-number"><?= $stats['total_tests'] ?></div>
                <div class="stat-label">Пройдено тестов</div>
            </div>

            <!-- Заблокированные -->
            <div class="stat-card">
                <div class="stat-icon bg-red-100 text-red-600">
                    <i class="bi bi-person-x text-xl"></i>
                </div>
                <div class="stat-number"><?= $stats['blocked_users'] ?></div>
                <div class="stat-label">Заблокировано</div>
            </div>
        </div>

        <!-- Быстрый доступ - ТОЛЬКО здесь анимация -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <a href="index.php?page=admin-users" class="admin-card">
                <div class="icon-container bg-blue-100">
                    <i class="bi bi-people-fill text-blue-600"></i>
                </div>
                <h3>Управление пользователями</h3>
                <p>Блокировка, сброс паролей, просмотр активности</p>
            </a>

            <a href="index.php?page=admin-tests" class="admin-card">
                <div class="icon-container bg-green-100">
                    <i class="bi bi-clipboard-data text-green-600"></i>
                </div>
                <h3>Управление тестами</h3>
                <p>Добавление и редактирование тестов и вопросов</p>
            </a>

            <a href="index.php?page=admin-professions" class="admin-card">
                <div class="icon-container bg-purple-100">
                    <i class="bi bi-briefcase-fill text-purple-600"></i>
                </div>
                <h3>Управление профессиями</h3>
                <p>Каталог профессий и рекомендаций</p>
            </a>

            <a href="index.php?page=admin-companies" class="admin-card">
                <div class="icon-container bg-orange-100">
                    <i class="bi bi-building text-orange-600"></i>
                </div>
                <h3>Компании & ВУЗы</h3>
                <p>База компаний и учебных заведений</p>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Последние пользователи -->
            <div class="stat-card">
                <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                    <i class="bi bi-person-plus text-blue-500"></i>
                    Последние регистрации
                </h3>
                <div class="space-y-3">
                    <?php while($user = $recent_users->fetch_assoc()): ?>
                        <div class="flex items-center justify-between py-2 border-b border-neutral dark:border-dark-neutral-border last:border-0">
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($user['name']) ?></p>
                                <p class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                            <span class="badge <?= $user['role'] === 'студент' ? 'badge-success' : ($user['role'] === 'куратор' ? 'badge-warning' : 'badge-primary') ?>">
                                <?= $user['role'] ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Статистика платформы -->
            <div class="stat-card">
                <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                    <i class="bi bi-graph-up text-green-500"></i>
                    Статистика платформы
                </h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span>Всего профессий:</span>
                        <span class="font-semibold text-lg"><?= $stats['total_professions'] ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Планов развития:</span>
                        <span class="font-semibold text-lg"><?= $stats['total_plans'] ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Рекомендаций:</span>
                        <span class="font-semibold text-lg"><?= $stats['total_recommendations'] ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Компаний в базе:</span>
                        <span class="font-semibold text-lg"><?= $stats['total_companies'] ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Активность сегодня:</span>
                        <span class="font-semibold text-lg text-green-500"><?= $today_activity['count'] ?> тестов</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>