CREATE TABLE IF NOT EXISTS drinks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    price DECIMAL(10,2) NOT NULL,
    orders INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS order_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    drink_id INT NOT NULL,
    ordered_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (drink_id) REFERENCES drinks(id)
);

INSERT INTO drinks (name, price, orders)
VALUES
    ('Cola', 2.50, 0),
    ('Fanta', 2.80, 0),
    ('Wasser', 1.90, 0)
ON DUPLICATE KEY UPDATE name = VALUES(name);