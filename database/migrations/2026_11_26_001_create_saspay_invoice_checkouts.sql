ALTER TABLE payments
    MODIFY payment_method ENUM(
        'cash',
        'wave',
        'orange_money',
        'bank_transfer',
        'check',
        'other',
        'saspay'
    ) NOT NULL DEFAULT 'cash';

CREATE TABLE saspay_invoice_checkouts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    establishment_id BIGINT UNSIGNED NOT NULL,
    invoice_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    reference VARCHAR(80) NOT NULL,
    checkout_session_id VARCHAR(150) DEFAULT NULL,
    provider_transaction_id VARCHAR(150) DEFAULT NULL,
    payment_id BIGINT UNSIGNED DEFAULT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL,
    status ENUM('initiating', 'pending', 'succeeded', 'failed', 'review') NOT NULL DEFAULT 'initiating',
    checkout_url TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_saspay_checkout_token (token_hash),
    UNIQUE KEY uq_saspay_checkout_reference (reference),
    UNIQUE KEY uq_saspay_checkout_session (checkout_session_id),
    UNIQUE KEY uq_saspay_provider_transaction (provider_transaction_id),
    UNIQUE KEY uq_saspay_checkout_payment (payment_id),
    KEY idx_saspay_checkout_invoice (establishment_id, invoice_id, status),
    CONSTRAINT fk_saspay_checkout_establishment
        FOREIGN KEY (establishment_id) REFERENCES establishments (id),
    CONSTRAINT fk_saspay_checkout_invoice
        FOREIGN KEY (invoice_id) REFERENCES invoices (id),
    CONSTRAINT fk_saspay_checkout_payment
        FOREIGN KEY (payment_id) REFERENCES payments (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
