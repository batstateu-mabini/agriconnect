CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    barangay VARCHAR(100),
    sitio VARCHAR(100),
    role ENUM('personal', 'relative'),
    relative_name VARCHAR(100) DEFAULT NULL,
    relationship VARCHAR(100) DEFAULT NULL,
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(11),
    password_hash TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(150) UNIQUE,
    password_hash TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    animal_type VARCHAR(100) NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    barangay VARCHAR(100),
    sitio VARCHAR(100),
    service_notes TEXT DEFAULT NULL,
    status ENUM('pending', 'approved', 'completed') DEFAULT 'pending',
    schedule_date DATE DEFAULT NULL,
    schedule_time TIME DEFAULT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE crop_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    crops TEXT NOT NULL, -- comma-separated crop names or JSON
    quantity VARCHAR(50) NOT NULL,
    notes TEXT,
    status ENUM('pending', 'approved', 'completed') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table for fisher requests
CREATE TABLE fisher_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fishers TEXT NOT NULL, -- comma-separated fisher names or JSON
    quantity VARCHAR(50) NOT NULL,
    notes TEXT,
    status ENUM('pending', 'approved', 'completed') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);