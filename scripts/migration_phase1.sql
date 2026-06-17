-- ===================================================================
-- PASTIMES DATABASE MIGRATION
-- Adds the columns and tables expected by the current PHP pages.
-- Safe to rerun on MariaDB/MySQL versions that support IF NOT EXISTS.
-- Date: 2026-06-17
-- ===================================================================

-- User account fields used by registration, login, and admin pages.
ALTER TABLE tbluser
    ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'user' COMMENT 'Role: user, seller, admin',
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Account creation date';

-- Product fields used by seller hub, cart, and checkout.
ALTER TABLE tblclothes
    ADD COLUMN IF NOT EXISTS seller_id INT NULL AFTER user_id,
    ADD COLUMN IF NOT EXISTS brand VARCHAR(100) NULL COMMENT 'Brand/Seller name',
    ADD COLUMN IF NOT EXISTS stock INT DEFAULT 0 COMMENT 'Available inventory',
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Listing creation date',
    MODIFY COLUMN image VARCHAR(255) NOT NULL DEFAULT 'placeholder-clothing.jpg';

-- Order summary fields used by checkout history/status flows.
ALTER TABLE tblorder
    ADD COLUMN IF NOT EXISTS order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Order date',
    ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'pending' COMMENT 'Status: pending, confirmed, shipped, delivered',
    ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'pending' COMMENT 'Payment status: pending, paid, failed';

-- Individual order items.
CREATE TABLE IF NOT EXISTS tblorderline (
    orderline_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Order line item ID',
    order_id INT NOT NULL COMMENT 'Reference to order',
    product_id INT NOT NULL COMMENT 'Reference to product',
    quantity INT NOT NULL COMMENT 'Quantity ordered',
    unit_price DECIMAL(10,2) NOT NULL COMMENT 'Price per unit at time of order',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date line created',
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id),
    CONSTRAINT fk_tblorderline_order FOREIGN KEY (order_id) REFERENCES tblorder(order_id) ON DELETE CASCADE,
    CONSTRAINT fk_tblorderline_product FOREIGN KEY (product_id) REFERENCES tblclothes(product_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Individual line items within orders';

ALTER TABLE tblorderline
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date line created';

-- Persistent shopping cart.
CREATE TABLE IF NOT EXISTS tblcart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Cart item ID',
    user_id INT NOT NULL COMMENT 'User ID',
    product_id INT NOT NULL COMMENT 'Product ID',
    quantity INT DEFAULT 1 COMMENT 'Quantity in cart',
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date added to cart',
    UNIQUE KEY uk_user_product (user_id, product_id),
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id),
    CONSTRAINT fk_tblcart_user FOREIGN KEY (user_id) REFERENCES tbluser(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_tblcart_product FOREIGN KEY (product_id) REFERENCES tblclothes(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Shopping cart items for users';

ALTER TABLE tblcart
    ADD COLUMN IF NOT EXISTS added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date added to cart';

-- Seller profiles.
CREATE TABLE IF NOT EXISTS tblseller (
    seller_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Seller ID',
    user_id INT NOT NULL UNIQUE COMMENT 'Reference to user account',
    brand_name VARCHAR(100) COMMENT 'Seller brand name',
    description TEXT COMMENT 'Seller description',
    phone VARCHAR(30) COMMENT 'Seller phone number',
    approval_status VARCHAR(20) DEFAULT 'pending' COMMENT 'Status: pending, approved, rejected',
    approved_date DATETIME COMMENT 'Date approved by admin',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Application date',
    INDEX idx_approval_status (approval_status),
    CONSTRAINT fk_tblseller_user FOREIGN KEY (user_id) REFERENCES tbluser(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Seller profiles and applications';

ALTER TABLE tblseller
    ADD COLUMN IF NOT EXISTS phone VARCHAR(30) COMMENT 'Seller phone number',
    ADD COLUMN IF NOT EXISTS approved_date DATETIME COMMENT 'Date approved by admin',
    ADD COLUMN IF NOT EXISTS created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Application date';

-- Helpful indexes. If your MariaDB version does not support IF NOT EXISTS
-- for indexes, skip failed index statements; the application will still run.
ALTER TABLE tbluser
    ADD INDEX IF NOT EXISTS idx_status (status),
    ADD INDEX IF NOT EXISTS idx_role (role),
    ADD INDEX IF NOT EXISTS idx_created_at (created_at);

ALTER TABLE tblclothes
    ADD INDEX IF NOT EXISTS idx_user_id (user_id),
    ADD INDEX IF NOT EXISTS idx_seller_id (seller_id),
    ADD INDEX IF NOT EXISTS idx_brand (brand),
    ADD INDEX IF NOT EXISTS idx_stock (stock),
    ADD INDEX IF NOT EXISTS idx_product_created_at (created_at);

ALTER TABLE tblorder
    ADD INDEX IF NOT EXISTS idx_user_id (user_id),
    ADD INDEX IF NOT EXISTS idx_status (status),
    ADD INDEX IF NOT EXISTS idx_order_date (order_date);

-- Login requires this admin record. Insert only when it does not already exist.
INSERT INTO tbladmin (username, password)
SELECT 'admin123', '$2y$10$/e6yYcBMdMYZo8FZoYZHOufWCkJJj4Emlw7HTOa.4MPrp.0GFgFOK'
WHERE NOT EXISTS (
    SELECT 1 FROM tbladmin WHERE username = 'admin123'
);

-- ===================================================================
-- END MIGRATION
-- ===================================================================
