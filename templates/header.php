<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AP Dental — современная стоматологическая клиника. 
    Профессиональное лечение зубов, имплантация, протезирование, отбеливание, гигиена полости рта. 
    Опытные врачи, доступные цены. Запишитесь на приём на сайте!">
    <meta name="keywords" content="стоматология, лечение зубов, имплантация, протезирование зубов, 
    отбеливание, стоматологическая клиника, AP Dental, врач стоматолог, запись к стоматологу, 
    гигиена полости рта, кариес, виниры, брекеты">
    <title><?= $title ?? 'AP Dental | Стоматология' ?></title>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="icon" type="image/x-icon" href="./img/icon.ico">
</head>

<body class="body">
    <header class="header">
        <div class="container">
            <div class="header-top">
                <a href="index.php" class="logo">
                    <img src="./img/logo.jpg" alt="AP Dental" class="logo-img">
                    <div class="logo-text">
                        <span class="logo-title">AP Dental</span>
                    </div>
                </a>

                <nav class="nav">
                    <ul class="nav-list">
                        <li><a href="index.php#services">Услуги</a></li>
                        <li><a href="index.php#advantages">О нас</a></li>
                        <li><a href="index.php#technologies">Технологии</a></li>
                        <li><a href="index.php#doctors">Врачи</a></li>
                        <li><a href="index.php#reviews">Отзывы</a></li>
                        <li><a href="index.php#contacts">Контакты</a></li>

                        <?php if (isset($_SESSION['patient_id'])): ?>
                            <li><a href="logout.php" class="nav-link">Выйти</a></li>
                        <?php elseif (isset($_SESSION['dentist_id'])): ?>
                            <li><a href="logout.php" class="nav-link">Выйти</a></li>
                        <?php else: ?>
                            <li><a href="login.php" class="nav-link btn-login">Войти</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php if (isset($_SESSION['patient_id'])): ?>
                    <a href="profile.php" class="nav-link btn btn-primary">Личный кабинет</a>
                <?php elseif (isset($_SESSION['dentist_id'])): ?>
                    <a href="dentist-profile.php" class="nav-link btn btn-primary">Кабинет врача</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-appointment">
                        Запись на прием
                    </a>
                <?php endif; ?>

            </div>
        </div>
    </header>