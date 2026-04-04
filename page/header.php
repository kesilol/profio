<!DOCTYPE html>
<html class="scroll-smooth overflow-x-hidden" lang="ru">

<head>
    <meta charset="utf-8">
    <title>Profio - Система профессиональной ориентации</title>
    <meta name="description" content="Profio - платформа для профориентации учащихся, тестирования и построения карьерного пути">
    <meta name="keywords" content="профориентация, тесты, профессии, карьера, образование">
    <meta name="robots" content="index, follow">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <link rel="icon" href="assets/images/icons/favicon.svg" type="image/svg+xml" sizes="16x16">
    <link rel="stylesheet" href="assets/styles/tailwind.min.css?v=5.0">
    <link rel="stylesheet" href="assets/styles/style.min.css?v=5.0">
    <link rel="stylesheet" href="assets/styles/admin.css?v=1.0">
    <!-- Мобильные стили -->
    <link rel="stylesheet" href="assets/styles/mobile.css?v=1.0">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chivo:wght@400;700;900&amp;family=Noto+Sans:wght@400;500;600;700;800&amp;display=swap">
    <link rel="stylesheet" href="assets/styles/mobile.css">

    <style>
        .logo-square {
            background: var(--color-brands, #7364db) !important;
        }

        .bg-primary {
            background-color: var(--color-brands, #7364db) !important;
        }
        
        /* Стиль для кнопки обратной связи */
        .feedback-link {
            position: relative;
        }
        
        .feedback-link .feedback-badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 5px;
            border-radius: 9999px;
            min-width: 16px;
            text-align: center;
        }
    </style>
</head>

<body class="w-screen relative overflow-x-hidden min-h-screen bg-gray-100 scrollbar-hide">
    <div class="wrapper mx-auto text-gray-900 font-normal grid scrollbar-hide grid-cols-[257px,1fr] grid-rows-[auto,1fr]" id="layout">
        <!-- Боковое меню -->
        <aside class="bg-white row-span-2 border-r border-neutral relative flex flex-col justify-between p-[25px]" id="sidebar">

            <div>
                <a class="mb-10 flex items-center gap-3" href="index.php">
                    <div class="logo-square bg-primary rounded flex items-center justify-center" style="width: 32px; height: 32px;">
                        <span class="text-white fw-bold small">P</span>
                    </div>
                    <span class="fw-bold text-dark fs-5">Profio</span>
                </a>

                <div class="pt-[35px] pb-[18px] space-y-2">
                    <!-- Главная -->
                    <a href="index.php" class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 transition-colors">
                        <i class="bi bi-house-door text-gray-500 text-lg"></i>
                        <span class="text-normal font-semibold text-gray-500">Главная</span>
                    </a>

                    <!-- Тестирование -->
                    <a href="index.php?page=tests" class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 transition-colors">
                        <i class="bi bi-pencil-square text-gray-500 text-lg"></i>
                        <span class="text-normal font-semibold text-gray-500">Тестирование</span>
                    </a>

                    <!-- Мои результаты -->
                    <a href="index.php?page=my-results" class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 transition-colors ml-8">
                        <i class="bi bi-graph-up text-gray-500 text-lg"></i>
                        <span class="text-normal font-semibold text-gray-500">Мои результаты</span>
                    </a>

                    <!-- Профессии -->
                    <a href="index.php?page=professions" class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 transition-colors">
                        <i class="bi bi-briefcase text-gray-500 text-lg"></i>
                        <span class="text-normal font-semibold text-gray-500">Профессии</span>
                    </a>

                    <!-- Мои рекомендации -->
                    <a href="index.php?page=recommendations" class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 transition-colors ml-8">
                        <i class="bi bi-star text-gray-500 text-lg"></i>
                        <span class="text-normal font-semibold text-gray-500">Мои рекомендации</span>
                    </a>

                    <!-- План развития -->
                    <a href="index.php?page=plan" class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 transition-colors">
                        <i class="bi bi-kanban text-gray-500 text-lg"></i>
                        <span class="text-normal font-semibold text-gray-500">План развития</span>
                    </a>

                    <!-- Отчеты -->
                    <a href="index.php?page=reports" class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 transition-colors">
                        <i class="bi bi-card-checklist text-gray-500 text-lg"></i>
                        <span class="text-normal font-semibold text-gray-500">Отчеты</span>
                    </a>

                    <!-- В боковом меню после существующих пунктов добавить -->
                    <?php if ($_SESSION['user']['role'] === 'администратор'): ?>
                        <div class="pt-[35px] pb-[18px] space-y-2 border-t border-neutral mt-4">
                            <p class="text-sm text-gray-500 px-6 py-2">Администрирование</p>

                            <a href="index.php?page=admin-dashboard"
                                class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 transition-colors">
                                <i class="bi bi-speedometer2 text-gray-500 text-lg"></i>
                                <span class="text-normal font-semibold text-gray-500">Админ-панель</span>
                            </a>

                            <a href="index.php?page=admin-users"
                                class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 transition-colors ml-8">
                                <i class="bi bi-people text-gray-500 text-lg"></i>
                                <span class="text-normal font-semibold text-gray-500">Пользователи</span>
                            </a>

                            <a href="index.php?page=admin-tests"
                                class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 transition-colors ml-8">
                                <i class="bi bi-clipboard-data text-gray-500 text-lg"></i>
                                <span class="text-normal font-semibold text-gray-500">Тесты</span>
                            </a>

                            <a href="index.php?page=admin-professions"
                                class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 transition-colors ml-8">
                                <i class="bi bi-briefcase text-gray-500 text-lg"></i>
                                <span class="text-normal font-semibold text-gray-500">Профессии</span>
                            </a>

                            <a href="index.php?page=admin-companies"
                                class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 transition-colors ml-8">
                                <i class="bi bi-building text-gray-500 text-lg"></i>
                                <span class="text-normal font-semibold text-gray-500">Компании & ВУЗы</span>
                            </a>
                            
                            <!-- Ссылка на обратную связь в админке -->
                            <a href="index.php?page=admin-feedback"
                                class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 transition-colors ml-8">
                                <i class="bi bi-chat-dots text-gray-500 text-lg"></i>
                                <span class="text-normal font-semibold text-gray-500">Обратная связь</span>
                                <?php 
                                // Считаем новые сообщения для админа
                                if ($_SESSION['user']['role'] === 'администратор') {
                                    $new_feedback_count = $link->query("SELECT COUNT(*) as count FROM feedback WHERE status = 'new'")->fetch_assoc()['count'] ?? 0;
                                    if ($new_feedback_count > 0): 
                                ?>
                                    <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $new_feedback_count ?></span>
                                <?php 
                                    endif;
                                }
                                ?>
                            </a>
                            
                            <!-- Ссылка на журнал действий -->
                            <a href="index.php?page=admin-logs"
                                class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 transition-colors ml-8">
                                <i class="bi bi-journal-text text-gray-500 text-lg"></i>
                                <span class="text-normal font-semibold text-gray-500">Журнал действий</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

        <!-- Верхняя панель -->
        <header class="flex items-center justify-between flex-wrap bg-neutral-bg p-5 gap-5 md:py-6 md:pl-[25px] md:pr-[38px] lg:flex-nowrap lg:gap-0">

            <!-- Гамбургер меню для мобильных -->
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <!-- Логотип для мобильных -->
            <div class="mobile-logo lg:hidden">
                <a class="flex items-center gap-3" href="index.php">
                    <div class="logo-square bg-primary rounded flex items-center justify-center" style="width: 32px; height: 32px;">
                        <span class="text-white fw-bold small">P</span>
                    </div>
                    <span class="fw-bold text-dark fs-5">Profio</span>
                </a>
            </div>

            <!-- Навигационные кнопки -->
            <div class="desktop-nav flex items-center gap-4 order-last lg:order-first lg:ml-6 mobile-nav-buttons">
                <a href="index.php?page=info#about"
                    class="px-4 py-2 text-gray-600 hover:text-color-brands font-medium transition">
                    О нас
                </a>
                <a href="index.php?page=info#contacts"
                    class="px-4 py-2 text-gray-600 hover:text-color-brands font-medium transition">
                    Контакты
                </a>
                <!-- Кнопка обратной связи -->
                <a href="index.php?page=info#feedback"
                    class="feedback-link px-4 py-2 bg-color-brands text-white font-medium transition rounded-lg hover:bg-opacity-90 flex items-center gap-2">
                    <i class="bi bi-chat-dots"></i>
                    <span>Обратная связь</span>
                </a>
            </div>

            <!-- Профиль пользователя -->
            <div class="flex items-center order-2 user-noti gap-[30px] xl:gap-[48px] lg:order-3 lg:mr-0">
                
                <div class="dropdown dropdown-end">
                    <label class="cursor-pointer dropdown-label" tabindex="0">
                        <div class="flex items-center gap-3">
                            <span class="text-normal font-semibold text-gray-500">
                                <?php echo $_SESSION['user']['login'] ?? 'Вход'; ?>
                            </span>
                        </div>
                    </label>
                    <ul class="dropdown-content" tabindex="0">
                        <div class="relative menu rounded-box dropdown-shadow p-[25px] pb-[10px] bg-neutral-bg mt-[25px] md:mt-[40px] min-w-[237px]">
                            <div class="border-solid border-b-8 border-x-transparent border-x-8 border-t-0 absolute w-[14px] top-[-7px] border-b-neutral-bg right-[18px]"></div>
                            <li class="text-gray-500 hover:text-gray-1100 hover:bg-gray-100 rounded-lg group p-[15px] pl-[21px]">
                                <a class="flex items-center bg-transparent p-0 gap-[7px]" href="profile.php">
                                    <i class="w-4 h-4 grid place-items-center">
                                        <img class="group-hover:filter-black" src="assets/images/icons/icon-user.svg" alt="Профиль">
                                    </i>
                                    <span>Мой профиль</span>
                                </a>
                            </li>
                            <li class="text-gray-500 hover:text-gray-1100 hover:bg-gray-100 rounded-lg group p-[15px] pl-[21px]">
                                <a class="flex items-center bg-transparent p-0 gap-[7px]" href="settings.php">
                                    <i class="w-4 h-4 grid place-items-center">
                                        <img class="group-hover:filter-black" src="assets/images/icons/icon-setting.svg" alt="Настройки">
                                    </i>
                                    <span>Настройки</span>
                                </a>
                            </li>
                            <div class="w-full bg-neutral h-[1px] my-[7px]"></div>
                            <li class="text-gray-500 hover:text-gray-1100 hover:bg-gray-100 rounded-lg group p-[15px] pl-[21px]">
                                <a class="flex items-center bg-transparent p-0 gap-[7px]" href="event_user/logout.php">
                                    <i class="w-4 h-4 grid place-items-center">
                                        <img class="group-hover:filter-black" src="assets/images/icons/icon-logout.svg" alt="Выход">
                                    </i>
                                    <span>Выйти</span>
                                </a>
                            </li>
                        </div>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Оверлей для мобильного меню -->
        <div class="mobile-overlay" id="mobileOverlay"></div>
        
        <script>
        // Мобильное меню
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');
        
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                mobileOverlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            });
        }
        
        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                mobileOverlay.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
        </script>
</body>
</html>