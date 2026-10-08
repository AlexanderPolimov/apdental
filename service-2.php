<?php
require_once 'includes/init.php';

$page_id = 2;
$page_title = 'Имплантация';
$page_subtitle = 'Восстановление зубов на всю жизнь с гарантией до 25 лет';

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
        <img src="img/implant.jpg" alt="<?= $page_title ?>" class="service-bg">
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
                    <h2 class="service-section-title">О процедуре</h2>
                    <p>Имплантация зубов - это современный метод восстановления утраченных зубов, который позволяет
                        вернуть не только эстетику, но и полноценную функцию жевания. Имплант заменяет корень зуба и
                        служит надежной опорой для коронки.</p>
                </div>

                <div class="service-section">
                    <h2 class="service-section-title">Виды имплантации</h2>
                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/circle-check.svg" alt="Круг">
                            </div>
                            <div>
                                <h3>Одноэтапная</h3>
                                <p>Установка импланта сразу после удаления зуба. Сроки: 1-2 визита</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/calendar.svg" alt="Календарь">
                            </div>
                            <div>
                                <h3>Двухэтапная</h3>
                                <p>Классический метод с периодом остеоинтеграции. Сроки: 3-6 месяцев</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/allergies.svg" alt="Аллергия">
                            </div>
                            <div>
                                <h3>All-on-4/All-on-6</h3>
                                <p>Полное восстановление зубного ряда на 4 или 6 имплантах</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/bone.svg" alt="Кость">
                            </div>
                            <div>
                                <h3>С костной пластикой</h3>
                                <p>При недостатке костной ткани. С предварительным наращиванием</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="service-section">
                    <h2 class="service-section-title">Преимущества имплантации</h2>
                    <ul class="benefits-list">
                        <li>Пожизненная служба имплантов</li>
                        <li>Сохранение костной ткани</li>
                        <li>Естественный внешний вид</li>
                        <li>Полноценная жевательная функция</li>
                        <li>Не требуется обточка соседних зубов</li>
                        <li>Гарантия до 25 лет</li>
                    </ul>
                </div>

                <div class="service-section">
                    <h2 class="service-section-title">Этапы установки импланта</h2>
                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="step-number">1</div>
                            <div>
                                <h3>Диагностика</h3>
                                <p>КТ-исследование, планирование 3D, выбор имплантационной системы</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">2</div>
                            <div>
                                <h3>Установка импланта</h3>
                                <p>Мини-операция под местной анестезией, длится 30-60 минут</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">3</div>
                            <div>
                                <h3>Остеоинтеграция</h3>
                                <p>Приживление импланта (2-6 месяцев в зависимости от зоны)</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="step-number">4</div>
                            <div>
                                <h3>Установка коронки</h3>
                                <p>Фиксация постоянной коронки на абатмент</p>
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
                            <h4>Сертифицированный центр Nobel</h4>
                            <p>Официальный партнер</p>
                        </div>
                        <div class="certificate-item">
                            <img src="img/certificate.svg" alt="Курсы">
                            <h4>Имплантолог-эксперт</h4>
                            <p>Курсы Nobel Biocare</p>
                        </div>
                        <div class="certificate-item">
                            <img src="img/flask.svg" alt="Колба">
                            <h4>Стерилизация класса А</h4>
                            <p>Соответствие стандартам</p>
                        </div>
                    </div>
                </div>

                <div class="warning-box">
                    <h4><i class="fas fa-exclamation-triangle"></i> Важно!</h4>
                    <p>После установки импланта необходимо соблюдать рекомендации врача и регулярно проходить
                        профилактические осмотры.</p>
                </div>

            </div>
        </div>

        <div class="cta-box">
            <h2 class="cta-title">Верните себе красивую улыбку!</h2>
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