<?php
// Если пользователь уже авторизован, перенаправляем
if (isset($_SESSION['user'])) {
    header("Location: index.php?page=main");
    exit();
}
?>

<main class="overflow-x-scroll scrollbar-hide flex flex-col justify-between pt-[42px] px-[23px] pb-[28px]">
  <div>
    <form class="rounded-2xl bg-white mx-auto p-10 text-center max-w-[440px] my-[84px] dark:bg-[#1F2128]" action="event_user/signup.php" method="POST">
      <div class="mb-4 text-center mx-auto">
        <img class="inline-block" src="assets/images/icons/icon-landing-success-1.svg" alt="landing success">
      </div>
      
      <h3 class="font-bold text-2xl text-gray-1100 capitalize mb-[5px] dark:text-gray-dark-1100">
        <?php if (isset($_SESSION['onboarding_completed'])): ?>
          Создайте аккаунт для сохранения результатов
        <?php else: ?>
          Регистрация
        <?php endif; ?>
      </h3>
      
      <p class="text-sm text-gray-500 mb-[30px] dark:text-gray-dark-500">
        <?php if (isset($_SESSION['onboarding_completed'])): ?>
          Ваши результаты теста готовы! Сохраните их в личном кабинете.
        <?php else: ?>
          Добро пожаловать!
        <?php endif; ?>
      </p>

      <?php if (isset($_SESSION['onboarding_completed']) && isset($_SESSION['onboarding_results'])): ?>
        <div class="mb-4 p-3 bg-green-50 rounded-lg border border-green-200 dark:bg-green-900/20 dark:border-green-800">
          <p class="text-sm text-green-800 dark:text-green-300">
            ✅ Вы прошли тест: <strong><?php echo $_SESSION['onboarding_results']['test_title']; ?></strong><br>
            Ваш тип: <strong><?php echo ucfirst($_SESSION['onboarding_results']['result_type']); ?></strong>
          </p>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION['message'])): ?>
        <div class="text-green-500 text-sm mb-4"><?php echo $_SESSION['message']; ?></div>
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
            <div class="text-red-500 text-xs mt-1"><?php echo $_SESSION['error_required']; ?></div>
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
            <div class="text-red-500 text-xs mt-1"><?php echo $_SESSION['error_email']; ?></div>
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
                  <option value="<?php echo $role; ?>"
                    <?php echo (isset($_SESSION['form_data']['role']) && $_SESSION['form_data']['role'] == $role) ? 'selected' : ''; ?>>
                    <?php echo ucfirst($role); ?>
                  </option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
            <button class="btn-square flex items-center justify-center bg-transparent">
              <img src="assets/images/icons/icon-input-user.svg" alt="role icon">
            </button>
          </div>
          <?php if (isset($_SESSION['error_required'])): ?>
            <div class="text-red-500 text-xs mt-1"><?php echo $_SESSION['error_required']; ?></div>
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
                <option value="<?php echo $level; ?>"
                  <?php echo (isset($_SESSION['form_data']['education_level']) && $_SESSION['form_data']['education_level'] == $level) ? 'selected' : ''; ?>>
                  <?php echo ucfirst(str_replace('-', ' ', $level)); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button class="btn-square flex items-center justify-center bg-transparent">
              <img src="assets/images/icons/icon-input-user.svg" alt="education icon">
            </button>
          </div>
          <?php if (isset($_SESSION['error_required'])): ?>
            <div class="text-red-500 text-xs mt-1"><?php echo $_SESSION['error_required']; ?></div>
          <?php endif; ?>
        </div>

        <label for="psw">
          <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">Пароль</p>
        </label>
        <div class="form-control mb-[20px]">
          <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442] <?php echo isset($_SESSION['error_pas']) ? 'border-red-500' : ''; ?>">
            <input class="input flex-1 bg-transparent text-black focus:outline-none dark:text-white" type="password" placeholder="Password" name="password1" autocomplete="on" required>
            <button class="btn-square border-white flex items-center justify-center bg-transparent">
              <img src="assets/images/icons/icon-eye.svg" alt="eye icon">
            </button>
          </div>
          <?php if (isset($_SESSION['error_pas'])): ?>
            <div class="text-red-500 text-xs mt-1"><?php echo $_SESSION['error_pas']; ?></div>
          <?php endif; ?>
        </div>

        <label for="psw">
          <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">Подтверждение пароля</p>
        </label>
        <div class="form-control mb-[20px]">
          <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442] <?php echo isset($_SESSION['error_pas']) ? 'border-red-500' : ''; ?>">
            <input class="input flex-1 bg-transparent text-black focus:outline-none dark:text-white" type="password" placeholder="Password" name="password2" autocomplete="on" required>
            <button class="btn-square border-white flex items-center justify-center bg-transparent">
              <img src="assets/images/icons/icon-eye.svg" alt="eye icon">
            </button>
          </div>
          <?php if (isset($_SESSION['error_pas'])): ?>
            <div class="text-red-500 text-xs mt-1"><?php echo $_SESSION['error_pas']; ?></div>
          <?php endif; ?>
        </div>
      </div>

      <button type="submit" class="btn normal-case h-fit min-h-fit transition-all duration-300 border-4 w-full border-neutral-bg mb-[20px] py-[14px] dark:border-dark-neutral-bg">
        <?php if (isset($_SESSION['onboarding_completed'])): ?>
          Сохранить результаты и создать аккаунт
        <?php else: ?>
          Зарегистрироваться
        <?php endif; ?>
      </button>
      
      <p class="text-sm text-gray-1100 dark:text-gray-dark-1100">
        У вас уже есть учетная запись?
        <a class="text-color-brands" href="index.php?page=sign-in">&nbsp;Войти</a>
      </p>
      
      <?php if (!isset($_SESSION['onboarding_completed'])): ?>
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

    <!-- Очистка ошибок после показа -->
    <?php
    // Очищаем ошибки после того как они показались
    if (isset($_SESSION['error_email']) || isset($_SESSION['error_pas']) || isset($_SESSION['error_required']) || isset($_SESSION['form_data'])) {
      unset($_SESSION['error_email'], $_SESSION['error_pas'], $_SESSION['error_required'], $_SESSION['form_data']);
    }
    ?>
  </div>
</main>