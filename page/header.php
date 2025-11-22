<!DOCTYPE html>
<html class="scroll-smooth overflow-x-hidden" lang="ru">

<head>
    <meta charset="utf-8">
    <title>Profio - Система профессиональной ориентации</title>
    <meta name="description" content="Profio - платформа для профориентации учащихся, тестирования и построения карьерного пути">
    <meta name="keywords" content="профориентация, тесты, профессии, карьера, образование">
    <meta name="robots" content="index, follow">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <link rel="icon" href="assets/images/icons/icon-favicon.svg" type="image/x-icon" sizes="16x16">
    <link rel="stylesheet" href="assets/styles/tailwind.min.css?v=5.0">
    <link rel="stylesheet" href="assets/styles/style.min.css?v=5.0">
    <link rel="stylesheet" href="assets/styles/admin.css?v=1.0">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chivo:wght@400;700;900&amp;family=Noto+Sans:wght@400;500;600;700;800&amp;display=swap">

    <style>
        .logo-square {
            background: var(--color-brands, #7364db) !important;
        }

        .bg-primary {
            background-color: var(--color-brands, #7364db) !important;
        }
    </style>
</head>

<body class="w-screen relative overflow-x-hidden min-h-screen bg-gray-100 scrollbar-hide dark:bg-[#000]">
    <div class="wrapper mx-auto text-gray-900 font-normal grid scrollbar-hide grid-cols-[257px,1fr] grid-rows-[auto,1fr]" id="layout">
        <!-- Боковое меню -->
        <aside class="bg-white row-span-2 border-r border-neutral relative flex flex-col justify-between p-[25px] dark:bg-dark-neutral-bg dark:border-dark-neutral-border">

            <div>
                <a class="mb-10 flex items-center gap-3" href="index.php">
                    <div class="logo-square bg-primary rounded flex items-center justify-center" style="width: 32px; height: 32px;">
                        <span class="text-white fw-bold small">P</span>
                    </div>
                    <span class="fw-bold text-dark dark:text-white fs-5">Profio</span>
                </a>

                <div class="pt-[35px] pb-[18px] space-y-2">
                    <!-- Главная -->
                    <a href="index.php" class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-dark-100 transition-colors">
                        <i class="bi bi-house-door text-gray-500 dark:text-gray-dark-500 text-lg"></i>
                        <span class="text-normal font-semibold text-gray-500 dark:text-gray-dark-500">Главная</span>
                    </a>

                    <!-- Тестирование -->
                    <a href="index.php?page=tests" class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-dark-100 transition-colors">
                        <i class="bi bi-pencil-square text-gray-500 dark:text-gray-dark-500 text-lg"></i>
                        <span class="text-normal font-semibold text-gray-500 dark:text-gray-dark-500">Тестирование</span>
                    </a>

                    <!-- Мои результаты -->
                    <a href="index.php?page=my-results" class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-dark-100 transition-colors ml-8">
                        <i class="bi bi-graph-up text-gray-500 dark:text-gray-dark-500 text-lg"></i>
                        <span class="text-normal font-semibold text-gray-500 dark:text-gray-dark-500">Мои результаты</span>
                    </a>

                    <!-- Профессии -->
                    <a href="index.php?page=professions" class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-dark-100 transition-colors">
                        <i class="bi bi-briefcase text-gray-500 dark:text-gray-dark-500 text-lg"></i>
                        <span class="text-normal font-semibold text-gray-500 dark:text-gray-dark-500">Профессии</span>
                    </a>

                    <!-- Мои рекомендации -->
                    <a href="index.php?page=recommendations" class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-dark-100 transition-colors ml-8">
                        <i class="bi bi-star text-gray-500 dark:text-gray-dark-500 text-lg"></i>
                        <span class="text-normal font-semibold text-gray-500 dark:text-gray-dark-500">Мои рекомендации</span>
                    </a>

                    <!-- План развития -->
                    <a href="index.php?page=plan" class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-dark-100 transition-colors">
                        <i class="bi bi-kanban text-gray-500 dark:text-gray-dark-500 text-lg"></i>
                        <span class="text-normal font-semibold text-gray-500 dark:text-gray-dark-500">План развития</span>
                    </a>

                    <!-- Отчеты -->
                    <a href="index.php?page=reports" class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-dark-100 transition-colors">
                        <i class="bi bi-card-checklist text-gray-500 dark:text-gray-dark-500 text-lg"></i>
                        <span class="text-normal font-semibold text-gray-500 dark:text-gray-dark-500">Отчеты</span>
                    </a>

                    <!-- В боковом меню после существующих пунктов добавить -->
                    <?php if ($_SESSION['user']['role'] === 'администратор'): ?>
                        <div class="pt-[35px] pb-[18px] space-y-2 border-t border-neutral dark:border-dark-neutral-border mt-4">
                            <p class="text-sm text-gray-500 dark:text-gray-dark-500 px-6 py-2">Администрирование</p>

                            <a href="index.php?page=admin-dashboard"
                                class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-dark-100 transition-colors">
                                <i class="bi bi-speedometer2 text-gray-500 dark:text-gray-dark-500 text-lg"></i>
                                <span class="text-normal font-semibold text-gray-500 dark:text-gray-dark-500">Админ-панель</span>
                            </a>

                            <a href="index.php?page=admin-users"
                                class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-dark-100 transition-colors ml-8">
                                <i class="bi bi-people text-gray-500 dark:text-gray-dark-500 text-lg"></i>
                                <span class="text-normal font-semibold text-gray-500 dark:text-gray-dark-500">Пользователи</span>
                            </a>

                            <a href="index.php?page=admin-tests"
                                class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-dark-100 transition-colors ml-8">
                                <i class="bi bi-clipboard-data text-gray-500 dark:text-gray-dark-500 text-lg"></i>
                                <span class="text-normal font-semibold text-gray-500 dark:text-gray-dark-500">Тесты</span>
                            </a>

                            <a href="index.php?page=admin-professions"
                                class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-dark-100 transition-colors ml-8">
                                <i class="bi bi-briefcase text-gray-500 dark:text-gray-dark-500 text-lg"></i>
                                <span class="text-normal font-semibold text-gray-500 dark:text-gray-dark-500">Профессии</span>
                            </a>

                            <a href="index.php?page=admin-companies"
                                class="sidemenu-item flex items-center gap-3 py-4 px-6 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-dark-100 transition-colors ml-8">
                                <i class="bi bi-building text-gray-500 dark:text-gray-dark-500 text-lg"></i>
                                <span class="text-normal font-semibold text-gray-500 dark:text-gray-dark-500">Компании & ВУЗы</span>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Управление темой -->
                    <div class="rounded-xl bg-neutral pt-4 flex items-center gap-5 mt-5 sidebar-control pr-[18px] pb-[13px] pl-[19px] dark:bg-dark-neutral-border">
                        <div class="flex items-center gap-3">
                            <i class="moon-icon" id="theme-toggle-dark-icon">
                                <img class="cursor-pointer" src="assets/images/icons/icon-moon.svg" alt="Темная тема">
                            </i>
                            <label class="flex items-center cursor-pointer" for="theme-toggle" id="toggle-theme-btn">
                                <div class="relative">
                                    <input class="sr-only peer" type="checkbox" name="" id="theme-toggle">
                                    <div class="block rounded-full w-[48px] h-[16px] bg-gray-300 peer-checked:bg-[#B2A7FF]"></div>
                                    <div class="dot dotS absolute rounded-full transition h-[24px] w-[24px] top-[-4px] left-[-4px] bg-[#B2A7FF] peer-checked:bg-color-brands"></div>
                                </div>
                            </label>
                            <i class="sun-icon" id="theme-toggle-light-icon">
                                <img class="cursor-pointer" src="assets/images/icons/icon-sun.svg" alt="Светлая тема">
                            </i>
                        </div>
                        <div class="bg-neutral-bg w-[2px] h-[30px] dark:bg-dark-neutral-bg"></div>
                        <div>
                            <img class="cursor-pointer" id="sidebar-expand" src="assets/images/icons/icon-maximize-3.svg" alt="Развернуть меню">
                        </div>
                    </div>
        </aside>

        <!-- Верхняя панель -->
        <header class="flex items-center justify-between flex-wrap bg-neutral-bg p-5 gap-5 md:py-6 md:pl-[25px] md:pr-[38px] lg:flex-nowrap dark:bg-dark-neutral-bg lg:gap-0">


            <!-- Навигационные кнопки -->
            <div class="flex items-center gap-4 order-last lg:order-first lg:ml-6">
                <a href="index.php?page=info#about"
                    class="px-4 py-2 text-gray-600 dark:text-gray-dark-500 hover:text-color-brands font-medium transition">
                    О нас
                </a>
                <a href="index.php?page=info#contacts"
                    class="px-4 py-2 text-gray-600 dark:text-gray-dark-500 hover:text-color-brands font-medium transition">
                    Контакты
                </a>
                <a href="index.php?page=info#help"
                    class="px-4 py-2 text-gray-600 dark:text-gray-dark-500 hover:text-color-brands font-medium transition">
                    Поддержка
                </a>
            </div>

            <!-- Профиль пользователя -->
            <div class="flex items-center order-2 user-noti gap-[30px] xl:gap-[48px] lg:order-3 lg:mr-0">
                <div class="dropdown dropdown-end">
                    <label class="cursor-pointer dropdown-label" tabindex="0">
                        <div class="flex items-center gap-3">
                            <span class="text-normal font-semibold text-gray-500 dark:text-gray-dark-500">
                                <?php echo $_SESSION['user']['login'] ?? 'Иван Петров'; ?>
                            </span>
                        </div>
                    </label>
                    <ul class="dropdown-content" tabindex="0">
                        <div class="relative menu rounded-box dropdown-shadow p-[25px] pb-[10px] bg-neutral-bg mt-[25px] md:mt-[40px] min-w-[237px] dark:text-gray-dark-500 dark:border-dark-neutral-border dark:bg-dark-neutral-bg">
                            <div class="border-solid border-b-8 border-x-transparent border-x-8 border-t-0 absolute w-[14px] top-[-7px] border-b-neutral-bg dark:border-b-dark-neutral-bg right-[18px]"></div>
                            <li class="text-gray-500 hover:text-gray-1100 hover:bg-gray-100 dark:text-gray-dark-500 dark:hover:text-gray-dark-1100 dark:hover:bg-gray-dark-100 rounded-lg group p-[15px] pl-[21px]">
                                <a class="flex items-center bg-transparent p-0 gap-[7px]" href="profile.php">
                                    <i class="w-4 h-4 grid place-items-center">
                                        <img class="group-hover:filter-black dark:group-hover:filter-white" src="assets/images/icons/icon-user.svg" alt="Профиль">
                                    </i>
                                    <span>Мой профиль</span>
                                </a>
                            </li>
                            <li class="text-gray-500 hover:text-gray-1100 hover:bg-gray-100 dark:text-gray-dark-500 dark:hover:text-gray-dark-1100 dark:hover:bg-gray-dark-100 rounded-lg group p-[15px] pl-[21px]">
                                <a class="flex items-center bg-transparent p-0 gap-[7px]" href="settings.php">
                                    <i class="w-4 h-4 grid place-items-center">
                                        <img class="group-hover:filter-black dark:group-hover:filter-white" src="assets/images/icons/icon-setting.svg" alt="Настройки">
                                    </i>
                                    <span>Настройки</span>
                                </a>
                            </li>
                            <div class="w-full bg-neutral h-[1px] my-[7px] dark:bg-dark-neutral-border"></div>
                            <li class="text-gray-500 hover:text-gray-1100 hover:bg-gray-100 dark:text-gray-dark-500 dark:hover:text-gray-dark-1100 dark:hover:bg-gray-dark-100 rounded-lg group p-[15px] pl-[21px]">
                                <a class="flex items-center bg-transparent p-0 gap-[7px]" href="../event_user/logout.php">
                                    <i class="w-4 h-4 grid place-items-center">
                                        <img class="group-hover:filter-black dark:group-hover:filter-white" src="assets/images/icons/icon-logout.svg" alt="Выход">
                                    </i>
                                    <span>Выйти</span>
                                </a>
                            </li>
                        </div>
                    </ul>
                </div>
            </div>
        </header>