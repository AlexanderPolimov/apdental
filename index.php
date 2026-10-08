<?php
require_once 'includes/init.php';

$title = "AP Dental - Стоматологическая клиника премиум-класса";

$dentists = get_all_dentists($connection);
$categories = get_service_categories_with_min_price($connection);
$reviews = get_all_reviews($connection);
$equipment = get_all_equipment($connection);

if (isset($_POST['callback_submit'])) {
    $_SESSION['callback_success'] = 'Спасибо! Мы перезвоним вам в ближайшее время.';
    header('Location: index.php#contacts');
    exit;
}

include_once 'templates/header.php';
?>

<section class="hero">
    <div class="hero-slider">
        <div class="slides-container">
            <!-- Слайд 1 -->
            <div class="slide active">
                <div class="hero-image">
                    <img src="img/dental1.jpg" alt="Стоматология" class="hero__bg">
                </div>
                <div class="container">
                    <div class="hero-content">
                        <h1 class="hero-title">Идеальная улыбка за 70 000 ₽ +</h1>
                        <h2 class="hero-subtitle">Рассрочка до 12 месяцев без %</h2>
                        <div class="hero-buttons">
                            <?php if (isset($_SESSION['patient_id'])): ?>
                                <a href="appointment.php" class="btn btn-secondary btn-large">Запись онлайн</a>
                            <?php elseif (isset($_SESSION['dentist_id'])): ?>
                                <a href="dentist-profile.php" class="btn btn-secondary btn-large">Запись онлайн</a>
                            <?php else: ?>
                                <a href="register.php" class="btn btn-secondary btn-large">Запись онлайн</a>
                            <?php endif; ?>
                        </div>
                        <div class="hero-features">
                            <div class="feature">
                                <img src="img/tooth.svg" alt="Зуб">
                                <span>Стоматология премиум-класса</span>
                            </div>
                            <div class="feature">
                                <img src="img/gift-solid-full.svg" alt="Подарок">
                                <span>Подарочные сертификаты</span>
                            </div>
                            <div class="feature">
                                <img src="img/heart-solid-full.svg" alt="Сердце">
                                <span>Здоровье близких</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Слайд 2 -->
            <div class="slide">
                <div class="hero-image">
                    <img src="img/dental2.png" alt="Оборудование" class="hero__bg">
                    <div class="hero-overlay"></div>
                </div>
                <div class="container">
                    <div class="hero-content">
                        <h1 class="hero-title">Современное оборудование</h1>
                        <h2 class="hero-subtitle">Безопасность и комфорт</h2>
                        <div class="hero-buttons">
                            <a href="#technologies" class="btn btn-secondary btn-large">Наши технологии</a>
                        </div>
                        <div class="hero-features">
                            <div class="feature">
                                <img src="img/microscope.svg" alt="Микроскоп">
                                <span>3D-диагностика</span>
                            </div>
                            <div class="feature">
                                <img src="img/shield.svg" alt="Щит">
                                <span>Стерилизация</span>
                            </div>
                            <div class="feature">
                                <img src="img/star.svg" alt="Звезда">
                                <span>Премиум материалы</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Слайд 3 -->
            <div class="slide">
                <div class="hero-image">
                    <img src="img/dental3.jpg" alt="Врачи" class="hero__bg">
                    <div class="hero-overlay"></div>
                </div>
                <div class="container">
                    <div class="hero-content">
                        <h1 class="hero-title">Опытные специалисты</h1>
                        <h2 class="hero-subtitle">Более 15 лет опыта</h2>
                        <div class="hero-buttons">
                            <a href="#doctors" class="btn btn-secondary btn-large">Наши врачи</a>
                        </div>
                        <div class="hero-features">
                            <div class="feature">
                                <img src="img/doctor.svg" alt="Врач">
                                <span>Врачи эксперты</span>
                            </div>
                            <div class="feature">
                                <img src="img/award.svg" alt="Награда">
                                <span>Сертификаты</span>
                            </div>
                            <div class="feature">
                                <img src="img/hand-holding-heart.svg" alt="Сердце">
                                <span>Индивидуальный подход</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button class="slider-btn slider-btn-prev" aria-label="Предыдущий слайд">
            <img src="img/angle-left-solid.svg" alt="Стрелка влево">
        </button>
        <button class="slider-btn slider-btn-next" aria-label="Следующий слайд">
            <img src="img/angle-right-solid.svg" alt="Стрелка вправо">
        </button>

        <div class="slider-dots"></div>
    </div>
</section>

