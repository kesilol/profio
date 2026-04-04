<?php
$pageTitle = "Информация";

// Подключаем БД если еще не подключена
if (!isset($link)) {
    require('connect.php');
}

// Обработка формы обратной связи
$feedback_sent = false;
$feedback_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_feedback'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $user_id = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? null;
    
    if (empty($name) || empty($email) || empty($message)) {
        $feedback_error = 'Пожалуйста, заполните все поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feedback_error = 'Введите корректный email';
    } elseif (strlen($message) < 10) {
        $feedback_error = 'Сообщение должно содержать не менее 10 символов';
    } else {
        $insert_query = $link->prepare("
            INSERT INTO feedback (name, email, message, user_id, status, created_at) 
            VALUES (?, ?, ?, ?, 'new', NOW())
        ");
        $insert_query->bind_param("sssi", $name, $email, $message, $user_id);
        
        if ($insert_query->execute()) {
            $feedback_sent = true;
        } else {
            $feedback_error = 'Ошибка при отправке сообщения. Пожалуйста, попробуйте позже.';
        }
        $insert_query->close();
    }
}
?>

<div class="max-w-4xl mx-auto pb-12">
    
    <!-- Заголовок -->
    <div class="text-center py-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-color-brands/10 rounded-2xl mb-4">
            <i class="bi bi-info-circle text-2xl text-color-brands"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Информация</h1>
        <p class="text-gray-500 dark:text-gray-400">Всё о системе Profio</p>
    </div>

    <!-- О системе -->
    <section id="about" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700 mb-8">
        <div class="flex items-center gap-2 mb-4">
            <i class="bi bi-stars text-color-brands text-xl"></i>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">О системе Profio</h2>
        </div>
        
        <div class="space-y-4 text-gray-600 dark:text-gray-300 leading-relaxed">
            <p>
                <strong class="text-gray-900 dark:text-white">Profio</strong> — это современная система профессиональной ориентации, созданная для помощи учащимся и соискателям в выборе будущей профессии. 
                Платформа объединяет проверенные научные методики, персональные рекомендации и инструменты для построения индивидуального плана развития.
            </p>
            
            <p>
                Система разработана с учётом современных требований рынка труда и психологических особенностей личности. 
                Мы помогаем не просто определить подходящую профессию, но и построить путь к ней — от выбора направления до трудоустройства.
            </p>
            
            <h3 class="font-semibold text-gray-900 dark:text-white mt-4 mb-2">Как это работает?</h3>
            <p>
                Процесс начинается с прохождения комплексного тестирования, которое включает в себя несколько методик: 
                <strong>методику Климова</strong> (определение склонностей к разным типам профессий), 
                <strong>методику Голланда</strong> (выявление профессионального типа личности) и 
                <strong>тест MBTI</strong> (определение типа личности по 4 шкалам).
            </p>
            <p>
                На основе результатов тестирования система формирует персональные рекомендации профессий с указанием процента соответствия. 
                Для каждой рекомендованной профессии доступна подробная информация: описание, необходимые навыки, уровень зарплат, 
                востребованность на рынке, возможные места работы и учебные заведения.
            </p>
            
            <h3 class="font-semibold text-gray-900 dark:text-white mt-4 mb-2">Возможности платформы</h3>
            <ul class="space-y-2 list-disc list-inside">
                <li><strong>Тестирование</strong> — прохождение научно обоснованных тестов для определения профессиональных склонностей и типа личности</li>
                <li><strong>Рекомендации</strong> — подбор профессий на основе ваших результатов с указанием степени соответствия</li>
                <li><strong>План развития</strong> — создание индивидуального плана обучения и развития с отслеживанием прогресса</li>
                <li><strong>Каталог профессий</strong> — подробная информация о различных профессиях, их особенностях и перспективах</li>
                <li><strong>Аналитика</strong> — детальные отчёты по результатам тестирования и динамике прогресса</li>
                <li><strong>Для кураторов</strong> — возможность управлять студентами и отслеживать их результаты</li>
            </ul>
            
            <h3 class="font-semibold text-gray-900 dark:text-white mt-4 mb-2">Кому подходит?</h3>
            <p>
                Profio создан для широкого круга пользователей: школьников 8-11 классов, которые выбирают будущую профессию; 
                студентов, сомневающихся в правильности выбора; взрослых людей, желающих сменить сферу деятельности; 
                кураторов и учителей, помогающих ученикам с профессиональным самоопределением.
            </p>
            
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mt-4">
                <div class="flex items-center gap-2 text-color-brands mb-2">
                    <i class="bi bi-megaphone"></i>
                    <span class="font-medium">Важно знать</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Система полностью бесплатна. Все данные пользователей защищены и не передаются третьим лицам. 
                    Результаты тестирования доступны только вам и назначенному куратору (если он есть).
                </p>
            </div>
        </div>
    </section>

    <!-- Контакты -->
    <section id="contacts" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700 mb-8">
        <div class="flex items-center gap-2 mb-4">
            <i class="bi bi-headset text-color-brands text-xl"></i>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Контакты</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                    <i class="bi bi-telephone text-blue-500 text-lg"></i>
                </div>
                <div>
                    <div class="text-xs text-gray-400">Техническая поддержка</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">8 800 555-35-35</div>
                    <div class="text-xs text-gray-500">support@profio.ru</div>
                </div>
            </div>
            
            <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="w-10 h-10 bg-green-50 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                    <i class="bi bi-chat-dots text-green-500 text-lg"></i>
                </div>
                <div>
                    <div class="text-xs text-gray-400">Общие вопросы</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">8 950 161-08-88</div>
                    <div class="text-xs text-gray-500">info@profio.ru</div>
                </div>
            </div>
            
            <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="w-10 h-10 bg-purple-50 dark:bg-purple-900/20 rounded-lg flex items-center justify-center">
                    <i class="bi bi-geo-alt text-purple-500 text-lg"></i>
                </div>
                <div>
                    <div class="text-xs text-gray-400">Адрес</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">г. Нижний Новгород</div>
                    <div class="text-xs text-gray-500">ул. Студенческая, 6Б</div>
                </div>
            </div>
            
            <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <div class="w-10 h-10 bg-orange-50 dark:bg-orange-900/20 rounded-lg flex items-center justify-center">
                    <i class="bi bi-clock text-orange-500 text-lg"></i>
                </div>
                <div>
                    <div class="text-xs text-gray-400">Время работы</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">Пн-Вс: 9:00 - 18:30</div>
                    <div class="text-xs text-gray-500">Без перерыва</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Обратная связь -->
    <section id="feedback" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-2 mb-4">
            <i class="bi bi-chat-dots text-color-brands text-xl"></i>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Обратная связь</h2>
        </div>
        
        <p class="text-gray-500 dark:text-gray-400 mb-5">
            Есть вопросы, предложения или нашли ошибку? Напишите нам, и мы обязательно ответим.
        </p>
        
        <?php if ($feedback_sent): ?>
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-lg mb-4 text-sm">
                <i class="bi bi-check-circle mr-2"></i> Спасибо за ваше сообщение! Мы ответим вам в ближайшее время.
            </div>
        <?php endif; ?>
        
        <?php if ($feedback_error): ?>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg mb-4 text-sm">
                <i class="bi bi-exclamation-triangle mr-2"></i> <?= $feedback_error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <input type="text" name="name" required
                           value="<?= isset($_SESSION['user']['name']) ? htmlspecialchars($_SESSION['user']['name']) : '' ?>"
                           placeholder="Ваше имя *"
                           class="w-full px-4 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-color-brands focus:outline-none focus:ring-1 focus:ring-color-brands transition">
                </div>
                <div>
                    <input type="email" name="email" required
                           value="<?= isset($_SESSION['user']['email']) ? htmlspecialchars($_SESSION['user']['email']) : '' ?>"
                           placeholder="Email *"
                           class="w-full px-4 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-color-brands focus:outline-none focus:ring-1 focus:ring-color-brands transition">
                </div>
            </div>
            <div>
                <textarea name="message" required rows="4"
                          placeholder="Ваше сообщение *"
                          class="w-full px-4 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-color-brands focus:outline-none focus:ring-1 focus:ring-color-brands transition resize-none"></textarea>
            </div>
            <div class="flex justify-end">
                <button type="submit" name="send_feedback"
                        class="bg-color-brands hover:bg-opacity-90 text-white px-6 py-2 rounded-lg text-sm font-medium transition">
                    Отправить сообщение
                </button>
            </div>
        </form>
    </section>
</div>

<script>
// Плавная прокрутка к якорям
document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href && href !== '#') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    });
});
</script>