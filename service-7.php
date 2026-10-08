<?php
require_once 'includes/init.php';

$page_id = 7;
$page_title = 'Протезирование зубов';
$page_subtitle = 'Восстановление утраченных зубов любой сложности';

// Получаем услуги данной категории
$services_query = "
    SELECT 
        service_id,
        service_name,
        service_description,
        service_cost,
        service_duration
    FROM services
    WHERE service_category_id = ? AND service_cost > 0
    ORDER BY service_cost
";
$stmt = $connection->prepare($services_query);
$stmt->bind_param('i', $page_id);
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Получаем врачей этой специализации
$specialists_query = "
    SELECT 
        d.dentist_id,
        d.dentist_full_name,
        d.dentist_experience_years,
        d.dentist_description,
        d.dentist_photo,
        s.specialization_name
    FROM dentists d
    LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
    JOIN dentists_service_categories dsc ON d.dentist_id = dsc.dentist_id
    WHERE dsc.service_category_id = ?
    LIMIT 3
";
$stmt = $connection->prepare($specialists_query);
$stmt->bind_param('i', $page_id);
$stmt->execute();
$specialists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$title = $page_title . ' | AP Dental';

include_once 'templates/header.php';
?>

<div class="breadcrumbs">
    <div class="container">
        <ul class="breadcrumbs-list">
            <li class="breadcrumbs-item"><a href="index.php">Главная</a></li>
            <li class="breadcrumbs-item"><a href="index.php#services">Услуги</a></li>
            <li class="breadcrumbs-item"><?= $page_title ?></li>
        </ul>
    </div>
</div>

<section class="service-header">
    <div class="container">
        <h1 class="medical-service-title"><?= $page_title ?></h1>
        <p class="service-subtitle"><?= $page_subtitle ?></p>
    </div>
    <div class="service-image">
        <img src="img/protezirovanie.png" alt="<?= $page_title ?>" class="service-bg">
    </div>
</section>

<section class="service-content">
    <div class="container">
        <div class="service-grid">
            <div class="service-main">

                <div class="service-section">
                    <h2 class="service-section-title">Наши услуги</h2>
                    <div class="services-list">
                        <?php foreach ($services as $service): ?>
                            <div class="service-item-card">
                                <div class="service-item-header">
                                    <h3><?= htmlspecialchars($service['service_name']) ?></h3>
                                    <span class="price"><?= number_format($service['service_cost'], 0, ',', ' ') ?> ₽</span>
                                </div>
                                <p><?= htmlspecialchars($service['service_description']) ?></p>
                                <div class="service-item-footer">
                                    <span class="duration"><i class="fas fa-clock"></i>Длительность:
                                        <?= $service['service_duration'] ?? 60 ?> мин</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="service-section">
                    <h2 class="service-section-title">Виды протезирования</h2>
                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/crown.svg" alt="Корона">
                            </div>
                            <div>
                                <h3>Коронки</h3>
                                <p>Металлокерамика, цирконий, керамика E-max</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/bridge.svg" alt="Мост">
                            </div>
                            <div>
                                <h3>Мостовидные протезы</h3>
                                <p>Восстановление 2-3 отсутствующих зубов подряд</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/teeth.svg" alt="Зубы">
                            </div>
                            <div>
                                <h3>Съемные протезы</h3>
                                <p>Акриловые, нейлоновые, бюгельные с кламмерами</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/anchor.svg" alt="Якорь">
                            </div>
                            <div>
                                <h3>Протезы на имплантах</h3>
                                <p>Коронки и мосты на имплантах, All-on-4</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="service-section">
                    <h2 class="service-section-title">Циркониевые коронки</h2>
                    <ul class="benefits-list">
                        <li>Высокая прочность и долговечность</li>
                        <li>Идеальная эстетика (прозрачность как у натуральных зубов)</li>
                        <li>Биосовместимость (не вызывают аллергии)</li>
                        <li>Минимальная обточка зуба</li>
                        <li>Не темнеют со временем</li>
                        <li>Гарантия 10 лет</li>
                    </ul>
                </div>

                <div class="service-section">
                    <h2 class="service-section-title">Процесс протезирования</h2>
                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="step-number">1</div>
                            <div>
                                <h3>Подготовка</h3>
                                <p>Лечение зубов, снятие слепков, выбор цвета</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">2</div>
                            <div>
                                <h3>Обточка</h3>
                                <p>Минимальная препаровка зубов под коронку</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">3</div>
                            <div>
                                <h3>Примерка</h3>
                                <p>Примерка каркаса, проверка прикуса</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">4</div>
                            <div>
                                <h3>Установка</h3>
                                <p>Фиксация готовой конструкции</p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($specialists)): ?>
                    <div class="service-section">
                        <h2 class="service-section-title">Специалисты</h2>
                        <div class="doctors-grid">
                            <?php foreach ($specialists as $doctor): ?>
                                <div class="doctor-card" style="width: 340px; align-items: center">
                                    <div class="doctor-image">
                                        <img src="img/<?= $doctor['dentist_photo'] ?? 'doctor-default.jpg' ?>"
                                            alt="<?= $doctor['dentist_full_name'] ?>">
                                    </div>

                                    <div class="doctor-info">
                                        <h3><?= $doctor['dentist_full_name'] ?></h3>
                                        <p class="doctor-specialty"><?= $doctor['specialization_name'] ?? 'Стоматолог' ?></p>
                                        <p class="doctor-experience">Стаж: <?= $doctor['dentist_experience_years'] ?> лет</p>
                                        <p class="doctor-description"><?= $doctor['dentist_description'] ?></p>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="service-section">
                    <h2 class="service-section-title">Сертификаты</h2>
                    <div class="certificates-grid">
                        <div class="certificate-item">
                            <img src="img/teeth.svg" alt="Зубы">
                            <h4>Цифровое протезирование</h4>
                            <p>CAD/CAM технологии</p>
                        </div>
                        <div class="certificate-item">
                            <img src="img/crown.svg" alt="Корона">
                            <h4>Керамические реставрации</h4>
                            <p>E.max, Премиум</p>
                        </div>
                    </div>
                </div>

                <div class="success-box">
                    <h4><i class="fas fa-gem"></i> Керамика E-max</h4>
                    <p>Самые эстетичные коронки для передних зубов. Полная имитация натуральной эмали.</p>
                </div>

                <div class="success-box">
                    <h4><i class="fas fa-camera"></i> 3D-моделирование</h4>
                    <p>Визуализация результата до начала лечения. Вы увидите свою новую улыбку!</p>
                </div>

            </div>
        </div>

        <div class="cta-box">
            <h2 class="cta-title">Верните себе возможность улыбаться!</h2>
            <div class="cta-buttons">
                <a href="appointment.php?category=<?= $page_id ?>" class="btn btn-primary btn-large">
                    <i class="fas fa-calendar-alt"></i> Записаться онлайн
                </a>
            </div>
        </div>
    </div>
</section>

<?php include_once 'templates/footer.php'; ?>

<script src="./js/script.js"></script>