<section class="advantages" id="advantages">
    <div class="container">
        <h2 class="section-title">О нас</h2>
        <p class="section-subtitle">
            AP Dental — стоматология, где встречаются безупречная эстетика, передовая наука и искусство заботы. Мы
            создали пространство, в котором визит к стоматологу перестает быть необходимостью, а становится осознанным
            вкладом в качество вашей жизни. AP Dental — это стоматология для тех, кто ценит лучшее.
        </p>

        <div class="advantages-grid">
            <div class="advantage">
                <div class="advantage-number">1</div>
                <h3 class="advantage-title">Коллектив</h3>
                <p class="advantage-text">Высококвалифицированные врачи с многолетним опытом</p>
            </div>
            <div class="advantage">
                <div class="advantage-number">2</div>
                <h3 class="advantage-title">Оборудование</h3>
                <p class="advantage-text">Современное оборудование последнего поколения</p>
            </div>
            <div class="advantage">
                <div class="advantage-number">3</div>
                <h3 class="advantage-title">Атмосфера</h3>
                <p class="advantage-text">Уютная и комфортная обстановка для пациентов</p>
            </div>
            <div class="advantage">
                <div class="advantage-number">4</div>
                <h3 class="advantage-title">Индивидуальный подход</h3>
                <p class="advantage-text">Персональное внимание к каждому пациенту</p>
            </div>
            <div class="advantage">
                <div class="advantage-number">5</div>
                <h3 class="advantage-title">Полноценная диагностика</h3>
                <p class="advantage-text">Точная диагностика перед началом лечения</p>
            </div>
            <div class="advantage">
                <div class="advantage-number">6</div>
                <h3 class="advantage-title">Ценовая политика</h3>
                <p class="advantage-text">Доступные цены и удобные варианты оплаты</p>
            </div>
        </div>
    </div>
</section>

<section class="services" id="services">
    <div class="container">
        <h2 class="section-title">Наши услуги</h2>

        <?php if (!empty($categories)): ?>
            <div class="services-grid">
                <?php foreach ($categories as $category): ?>
                    <?php
                    $page_link = "service-{$category['service_category_id']}.php";
                    $page_link = file_exists(__DIR__ . '/' . $page_link) ? $page_link : '#';
                    ?>
                    <a href="<?= $page_link ?>" class="service-link">
                        <div class="service-card">
                            <div class="service-icon">
                                <img src="img/category-<?= $category['service_category_id'] ?>.svg"
                                    alt="<?= htmlspecialchars($category['service_category_name']) ?>"
                                    onerror="this.src='img/service-default.svg'">
                            </div>
                            <h3 class="service-title"><?= htmlspecialchars($category['service_category_name']) ?></h3>
                            <?php if (!empty($category['min_price']) && $category['min_price'] > 0): ?>
                                <p class="service-price">от <?= number_format($category['min_price'], 0, ',', ' ') ?> ₽</p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert-info">
                <p>Услуги временно недоступны. Приносим извинения за неудобства.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="doctors" id="doctors">
    <div class="container">
        <h2 class="section-title">Наши врачи</h2>

        <?php if (!empty($dentists)): ?>
            <div class="doctors-grid">
                <?php foreach ($dentists as $dentist): ?>
                    <div class="doctor-card">
                        <div class="doctor-image">
                            <img src="img/<?= htmlspecialchars($dentist['dentist_photo']) ?>"
                                alt="<?= htmlspecialchars($dentist['dentist_full_name']) ?>">
                        </div>
                        <div class="doctor-info">
                            <h3 class="doctor-name"><?= htmlspecialchars($dentist['dentist_full_name']) ?></h3>
                            <p class="doctor-specialty"><?= htmlspecialchars($dentist['specialization_name'] ?? 'Стоматолог') ?>
                            </p>
                            <p class="doctor-experience">Опыт работы: <?= $dentist['dentist_experience_years'] ?> лет</p>
                            <?php if (is_auth()): ?>
                                <a href="appointment.php?dentist=<?= $dentist['dentist_id'] ?>"
                                    class="btn btn-secondary">Записаться</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-secondary">Записаться</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center">Информация о врачах временно недоступна</p>
        <?php endif; ?>
    </div>
</section>

