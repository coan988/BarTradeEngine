CREATE TABLE IF NOT EXISTS drinks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    price DECIMAL(4,2) NOT NULL,
    order_count INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS order_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    drink_id INT NOT NULL,
    price_at_order DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_log_drink
        FOREIGN KEY (drink_id) REFERENCES drinks(id)
);

CREATE TABLE IF NOT EXISTS scenario_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scenario_name VARCHAR(100) NOT NULL,
    executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS price_change (
    id INT AUTO_INCREMENT PRIMARY KEY,
    old_price DECIMAL(4,2) NOT NULL,
    new_price DECIMAL(4,2) NOT NULL,
    drink_id INT NOT NULL,
    order_id INT,
    scenario_id INT,
    CONSTRAINT fk_price_change_drink
        FOREIGN KEY (drink_id) REFERENCES drinks(id),
    CONSTRAINT fk_price_change_order
        FOREIGN KEY (order_id) REFERENCES order_log(id),
    CONSTRAINT fk_price_change_scenario
        FOREIGN KEY (scenario_id) REFERENCES scenario_log(id)
);

LOAD DATA INFILE '/var/lib/mysql-files/pricelist.txt'
INTO TABLE drinks
FIELDS TERMINATED BY ','
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(name, price);
