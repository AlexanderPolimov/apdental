
// ============================================
// ИНИЦИАЛИЗАЦИЯ
// ============================================

/**
 * Точка ввода в приложение, инициализирует все компоненты сайта
 */
document.addEventListener('DOMContentLoaded', function() {
    initSmoothScroll();
    initAppointmentForm();
    initFooterYear();
    initSlider();
    initEquipment();
    initReviews();
    initPatients();
    initAdminAppointments();
    initClientSearch();
    initPhoneMask();
    initDentistFormValidation();
    initPatientFormValidation();
    initConsultationForm();
    initInsuranceMask();
});

// ============================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================

/**
 * Временное уведомление в правом верхнем углу экрана
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `admin-alert-${type}`;
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        min-width: 300px; padding: 16px 20px; border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Прокручивает страницу к указанному элементу
 */
function smoothScrollTo(targetElement, offset = 100) {
    if (targetElement) {
        window.scrollTo({
            top: targetElement.offsetTop - offset,
            behavior: 'smooth'
        });
    }
}

// ============================================
// ПЛАВНАЯ ПРОКРУТКА ДЛЯ НАВИГАЦИИ
// ============================================

/**
 * Инициализирует плавную прокрутку для всех якорных ссылок
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            smoothScrollTo(targetElement);
        });
    });
}

// ============================================
// ФОРМА ЗАПИСИ (Public)
// ============================================

/**
 * Инициализирует и обрабатывает публичную форму записи на прием
 */
function initAppointmentForm() {
    const form = document.querySelector('.contact-form form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const name = new FormData(this).get('name') || 'Пациент';

        const msg = document.createElement('div');
        msg.className = 'alert alert-success';
        msg.textContent = `Спасибо, ${name}! Мы перезвоним вам в ближайшее время.`;
        this.before(msg);
        this.reset();

        setTimeout(() => msg.remove(), 4000);
    });
}

// ============================================
// ГОД В ПОДВАЛЕ САЙТА
// ============================================

/**
 * Автоматически обновляет год в копирайте подвала сайта
 */
function initFooterYear() {
    const yearElement = document.querySelector('.copyright');
    if (!yearElement) return;
    
    const currentYear = new Date().getFullYear();
    yearElement.textContent = yearElement.textContent.replace(/\d{4}/, currentYear);
}

// ============================================
// СЛАЙДЕР
// ============================================

/**
 * Инициализирует главный слайдер на странице, настраивает автоматическую прокрутку, 
 * навигационные кнопки, точки-индикаторы и приостановку при наведении мыши
 */
function initSlider() {
    const slider = document.querySelector('.hero-slider');
    if (!slider) return;
    
    const slidesContainer = slider.querySelector('.slides-container');
    const slides = slider.querySelectorAll('.slide');
    const prevBtn = slider.querySelector('.slider-btn-prev');
    const nextBtn = slider.querySelector('.slider-btn-next');
    const dotsContainer = slider.querySelector('.slider-dots');
    
    if (!slidesContainer || !slides.length || !prevBtn || !nextBtn || !dotsContainer) return;
    
    let currentSlide = 0;
    let autoPlayTimer = null;
    let isOverSlider = false;
    
    // Создание точек-индикаторов
    dotsContainer.innerHTML = '';
    slides.forEach((_, index) => {
        const dot = document.createElement('button');
        dot.className = 'slider-dot';
        if (index === 0) dot.classList.add('active');
        dot.addEventListener('click', () => goToSlide(index));
        dotsContainer.appendChild(dot);
    });
    
    // Начальная позиция слайдера
    slidesContainer.style.transition = 'none';
    slidesContainer.style.transform = 'translateX(0%)';
    
    // Автопрокрутка после полной загрузки страницы
    window.addEventListener('load', () => {
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                slidesContainer.style.transition = 'transform 0.5s ease';
                startAutoPlay();
            });
        });
    });
    
    // Обработчики кнопок навигации
    prevBtn.addEventListener('click', () => {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        updateSlider();
        resetAutoPlay();
    });
    
    nextBtn.addEventListener('click', () => {
        currentSlide = (currentSlide + 1) % slides.length;
        updateSlider();
        resetAutoPlay();
    });
    
    // Отслеживание мыши
    document.addEventListener('mousemove', (e) => {
        const rect = slider.getBoundingClientRect();
        const wasOver = isOverSlider;
        isOverSlider = (
            e.clientX >= rect.left && e.clientX <= rect.right &&
            e.clientY >= rect.top && e.clientY <= rect.bottom
        );
        
        if (!wasOver && isOverSlider) stopAutoPlay();
        if (wasOver && !isOverSlider) startAutoPlay();
    });
    
    // Переключает слайдер на указанный индекс
    function goToSlide(index) {
        currentSlide = index;
        updateSlider();
        resetAutoPlay();
    }
    
    // Обновляет визуальное состояние слайдера
    function updateSlider() {
        slidesContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
        
        slides.forEach((slide, index) => {
            slide.classList.toggle('active', index === currentSlide);
        });
        
        dotsContainer.querySelectorAll('.slider-dot').forEach((dot, index) => {
            dot.classList.toggle('active', index === currentSlide);
        });
    }
    
    function startAutoPlay() {
        if (isOverSlider) return;
        stopAutoPlay();
        autoPlayTimer = setInterval(() => {
            currentSlide = (currentSlide + 1) % slides.length;
            updateSlider();
        }, 5000);
    }
    
    function stopAutoPlay() {
        clearInterval(autoPlayTimer);
    }
    
    function resetAutoPlay() {
        stopAutoPlay();
        startAutoPlay();
    }
    
    // Обработчик изменения размера окна
    window.addEventListener('resize', () => {
        slidesContainer.style.transition = 'none';
        slidesContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
    });
}

