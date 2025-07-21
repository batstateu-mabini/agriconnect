CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    role ENUM('personal', 'relative'),
    relative_name VARCHAR(100) DEFAULT NULL,
    relationship VARCHAR(100) DEFAULT NULL,
    email VARCHAR(150) UNIQUE,
    password_hash TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
