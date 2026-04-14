<?php

function inventra_ensure_stock_movements_table(mysqli $conn): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS stock_movements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reference VARCHAR(50) NOT NULL UNIQUE,
            product_id INT NOT NULL,
            movement_type ENUM('in', 'out') NOT NULL,
            quantity INT NOT NULL,
            notes TEXT NULL,
            full_name VARCHAR(150) NULL,
            contact VARCHAR(50) NULL,
            amount_per_piece DECIMAL(10,2) NOT NULL DEFAULT 0,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            payment_status ENUM('paid', 'unpaid') NOT NULL DEFAULT 'paid',
            payment_method ENUM('cash', 'card') NOT NULL DEFAULT 'cash',
            incoming_status VARCHAR(50) NULL,
            movement_status VARCHAR(50) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_stock_movements_product_id_created_at (product_id, created_at),
            CONSTRAINT fk_stock_movements_product
                FOREIGN KEY (product_id) REFERENCES products(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    $conn->query($sql);
}

function inventra_generate_stock_reference(): string
{
    return 'STK-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
}