// ============================================
// НАВИГАЦИЯ ПО ОБОРУДОВАНИЮ
// ============================================

/**
 * Инициализирует интерактивную навигацию по медицинскому оборудованию
 */
function initEquipment() {
    if (!document.querySelector('.equipment-navigation')) return;
    new EquipmentNavigation();
}

/**
 * Класс для управления навигацией по оборудованию
 * обрабатывает клики по пунктам списка оборудования,
 * показывает соответствующие карточки
 */
class EquipmentNavigation {
    constructor() {
        this.equipmentItems = document.querySelectorAll('.equipment-item');
        this.techCards = document.querySelectorAll('.tech-card');
        this.init();
    }
    
    // Инициализирует обработчики событий и показывает первое оборудование
    init() {
        this.equipmentItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = item.dataset.target;
                this.showEquipment(targetId);
                this.setActiveItem(item);
                this.scrollToCard();
            });
        });
        
        if (this.equipmentItems.length > 0) {
            const firstTarget = this.equipmentItems[0].dataset.target;
            this.showEquipment(firstTarget);
        }
    }

    // Показывает карточку оборудования по ID и скрывает остальные
    showEquipment(targetId) {
        this.techCards.forEach(card => card.classList.remove('active'));
        const targetCard = document.getElementById(targetId);
        if (targetCard) targetCard.classList.add('active');
    }
    
    // Устанавливает активное состояние для выбранного пункта навигации
    setActiveItem(activeItem) {
        this.equipmentItems.forEach(item => item.classList.remove('active'));
        activeItem.classList.add('active');
    }
    
    // Плавно прокручивает страницу к карточкам оборудования
    scrollToCard() {
        const techCards = document.querySelector('.tech-cards');
        smoothScrollTo(techCards);
    }
}

// ============================================
// ПЕРЕКЛЮЧАТЕЛЬ ОТЗЫВОВ
// ============================================

/**
 * Инициализирует кнопки "Показать все отзывы" и "Свернуть отзывы"
 */
function initReviews() {
    const showAllBtn = document.getElementById('showAllReviews');
    const hideBtn = document.getElementById('hideReviews');
    if (!showAllBtn) return;
    
    showAllBtn.addEventListener('click', () => {
        document.querySelectorAll('.review-card').forEach(card => {
            card.classList.remove('review-hidden');
        });
        showAllBtn.style.display = 'none';
        if (hideBtn) hideBtn.style.display = 'inline-block';
    });
    
    if (hideBtn) {
        hideBtn.addEventListener('click', () => {
            document.querySelectorAll('.review-card').forEach((card, index) => {
                card.classList.toggle('review-hidden', index >= 2);
            });
            hideBtn.style.display = 'none';
            showAllBtn.style.display = 'inline-block';
        });
    }
}

