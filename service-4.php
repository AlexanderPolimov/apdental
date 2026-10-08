<?php
require_once 'includes/init.php';

$page_id = 4;
$page_title = 'Ортодонтия';
$page_subtitle = 'Идеально ровные зубы в любом возрасте';

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
        <img src="img/ortodontiya.png" alt="<?= $page_title ?>" class="service-bg">
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
                    <h2 class="service-section-title">Виды ортодонтического лечения</h2>
                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/smile.svg" alt="Улыбка">
                            </div>
                            <div>
                                <h3>Брекет-системы</h3>
                                <p>Металлические, керамические, сапфировые, лингвальные</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/teeth.svg" alt="Зубы">
                            </div>
                            <div>
                                <h3>Элайнеры</h3>
                                <p>Прозрачные каппы для незаметного выравнивания</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/child.svg" alt="Ребенок">
                            </div>
                            <div>
                                <h3>Детская ортодонтия</h3>
                                <p>Съемные пластинки, трейнеры, миофункциональные аппараты</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/retweet.svg" alt="Ретвит">
                            </div>
                            <div>
                                <h3>Ретенция</h3>
                                <p>Закрепление результатов после снятия брекетов</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="service-section">
                    <h2 class="service-section-title">Преимущества элайнеров</h2>
                    <ul class="benefits-list">
                        <li>Невидимы на зубах</li>
                        <li>Съемные - можно снять для еды и чистки</li>
                        <li>Не травмируют слизистую</li>
                        <li>Предсказуемый результат в 3D</li>
                        <li>Гипоаллергенный материал</li>
                        <li>Подходят аллергикам</li>
                    </ul>
                </div>

                <div class="service-section">
                    <h2 class="service-section-title">Этапы лечения</h2>
                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="step-number">1</div>
                            <div>
                                <h3>Диагностика</h3>
                                <p>Осмотр, снимки, слепки, планирование лечения</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">2</div>
                            <div>
                                <h3>Подготовка</h3>
                                <p>Профессиональная гигиена, лечение кариеса</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">3</div>
                            <div>
                                <h3>Установка</h3>
                                <p>Фиксация брекетов или выдача элайнеров</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">4</div>
                            <div>
                                <h3>Наблюдение</h3>
                                <p>Регулярные визиты для коррекции</p>
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
                            <img src="img/smile.svg" alt="Улыбка">
                            <h4>Invisalign-провайдер</h4>
                            <p>Сертифицированный специалист</p>
                        </div>
                        <div class="certificate-item">
                            <img src="img/file-medical.svg" alt="Файл">
                            <h4>Система Damon</h4>
                            <p>Официальный партнер</p>
                        </div>
                    </div>
                </div>

                <div class="success-box">
                    <h4><i class="fas fa-crown"></i> Результат</h4>
                    <p>После ортодонтического лечения вы получите не только ровные зубы, но и правильный прикус, что
                        улучшит здоровье в целом.</p>
                </div>

            </div>
        </div>

        <div class="cta-box">
            <h2 class="cta-title">Пора меняться к лучшему!</h2>
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