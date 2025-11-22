<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=sign-in");
    exit();
}

// Подключение обработчиков
require_once('event_user/professions_handler.php');
require_once('event_user/profession_detail_handler.php');
require_once('event_user/education_handler.php');
require_once('event_user/companies_handler.php');

// Получаем ID профессии
$profession_id = $_GET['id'] ?? 0;

// Получаем информацию о профессии с деталями
$profession = getProfessionDetail($link, $profession_id);

if (!$profession) {
    echo "<script>alert('Профессия не найдена!'); window.location.href = 'index.php?page=professions';</script>";
    exit();
}

// Получаем похожие профессии
$similar_professions = getSimilarProfessions($link, $profession_id, $profession['category']);

// Получаем учебные заведения для профессии
$institutions = getInstitutionsForProfession($link, $profession_id);

// Получаем компании для профессии
$companies = getCompaniesForProfession($link, $profession_id);
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <!-- Хлебные крошки -->
        <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
            <div class="flex items-center gap-x-1">
                <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                <a class="capitalize" href="index.php">Главная</a>
            </div>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <a class="capitalize text-color-brands" href="index.php?page=professions">Каталог профессий</a>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <span class="capitalize text-color-brands"><?php echo htmlspecialchars($profession['title']); ?></span>
        </div>

        <!-- Основной блок профессии -->
        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 flex flex-col gap-4">
            <div class="flex flex-col md:flex-row gap-6 mb-6">
                <!-- Левая картинка -->
                <div class="flex-shrink-0 w-[180px] h-[250px]"> 
                    <div class="w-full h-full overflow-hidden rounded-lg">
                        <?php if (!empty($profession['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($profession['image_url']); ?>"
                                 alt="<?php echo htmlspecialchars($profession['title']); ?>"
                                 class="w-full h-full object-cover object-center">
                        <?php else: ?>
                            <div class="w-full h-[150px] grid place-items-center bg-gray-100 dark:bg-gray-dark-100 rounded-lg">
                                <i class="bi <?php echo PROFESSION_ICONS_DETAIL[$profession['category']] ?? 'bi-briefcase'; ?> text-4xl text-gray-400"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Правая часть с текстом -->
                <div class="flex-1 flex flex-col gap-2 text-left justify-center">
                    <h2 class="text-2xl font-bold text-gray-1100 dark:text-gray-dark-1100">
                        <?php echo htmlspecialchars($profession['title']); ?>
                    </h2>

                    <div class="flex flex-wrap gap-2 text-xs mb-1">
                        <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-dark-100 text-gray-800 dark:text-gray-dark-800">
                            <?php echo ucfirst($profession['category']); ?>
                        </span>
                        <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-dark-100 text-gray-800 dark:text-gray-dark-800">
                            <?php echo ucfirst($profession['demand_level']); ?> спрос
                        </span>
                        <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-dark-100 text-gray-800 dark:text-gray-dark-800">
                            <?php echo $profession['education_level']; ?>
                        </span>
                    </div>

                    <p class="text-gray-500 dark:text-gray-dark-500 text-sm">
                        <?php echo htmlspecialchars($profession['description']); ?>
                    </p>

                    <div class="mt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Зарплата</p>
                        <p class="text-lg font-bold text-gray-1100 dark:text-gray-dark-1100">
                            <?php echo $profession['salary_range']; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Нижний блок: обязанности, карьерный рост и прочее -->
            <div class="flex flex-col gap-4 text-left">
                <?php if (!empty($profession['responsibilities'])): ?>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Обязанности:</p>
                        <p class="text-gray-1100 dark:text-gray-dark-1100"><?php echo $profession['responsibilities']; ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($profession['career_growth'])): ?>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Карьерный рост:</p>
                        <p class="text-gray-1100 dark:text-gray-dark-1100"><?php echo $profession['career_growth']; ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($profession['employment_prospects'])): ?>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Перспективы трудоустройства:</p>
                        <p class="text-gray-1100 dark:text-gray-dark-1100"><?php echo $profession['employment_prospects']; ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($profession['related_courses'])): ?>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-dark-500 mb-1">Связанные курсы:</p>
                        <p class="text-gray-1100 dark:text-gray-dark-1100"><?php echo $profession['related_courses']; ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Кнопка назад -->
            <div class="pt-4 mt-4">
                <a href="index.php?page=professions" class="btn border border-neutral text-gray-500 dark:border-dark-neutral-border dark:text-gray-dark-500 px-6 text-center flex items-center justify-center">
                    Назад к списку профессий
                </a>
            </div>
        </div>

        <!-- Секция учебных заведений -->
        <section class="mt-8">
            <h3 class="text-2xl font-bold text-gray-1100 dark:text-gray-dark-1100 mb-6">
                <i class="bi bi-mortarboard text-color-brands"></i> Где учиться?
            </h3>
            
            <?php if ($institutions && $institutions->num_rows > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php while ($institution = $institutions->fetch_assoc()): ?>
                        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-start gap-4 mb-4">
                                <?php if (!empty($institution['image_url'])): ?>
                                    <img src="<?php echo $institution['image_url']; ?>" 
                                         alt="<?php echo $institution['name']; ?>" 
                                         class="w-16 h-16 rounded-lg object-cover">
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded-lg bg-gray-100 dark:bg-gray-dark-100 grid place-items-center">
                                        <i class="bi bi-building text-2xl text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100 text-lg">
                                        <?php echo $institution['name']; ?>
                                    </h4>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs px-2 py-1 rounded-full 
                                            <?php echo $institution['type'] === 'ВУЗ' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'; ?>">
                                            <i class="bi <?php echo $institution['type'] === 'ВУЗ' ? 'bi-building' : 'bi-house-door'; ?> me-1"></i>
                                            <?php echo $institution['type']; ?>
                                        </span>
                                        <span class="text-sm text-gray-500 dark:text-gray-dark-500">
                                            <i class="bi bi-geo-alt me-1"></i><?php echo $institution['location']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($institution['program_name'])): ?>
                                <div class="mb-3">
                                    <p class="text-sm text-gray-500 dark:text-gray-dark-500">
                                        <i class="bi bi-journal-text me-1"></i>Программа:
                                    </p>
                                    <p class="font-semibold text-gray-1100 dark:text-gray-dark-1100">
                                        <?php echo $institution['program_name']; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <?php if (!empty($institution['duration'])): ?>
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-dark-500">
                                            <i class="bi bi-clock me-1"></i>Длительность:
                                        </p>
                                        <p class="font-semibold"><?php echo $institution['duration']; ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($institution['cost'])): ?>
                                    <div>
                                        <p class="text-gray-500 dark:text-gray-dark-500">
                                            <i class="bi bi-currency-dollar me-1"></i>Стоимость:
                                        </p>
                                        <p class="font-semibold"><?php echo $institution['cost']; ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($institution['website'])): ?>
                                <div class="mt-4 pt-4 border-t border-neutral dark:border-dark-neutral-border">
                                    <a href="<?php echo $institution['website']; ?>" 
                                       target="_blank" 
                                       class="text-color-brands hover:underline text-sm flex items-center gap-2">
                                        <i class="bi bi-link-45deg"></i>
                                        Официальный сайт
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-8 text-center">
                    <i class="bi bi-building text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-dark-500">
                        Информация о учебных заведениях обновляется
                    </p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Секция компаний -->
        <section class="mt-8">
            <h3 class="text-2xl font-bold text-gray-1100 dark:text-gray-dark-1100 mb-6">
                <i class="bi bi-briefcase text-color-brands"></i> Где работать?
            </h3>
            
            <?php if ($companies && $companies->num_rows > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while ($company = $companies->fetch_assoc()): ?>
                        <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-center gap-4 mb-4">
                                <?php if (!empty($company['image_url'])): ?>
                                    <img src="<?php echo $company['image_url']; ?>" 
                                         alt="<?php echo $company['name']; ?>" 
                                         class="w-12 h-12 rounded-lg object-cover">
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-dark-100 grid place-items-center">
                                        <i class="bi bi-building text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div>
                                    <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100">
                                        <?php echo $company['name']; ?>
                                    </h4>
                                    <?php if (!empty($company['industry'])): ?>
                                        <p class="text-sm text-gray-500 dark:text-gray-dark-500">
                                            <i class="bi bi-diagram-3 me-1"></i><?php echo $company['industry']; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($company['position_name'])): ?>
                                <div class="mb-3">
                                    <p class="text-sm text-gray-500 dark:text-gray-dark-500">
                                        <i class="bi bi-person-badge me-1"></i>Должность:
                                    </p>
                                    <p class="font-semibold text-gray-1100 dark:text-gray-dark-1100">
                                        <?php echo $company['position_name']; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($company['experience_level'])): ?>
                                <div class="mb-3">
                                    <p class="text-sm text-gray-500 dark:text-gray-dark-500">
                                        <i class="bi bi-graph-up me-1"></i>Уровень:
                                    </p>
                                    <span class="text-xs px-2 py-1 rounded-full 
                                        <?php echo $company['experience_level'] === 'senior' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 
                                              ($company['experience_level'] === 'middle' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                              ($company['experience_level'] === 'junior' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                              'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200')); ?>">
                                        <i class="bi <?php 
                                            echo $company['experience_level'] === 'senior' ? 'bi-star-fill' : 
                                                 ($company['experience_level'] === 'middle' ? 'bi-star-half' : 
                                                 ($company['experience_level'] === 'junior' ? 'bi-star' : 'bi-person')); 
                                        ?> me-1"></i>
                                        <?php echo $company['experience_level']; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-sm text-gray-500 dark:text-gray-dark-500 space-y-1">
                                <?php if (!empty($company['location'])): ?>
                                    <p><i class="bi bi-geo-alt me-1"></i><?php echo $company['location']; ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['employee_count'])): ?>
                                    <p><i class="bi bi-people me-1"></i><?php echo $company['employee_count']; ?> сотрудников</p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($company['website'])): ?>
                                <div class="mt-4 pt-4 border-t border-neutral dark:border-dark-neutral-border">
                                    <a href="<?php echo $company['website']; ?>" 
                                       target="_blank" 
                                       class="text-color-brands hover:underline text-sm flex items-center gap-2">
                                        <i class="bi bi-link-45deg"></i>
                                        Сайт компании
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-8 text-center">
                    <i class="bi bi-briefcase text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-dark-500">
                        Информация о компаниях обновляется
                    </p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Похожие профессии -->
        <?php if ($similar_professions->num_rows > 0): ?>
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                    <i class="bi bi-diagram-3 text-color-brands"></i> Похожие профессии
                </h3>
                <div class="space-y-3">
                    <?php while($similar = $similar_professions->fetch_assoc()): ?>
                        <a href="index.php?page=profession-detail&id=<?php echo $similar['id']; ?>" class="flex items-center gap-3 p-3 rounded-lg border border-neutral dark:border-dark-neutral-border hover:bg-gray-50 dark:hover:bg-gray-dark-50 transition-colors">
                            <div class="w-10 h-10 rounded-lg grid place-items-center bg-gray-100 dark:bg-gray-dark-100 flex-shrink-0">
                                <i class="bi <?php echo PROFESSION_ICONS_DETAIL[$similar['category']] ?? 'bi-briefcase'; ?> text-base"></i>
                            </div>
                            <span class="text-sm text-gray-1100 dark:text-gray-dark-1100 break-words flex-1">
                                <?php echo $similar['title']; ?>
                            </span>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>