// ============================================
// ПЕРЕКЛЮЧАТЕЛЬ ПАЦИЕНТОВ
// ============================================

/**
 * Инициализирует кнопку переключения видимости пациентов
 */
function initPatients() {
    const toggleBtn = document.getElementById('togglePatientsBtn');
    if (!toggleBtn) return;
    
    const hiddenPatients = document.querySelectorAll('.hidden-patient');
    hiddenPatients.forEach(patient => patient.style.display = 'none');
    
    let isExpanded = false;
    
    toggleBtn.addEventListener('click', function() {
        hiddenPatients.forEach(patient => {
            patient.style.display = isExpanded ? 'none' : 'flex';
        });
        isExpanded = !isExpanded;
        this.textContent = isExpanded ? 'Скрыть' : 'Показать всех пациентов';
    });
}

// ============================================
// ФОРМА ЗАПИСИ С КОНСУЛЬТАЦИЯМИ
// ============================================

/**
 * Инициализирует форму записи с динамической загрузкой врачей по категориям
 * и доступного времени для выбранной консультации
 */
function initConsultationForm() {
    const dentistSelect = document.getElementById('dentist_id');
    const dateInput = document.getElementById('appointment_date');
    const timeSelect = document.getElementById('appointment_time');
    const timeLoading = document.getElementById('timeLoading');
    const consultationSelect = document.getElementById('consultation_id');
    
    if (!consultationSelect || !dentistSelect || !dateInput || !timeSelect) return;
    
    // Данные из PHP
    const dentistsByCategory = window.dentistsByCategory || {};
    const allDentists = window.allDentists || [];
    
    /**
     * Загружает доступное время для выбранных параметров
     */
    function loadAvailableTimes() {
        const dentistId = dentistSelect.value;
        const date = dateInput.value;
        const serviceId = consultationSelect.value;
        
        if (!dentistId || !date || !serviceId) {
            timeSelect.innerHTML = '<option value="">-- Сначала выберите дату и врача --</option>';
            timeSelect.disabled = true;
            return;
        }
        
        timeSelect.innerHTML = '<option value="">Загрузка...</option>';
        timeSelect.disabled = true;
        if (timeLoading) timeLoading.style.display = 'block';
        
        fetch(`get-available-times.php?dentist_id=${dentistId}&date=${date}&service_id=${serviceId}`)
            .then(response => response.json())
            .then(data => {
                if (timeLoading) timeLoading.style.display = 'none';
                
                if (data.error) {
                    timeSelect.innerHTML = '<option value="">-- Ошибка загрузки --</option>';
                    return;
                }
                
                if (data.times && data.times.length > 0) {
                    let options = '<option value="">-- Выберите время --</option>';
                    data.times.forEach(time => {
                        options += `<option value="${time}">${time}</option>`;
                    });
                    timeSelect.innerHTML = options;
                    timeSelect.disabled = false;
                } else {
                    timeSelect.innerHTML = '<option value="">-- Нет свободного времени --</option>';
                    timeSelect.disabled = true;
                }
            })
            .catch(() => {
                if (timeLoading) timeLoading.style.display = 'none';
                timeSelect.innerHTML = '<option value="">-- Ошибка загрузки --</option>';
                timeSelect.disabled = true;
            });
    }
    
    // Обработчик изменения консультации
    consultationSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const categoryId = selectedOption.dataset.category;
        
        dentistSelect.innerHTML = '<option value="">-- Сначала выберите консультацию --</option>';
        dentistSelect.disabled = true;
        dateInput.disabled = true;
        dateInput.value = '';
        timeSelect.innerHTML = '<option value="">-- Сначала выберите дату и врача --</option>';
        timeSelect.disabled = true;
        
        if (!this.value) return;
        
        // Получаем врачей для этой категории
        const allowedDentistIds = dentistsByCategory[categoryId] || [];
        
        if (!allowedDentistIds || allowedDentistIds.length === 0) {
            dentistSelect.innerHTML = '<option value="">-- Нет доступных врачей --</option>';
            return;
        }
        
        // Заполняем врачей
        let options = '<option value="">-- Выберите врача --</option>';
        let hasDentists = false;
        
        allDentists.forEach(dentist => {
            if (allowedDentistIds.includes(dentist.dentist_id)) {
                options += `<option value="${dentist.dentist_id}">${dentist.dentist_full_name} (${dentist.specialization_name || 'Стоматолог'})</option>`;
                hasDentists = true;
            }
        });
        
        dentistSelect.innerHTML = options;
        dentistSelect.disabled = !hasDentists;
        
        if (hasDentists) {
            dateInput.disabled = false;
        }
    });
    
    // Обработчик изменения врача
    dentistSelect.addEventListener('change', function() {
        if (this.value && consultationSelect.value && dateInput.value) {
            loadAvailableTimes();
        } else {
            timeSelect.innerHTML = '<option value="">-- Сначала выберите дату и врача --</option>';
            timeSelect.disabled = true;
        }
    });
    
    // Обработчик изменения даты
    dateInput.addEventListener('change', function() {
        if (dentistSelect.value && consultationSelect.value && this.value) {
            loadAvailableTimes();
        } else {
            timeSelect.innerHTML = '<option value="">-- Сначала выберите дату и врача --</option>';
            timeSelect.disabled = true;
        }
    });
    
    // Установка ограничений по датам
    const today = new Date();
    const maxDate = new Date();
    maxDate.setMonth(maxDate.getMonth() + 3);
    
    dateInput.setAttribute('min', today.toISOString().split('T')[0]);
    dateInput.setAttribute('max', maxDate.toISOString().split('T')[0]);
}

