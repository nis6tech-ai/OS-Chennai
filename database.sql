-- Nutpa CMS Database Schema
CREATE DATABASE IF NOT EXISTS nutpa_db;
USE nutpa_db;

-- Table for Global settings
CREATE TABLE IF NOT EXISTS site_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id VARCHAR(50) UNIQUE,
    site_name VARCHAR(100),
    site_tagline VARCHAR(255),
    site_logo VARCHAR(255),
    hero_image VARCHAR(255),
    contact_email VARCHAR(100),
    contact_phone VARCHAR(50),
    whatsapp_number VARCHAR(50),
    contact_address TEXT,
    social_links JSON,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for Categories
CREATE TABLE IF NOT EXISTS categories (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image_url VARCHAR(255)
);

-- Table for Products
CREATE TABLE IF NOT EXISTS products (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category_id VARCHAR(50),
    price VARCHAR(50),
    description TEXT,
    features JSON,
    media JSON, -- Stores array of objects [{url, type}]
    is_top_selling BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Initial Client Seed
INSERT INTO site_settings (project_id, site_name) VALUES ('nutpa', 'Nutpa Electronics');
