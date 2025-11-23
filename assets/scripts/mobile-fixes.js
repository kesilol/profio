// Мобильные исправления для админ-панелей
document.addEventListener('DOMContentLoaded', function() {
    let scrollIndicators = [];
    
    // Исправление для таблиц
    function fixAdminTables() {
        // Удаляем старые индикаторы перед созданием новых
        removeScrollIndicators();
        
        const tables = document.querySelectorAll('.stat-card table');
        tables.forEach((table, index) => {
            const container = table.closest('.overflow-x-auto');
            if (container) {
                // Убедимся, что контейнер имеет правильный фон
                container.style.background = 'white';
                if (document.documentElement.classList.contains('dark')) {
                    container.style.background = '#1f2937';
                }
                
                // Добавляем индикатор скролла только на мобильных
                if (window.innerWidth <= 768 && !container.nextElementSibling?.classList.contains('scroll-indicator')) {
                    const indicator = document.createElement('div');
                    indicator.className = 'scroll-indicator';
                    indicator.textContent = '← прокрутите для просмотра всей таблицы →';
                    indicator.setAttribute('data-table-index', index);
                    
                    container.parentNode.insertBefore(indicator, container.nextSibling);
                    scrollIndicators.push(indicator);
                }
            }
            
            // Оптимизируем кнопки в таблицах
            const buttons = table.querySelectorAll('.btn');
            buttons.forEach(btn => {
                if (window.innerWidth <= 768) {
                    btn.style.fontSize = '11px';
                    btn.style.padding = '4px 8px';
                    btn.style.margin = '2px';
                    btn.style.minWidth = 'auto';
                } else {
                    // Сбрасываем стили на десктопе
                    btn.style.fontSize = '';
                    btn.style.padding = '';
                    btn.style.margin = '';
                    btn.style.minWidth = '';
                }
            });
        });
    }
    
    // Функция для удаления индикаторов
    function removeScrollIndicators() {
        scrollIndicators.forEach(indicator => {
            if (indicator && indicator.parentNode) {
                indicator.parentNode.removeChild(indicator);
            }
        });
        scrollIndicators = [];
        
        // Также удаляем все существующие индикаторы
        const existingIndicators = document.querySelectorAll('.scroll-indicator');
        existingIndicators.forEach(indicator => {
            if (indicator.parentNode) {
                indicator.parentNode.removeChild(indicator);
            }
        });
    }

    // Исправление для выпадающих окон
    function fixDropdowns() {
        const dropdowns = document.querySelectorAll('.dropdown');
        
        dropdowns.forEach(dropdown => {
            const label = dropdown.querySelector('.dropdown-label');
            const content = dropdown.querySelector('.dropdown-content');
            
            if (label && content) {
                // Создаем оверлей для мобильных
                let overlay = dropdown.querySelector('.dropdown-overlay');
                if (!overlay && window.innerWidth <= 768) {
                    overlay = document.createElement('div');
                    overlay.className = 'dropdown-overlay';
                    dropdown.appendChild(overlay);
                    
                    overlay.addEventListener('click', function() {
                        dropdown.removeAttribute('open');
                        overlay.classList.remove('active');
                    });
                }
                
                // Обновляем обработчик для мобильных
                const originalClickHandler = function(e) {
                    if (window.innerWidth <= 768) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const isOpen = dropdown.hasAttribute('open');
                        if (isOpen) {
                            dropdown.removeAttribute('open');
                            if (overlay) overlay.classList.remove('active');
                        } else {
                            // Закрываем другие открытые dropdowns
                            document.querySelectorAll('.dropdown[open]').forEach(other => {
                                other.removeAttribute('open');
                                other.querySelector('.dropdown-overlay')?.classList.remove('active');
                            });
                            
                            dropdown.setAttribute('open', 'true');
                            if (overlay) overlay.classList.add('active');
                        }
                    }
                };
                
                // Удаляем старый обработчик и добавляем новый
                label.removeEventListener('click', originalClickHandler);
                label.addEventListener('click', originalClickHandler);
            }
        });
        
        // Закрытие dropdown при клике вне его
        const closeDropdowns = function(e) {
            if (window.innerWidth <= 768) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown[open]').forEach(dropdown => {
                        dropdown.removeAttribute('open');
                        dropdown.querySelector('.dropdown-overlay')?.classList.remove('active');
                    });
                }
            }
        };
        
        document.removeEventListener('click', closeDropdowns);
        document.addEventListener('click', closeDropdowns);
    }

    // Исправление для заголовков и макета
    function fixAdminLayout() {
        if (window.innerWidth <= 768) {
            // Исправляем заголовки страниц
            const pageHeaders = document.querySelectorAll('.flex.justify-between.items-center.mb-6');
            pageHeaders.forEach(header => {
                header.style.flexDirection = 'column';
                header.style.alignItems = 'flex-start';
                header.style.gap = '16px';
            });
            
            // Исправляем кнопки действий
            const actionButtons = document.querySelectorAll('.flex.gap-2');
            actionButtons.forEach(container => {
                if (container.children.length > 2) {
                    container.style.flexWrap = 'wrap';
                    container.style.gap = '8px';
                }
            });
        } else {
            // Сбрасываем стили на десктопе
            const pageHeaders = document.querySelectorAll('.flex.justify-between.items-center.mb-6');
            pageHeaders.forEach(header => {
                header.style.flexDirection = '';
                header.style.alignItems = '';
                header.style.gap = '';
            });
            
            const actionButtons = document.querySelectorAll('.flex.gap-2');
            actionButtons.forEach(container => {
                container.style.flexWrap = '';
                container.style.gap = '';
            });
        }
    }

    // Инициализация
    function initAdminFixes() {
        fixAdminTables();
        fixDropdowns();
        fixAdminLayout();
    }

    // Запуск при загрузке
    initAdminFixes();

    // Запуск при изменении размера окна с debounce
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(initAdminFixes, 100);
    });
    
    // Закрытие dropdown при нажатии ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.dropdown[open]').forEach(dropdown => {
                dropdown.removeAttribute('open');
                dropdown.querySelector('.dropdown-overlay')?.classList.remove('active');
            });
        }
    });
    
    // Очистка при размонтировании (если используется SPA)
    window.addEventListener('beforeunload', function() {
        removeScrollIndicators();
    });
});