-- Database Setup for Inventory Management System
-- Run these SQL queries in phpMyAdmin or MySQL client

-- Create database
CREATE DATABASE IF NOT EXISTS smart_inventory;
USE smart_inventory;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create admin user (password: admin123)
INSERT INTO users (username, password) VALUES (
    'admin',
    '$2y$10$8nEEU8bWClZvJ9.Nw0nNdOJjIb5i0jXtKzIjNDxSK1M0Lh8JhVZWe'
);

-- Create products table (METADATA ONLY - No quantity here)
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    price DECIMAL(10, 2),
    supplier_id INT,
    sku VARCHAR(50),
    description TEXT,
    barcode VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_category (category)
);

-- Create stock_movements table (INVENTORY TRUTH)
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    movement_type ENUM('IN', 'OUT') NOT NULL,
    quantity INT NOT NULL,
    reason VARCHAR(100),
    reference_no VARCHAR(50),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_product (product_id),
    INDEX idx_type (movement_type),
    INDEX idx_date (created_at)
);

-- Sample products for testing
INSERT INTO products (name, category, price, sku) VALUES
    ('Laptop', 'Electronics', 999.99, 'LAPTOP-001'),
    ('Mouse', 'Accessories', 25.00, 'MOUSE-001'),
    ('Keyboard', 'Accessories', 75.00, 'KEYBOARD-001'),
    ('Monitor', 'Electronics', 299.99, 'MONITOR-001');

-- Sample stock movements
INSERT INTO stock_movements (product_id, movement_type, quantity, reason, reference_no, created_by) VALUES
    (1, 'IN', 50, 'Initial stock', 'PO-001', 1),
    (1, 'OUT', 5, 'Sale', 'SALE-001', 1),
    (1, 'OUT', 1, 'Damaged', 'ADJ-001', 1),
    (2, 'IN', 100, 'Initial stock', 'PO-002', 1),
    (2, 'OUT', 20, 'Sale', 'SALE-002', 1),
    (3, 'IN', 75, 'Initial stock', 'PO-003', 1),
    (3, 'OUT', 3, 'Sale', 'SALE-003', 1),
    (4, 'IN', 15, 'Initial stock', 'PO-004', 1);
