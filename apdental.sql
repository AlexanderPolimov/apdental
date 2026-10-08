CREATE TABLE specializations (
    specialization_id SERIAL PRIMARY KEY,
    specialization_name VARCHAR(100) NOT NULL UNIQUE
); 

CREATE TABLE dentists (
    dentist_id SERIAL PRIMARY KEY,
    dentist_full_name VARCHAR(255) NOT NULL,
    dentist_phone_number VARCHAR(20) NOT NULL,
    dentist_email VARCHAR(100),
    dentist_password VARCHAR(255) NOT NULL,
    dentist_experience_years INTEGER NOT NULL,
    dentist_description TEXT,
    dentist_photo VARCHAR(255) NOT NULL,
    specialization_id INTEGER REFERENCES specializations(specialization_id)
);

CREATE TABLE cabinets (
    cabinet_id SERIAL PRIMARY KEY,
    cabinet_name VARCHAR(255) NOT NULL
);

CREATE TABLE administrators(
	administrator_id SERIAL PRIMARY KEY,
	administrator_full_name VARCHAR(255) NOT NULL,
    administrator_phone_number VARCHAR(20) NOT NULL,
	administrator_email VARCHAR(100),
	administrator_password VARCHAR(255) NOT NULL
);

CREATE TABLE patients (
    patient_id SERIAL PRIMARY KEY,
    patient_full_name VARCHAR(255) NOT NULL,
    patient_phone_number VARCHAR(20) NOT NULL,
    patient_email VARCHAR(100) NOT NULL,
    patient_password VARCHAR(255) NOT NULL,
    patient_gender VARCHAR(10) NOT NULL,
    patient_birth_date DATE,
    patient_insurance_number VARCHAR(16) NOT NULL
);

CREATE TABLE service_categories (
    service_category_id SERIAL PRIMARY KEY,
    service_category_name VARCHAR(100) NOT NULL
);

CREATE TABLE services (
    service_id SERIAL PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    service_description varchar(200) NOT NULL,
    service_cost DECIMAL NOT NULL,
    service_duration INTEGER DEFAULT 60,
    service_category_id INTEGER REFERENCES service_categories(service_category_id)
);

CREATE TABLE appointments (
    appointment_id SERIAL PRIMARY KEY,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    appointment_status VARCHAR(20),
    appointment_cost DECIMAL(10,2) NULL,
    appointment_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    patient_id INTEGER REFERENCES patients(patient_id),
    dentist_id INTEGER REFERENCES dentists(dentist_id),
    service_id INTEGER REFERENCES services(service_id),
    cabinet_id INTEGER REFERENCES cabinets(cabinet_id)
);

CREATE TABLE reviews (
    review_id SERIAL PRIMARY KEY,
    review_client_name VARCHAR(100) NOT NULL,
    review_text TEXT NOT NULL,
    review_rating INT DEFAULT 5 CHECK (review_rating >= 1 AND review_rating <= 5),
    review_date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE dentists_service_categories (
    dentists_service_categories_id SERIAL PRIMARY KEY,
    dentist_id INTEGER REFERENCES dentists(dentist_id),
    service_category_id INTEGER REFERENCES service_categories(service_category_id)
);

CREATE TABLE medical_cards (
    medical_card_id SERIAL PRIMARY KEY,
    medical_card_diagnosis TEXT,
    medical_card_treatment_plan TEXT,
    medical_card_prescriptions TEXT,
    medical_card_recommendations TEXT,
    patient_id INTEGER REFERENCES patients(patient_id),
    dentist_id INTEGER REFERENCES dentists(dentist_id),
    appointment_id INTEGER REFERENCES appointments(appointment_id)
);

CREATE TABLE equipment (
    equipment_id SERIAL PRIMARY KEY,
    equipment_name VARCHAR(100) NOT NULL,
    equipment_description TEXT,
    equipment_specifications TEXT,
    equipment_photo VARCHAR(255) NOT NULL
);

CREATE TABLE appointment_services (
    appointment_service_id SERIAL PRIMARY KEY,
    appointment_id INTEGER REFERENCES appointments(appointment_id),
    service_id INTEGER REFERENCES services(service_id)
);

CREATE TABLE cabinets_service_categories (
    cabinets_service_categories_id SERIAL PRIMARY KEY,
    cabinet_id INTEGER REFERENCES cabinets(cabinet_id),
    service_category_id INTEGER REFERENCES service_categories(service_category_id)
);