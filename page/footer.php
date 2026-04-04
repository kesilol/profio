</div> <!-- закрываем content-wrapper -->
</main>

<!-- Футер -->
<footer class="col-start-2 row-start-3 mt-auto">
    <div class="border-t border-neutral dark:border-dark-neutral-border">
        <div class="max-w-7xl mx-auto px-5 md:px-6 py-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                <!-- Копирайт и информация -->
                <div class="text-gray-500 dark:text-gray-dark-500 text-sm">
                    <p>&copy; 2024 Profio — Система профессиональной ориентации</p>
                    <p class="mt-1">Помогаем выбрать путь к успешной карьере</p>
                </div>

                <!-- Служба заботы -->
                <div class="text-gray-500 dark:text-gray-dark-500">
                    <h4 class="text-gray-700 dark:text-gray-dark-700 font-semibold mb-2 text-sm">Служба заботы</h4>
                    <div class="space-y-2 text-sm">
                        <p class="flex items-center gap-2">
                            <i class="bi bi-telephone text-color-brands"></i>
                            <span>8-800-2000-122</span>
                            <span class="text-xs text-gray-400">(Единый детский телефон доверия)</span>
                        </p>
                        <p class="flex items-center gap-2">
                            <i class="bi bi-telephone text-color-brands"></i>
                            <span>8 (831) 215-04-66</span>
                            <span class="text-xs text-gray-400">(Телефон экстренной психологической помощи)</span>
                        </p>
                        <p class="flex items-center gap-2">
                            <i class="bi bi-envelope text-color-brands"></i>
                            <a href="mailto:zabota@myrosmol.ru" class="hover:text-color-brands transition">zabota@myrosmol.ru</a>
                        </p>
                        <p class="flex items-start gap-2 text-xs text-gray-400">
                            <i class="bi bi-info-circle text-color-brands mt-0.5"></i>
                            <span>Звонок бесплатный, анонимный, конфиденциальный</span>
                        </p>
                    </div>
                </div>

                <!-- Ссылки -->
                <div class="text-gray-500 dark:text-gray-dark-500">
                    <h4 class="text-gray-700 dark:text-gray-dark-700 font-semibold mb-2 text-sm">Полезные ссылки</h4>
                    <div class="flex flex-col space-y-1 text-sm">
                        <a href="index.php?page=info#about" class="hover:text-color-brands transition flex items-center gap-2">
                            <i class="bi bi-info-circle"></i> О нас
                        </a>
                        <a href="index.php?page=info#feedback" class="hover:text-color-brands transition flex items-center gap-2">
                            <i class="bi bi-question-circle"></i> Помощь и поддержка
                        </a>
                        <a href="index.php?page=info#contacts" class="hover:text-color-brands transition flex items-center gap-2">
                            <i class="bi bi-envelope-paper"></i> Контакты
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Нижняя линия с дополнительной информацией -->
            <div class="border-t border-neutral dark:border-dark-neutral-border mt-4 pt-4 text-center">
                <div class="flex flex-col md:flex-row justify-center items-center gap-3 text-xs text-gray-400 dark:text-gray-dark-500">
                    <span>Проект реализуется при поддержке Министерства образования Нижегородской области</span>
                    <span class="hidden md:inline">•</span>
                    <span>© 2024 Все права защищены</span>
                </div>
            </div>
        </div>
    </div>
</footer>
</div>

<script type="text/javascript" src="assets/scripts/vendors/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="assets/scripts/app.js"></script>
<script src="assets/scripts/mobile-fixes.js"></script>

<!-- Мобильный скрипт -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const mobileOverlay = document.getElementById('mobileOverlay');
    
    if (mobileMenuBtn && sidebar && mobileOverlay) {
        // Открытие/закрытие меню
        function toggleMobileMenu() {
            const isOpen = mobileMenuBtn.classList.contains('mobile-open');
            mobileMenuBtn.classList.toggle('mobile-open');
            sidebar.classList.toggle('mobile-open');
            mobileOverlay.classList.toggle('active');
            
            if (!isOpen) {
                document.body.classList.add('mobile-menu-open');
            } else {
                document.body.classList.remove('mobile-menu-open');
            }
        }
        
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        mobileOverlay.addEventListener('click', toggleMobileMenu);
        
        // Закрытие меню при клике на ссылку в сайдбаре
        const sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 1024) {
                    toggleMobileMenu();
                }
            });
        });
        
        // Закрытие меню при изменении размера окна
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1024) {
                mobileMenuBtn.classList.remove('mobile-open');
                sidebar.classList.remove('mobile-open');
                mobileOverlay.classList.remove('active');
                document.body.classList.remove('mobile-menu-open');
            }
        });
        
        // Закрытие меню при нажатии ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
                toggleMobileMenu();
            }
        });
    }
    
    // Адаптация для существующего функционала темы
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('change', function() {
            // Ваш существующий код для переключения темы
        });
    }
});

// Дополнительный скрипт для мобильной адаптации dropdown
document.addEventListener('DOMContentLoaded', function() {
    // Закрытие dropdown при клике на ссылку внутри него
    document.querySelectorAll('.dropdown-content a').forEach(link => {
        link.addEventListener('click', function() {
            const dropdown = this.closest('.dropdown');
            if (dropdown && window.innerWidth <= 768) {
                dropdown.removeAttribute('open');
                const overlay = dropdown.querySelector('.dropdown-overlay');
                if (overlay) overlay.classList.remove('active');
            }
        });
    });
});
// Дополнительный скрипт для управления индикаторами таблиц
document.addEventListener('DOMContentLoaded', function() {
    // Функция для обновления индикаторов при переходе между страницами
    function updateTableIndicators() {
        const indicators = document.querySelectorAll('.scroll-indicator');
        indicators.forEach(indicator => {
            // Удаляем дубликаты
            if (!indicator.isConnected || indicator.parentNode.querySelectorAll('.scroll-indicator').length > 1) {
                indicator.remove();
            }
        });
    }
    
    // Обновляем при загрузке и после навигации
    updateTableIndicators();
    
    // Если используется AJAX навигация, добавьте здесь обработчики
    // Например, для Intersection Observer или MutationObserver
    
    // Закрытие dropdown при клике на ссылку внутри него
    document.querySelectorAll('.dropdown-content a').forEach(link => {
        link.addEventListener('click', function() {
            const dropdown = this.closest('.dropdown');
            if (dropdown && window.innerWidth <= 768) {
                dropdown.removeAttribute('open');
                const overlay = dropdown.querySelector('.dropdown-overlay');
                if (overlay) overlay.classList.remove('active');
            }
        });
    });
});

// Улучшенная обработка изменения темы для таблиц
function updateTableStylesForTheme() {
    const isDark = document.documentElement.classList.contains('dark');
    const containers = document.querySelectorAll('.stat-card .overflow-x-auto');
    
    containers.forEach(container => {
        if (isDark) {
            container.style.background = '#1f2937';
        } else {
            container.style.background = 'white';
        }
    });
}

// Слушаем изменения темы
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.attributeName === 'class') {
            updateTableStylesForTheme();
        }
    });
});

observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class']
});
</script>
</body>

</html>