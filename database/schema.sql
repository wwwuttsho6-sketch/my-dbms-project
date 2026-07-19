CREATE DATABASE IF NOT EXISTS findit_db;
USE findit_db;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    department VARCHAR(100) NOT NULL,
    university_id VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Lost Items Table
CREATE TABLE IF NOT EXISTS lost_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    category ENUM('Mobile Phones', 'Wallets', 'Laptops', 'ID Cards', 'Other Items') NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    lost_date DATE NOT NULL,
    image_path VARCHAR(255) NULL,
    status ENUM('Lost', 'Recovered') DEFAULT 'Lost',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Found Items Table
CREATE TABLE IF NOT EXISTS found_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    category ENUM('Mobile Phones', 'Wallets', 'Laptops', 'ID Cards', 'Other Items') NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    found_date DATE NOT NULL,
    image_path VARCHAR(255) NULL,
    status ENUM('Found', 'Returned') DEFAULT 'Found',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Claims Table (Stores the verification form data as JSON text)
CREATE TABLE IF NOT EXISTS claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_type ENUM('lost', 'found') NOT NULL, -- Is the claimant claiming from a 'lost' post or a 'found' post?
    item_id INT NOT NULL, -- References either lost_items.id or found_items.id
    claimant_id INT NOT NULL, -- User who is filling out the form
    verification_data TEXT NOT NULL, -- JSON string containing category fields
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (claimant_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);