// ============================================
// АДМИНКА: УПРАВЛЕНИЕ ЗАПИСЯМИ НА ПРИЕМ
// ============================================

/**
 * Инициализирует административную панель управления записями,
 * настраивает выпадающие списки врачей, услуг, дат и времени,
 * а также обработчики изменений для динамической загрузки данных
 */
function initAdminAppointments() {
    const container = document.getElementById('appointmentsContainer');
    if (!container) return;
 
    // Ограничения дат
    document.querySelectorAll('.appointment-date').forEach(input => {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        input.min = tomorrow.toISOString().split('T')[0];
        const max = new Date();
        max.setMonth(max.getMonth() + 3);
        input.max = max.toISOString().split('T')[0];
    });
 
    container.addEventListener('change', function(e) {
        const card = e.target.closest('.appointment-card');
        if (!card) return;
        const index = card.dataset.index;
 
        if (e.target.classList.contains('dentist-select')) {
            updateServicesForAppointment(e.target, index);
            const ts = card.querySelector('.appointment-time-select');
            if (ts) { ts.innerHTML = '<option value="">-- Сначала выберите услугу, врача и дату --</option>'; ts.disabled = true; }
        }
 
        if (e.target.classList.contains('service-select') || e.target.classList.contains('appointment-date')) {
            const d = card.querySelector('.dentist-select')?.value;
            const s = card.querySelector('.service-select')?.value;
            const dt = card.querySelector('.appointment-date')?.value;
            const ts = card.querySelector('.appointment-time-select');
            if (d && s && dt) {
                loadAvailableTimes(index);
            } else {
                if (ts) { ts.innerHTML = '<option value="">-- Сначала выберите услугу, врача и дату --</option>'; ts.disabled = true; }
            }
        }
    });
}


let appointmentCount = 1;
let changes = {};

/**
 * Добавляет новую карточку записи на прием в админ-панели,
 * клонирует существующую карточку и обновляет индексы полей
 */
function addAppointment() {
    const container = document.getElementById('appointmentsContainer');
    if (!container) return;
 
    const template = container.children[0].cloneNode(true);
    const newIndex = appointmentCount;
 
    template.dataset.index = newIndex; // <-- это и было сломано
 
    template.querySelectorAll('[name*="[0]"]').forEach(el => {
        el.name = el.name.replace('[0]', `[${newIndex}]`);
        if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
    });
 
    const serviceSelect = template.querySelector('.service-select');
    if (serviceSelect) {
        serviceSelect.innerHTML = '<option value="">-- Сначала выберите врача --</option>';
        serviceSelect.disabled = true;
    }
 
    const ts = template.querySelector('.appointment-time-select');
    if (ts) { ts.innerHTML = '<option value="">-- Сначала выберите услугу, врача и дату --</option>'; ts.disabled = true; }
 
    const dateInput = template.querySelector('.appointment-date');
    if (dateInput) {
        dateInput.value = '';
        const tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1);
        dateInput.min = tomorrow.toISOString().split('T')[0];
        const max = new Date(); max.setMonth(max.getMonth() + 3);
        dateInput.max = max.toISOString().split('T')[0];
    }
 
    template.querySelector('.card-header h3').textContent = `Прием #${newIndex + 1}`;
    template.querySelector('.remove-btn').style.display = 'block';
 
    container.appendChild(template);
    appointmentCount++;
}


