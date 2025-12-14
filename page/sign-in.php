<form class="rounded-2xl bg-white mx-auto p-10 text-center max-w-[440px] my-[84px] dark:bg-[#1F2128]" action="event_user/login.php" method="POST">
  <div class="mb-4 text-center mx-auto"><img class="inline-block" src="assets/images/icons/icon-landing-success-1.svg" alt="landing success"></div>
  <h3 class="font-bold text-2xl text-gray-1100 capitalize mb-[5px] dark:text-gray-dark-1100">Добро пожаловать!</h3>
  <p class="text-sm text-gray-500 mb-[30px] dark:text-gray-dark-500">Давай узнаем, какая профессия тебе подходит!</p>

  <?php if (isset($_SESSION['error_login'])): ?>
    <div class="text-red-500 text-sm mb-4"><?php echo $_SESSION['error_login'];
         unset($_SESSION['error_login']); ?></div>
  <?php endif; ?>

  <div>
    <label for="email">
      <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">E-mail</p>
    </label>
    <div class="form-control mb-[20px]">
      <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442]">
        <input class="input flex-1 bg-transparent text-black focus:outline-none dark:text-gray-dark-300" type="text" placeholder="Email" name="email">
        <button class="btn-square flex items-center justify-center bg-transparent"><img src="assets/images/icons/icon-sms.svg" alt="sms icon"></button>
      </div>
    </div>
    <label for="psw">
      <p class="text-left text-sm mb-2 text-gray-1100 dark:text-gray-dark-1100">Пароль</p>
    </label>
    <div class="form-control mb-[20px]">
      <div class="input-group border rounded-lg border-[#E8EDF2] dark:border-[#313442]">
        <input class="input flex-1 bg-transparent text-black focus:outline-none dark:text-gray-dark-300" type="password" placeholder="Password" name="psw" id="password" autocomplete="on" required>
        <button type="button" class="btn-square border-white flex items-center justify-center bg-transparent toggle-password">
          <img src="assets/images/icons/icon-eye.svg" alt="eye icon">
        </button>
      </div>
    </div>
  </div>
  <button class="btn normal-case h-fit min-h-fit transition-all duration-300 border-4 w-full border-neutral-bg mb-[20px] py-[14px] dark:border-dark-neutral-bg">Вход</button>
  <p class="text-sm text-gray-1100 dark:text-gray-dark-1100">У вас нет учетной записи?<a class="text-color-brands" href="index.php?page=sign-up">&nbsp;Зарегестрироваться</a></p>
</form>

<script>
  // Функция для переключения видимости пароля
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-password').forEach(button => {
      button.addEventListener('click', function() {
        const input = this.closest('.input-group').querySelector('input');
        const icon = this.querySelector('img');

        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.add('text-color-brands');
        } else {
          input.type = 'password';
          icon.classList.remove('text-color-brands');
        }
      });
    });
  });
</script>
