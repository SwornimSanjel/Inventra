CREATE TABLE IF NOT EXISTS stock_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_type ENUM('Stock In', 'Stock Out') NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    movement_notes TEXT NULL,
    user_id INT UNSIGNED NOT NULL,
    incoming_status ENUM('Order Dispatched', 'In Transit', 'Received at Warehouse') NULL,
    full_name VARCHAR(150) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    amount_per_piece DECIMAL(12, 2) NOT NULL,
    total_amount DECIMAL(12, 2) NOT NULL,
    payment_status VARCHAR(50) NOT NULL,
    payment_method ENUM('Cash', 'Card') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_stock_movements_product_id (product_id),
    INDEX idx_stock_movements_user_id (user_id),
    INDEX idx_stock_movements_stock_type (stock_type),
    CONSTRAINT fk_stock_movements_product_id
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_stock_movements_user_id
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);
