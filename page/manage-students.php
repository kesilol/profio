<?php
// Проверка авторизации и роли куратора
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'куратор') {
    header("Location: index.php?page=sign-in");
    exit();
}

require_once('event_user/reports_handler.php');
$curator_id = $_SESSION['user']['id_user'];
?>
<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
    <div>
        <h2 class="capitalize text-gray-1100 font-bold text-[28px] leading-[35px] dark:text-gray-dark-1100 mb-[13px]">
            Управление студентами
        </h2>
        <div class="flex items-center text-xs text-gray-500 gap-x-[11px] mb-[37px]">
            <div class="flex items-center gap-x-1">
                <img src="assets/images/icons/icon-home-2.svg" alt="Главная">
                <a class="capitalize" href="index.php">Главная</a>
            </div>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <a class="capitalize" href="index.php?page=reports">Отчеты</a>
            <img src="assets/images/icons/icon-arrow-right.svg" alt="Стрелка">
            <span class="capitalize text-color-brands">Управление студентами</span>
        </div>

        <!-- Сообщения об ошибках/успехе -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center gap-3">
                <i class="bi bi-check-circle-fill text-green-500 text-lg"></i>
                <div>
                    <strong>Успешно!</strong>
                    <div class="text-sm"><?= $_SESSION['success'] ?></div>
                </div>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill text-red-500 text-lg"></i>
                <div>
                    <strong>Ошибка!</strong>
                    <div class="text-sm"><?= $_SESSION['error'] ?></div>
                </div>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Добавление студентов -->
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100 mb-4">
                    Добавить студентов
                </h3>
                <p class="text-gray-500 mb-4">Найдите студентов по email и добавьте их в свой список. Каждый студент может быть привязан только к одному куратору.</p>
                
                <form method="POST" action="event_user/add_student.php" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-dark-700 mb-2">
                            Email студента
                        </label>
                        <input type="email" name="student_email" 
                               class="w-full p-3 border border-neutral dark:border-dark-neutral-border rounded-lg bg-white dark:bg-dark-neutral-bg focus:border-color-brands focus:ring-1 focus:ring-color-brands transition"
                               placeholder="student@profio.ru" 
                               value="<?= isset($_POST['student_email']) ? htmlspecialchars($_POST['student_email']) : '' ?>"
                               required>
                    </div>
                    <button type="submit" class="btn bg-color-brands text-white px-4 py-2 flex items-center gap-2 hover:bg-opacity-90 transition">
                        <i class="bi bi-person-plus"></i>
                        Добавить студента
                    </button>
                </form>

                <!-- Список доступных студентов -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <h4 class="font-semibold text-blue-800 mb-2 flex items-center gap-2">
                        <i class="bi bi-info-circle"></i>
                        Доступные студенты для добавления:
                    </h4>
                    <div class="text-sm text-blue-700 space-y-1">
                        <?php
                        // Получаем студентов, которые не привязаны к кураторам
                        $available_students_query = $link->prepare("
                            SELECT u.id, u.name, u.email 
                            FROM users u 
                            WHERE u.role = 'студент' 
                            AND u.id NOT IN (SELECT student_id FROM curator_students)
                            ORDER BY u.name
                            LIMIT 10
                        ");
                        $available_students_query->execute();
                        $available_students = $available_students_query->get_result();
                        
                        if ($available_students->num_rows > 0): 
                            while ($student = $available_students->fetch_assoc()):
                        ?>
                            <div class="flex justify-between items-center py-1">
                                <span><?= $student['name'] ?></span>
                                <span class="text-blue-600 text-xs"><?= $student['email'] ?></span>
                            </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <div class="text-blue-600">Нет доступных студентов</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Текущий список студентов -->
            <div class="rounded-2xl border border-neutral bg-neutral-bg dark:border-dark-neutral-border dark:bg-dark-neutral-bg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-subtitle-semibold font-semibold text-gray-1100 dark:text-gray-dark-1100">
                        Текущий список студентов
                    </h3>
                    <span class="text-sm text-gray-500 bg-gray-100 px-2 py-1 rounded">
                        <?php
                        $count_query = $link->prepare("SELECT COUNT(*) as count FROM curator_students WHERE curator_id = ?");
                        $count_query->bind_param("i", $curator_id);
                        $count_query->execute();
                        $count_result = $count_query->get_result();
                        $student_count = $count_result->fetch_assoc()['count'];
                        echo "{$student_count} студентов";
                        ?>
                    </span>
                </div>
                
                <?php
                // Получаем текущих студентов куратора
                $students_query = $link->prepare("
                    SELECT u.id, u.name, u.email, u.education_level, cs.assigned_at
                    FROM curator_students cs
                    JOIN users u ON cs.student_id = u.id
                    WHERE cs.curator_id = ?
                    ORDER BY u.name
                ");
                $students_query->bind_param("i", $curator_id);
                $students_query->execute();
                $current_students = $students_query->get_result();
                ?>
                
                <?php if ($current_students->num_rows > 0): ?>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <?php while ($student = $current_students->fetch_assoc()): ?>
                            <div class="flex items-center justify-between p-3 border border-neutral dark:border-dark-neutral-border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-dark-50 transition">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-1100 dark:text-gray-dark-1100">
                                        <?= $student['name'] ?>
                                    </h4>
                                    <p class="text-sm text-gray-500"><?= $student['email'] ?></p>
                                    <div class="flex items-center gap-4 mt-1 text-xs text-gray-400">
                                        <span class="bg-gray-100 px-2 py-1 rounded"><?= ucfirst($student['education_level']) ?></span>
                                        <span>Добавлен: <?= date('d.m.Y', strtotime($student['assigned_at'])) ?></span>
                                    </div>
                                </div>
                                <form method="POST" action="event_user/remove_student.php" class="ml-4">
                                    <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700 p-2 rounded hover:bg-red-50 transition" 
                                            onclick="return confirm('Удалить студента <?= htmlspecialchars($student['name']) ?> из списка?')"
                                            title="Удалить из списка">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="bi bi-people text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">У вас пока нет студентов в списке</p>
                        <p class="text-sm text-gray-400 mt-1">Добавьте студентов используя форму слева</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>