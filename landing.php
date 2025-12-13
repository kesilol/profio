<?php
session_start();
require('connect.php');

if (isset($_SESSION['user'])) {
    header("Location: index.php?page=main");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profio - Найдите свою профессию мечты</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Tailwind -->
  <link rel="stylesheet" href="assets/styles/tailwind.min.css" />
  <!-- Custom Landing Styles -->
  <link rel="stylesheet" href="assets/styles/landing.css" />
  <!-- Main styles -->
  <link rel="stylesheet" href="assets/styles/style.min.css" />
  <link rel="icon" href="assets/images/icons/favicon.svg" type="image/svg+xml" sizes="16x16">
</head>

<body class="bg-white dark:bg-gray-900">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg fixed-top bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-bottom border-gray-200 dark:border-gray-700 py-3">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center text-decoration-none" href="#">
      <div class="bg-primary rounded me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
        <span class="text-white fw-bold small">P</span>
      </div>
      <span class="fw-bold text-dark dark:text-white fs-5">Profio</span>
    </a>
    
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
        <li class="nav-item mx-2">
          <a class="nav-link text-dark dark:text-gray-300 hover-primary fw-medium" href="#features">Возможности</a>
        </li>
        <li class="nav-item mx-2">
          <a class="nav-link text-dark dark:text-gray-300 hover-primary fw-medium" href="#how-it-works">Как работает</a>
        </li>
        <li class="nav-item mx-2">
          <a class="nav-link text-dark dark:text-gray-300 hover-primary fw-medium" href="#testimonials">Отзывы</a>
        </li>
        <li class="nav-item mx-2">
          <a class="nav-link text-dark dark:text-gray-300 hover-primary fw-medium" href="index.php?page=sign-in">Войти</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero-section pt-5">
  <div class="container py-5 mt-5">
    <div class="row justify-content-center text-center mb-5">
      <div class="col-lg-10">
        <!-- Badge -->
        <div class="badge-custom animate-fade-in-up mb-4">
          <span class="badge-dot"></span>
          <span class="badge-text">Более 10,000+ довольных пользователей</span>
        </div>

        <!-- Main heading -->
        <h1 class="hero-title animate-fade-in-up mb-4">
          Найдите свою
          <span class="text-primary">профессию мечты</span>
        </h1>

        <!-- Subheading -->
        <p class="hero-subtitle animate-fade-in-up delay-100 mb-5">
          Научно обоснованный тест, который поможет раскрыть ваш потенциал и найти карьерный путь, идеально соответствующий вашей личности
        </p>

        <!-- CTA Buttons -->
        <div class="hero-buttons animate-fade-in-up delay-200 mb-5">
          <div class="d-flex flex-column flex-sm-row justify-content-center align-items-center gap-3">
            <a href="test-onboarding.php" class="btn btn-primary btn-custom-primary">
              <span class="btn-text">Начать тест бесплатно</span>
            </a>
            
            <a href="#how-it-works" class="btn btn-outline-custom">
              <span class="btn-text">Как это работает</span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="row justify-content-center animate-fade-in-up delay-300">
      <div class="col-lg-8">
        <div class="row g-4">
          <div class="col-6 col-md-3">
            <div class="stat-card text-center">
              <div class="stat-number text-primary mb-2">10K+</div>
              <div class="stat-label">Пользователей</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-card text-center">
              <div class="stat-number text-primary mb-2">95%</div>
              <div class="stat-label">Точность теста</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-card text-center">
              <div class="stat-number text-primary mb-2">50+</div>
              <div class="stat-label">Профессий</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-card text-center">
              <div class="stat-number text-primary mb-2">24/7</div>
              <div class="stat-label">Поддержка</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Features Section -->
<section id="features" class="features-section py-5">
  <div class="container py-5">
    <div class="row justify-content-center text-center mb-5">
      <div class="col-lg-8">
        <h2 class="section-title mb-4">
          Почему выбирают <span class="text-primary">Profio</span>?
        </h2>
        <p class="section-subtitle">
          Современный подход к профориентации с научной основой
        </p>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon">
            🧠
          </div>
          <h3 class="feature-title">Научный подход</h3>
          <p class="feature-description">
            Тест основан на современных психологических методиках и исследованиях в области профориентации
          </p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon">
            ⚡
          </div>
          <h3 class="feature-title">Быстрый результат</h3>
          <p class="feature-description">
            Получите детализированный отчет с рекомендациями всего за 15 минут
          </p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="feature-card">
          <div class="feature-icon">
            📊
          </div>
          <h3 class="feature-title">Детальная аналитика</h3>
          <p class="feature-description">
            Подробный разбор ваших сильных сторон и зон для развития с конкретными рекомендациями
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="how-it-works-section py-5">
  <div class="container py-5">
    <div class="row justify-content-center text-center mb-5">
      <div class="col-lg-8">
        <h2 class="section-title mb-4">
          Всего <span class="text-primary">3 шага</span> к успеху
        </h2>
        <p class="section-subtitle">
          Простой и понятный процесс к осознанному выбору профессии
        </p>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="step-card text-center">
          <div class="step-number">1</div>
          <h3 class="step-title">Пройдите тест</h3>
          <p class="step-description">
            Ответьте на вопросы о ваших интересах, ценностях и предпочтениях
          </p>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="step-card text-center">
          <div class="step-number">2</div>
          <h3 class="step-title">Получите анализ</h3>
          <p class="step-description">
            Наша система проанализирует ваши ответы и подберет подходящие профессии
          </p>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="step-card text-center">
          <div class="step-number">3</div>
          <h3 class="step-title">Начните развитие</h3>
          <p class="step-description">
            Получите персональный план развития с конкретными шагами к вашей цели
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Testimonials Section -->
<section id="testimonials" class="testimonials-section py-5">
  <div class="container py-5">
    <div class="row justify-content-center text-center mb-5">
      <div class="col-lg-8">
        <h2 class="section-title mb-4">
          Реальные <span class="text-primary">истории успеха</span>
        </h2>
        <p class="section-subtitle">
          Узнайте, как Profio помог другим найти свой путь
        </p>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="testimonial-card">
          <div class="testimonial-header">
            <div class="testimonial-avatar">А</div>
            <div class="testimonial-info">
              <div class="testimonial-name">Анна, 21 год</div>
              <div class="testimonial-role">Студентка → UX-дизайнер</div>
            </div>
          </div>
          <p class="testimonial-text">
            "Тест открыл мне глаза на карьеру в UX-дизайне. Уже через месяц после прохождения устроилась на стажировку и сейчас работаю над реальными проектами!"
          </p>
          <div class="testimonial-rating">
            <?php for($i = 0; $i < 5; $i++): ?>
              <span class="star">★</span>
            <?php endfor; ?>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="testimonial-card">
          <div class="testimonial-header">
            <div class="testimonial-avatar">М</div>
            <div class="testimonial-info">
              <div class="testimonial-name">Максим, 28 лет</div>
              <div class="testimonial-role">Менеджер → Разработчик</div>
            </div>
          </div>
          <p class="testimonial-text">
            "После 5 лет в продажах наконец-то понял, что хочу в IT. Благодаря Profio нашёл силы сменить профессию и сейчас изучаю программирование!"
          </p>
          <div class="testimonial-rating">
            <?php for($i = 0; $i < 4; $i++): ?>
              <span class="star">★</span>
            <?php endfor; ?>
            <span class="star muted">★</span>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="testimonial-card">
          <div class="testimonial-header">
            <div class="testimonial-avatar">Е</div>
            <div class="testimonial-info">
              <div class="testimonial-name">Екатерина, 32 года</div>
              <div class="testimonial-role">Учитель → Методист</div>
            </div>
          </div>
          <p class="testimonial-text">
            "Profio помог мне найти смелость сменить профессию. Теперь работаю методистом в образовательном центре и безумно счастлива!"
          </p>
          <div class="testimonial-rating">
            <?php for($i = 0; $i < 5; $i++): ?>
              <span class="star">★</span>
            <?php endfor; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Final CTA Section -->
<section class="cta-section py-5">
  <div class="container py-5">
    <div class="row justify-content-center text-center">
      <div class="col-lg-8">
        <h2 class="cta-title mb-4">
          Готовы найти свой путь?
        </h2>
        
        <p class="cta-subtitle mb-5">
          Присоединяйтесь к сообществу людей, которые сделали осознанный выбор профессии
        </p>

        <div class="cta-buttons mb-4">
          <div class="d-flex flex-column flex-sm-row justify-content-center align-items-center gap-3">
            <a href="test-onboarding.php" class="btn btn-light btn-custom-light">
              <span class="btn-text">Начать тест бесплатно</span>
            </a>
            
            <a href="index.php?page=sign-up" class="btn btn-outline-light">
              Создать аккаунт
            </a>
          </div>
        </div>

        <p class="cta-note">
          Без кредитной карты • Бесплатно навсегда • Поддержка 24/7
        </p>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="footer-section py-5">
  <div class="container">
    <div class="row align-items-center mb-4">
      <div class="col-md-6 mb-4 mb-md-0">
        <div class="d-flex align-items-center">
          <div class="footer-logo me-3">
            <span class="logo-text">P</span>
          </div>
          <span class="footer-brand">Profio</span>
        </div>
      </div>
      
      <div class="col-md-6 text-md-end">
        <div class="footer-links">
          <a href="#features" class="footer-link">Возможности</a>
          <a href="#how-it-works" class="footer-link">Как работает</a>
          <a href="#testimonials" class="footer-link">Отзывы</a>
        </div>
      </div>
    </div>
    
    <div class="footer-divider"></div>
    
    <div class="footer-copyright text-center">
      <p>&copy; 2024 Profio. Все права защищены.</p>
    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>