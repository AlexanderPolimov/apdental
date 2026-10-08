<?php
require_once 'includes/init.php';

$page_id = 1;
$page_title = 'Лечение под наркозом';
$page_subtitle = 'Безопасное и комфортное лечение зубов без страха и боли';

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
        <img src="img/narkoz-bg.jpg" alt="<?= $page_title ?>" class="service-bg">
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
                    <p>Современная стоматология предлагает безопасные методы обезболивания, которые делают лечение зубов
                        комфортным и безболезненным. Наркоз и седация позволяют провести даже самые сложные процедуры
                        без стресса для пациента.</p>

                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/user-md.svg" alt="Врач">
                            </div>
                            <div>
                                <h3>Профессиональные анестезиологи</h3>
                                <p>В нашей клинике работают сертифицированные анестезиологи-реаниматологи с многолетним
                                    опытом</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/shield-alt.svg" alt="Щит">
                            </div>
                            <div>
                                <h3>Безопасность</h3>
                                <p>Используем только сертифицированные препараты и современное оборудование для
                                    мониторинга</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/heartbeat.svg" alt="Сердце">
                            </div>
                            <div>
                                <h3>Индивидуальный подход</h3>
                                <p>Дозировка подбирается индивидуально с учетом возраста, веса и состояния здоровья
                                    пациента</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <img src="img/clock.svg" alt="Время">
                            </div>
                            <div>
                                <h3>Быстрое восстановление</h3>
                                <p>Пациент быстро приходит в себя после процедуры без побочных эффектов</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="service-section">
                    <h2 class="service-section-title">Показания к применению</h2>
                    <ul class="benefits-list">
                        <li>Сложные хирургические вмешательства (удаление зубов, имплантация)</li>
                        <li>Панический страх перед стоматологическим лечением (дентофобия)</li>
                        <li>Повышенный рвотный рефлекс</li>
                        <li>Лечение зубов у детей</li>
                        <li>Аллергия на местные анестетики</li>
                        <li>Необходимость проведения нескольких процедур за один визит</li>
                    </ul>
                </div>

                <div class="service-section">
                    <h2 class="service-section-title">Как проходит процедура</h2>
                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="feature-icon-step">
                                <span class="step-number">1</span>
                            </div>
                            <div>
                                <h3>Консультация</h3>
                                <p>Врач-анестезиолог проводит осмотр, изучает историю болезни и подбирает оптимальный
                                    метод обезболивания</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon-step">
                                <span class="step-number">2</span>
                            </div>
                            <div>
                                <h3>Подготовка</h3>
                                <p>Пациенту устанавливают катетер для введения препаратов и подключают к монитору
                                    жизненных показателей</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon-step">
                                <span class="step-number">3</span>
                            </div>
                            <div>
                                <h3>Лечение</h3>
                                <p>Стоматолог проводит необходимые процедуры, пока пациент находится под действием
                                    седации</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon-step">
                                <span class="step-number">4</span>
                            </div>
                            <div>
                                <h3>Восстановление</h3>
                                <p>После процедуры пациент отдыхает в комфортной палате под наблюдением медицинского
                                    персонала</p>
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
                    <h2 class="service-section-title">Лицензии и сертификаты</h2>
                    <div class="certificates-grid">
                        <div class="certificate-item">
                            <img src="img/award-solid.svg" alt="Лицензия">
                            <h4>Лицензия на анестезиологию</h4>
                            <p>№ ЛО-77-01-018945</p>
                        </div>
                        <div class="certificate-item">
                            <img src="img/file-medical.svg" alt="Файл">
                            <h4>Сертификат анестезиолога</h4>
                            <p>Европейская ассоциация анестезиологов</p>
                        </div>
                        <div class="certificate-item">
                            <img src="img/heartbeat.svg" alt="Сердце">
                            <h4>Реанимационное оборудование</h4>
                            <p>Сертифицировано Минздравом РФ</p>
                        </div>
                    </div>
                </div>


            </div>
        </div>

        <div class="cta-box">
            <h2 class="cta-title">Готовы к лечению без страха?</h2>
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