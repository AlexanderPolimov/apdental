<?php
require_once 'includes/init.php';

$page_id = 5;
$page_title = 'Пародонтология';
$page_subtitle = 'Лечение десен и сохранение зубов';

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
        <img src="img/paradontologiya.jpg" alt="<?= $page_title ?>" class="service-bg">
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
                    <h2 class="service-section-title">Заболевания десен</h2>
                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/triangle.svg" alt="Треугольник">
                            </div>
                            <div>
                                <h3>Гингивит</h3>
                                <p>Воспаление десен без нарушения зубодесневого соединения</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/times-circle.svg" alt="Временой круг">
                            </div>
                            <div>
                                <h3>Пародонтит</h3>
                                <p>Воспаление с разрушением костной ткани и образованием карманов</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/arrow-down.svg" alt="Стрелка">
                            </div>
                            <div>
                                <h3>Пародонтоз</h3>
                                <p>Дистрофическое поражение пародонта без воспаления</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/hand-holding-heart-solid.svg" alt="Сердце">
                            </div>
                            <div>
                                <h3>Профилактика</h3>
                                <p>Регулярная гигиена и профессиональная чистка</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="service-section">
                    <h2 class="service-section-title">Методы лечения</h2>
                    <ul class="benefits-list">
                        <li>Профессиональная гигиена полости рта</li>
                        <li>Вектор-терапия (Vector Paro)</li>
                        <li>Кюретаж пародонтальных карманов</li>
                        <li>Лоскутные операции</li>
                        <li>Плазмолифтинг десен</li>
                        <li>Медикаментозная терапия</li>
                    </ul>
                </div>

                <div class="service-section">
                    <h2 class="service-section-title">Вектор-терапия</h2>
                    <p>Современный безоперационный метод лечения пародонтита с использованием аппарата Vector.
                        Ультразвуковые колебания очищают пародонтальные карманы без повреждения тканей.</p>
                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="step-number">1</div>
                            <div>
                                <h3>Диагностика</h3>
                                <p>Измерение глубины карманов, панорамный снимок</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">2</div>
                            <div>
                                <h3>Очистка</h3>
                                <p>Безболезненная очистка карманов аппаратом Vector</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">3</div>
                            <div>
                                <h3>Полировка</h3>
                                <p>Полировка корней специальной суспензией</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">4</div>
                            <div>
                                <h3>Контроль</h3>
                                <p>Назначение домашнего ухода, контрольный осмотр</p>
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
                            <h4>Пародонтолог-эксперт</h4>
                            <p>Высшая категория</p>
                        </div>
                        <div class="certificate-item">
                            <img src="img/award-solid.svg" alt="Лицензия">
                            <h4>Вектор-терапия</h4>
                            <p>Сертифицированный специалист</p>
                        </div>
                    </div>
                </div>

                <div class="warning-box">
                    <h4><i class="fas fa-heart"></i> Важно!</h4>
                    <p>Заболевания десен - основная причина потери зубов у взрослых. Регулярные осмотры у пародонтолога
                        помогут сохранить зубы здоровыми.</p>
                </div>

            </div>
        </div>

        <div class="cta-box">
            <h2 class="cta-title">Здоровые десны - здоровые зубы!</h2>
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