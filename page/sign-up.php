<?php
session_start();
require('connect.php');

// ★★★ ОЧИЩАЕМ ВСЕ ВРЕМЕННЫЕ СООБЩЕНИЯ ПРИ ЗАГРУЗКЕ ★★★
// Страница регистрации не должна показывать сообщения от предыдущих действий
unset($_SESSION['message']);
unset($_SESSION['error']);
unset($_SESSION['success']);

// Если пользователь уже авторизован, перенаправляем
if (isset($_SESSION['user'])) {
  header("Location: index.php?page=main");
  exit();
}

// ★★★ ЛОГИКА ОЧИСТКИ УСТАРЕВШИХ РЕЗУЛЬТАТОВ ТЕСТА ★★★
// Проверяем, есть ли флаг, что пользователь только что прошел тест
if (!isset($_SESSION['just_completed_test'])) {
    unset($_SESSION['onboarding_completed']);
    unset($_SESSION['onboarding_results']);
    unset($_SESSION['onboarding_timestamp']);
} else {
    if (isset($_SESSION['onboarding_timestamp']) && (time() - $_SESSION['onboarding_timestamp'] > 1800)) {
        unset($_SESSION['onboarding_completed']);
        unset($_SESSION['onboarding_results']);
        unset($_SESSION['onboarding_timestamp']);
        unset($_SESSION['just_completed_test']);
        unset($_SESSION['pending_registration']);
    }
}

