CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stripe_session_id VARCHAR(255) NOT NULL UNIQUE,
    product_key VARCHAR(100) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    amount_total INT UNSIGNED NOT NULL,
    currency CHAR(3) NOT NULL,
    invoice_number VARCHAR(50) NULL UNIQUE,
    invoice_pdf_path VARCHAR(500) NULL,
    purchased_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE download_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_key VARCHAR(100) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    stripe_session_id VARCHAR(255) NOT NULL,
    token CHAR(64) NOT NULL UNIQUE,
    downloads_used INT UNSIGNED NOT NULL DEFAULT 0,
    max_downloads INT UNSIGNED NOT NULL DEFAULT 3,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_download_session (stripe_session_id)
);

CREATE TABLE email_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_email VARCHAR(255) NOT NULL,
    product_key VARCHAR(100) NOT NULL,
    status ENUM('sent', 'failed') NOT NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
