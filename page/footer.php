</div> <!-- закрываем content-wrapper -->
</main>

<!-- Футер -->
<footer class="col-start-2 row-start-3 mt-auto">
    <div class="border-t border-neutral dark:border-dark-neutral-border">
        <div class="max-w-7xl mx-auto px-5 md:px-6 py-4">
            <div class="flex flex-col md:flex-row justify-between items-center gap-3 text-sm">
                <!-- Копирайт -->
                <div class="text-gray-500 dark:text-gray-dark-500">
                    &copy; 2024 Profio — Система профессиональной ориентации
                </div>

                <!-- Ссылки -->
                <div class="flex gap-4 text-gray-500 dark:text-gray-dark-500">
                    <a href="index.php?page=info#help" class="hover:text-color-brands transition">Поддержка</a>
                    <a href="index.php?page=info#contacts" class="hover:text-color-brands transition">Контакты</a>
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