// Дополнительная проверка через referer
if (!isset($_SERVER['HTTP_REFERER']) || 
    (strpos($_SERVER['HTTP_REFERER'], 'onboarding_test.php') === false && 
     strpos($_SERVER['HTTP_REFERER'], 'test-onboarding.php') === false)) {
    unset($_SESSION['just_completed_test']);
    unset($_SESSION['pending_registration']);
}
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
  <div>
    <form class="rounded-2xl bg-white mx-auto p-10 text-center max-w-[440px] my-[84px] dark:bg-[#1F2128]" action="event_user/signup.php" method="POST" id="signupForm">
      <div class="mb-4 text-center mx-auto">
        <a href="index.php" class="inline-block hover:opacity-80 transition-opacity">
          <img class="inline-block" src="assets/images/icons/icon-landing-success-1.svg" alt="landing success">
        </a>
      </div>

      <h3 class="font-bold text-2xl text-gray-1100 capitalize mb-[5px] dark:text-gray-dark-1100">
        <?php if (isset($_SESSION['onboarding_completed']) && isset($_SESSION['just_completed_test'])): ?>
          Создайте аккаунт для сохранения результатов
        <?php else: ?>
          Регистрация
        <?php endif; ?>
      </h3>

      <p class="text-sm text-gray-500 mb-[30px] dark:text-gray-dark-500">
        <?php if (isset($_SESSION['onboarding_completed']) && isset($_SESSION['just_completed_test'])): ?>
          Ваши результаты теста готовы! Сохраните их в личном кабинете.
        <?php else: ?>
          Добро пожаловать!
        <?php endif; ?>
      </p>

      <?php if (isset($_SESSION['onboarding_completed']) && isset($_SESSION['just_completed_test']) && isset($_SESSION['onboarding_results'])): ?>
        <div class="mb-4 p-3 bg-green-50 rounded-lg border border-green-200 dark:bg-green-900/20 dark:border-green-800">
          <p class="text-sm text-green-800 dark:text-green-300">
            ✅ Вы прошли тест: <strong><?php echo htmlspecialchars($_SESSION['onboarding_results']['test_title']); ?></strong><br>
            Ваш тип: <strong><?php echo ucfirst(htmlspecialchars($_SESSION['onboarding_results']['result_type'])); ?></strong>
          </p>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION['message'])): ?>
        <div class="text-green-500 text-sm mb-4"><?php echo htmlspecialchars($_SESSION['message']); ?></div>
        <?php unset($_SESSION['message']); ?>
      <?php endif; ?>

      <div>
        <label for="name">
          <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">Ваше имя</p>
        </label>
        <div class="form-control mb-[20px]">
          <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442] <?php echo isset($_SESSION['error_required']) ? 'border-red-500' : ''; ?>">
            <input class="input flex-1 bg-transparent text-black focus:outline-none dark:text-white"
              type="text"
              placeholder="Full name"
              name="login"
              value="<?php echo isset($_SESSION['form_data']['login']) ? htmlspecialchars($_SESSION['form_data']['login']) : ''; ?>"
              required>
            <button class="btn-square flex items-center justify-center bg-transparent">
              <img src="assets/images/icons/icon-input-user.svg" alt="sms icon">
            </button>
          </div>
          <?php if (isset($_SESSION['error_required'])): ?>
            <div class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($_SESSION['error_required']); ?></div>
          <?php endif; ?>
        </div>

        <label for="email">
          <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">E-mail</p>
        </label>
        <div class="form-control mb-[20px]">
          <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442] <?php echo isset($_SESSION['error_email']) ? 'border-red-500' : ''; ?>">
            <input class="input flex-1 bg-transparent text-black focus:outline-none dark:text-white"
              type="email"
              placeholder="Email"
              name="email"
              value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>"
              required>
            <button class="btn-square flex items-center justify-center bg-transparent">
              <img src="assets/images/icons/icon-sms.svg" alt="sms icon">
            </button>
          </div>
          <?php if (isset($_SESSION['error_email'])): ?>
            <div class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($_SESSION['error_email']); ?></div>
          <?php endif; ?>
        </div>

        <?php
        // Получаем возможные значения ролей из структуры таблицы
        $role_query = $link->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
        $role_row = $role_query->fetch_assoc();
        preg_match("/^enum\(\'(.*)\'\)$/", $role_row['Type'], $role_matches);
        $roles = explode("','", $role_matches[1]);

        // Получаем возможные значения уровней образования
        $education_query = $link->query("SHOW COLUMNS FROM users WHERE Field = 'education_level'");
        $education_row = $education_query->fetch_assoc();
        preg_match("/^enum\(\'(.*)\'\)$/", $education_row['Type'], $education_matches);
        $education_levels = explode("','", $education_matches[1]);
        ?>

        <label for="role">
          <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">Роль</p>
        </label>
        <div class="form-control mb-[20px]">
          <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442] <?php echo isset($_SESSION['error_required']) ? 'border-red-500' : ''; ?>">
            <select class="select flex-1 bg-transparent text-black focus:outline-none dark:text-white w-full py-3 px-4" name="role" required>
              <option value="" disabled <?php echo !isset($_SESSION['form_data']['role']) ? 'selected' : ''; ?>>Выберите роль</option>
              <?php foreach ($roles as $role): ?>
                <?php if ($role != 'администратор'): ?>
                  <option value="<?php echo htmlspecialchars($role); ?>"
                    <?php echo (isset($_SESSION['form_data']['role']) && $_SESSION['form_data']['role'] == $role) ? 'selected' : ''; ?>>
                    <?php echo ucfirst(htmlspecialchars($role)); ?>
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
            <button class="btn-square flex items-center justify-center bg-transparent">
              <img src="assets/images/icons/icon-input-user.svg" alt="role icon">
            </button>
          </div>
          <?php if (isset($_SESSION['error_required'])): ?>
            <div class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($_SESSION['error_required']); ?></div>
          <?php endif; ?>
        </div>

        <label for="education_level">
          <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">Уровень образования</p>
        </label>
        <div class="form-control mb-[20px]">
          <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442] <?php echo isset($_SESSION['error_required']) ? 'border-red-500' : ''; ?>">
            <select class="select flex-1 bg-transparent text-black focus:outline-none dark:text-white w-full py-3 px-4" name="education_level" required>
              <option value="" disabled <?php echo !isset($_SESSION['form_data']['education_level']) ? 'selected' : ''; ?>>Выберите уровень образования</option>
              <?php foreach ($education_levels as $level): ?>
                <option value="<?php echo htmlspecialchars($level); ?>"
                  <?php echo (isset($_SESSION['form_data']['education_level']) && $_SESSION['form_data']['education_level'] == $level) ? 'selected' : ''; ?>>
                  <?php echo ucfirst(str_replace('-', ' ', htmlspecialchars($level))); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button class="btn-square flex items-center justify-center bg-transparent">
              <img src="assets/images/icons/icon-input-user.svg" alt="education icon">
            </button>
          </div>
          <?php if (isset($_SESSION['error_required'])): ?>
            <div class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($_SESSION['error_required']); ?></div>
          <?php endif; ?>
        </div>

        <label for="psw">
          <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">Пароль</p>
        </label>
        <div class="form-control mb-[10px]">
          <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442] <?php echo isset($_SESSION['error_pas']) ? 'border-red-500' : ''; ?> relative">
            <input class="input flex-1 bg-transparent text-black focus:outline-none dark:text-white"
              type="password"
              placeholder="Password"
              name="password1"
              id="password1"
              autocomplete="on"
              required
              onfocus="showPasswordRequirements()"
              onblur="hidePasswordRequirements()"
              oninput="validatePassword()">
            <button type="button" class="btn-square border-white flex items-center justify-center bg-transparent toggle-password">
              <img src="assets/images/icons/icon-eye.svg" alt="eye icon">
            </button>
          </div>

          <!-- Блок с требованиями к паролю (изначально скрыт) -->
          <div id="passwordRequirements" class="mt-2 text-left hidden transition-all duration-300">
            <p class="text-xs text-gray-500 dark:text-gray-dark-500 mb-2 font-medium">🔒 Требования к паролю:</p>
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3 space-y-1.5">
              <div id="length" class="text-xs flex items-center gap-2 text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Не менее 8 символов</span>
              </div>
              <div id="uppercase" class="text-xs flex items-center gap-2 text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Заглавная буква (A-Z)</span>
              </div>
              <div id="lowercase" class="text-xs flex items-center gap-2 text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Строчная буква (a-z)</span>
              </div>
              <div id="number" class="text-xs flex items-center gap-2 text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Цифра (0-9)</span>
              </div>
              <div id="special" class="text-xs flex items-center gap-2 text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Спецсимвол (!@#$%^&*-_)</span>
              </div>
            </div>
          </div>

          <?php if (isset($_SESSION['error_pas'])): ?>
            <div class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($_SESSION['error_pas']); ?></div>
          <?php endif; ?>
        </div>

        <label for="psw">
          <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">Подтверждение пароля</p>
        </label>
        <div class="form-control mb-[20px]">
          <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442] <?php echo isset($_SESSION['error_pas']) ? 'border-red-500' : ''; ?>">
            <input class="input flex-1 bg-transparent text-black focus:outline-none dark:text-white"
              type="password"
              placeholder="Password"
              name="password2"
              id="password2"
              autocomplete="on"
              required
              oninput="validatePasswordConfirmation()">
            <button type="button" class="btn-square border-white flex items-center justify-center bg-transparent toggle-password">
              <img src="assets/images/icons/icon-eye.svg" alt="eye icon">
            </button>
          </div>
          <div id="passwordMatch" class="text-xs mt-1 hidden">
            <span class="text-red-500 dark:text-red-400 flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Пароли не совпадают
            </span>
          </div>
          <?php if (isset($_SESSION['error_pas'])): ?>
            <div class="text-red-500 text-xs mt-1"><?php echo htmlspecialchars($_SESSION['error_pas']); ?></div>
          <?php endif; ?>
        </div>
      </div>

      <button type="submit" class="btn normal-case h-fit min-h-fit transition-all duration-300 border-4 w-full border-neutral-bg mb-[20px] py-[14px] dark:border-dark-neutral-bg bg-color-brands text-white cursor-pointer hover:opacity-90" id="submitBtn">
        <?php if (isset($_SESSION['onboarding_completed']) && isset($_SESSION['just_completed_test'])): ?>
          Сохранить результаты и создать аккаунт
        <?php else: ?>
          Зарегистрироваться
        <?php endif; ?>
      </button>

      <div class="text-center">
        <a href="index.php" class="text-sm text-gray-500 hover:text-color-brands transition-colors dark:text-gray-dark-500 inline-block mb-2">
          ← На главную
        </a>
        <p class="text-sm text-gray-1100 dark:text-gray-dark-1100">
          У вас уже есть учетная запись?
          <a class="text-color-brands" href="index.php?page=sign-in">&nbsp;Войти</a>
        </p>
      </div>

      <?php if (!isset($_SESSION['onboarding_completed']) || !isset($_SESSION['just_completed_test'])): ?>
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
            Хотите сначала пройти тест?
          </p>
          <a href="test-onboarding.php" class="text-color-brands hover:text-color-brands/80 underline text-sm">
            Пройти профориентационный тест
          </a>
        </div>
      <?php endif; ?>
    </form>

    <script>
      function showPasswordRequirements() {
        const requirementsDiv = document.getElementById('passwordRequirements');
        if (requirementsDiv) {
          requirementsDiv.classList.remove('hidden');
        }
      }

      function hidePasswordRequirements() {
        const password = document.getElementById('password1').value;
        // Скрываем только если поле пустое
        if (!password) {
          const requirementsDiv = document.getElementById('passwordRequirements');
          if (requirementsDiv) {
            requirementsDiv.classList.add('hidden');
          }
        }
      }

      function validatePassword() {
        const password = document.getElementById('password1').value;
        const password2 = document.getElementById('password2').value;

        // Регулярные выражения для проверки (добавлены - и _)
        const hasLength = password.length >= 8;
        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[!@#$%^&*\-_]/.test(password); // Добавлены - и _

        // Обновляем визуальные индикаторы
        updateRequirement('length', hasLength);
        updateRequirement('uppercase', hasUppercase);
        updateRequirement('lowercase', hasLowercase);
        updateRequirement('number', hasNumber);
        updateRequirement('special', hasSpecial);

        // Проверяем совпадение паролей
        validatePasswordConfirmation();

        return hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial;
      }

      function updateRequirement(elementId, isValid) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const textSpan = element.querySelector('span:last-child') || element.querySelector('span');
        const text = textSpan ? textSpan.textContent : element.textContent.replace(/[✓✗]/g, '').trim();
        const svg = element.querySelector('svg');

        if (isValid) {
          element.classList.remove('text-gray-500', 'dark:text-gray-400', 'text-red-500', 'dark:text-red-400');
          element.classList.add('text-green-500', 'dark:text-green-400');
          if (svg) {
            svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>';
          }
        } else {
          element.classList.remove('text-green-500', 'dark:text-green-400');
          element.classList.add('text-gray-500', 'dark:text-gray-400');
          if (svg) {
            svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';
          }
        }
      }

      function validatePasswordConfirmation() {
        const password1 = document.getElementById('password1').value;
        const password2 = document.getElementById('password2').value;
        const matchElement = document.getElementById('passwordMatch');

        if (password2 === '') {
          matchElement.classList.add('hidden');
          return true;
        }

        if (password1 === password2) {
          matchElement.classList.add('hidden');
          return true;
        } else {
          matchElement.classList.remove('hidden');
          return false;
        }
      }

      // Функция для переключения видимости пароля
      document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.toggle-password').forEach(button => {
          button.addEventListener('click', function() {
            const input = this.closest('.input-group').querySelector('input');
            const icon = this.querySelector('img');

            if (input.type === 'password') {
              input.type = 'text';
              if (icon) icon.style.opacity = '0.7';
            } else {
              input.type = 'password';
              if (icon) icon.style.opacity = '1';
            }
          });
        });

        // Инициализация валидации
        validatePassword();

        // Показываем требования если есть ошибка или поле не пустое
        const passwordField = document.getElementById('password1');
        if (passwordField && passwordField.value) {
          showPasswordRequirements();
        }
      });
    </script>

    <!-- Очистка ошибок после показа -->
    <?php
    // Очищаем ошибки после того как они показались
    if (isset($_SESSION['error_email']) || isset($_SESSION['error_pas']) || isset($_SESSION['error_required']) || isset($_SESSION['form_data'])) {
      unset($_SESSION['error_email'], $_SESSION['error_pas'], $_SESSION['error_required'], $_SESSION['form_data']);
    }
    ?>
  </div>
</main>