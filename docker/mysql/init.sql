-- Initialize database for Robust API
CREATE DATABASE IF NOT EXISTS robust_api;
USE robust_api;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    email_verified_at TIMESTAMP NULL,
    roles JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_active (is_active),
    INDEX idx_created_at (created_at)
);

-- Create refresh_tokens table for JWT management
CREATE TABLE IF NOT EXISTS refresh_tokens (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- Create password_resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- Create api_logs table for request logging
CREATE TABLE IF NOT EXISTS api_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NULL,
    method VARCHAR(10) NOT NULL,
    uri VARCHAR(500) NOT NULL,
    status_code INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    request_data JSON,
    response_data JSON,
    execution_time DECIMAL(8,3),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status_code (status_code),
    INDEX idx_created_at (created_at),
    INDEX idx_ip_address (ip_address)
);

-- Insert sample admin user (password: admin123)
INSERT INTO users (id, name, email, password_hash, is_active, is_verified, roles, email_verified_at) VALUES
('550e8400-e29b-41d4-a716-446655440000', 'Admin User', 'admin@example.com', '$argon2id$v=19$m=65536,t=4,p=3$YWRtaW4xMjM$8K8K8K8K8K8K8K8K8K8K8K8K8K8K8K8K8K8K8K8K8K8', TRUE, TRUE, '["admin", "user"]', NOW())
ON DUPLICATE KEY UPDATE name = name;