/**
 * Удаляет карточку записи на прием, не позволяет удалить последнюю оставшуюся карточку
 */
function removeAppointment(btn) {
    const card = btn.closest('.appointment-card');
    if (!card) return;
    
    if (document.querySelectorAll('.appointment-card').length > 1) {
        card.remove();
        renumberAppointments();
    }
}

/**
 * Перенумеровывает карточки записей после удаления,
 * обновляет заголовки и имена полей для сохранения порядка
 */
function renumberAppointments() {
    const cards = document.querySelectorAll('.appointment-card');
    cards.forEach((card, idx) => {
        card.querySelector('.card-header h3').textContent = `Прием #${idx + 1}`;
        card.querySelectorAll('[name*="appointments["]').forEach(element => {
            const oldName = element.getAttribute('name');
            const newName = oldName.replace(/appointments\[\d+\]/, `appointments[${idx}]`);
            element.setAttribute('name', newName);
        });
    });
    appointmentCount = cards.length;
}

/**
 * Загружает доступное время для записи с сервера,
 * отправляет AJAX-запрос с параметрами: врач, дата, услуга
 */
function loadAvailableTimes(index) {
    const dentistId = document.querySelector(`select[name="appointments[${index}][dentist_id]"]`)?.value;
    const date = document.querySelector(`input[name="appointments[${index}][appointment_date]"]`)?.value;
    const serviceId = document.querySelector(`select[name="appointments[${index}][service_id]"]`)?.value;
    const timeSelect = document.querySelector(`select[name="appointments[${index}][appointment_time]"]`);
    
    if (!dentistId || !date || !serviceId) {
        if (timeSelect) {
            timeSelect.innerHTML = '<option value="">-- Сначала выберите услугу, врача и дату --</option>';
            timeSelect.disabled = true;
        }
        return;
    }
    
    timeSelect.innerHTML = '<option>Загрузка...</option>';
    timeSelect.disabled = true;
    
    fetch(`../get-available-times.php?dentist_id=${dentistId}&date=${date}&service_id=${serviceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                timeSelect.innerHTML = '<option value="">-- Ошибка загрузки --</option>';
            } else if (data.times?.length) {
                timeSelect.innerHTML = '<option value="">-- Выберите время --</option>' +
                    data.times.map(t => `<option value="${t}">${t}</option>`).join('');
                timeSelect.disabled = false;
            } else {
                timeSelect.innerHTML = '<option value="">-- Нет свободного времени --</option>';
            }
        })
        .catch(() => {
            timeSelect.innerHTML = '<option value="">-- Ошибка загрузки --</option>';
            timeSelect.disabled = true;
        });
}

/**
 * Обновляет список услуг в зависимости от выбранного врача,
 * группирует услуги по категориям в выпадающем списке
 */
function updateServicesForAppointment(selectElement, index) {
    const dentistId = selectElement.value;
    const card = selectElement.closest('.appointment-card');
    const serviceSelect = card?.querySelector('.service-select');
    if (!serviceSelect) return;
 
    const dentistServices = window.servicesByDentist?.[dentistId];
    if (!dentistId || !dentistServices?.length) {
        serviceSelect.innerHTML = '<option value="">-- Нет доступных услуг --</option>';
        serviceSelect.disabled = true;
        return;
    }
 
    const byCategory = {};
    dentistServices.forEach(s => {
        (byCategory[s.category] = byCategory[s.category] || []).push(s);
    });
 
    let html = '<option value="">-- Выберите услугу --</option>';
    for (const [cat, services] of Object.entries(byCategory)) {
        html += `<optgroup label="${cat}">`;
        services.forEach(s => { html += `<option value="${s.id}">${s.name}</option>`; });
        html += '</optgroup>';
    }
 
    serviceSelect.innerHTML = html;
    serviceSelect.disabled = false;
}


// ============================================
// АДМИНКА: ВСТРОЕННОЕ РЕДАКТИРОВАНИЕ
// ============================================

/**
 * Включает режим редактирования поля даты или времени в карточке записи,
 * заменяет текстовое отображение на input соответствующего типа
 */
function editField(element, field, appointmentId) {
    const displayDiv = element.closest('div');
    const span = displayDiv.querySelector('span');
    const currentValue = span.textContent.trim();
    
    let input;
    if (field === 'date') {
        input = document.createElement('input');
        input.type = 'date';
        const parts = currentValue.split('.');
        if (parts.length === 3) {
            input.value = `${parts[2]}-${parts[1]}-${parts[0]}`;
        }
    } else if (field === 'time') {
        input = document.createElement('input');
        input.type = 'time';
        input.value = currentValue;
    }
    
    input.className = 'edit-input';
    const originalHTML = displayDiv.innerHTML;
    
    displayDiv.innerHTML = '';
    displayDiv.appendChild(input);
    displayDiv.classList.add('editing');
    input.focus();
    
    // При потере фокуса возвращение исходного отображения
    input.addEventListener('blur', () => {
        setTimeout(() => {
            displayDiv.innerHTML = originalHTML;
            displayDiv.classList.remove('editing');
        }, 200);
    });
    
    // Сохранение изменений по нажатию Enter
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            saveField(appointmentId, field, input.value);
            displayDiv.innerHTML = originalHTML;
            displayDiv.classList.remove('editing');
        }
    });
    
    // Сохранение изменений при выборе даты/времени
    input.addEventListener('change', function() {
        saveField(appointmentId, field, input.value);
    });
}

/**
 * Превращает статус записи в выпадающий список для редактирования,
 * при изменении статуса отправляет AJAX-запрос на сервер
 */
function editStatus(element, appointmentId) {
    const badge = element.closest('span');
    const currentStatus = badge.className.match(/admin-status-(\w+)/)?.[1];
    
    const select = document.createElement('select');
    select.style.cssText = 'width:130px; padding:6px;';
    
    const statuses = {
        'pending': 'Ожидание',
        'confirmed': 'Подтверждено',
        'completed': 'Выполнено',
        'cancelled': 'Отменено'
    };
    
    Object.entries(statuses).forEach(([key, label]) => {
        const option = document.createElement('option');
        option.value = key;
        option.textContent = label;
        option.selected = (key === currentStatus);
        select.appendChild(option);
    });
    
    badge.innerHTML = '';
    badge.appendChild(select);
    select.focus();
    
    // Отправление изменения статуса на сервер при выборе
    select.addEventListener('change', function() {
        fetch('appointment-update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                appointment_id: appointmentId,
                changes: { status: this.value }
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                badge.innerHTML = statuses[this.value];
                badge.className = `badge badge-${this.value}`;
            }
        });
    });
}

/**
 * Сохраняет измененное поле во временный объект изменений
 */
function saveField(appointmentId, field, value) {
    if (!changes[appointmentId]) changes[appointmentId] = {};
    changes[appointmentId][field] = value;
    showNotification('✅ Изменено. Нажмите 💾 для сохранения', 'info');
}

/**
 * Сохраняет изменение статуса записи во временный объект изменений
 */
function updateStatus(selectElement, appointmentId) {
    console.log('updateStatus called:', selectElement.value, appointmentId);
    saveField(appointmentId, 'status', selectElement.value);
}

/**
 * Отправляет все накопленные изменения записи на сервер, после успешного сохранения перезагружает страницу
 */
function saveAllChanges(appointmentId) {
    if (!changes[appointmentId] || Object.keys(changes[appointmentId]).length === 0) {
        showNotification('Нет изменений для сохранения', 'warning');
        return;
    }
    
    // Предупреждение если меняем статус прошедшей записи
    if (changes[appointmentId].status && changes[appointmentId].status !== 'completed') {
        const row = document.querySelector(`tr[data-id="${appointmentId}"]`);
        if (row) {
            const dateSpan = row.querySelector('.date-display span');
            if (dateSpan) {
                const parts = dateSpan.textContent.trim().split('.');
                const rowDate = new Date(`${parts[2]}-${parts[1]}-${parts[0]}`);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (rowDate < today) {
                    const newStatus = changes[appointmentId].status;
                    if (newStatus === 'confirmed' || newStatus === 'pending') {
                        showNotification('❌ Нельзя изменить статус прошедшей записи на этот', 'error');
                        return;
                    }
                }
            }
        }
    }

    fetch('appointment-update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            appointment_id: appointmentId,
            changes: changes[appointmentId]
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('✅ Изменения сохранены', 'success');
            delete changes[appointmentId];
            setTimeout(() => location.reload(), 800);
        } else {
            showNotification('❌ Ошибка: ' + (data.error || 'Неизвестная ошибка'), 'error');
        }
    })
    .catch(() => {
        showNotification('❌ Ошибка соединения с сервером', 'error');
    });
}

/**
 * Отмечает прием как выполненный, создает скрытую форму и отправляет её для обработки сервером
 */
function completeAppointment(id) {
    if (confirm('Отметить прием как выполненный?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'dentist-profile.php';
        form.innerHTML = `
            <input type="hidden" name="appointment_id" value="${id}">
            <input type="hidden" name="complete_appointment" value="1">
            <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

/**
 * Получает текущие фильтры из URL для сохранения контекста
 */
function getCurrentFilters() {
    const params = new URLSearchParams(window.location.search);
    const filters = {};
    params.forEach((value, key) => {
        if (value && key !== 'page') filters[key] = value;
    });
    return filters;
}

// ============================================
// ПОИСК КЛИЕНТОВ
// ============================================

/**
 * Инициализирует поиск клиентов с выпадающим списком подсказок
 */
function initClientSearch() {
    const input = document.getElementById('patient-search');
    const hidden = document.getElementById('client-id-hidden');
    const dropdown = document.getElementById('patient-dropdown');
    
    if (!input || !hidden || !dropdown) return;
    
    // Отображает выпадающий список с результатами поиска
    function renderDropdown(items) {
        if (!items.length) {
            dropdown.style.display = 'none';
            return;
        }
        dropdown.innerHTML = items.map(p => `
            <div data-id="${p.id}" data-name="${p.name}"
                 style="padding:8px 12px; cursor:pointer; font-size:14px; border-bottom:1px solid #f0f0f0;"
                 onmouseover="this.style.background='#f5f5f5'"
                 onmouseout="this.style.background=''">
                ${p.name}
            </div>
        `).join('');
        dropdown.style.display = 'block';
    }
    
    // Фильтрация пациентов при вводе текста
    input.addEventListener('input', function() {
        hidden.value = '';
        const query = this.value.toLowerCase().trim();
        if (!query) {
            dropdown.style.display = 'none';
            return;
        }
        renderDropdown(
            (window.patientData || []).filter(p => p.name.toLowerCase().includes(query))
        );
    });
    
    input.addEventListener('focus', function() {
        if (this.value.trim()) {
            this.dispatchEvent(new Event('input'));
        }
    });
    
    // Выбор пациента из выпадающего списка
    dropdown.addEventListener('click', function(e) {
        const item = e.target.closest('[data-id]');
        if (!item) return;
        input.value = item.dataset.name;
        hidden.value = item.dataset.id;
        dropdown.style.display = 'none';
    });
    
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') dropdown.style.display = 'none';
        if (e.key === 'Backspace' && !this.value) hidden.value = '';
    });
    
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
}

