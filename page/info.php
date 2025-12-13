<?php
$pageTitle = "Информация";
?>
<div class="max-w-4xl mx-auto space-y-8">
    <!-- Заголовок -->
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Информация</h1>
        <p class="text-gray-600 dark:text-gray-dark-500">Вся необходимая информация о системе Profio</p>
    </div>

    <!-- О системе -->
    <section id="about" class="bg-white dark:bg-dark-neutral-bg rounded-lg p-6 border border-neutral dark:border-dark-neutral-border scroll-mt-20">
        <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">О системе Profio</h2>
        
        <div class="prose dark:prose-invert max-w-none">
            <p class="text-gray-700 dark:text-gray-dark-300 mb-6">
                Profio — современная система профессиональной ориентации, разработанная для помощи учащимся 
                в выборе будущей профессии на основе научных методик и анализа способностей.
            </p>
            
            <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">Наши преимущества</h3>
            <div class="grid gap-4 md:grid-cols-2 mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                        <i class="bi bi-graph-up text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <span class="text-gray-700 dark:text-gray-dark-300">Научные методики тестирования</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                        <i class="bi bi-person-check text-green-600 dark:text-green-400"></i>
                    </div>
                    <span class="text-gray-700 dark:text-gray-dark-300">Персональные рекомендации</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                        <i class="bi bi-kanban text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <span class="text-gray-700 dark:text-gray-dark-300">Индивидуальные планы развития</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                        <i class="bi bi-bar-chart text-orange-600 dark:text-orange-400"></i>
                    </div>
                    <span class="text-gray-700 dark:text-gray-dark-300">Подробная аналитика результатов</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Контакты -->
    <section id="contacts" class="bg-white dark:bg-dark-neutral-bg rounded-lg p-6 border border-neutral dark:border-dark-neutral-border scroll-mt-20">
        <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Контакты</h2>
        
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <h3 class="text-lg font-medium mb-3 text-gray-900 dark:text-white">Контактная информация</h3>
                <div class="space-y-4">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white mb-1">Техническая поддержка</div>
                        <div class="text-gray-600 dark:text-gray-dark-500">8 800 555-35-35</div>
                        <div class="text-gray-600 dark:text-gray-dark-500">support@profio.ru</div>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white mb-1">Общие вопросы</div>
                        <div class="text-gray-600 dark:text-gray-dark-500">8 950 161-08-88</div>
                        <div class="text-gray-600 dark:text-gray-dark-500">info@profio.ru</div>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-medium mb-3 text-gray-900 dark:text-white">Реквизиты</h3>
                <div class="space-y-3 text-gray-600 dark:text-gray-dark-500">
                    <div class="flex items-start gap-3">
                        <i class="bi bi-geo-alt text-color-brands mt-1"></i>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Адрес</div>
                            <div>г. Нижний Новгород, ул. Студенческая, 6Б</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="bi bi-clock text-color-brands"></i>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Время работы</div>
                            <div>Пн-Вс: 9:00 - 18:30 (Без перерыва)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Поддержка -->
    <section id="help" class="bg-white dark:bg-dark-neutral-bg rounded-lg p-6 border border-neutral dark:border-dark-neutral-border scroll-mt-20">
        <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Поддержка</h2>
        
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <h3 class="text-lg font-medium mb-3 text-gray-900 dark:text-white">Служба поддержки</h3>
                <div class="space-y-4">
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-dark-100 rounded-lg">
                        <i class="bi bi-telephone text-color-brands"></i>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Телефон</div>
                            <div class="text-gray-600 dark:text-gray-dark-500">8 800 555-35-35</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-dark-100 rounded-lg">
                        <i class="bi bi-envelope text-color-brands"></i>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Email</div>
                            <div class="text-gray-600 dark:text-gray-dark-500">support@profio.ru</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-dark-100 rounded-lg">
                        <i class="bi bi-clock text-color-brands"></i>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Время работы</div>
                            <div class="text-gray-600 dark:text-gray-dark-500">Пн-Пт: 9:00-18:00</div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-medium mb-3 text-gray-900 dark:text-white">Частые вопросы</h3>
                <div class="space-y-3">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white mb-1">Как пройти тестирование?</div>
                        <div class="text-sm text-gray-600 dark:text-gray-dark-500">Перейдите в раздел "Тестирование" и выберите подходящий тест.</div>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white mb-1">Где посмотреть результаты?</div>
                        <div class="text-sm text-gray-600 dark:text-gray-dark-500">Все результаты доступны в разделе "Мои результаты".</div>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white mb-1">Как составить план развития?</div>
                        <div class="text-sm text-gray-600 dark:text-gray-dark-500">План развития можно создать индивидуально в разделе "План развития".</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Скрипт для плавной прокрутки -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Плавная прокрутка к якорям
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href.startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
});
</script>