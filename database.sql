-- Meal Management System Database
-- Created for tracking daily meals, expenses, and settlements

CREATE DATABASE IF NOT EXISTS meal_management;
USE meal_management;

-- Admin users table
CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Members table
CREATE TABLE IF NOT EXISTS members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
);

-- Meal periods table (e.g., October 2025, November 2025)
CREATE TABLE IF NOT EXISTS meal_periods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    period_name VARCHAR(100) NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    meal_rate DECIMAL(10,2) DEFAULT 0,
    total_expense DECIMAL(10,2) DEFAULT 0,
    total_meals INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_date (start_date, end_date)
);

-- Daily meals table
CREATE TABLE IF NOT EXISTS daily_meals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    period_id INT NOT NULL,
    member_id INT NOT NULL,
    meal_date DATE NOT NULL,
    meal_count INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (period_id) REFERENCES meal_periods(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    UNIQUE KEY unique_meal (period_id, member_id, meal_date),
    INDEX idx_period (period_id),
    INDEX idx_member (member_id),
    INDEX idx_date (meal_date)
);

-- Expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    period_id INT NOT NULL,
    member_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    description TEXT,
    receipt_image VARCHAR(255),
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (period_id) REFERENCES meal_periods(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    INDEX idx_period (period_id),
    INDEX idx_member (member_id),
    INDEX idx_date (expense_date)
);

-- Settlements table (calculated results)
CREATE TABLE IF NOT EXISTS settlements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    period_id INT NOT NULL,
    member_id INT NOT NULL,
    total_meals INT DEFAULT 0,
    total_expense DECIMAL(10,2) DEFAULT 0,
    meal_cost DECIMAL(10,2) DEFAULT 0,
    balance DECIMAL(10,2) DEFAULT 0,
    status ENUM('due', 'credit', 'settled') DEFAULT 'settled',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (period_id) REFERENCES meal_periods(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    UNIQUE KEY unique_settlement (period_id, member_id),
    INDEX idx_period (period_id),
    INDEX idx_status (status)
);

-- Insert default admin (username: admin, password: admin123)
-- Password is hashed using PHP password_hash()
INSERT INTO admins (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@mealapp.com');

-- Insert members from the CSV data
INSERT INTO members (name) VALUES 
('Bijoy'),
('Shamsul'),
('Ajad'),
('Sarwar'),
('Meraj'),
('Sayed'),
('Shahinur'),
('Suman'),
('Jaine'),
('Mehedi');

-- Create a sample meal period for October 2025
INSERT INTO meal_periods (period_name, month, year, start_date, end_date, is_active) VALUES
('October 2025', 10, 2025, '2025-10-01', '2025-10-31', 1);