// ============================================
// МАСКА ДЛЯ ТЕЛЕФОНА
// ============================================

/**
 * Инициализирует маску ввода для поля телефона,
 * автоматически форматирует ввод в формат: +7 (XXX) XXX-XX-XX
 */
function initPhoneMask() {
    const phoneInput = document.getElementById('phoneInput') 
                    || document.getElementById('phone_number');
    if (!phoneInput) return;

    // Форматируем существующее значение при загрузке
    function formatPhone(raw) {
        let digits = raw.replace(/\D/g, '');
        if (digits.startsWith('7') || digits.startsWith('8')) {
            digits = digits.substring(1);
        }
        digits = digits.substring(0, 10);

        let formatted = '+7';
        if (digits.length > 0) formatted += ' (' + digits.substring(0, 3);
        if (digits.length >= 3) formatted += ')';
        if (digits.length > 3) formatted += ' ' + digits.substring(3, 6);
        if (digits.length > 6) formatted += '-' + digits.substring(6, 8);
        if (digits.length > 8) formatted += '-' + digits.substring(8, 10);

        return formatted;
    }

    // Применяем к уже заполненному полю
    if (phoneInput.value) {
        phoneInput.value = formatPhone(phoneInput.value);
    }

    phoneInput.addEventListener('input', function(e) {
        const cursorPos = e.target.selectionStart;
        const prevLength = e.target.value.length;

        const formatted = formatPhone(e.target.value);
        e.target.value = formatted;

        const diff = formatted.length - prevLength;
        e.target.setSelectionRange(cursorPos + diff, cursorPos + diff);
    });

    phoneInput.addEventListener('focus', function() {
        if (!this.value) this.value = '+7 (';
    });

    phoneInput.addEventListener('blur', function() {
        if (this.value === '+7 (' || this.value === '+7') this.value = '';
    });

    phoneInput.addEventListener('keydown', function(e) {
        const allowedKeys = [8, 46, 9, 27, 13, 37, 38, 39, 40];
        if (allowedKeys.includes(e.keyCode)) return;
        if ((e.ctrlKey || e.metaKey) && [65, 67, 86].includes(e.keyCode)) return;
        if (!/\d/.test(e.key)) e.preventDefault();
    });
}