<section class="technologies" id="technologies">
    <div class="container">
        <h2 class="section-title">Технологии и оборудование</h2>

        <div class="tech-cards">
            <?php if (!empty($equipment)): ?>
                <?php foreach ($equipment as $index => $item): ?>
                    <div class="tech-card <?= $index === 0 ? 'active' : '' ?>" id="equipment<?= $item['equipment_id'] ?>">
                        <img src="img/<?= htmlspecialchars($item['equipment_photo']) ?>"
                            alt="<?= htmlspecialchars($item['equipment_name']) ?>" class="tech-card-image"
                            onerror="this.src='img/equipment-default.jpg'">

                        <div class="tech-card-content">
                            <h3 class="tech-card-title"><?= htmlspecialchars($item['equipment_name']) ?></h3>
                            <p class="tech-card-description"><?= htmlspecialchars($item['equipment_description']) ?></p>

                            <div class="tech-features-list">
                                <?php if (!empty($item['equipment_specifications'])): ?>
                                    <?php
                                    $specs = explode(';', $item['equipment_specifications']);
                                    foreach ($specs as $spec):
                                        if (trim($spec) !== ''):
                                            ?>
                                            <div class="tech-feature-item">
                                                <img src="img/circle-check-solid-full.svg" alt="Галочка">
                                                <span><?= htmlspecialchars(trim($spec)) ?></span>
                                            </div>
                                            <?php
                                        endif;
                                    endforeach;
                                    ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Информация об оборудовании временно недоступна</p>
            <?php endif; ?>
        </div>

        <div class="equipment-navigation">
            <h3 class="nav-title">Наше оборудование</h3>
            <div class="equipment-list">
                <?php if (!empty($equipment)): ?>
                    <?php foreach ($equipment as $index => $item): ?>
                        <button class="equipment-item <?= $index === 0 ? 'active' : '' ?>"
                            data-target="equipment<?= $item['equipment_id'] ?>">
                            <div class="equipment-icon">
                                <img src="img/<?= htmlspecialchars($item['equipment_photo']) ?>"
                                    alt="<?= htmlspecialchars($item['equipment_name']) ?>">
                            </div>
                            <div class="equipment-info">
                                <h4><?= htmlspecialchars(mb_strimwidth($item['equipment_name'], 0, 35, '...')) ?></h4>
                                <p><?= htmlspecialchars(mb_strimwidth($item['equipment_description'], 0, 30, '...')) ?></p>
                            </div>
                            <img src="img/angle-right-solid-black.svg" alt="Стрелка вправо" class="equipment-right-icon">
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="reviews" id="reviews">
    <div class="container">
        <h2 class="section-title">Отзывы пациентов</h2>

        <?php if (!empty($reviews)): ?>
            <div class="reviews-grid" id="reviewsContainer">
                <?php foreach ($reviews as $index => $review): ?>
                    <div class="review-card <?= $index >= 2 ? 'review-hidden' : '' ?>" data-index="<?= $index ?>">
                        <div class="review-header">
                            <div class="review-author">
                                <div class="author-avatar">
                                    <img src="img/user-solid-full.svg" alt="Юзер аватар">
                                </div>
                                <div class="author-info">
                                    <h4 class="author-name"><?= htmlspecialchars($review['review_client_name']) ?></h4>
                                    <p class="review-date"><?= format_russian_date($review['review_date_added']) ?></p>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $review['review_rating']): ?>
                                        ⭐
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="review-text"><?= htmlspecialchars($review['review_text']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($reviews) > 2): ?>
                <div class="reviews-action">
                    <button id="showAllReviews" class="btn btn-secondary">
                        Показать все отзывы (ещё <?= count($reviews) - 2 ?>)
                    </button>
                    <button id="hideReviews" class="btn btn-outline-secondary" style="display: none;">
                        Свернуть отзывы
                    </button>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-center">Отзывы временно недоступны</p>
        <?php endif; ?>
    </div>
</section>

<section class="contacts" id="contacts">
    <div class="container">
        <h2 class="section-title">Контакты</h2>
        <div class="contacts-grid">
            <div class="contact-info">
                <div class="contact-item">
                    <h3 class="contact-title">AP Dental</h3>
                    <p class="contact-subtitle">Если у вас остались вопросы или нужна консультация —
                        оставьте заявку, и мы свяжемся с вами в ближайшее время!</p>
                    <p class="contact-address">г. Екатеринбург, Ул. Добролюбова, 16/3</p>
                    <p class="contact-time">🕑 Режим работы: Пн-Вс 09:00 - 21:00</p>
                    <p class="contact-phone">📞 +7 (495) 023-02-17</p>
                    <p class="contact-email">✉️ dl_clinic@mail.ru</p>
                </div>
            </div>
            <div class="contact-form">
                <h3 class="form-title">Оставить заявку на звонок</h3>

                <?php if (isset($_SESSION['callback_sent'])): ?>
                    <div class="alert alert-success">
                        <?= $_SESSION['callback_sent'] ?>
                    </div>
                    <?php unset($_SESSION['callback_sent']); ?>
                <?php endif; ?>

                <form action="callback.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Ваше имя" required>
                    </div>
                    <div class="form-group">
                        <input type="tel" name="phone" placeholder="Телефон" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Отправить</button>
                </form>
            </div>
        </div>
    </div>
</section>

<script src="./js/script.js"></script>

<?php
include_once 'templates/footer.php';
?>