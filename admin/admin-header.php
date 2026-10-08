<?php
function nav_active(string $needle): string
{
    return strpos($_SERVER['PHP_SELF'], $needle) !== false ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Админ-панель | AP Dental' ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../img/icon.ico">
</head>

<body class="admin-body">
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="logo">
                <img src="../img/logo.jpg" alt="AP Dental" class="logo-img">
                <div class="admin-logo">
                    <a href="index.php">
                        AP Dental</a>
                    <span>Админ-панель</span>
                </div>
            </div>

            <nav class="admin-nav">
                <a href="index.php" class="<?= nav_active('index') ?>">
                    Панель управления
                </a>
                <a href="appointments.php" class="<?= nav_active('appointments') ?>">
                    Записи
                </a>
                <a href="patients.php" class="<?= nav_active('patients') ?>">
                    Пациенты
                </a>
                <a href="dentists.php" class="<?= nav_active('dentists') ?>">
                    Врачи
                </a>
            </nav>

            <div class="admin-user">
                <span class="admin-user-name">
                    <i class="fas fa-user-circle"></i>
                    <?= htmlspecialchars($_SESSION['administrator_full_name'] ?? 'Админ') ?>
                </span>
                <a href="logout.php" class="admin-logout" title="Выйти">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>
</body>

</html>