// ============================================
// МАСКА ДЛЯ НОМЕРА ПОЛИСА
// ============================================

/**
 * Инициализирует маску ввода для номера страхового полиса,
 * разрешает только цифры и ограничивает длину до 16 символов
 */
function initInsuranceMask() {
    const insuranceInput = document.getElementById('insurance_number');
    if (!insuranceInput) return;
    
    insuranceInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        // Ограничиваем до 16 цифр
        if (value.length > 16) {
            value = value.substring(0, 16);
        }
        
        e.target.value = value;
    });
}

// ============================================
// ВАЛИДАЦИЯ ФОРМ
// ============================================

/**
 * Валидирует номер телефона (должен содержать минимум 10 цифр)
 * @param {string} phoneValue - значение поля телефона
 * @returns {boolean} - результат валидации
 */
function validatePhone(phoneValue, formName) {
    const digits = phoneValue.replace(/\D/g, '');
    if (digits.length < 10) {
        alert('Пожалуйста, введите корректный номер телефона');
        return false;
    }
    return true;
}

/**
 * Инициализирует валидацию формы добавления/редактирования врача,
 * проверяет телефон (мин. 10 цифр) и стаж работы (0-60 лет)
 */
function initDentistFormValidation() {
    const form = document.getElementById('dentistForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        const phone = document.querySelector('[name="dentist_phone_number"]')?.value || '';
        if (!validatePhone(phone)) {
            e.preventDefault();
            return false;
        }
        
        const experience = parseInt(document.querySelector('[name="dentist_experience_years"]')?.value) || 0;
        if (experience < 0 || experience > 60) {
            e.preventDefault();
            alert('Стаж должен быть от 0 до 60 лет');
            return false;
        }
        
        return true;
    });
}

/**
 * Инициализирует валидацию формы добавления/редактирования пациента,
 * проверяет корректность номера телефона
 */
function initPatientFormValidation() {
    const form = document.getElementById('patientForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        const phone = document.querySelector('[name="patient_phone_number"]')?.value || '';
        if (!validatePhone(phone)) {
            e.preventDefault();
            return false;
        }
        return true;
    });
}

// ============================================
// АНИМАЦИИ
// ============================================

/**
 * Внедряет CSS-анимации в документ
 */
(function injectStyles() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to   { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to   { transform: translateX(100%); opacity: 0; }
        }
        .edit-input {
            width: 100%;
            padding: 4px 8px;
            border: 1px solid #007bff;
            border-radius: 4px;
            font-size: inherit;
        }
        .editing {
            padding: 0 !important;
        }
    `;
    document.head.appendChild(style);
})();