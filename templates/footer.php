<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-logo">
                <div class="logo">
                    <img src="./img/logo.jpg" alt="AP Dental" class="logo-img">
                    <div class="logo-text">
                        <span class="logo-title">AP Dental</span>
                    </div>
                </div>
                <p class="footer-text">Стоматология премиум-класса в Екатеринбурге</p>

                <div class="footer-social">
                    <div class="social-icons">
                        <a href="https://instagram.com/" class="social-icon" target="_blank">
                            <img src="./img/instagram.svg" alt="Instagram">
                        </a>
                        <a href="https://telegram.org/" class="social-icon" target="_blank">
                            <img src="./img/telegram.svg" alt="Telegram">
                        </a>
                        <a href="https://vk.com/" class="social-icon" target="_blank">
                            <img src="./img/vk.svg" alt="VK">
                        </a>
                    </div>
                </div>
            </div>

            <div class="footer-links">
                <h4 class="footer-title">Контакты</h4>
                <ul class="footer-list">
                    <li>
                        <div>
                            <strong>ул. Добролюбова, 16/3</strong>
                            <p>Ежедневно с 9:00 до 21:00</p>
                        </div>
                    </li>
                    <li>
                        <div>
                            <strong>+7 (495) 023-02-17</strong>
                            <p>Телефон для записи</p>
                        </div>
                    </li>
                    <li>
                        <div>
                            <strong>dl_clinic@mail.ru</strong>
                            <p>Электронная почта</p>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="footer-col">
                <h3 class="footer-title">Запись онлайн</h3>
                <p class="footer-text">Запишитесь через наш сайт и получите<br>бесплатную консультацию!</p>

                <?php if (is_auth()): ?>
                    <a href="appointment.php" class="btn btn-primary">Записаться на прием</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Записаться на прием</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="copyright">© <?= date('Y') ?> AP Dental Clinic. Все права защищены.</p>
        </div>
    </div>
</footer>

<script src="./js/script.js"></script>
</body>

</html>