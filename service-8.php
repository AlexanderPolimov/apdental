<?php
require_once 'includes/init.php';

$page_id = 8;
$page_title = 'Профессиональная гигиена зубов';
$page_subtitle = 'Чистка и отбеливание зубов для здоровья и красоты';

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
        <img src="img/gigiena.jpg" alt="<?= $page_title ?>" class="service-bg">
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
                    <h2 class="service-section-title">Процедуры гигиены</h2>
                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/spray.svg" alt="Спрей">
                            </div>
                            <div>
                                <h3>Air Flow</h3>
                                <p>Снятие мягкого налета и пигментации</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/tooth-solid.svg" alt="Зуб">
                            </div>
                            <div>
                                <h3>Ультразвук</h3>
                                <p>Удаление зубного камня без повреждения эмали</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/lightbulb.svg" alt="Лампа">
                            </div>
                            <div>
                                <h3>Отбеливание</h3>
                                <p>ZOOM 4, лазерное, домашнее отбеливание</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/shield-alt.svg" alt="Щит">
                            </div>
                            <div>
                                <h3>Фторирование</h3>
                                <p>Укрепление эмали, профилактика кариеса</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="service-section">
                    <h2 class="service-section-title">Преимущества профессиональной гигиены</h2>
                    <ul class="benefits-list">
                        <li>Профилактика кариеса и заболеваний десен</li>
                        <li>Осветление зубов на 1-2 тона</li>
                        <li>Устранение неприятного запаха изо рта</li>
                        <li>Обучение правильной домашней гигиене</li>
                        <li>Раннее выявление стоматологических проблем</li>
                        <li>Улучшение эстетики улыбки</li>
                    </ul>
                </div>

                <div class="service-section">
                    <h2 class="service-section-title">Этапы чистки</h2>
                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="step-number">1</div>
                            <div>
                                <h3>Осмотр</h3>
                                <p>Оценка состояния зубов и десен</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">2</div>
                            <div>
                                <h3>Ультразвук</h3>
                                <p>Удаление зубного камня</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">3</div>
                            <div>
                                <h3>Air Flow</h3>
                                <p>Снятие налета и пигментации</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">4</div>
                            <div>
                                <h3>Полировка и фторирование</h3>
                                <p>Гладкость эмали и защита</p>
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
                            <img src="img/award-solid.svg" alt="Лицензия">
                            <h4>Air Flow Expert</h4>
                            <p>Сертифицированный специалист</p>
                        </div>
                        <div class="certificate-item">
                            <img src="img/lightbulb.svg" alt="Лампа">
                            <h4>ZOOM Whitening</h4>
                            <p>Официальный партнер</p>
                        </div>
                        <div class="certificate-item">
                            <img src="img/flask.svg" alt="Колба">
                            <h4>Контроль стерилизации</h4>
                            <p>Класс А</p>
                        </div>
                    </div>
                </div>

                <div class="warning-box">
                    <h4><i class="fas fa-calendar-alt"></i> Рекомендация</h4>
                    <p>Профессиональную гигиену необходимо проводить каждые 6 месяцев для поддержания здоровья полости
                        рта.</p>
                </div>

                <div class="success-box">
                    <h4><i class="fas fa-gift"></i> Подарок</h4>
                    <p>При комплексной гигиене - набор индивидуальных средств гигиены в подарок!</p>
                </div>

            </div>
        </div>

        <div class="cta-box">
            <h2 class="cta-title">Подарите зубам здоровье и красоту!</h2>
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