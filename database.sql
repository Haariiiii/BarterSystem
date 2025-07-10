CREATE DATABASE IF NOT EXISTS barter_system;
USE barter_system;

DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS proposals;
DROP TABLE IF EXISTS trades;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    full_name VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    profile_complete BOOLEAN DEFAULT FALSE,
    profile_image VARCHAR(255) DEFAULT 'default.jpg'
);

CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    expected_value DECIMAL(10,2) NOT NULL,
    category VARCHAR(50),
    condition_status VARCHAR(20),
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('available', 'traded', 'reserved') DEFAULT 'available',
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE trades (
    trade_id INT PRIMARY KEY AUTO_INCREMENT,
    product_offered_id INT,
    product_wanted_id INT,
    status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_offered_id) REFERENCES products(product_id),
    FOREIGN KEY (product_wanted_id) REFERENCES products(product_id)
);

CREATE TABLE proposals (
    proposal_id INT PRIMARY KEY AUTO_INCREMENT,
    product_offered_id INT,
    product_wanted_id INT,
    sender_id INT,
    receiver_id INT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_offered_id) REFERENCES products(product_id),
    FOREIGN KEY (product_wanted_id) REFERENCES products(product_id),
    FOREIGN KEY (sender_id) REFERENCES users(user_id),
    FOREIGN KEY (receiver_id) REFERENCES users(user_id)
);

CREATE TABLE chat_messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    proposal_id INT,
    sender_id INT,
    message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (proposal_id) REFERENCES proposals(proposal_id),
    FOREIGN KEY (sender_id) REFERENCES users(user_id)
); 