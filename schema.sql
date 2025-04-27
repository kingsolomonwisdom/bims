    -- Business Inventory Management System (BBIMS) Database Schema

    -- Drop database if it exists
    DROP DATABASE IF EXISTS bbims;

    -- Create database
    CREATE DATABASE bbims;

    -- Use database
    USE bbims;

    -- Enable foreign key constraints
    SET FOREIGN_KEY_CHECKS = 1;

    -- Users Table
    CREATE TABLE users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        profile_photo VARCHAR(255) DEFAULT NULL,
        role ENUM('admin', 'manager', 'cashier', 'inventory', 'user') NOT NULL DEFAULT 'user',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Categories Table
    CREATE TABLE categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Suppliers Table
    CREATE TABLE suppliers (
        supplier_id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(100) NOT NULL,
        contact_person VARCHAR(100),
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100) NOT NULL,
        address TEXT NOT NULL,
        city VARCHAR(50),
        country VARCHAR(50),
        postal_code VARCHAR(20),
        status ENUM('active', 'inactive') DEFAULT 'active',
        notes TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Products Table
    CREATE TABLE products (
        product_id INT AUTO_INCREMENT PRIMARY KEY,
        sku VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        brand VARCHAR(100),
        category_id INT,
        supplier_id INT,
        purchase_price DECIMAL(10, 2) NOT NULL,
        selling_price DECIMAL(10, 2) NOT NULL,
        discount_price DECIMAL(10, 2),
        tax_rate DECIMAL(5, 2) DEFAULT 0,
        stock_quantity INT NOT NULL DEFAULT 0,
        reorder_level INT DEFAULT 5,
        weight DECIMAL(10, 2),
        dimensions VARCHAR(50),
        image_url VARCHAR(255),
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        expiry_date DATE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Inventory Table (for tracking stock movements)
    CREATE TABLE inventory_log (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        quantity_change INT NOT NULL,
        type ENUM('purchase', 'sale', 'return', 'adjustment', 'loss', 'transfer') NOT NULL,
        reference_id INT,
        notes TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Customers Table
    CREATE TABLE customers (
        customer_id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE,
        phone VARCHAR(20),
        address TEXT,
        city VARCHAR(50),
        country VARCHAR(50),
        postal_code VARCHAR(20),
        notes TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Sales/Orders Table
    CREATE TABLE orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT,
        user_id INT NOT NULL,
        order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        total_amount DECIMAL(10, 2) NOT NULL,
        tax_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
        discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
        payment_method ENUM('cash', 'credit_card', 'debit_card', 'mobile_payment', 'check', 'bank_transfer') NOT NULL DEFAULT 'cash',
        payment_status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
        order_status ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        notes TEXT,
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Order Items Table (line items for each order)
    CREATE TABLE order_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10, 2) NOT NULL,
        discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
        tax_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
        subtotal DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Purchases Table (from suppliers)
    CREATE TABLE purchases (
        purchase_id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NOT NULL,
        user_id INT NOT NULL,
        purchase_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expected_delivery_date DATE,
        actual_delivery_date DATE,
        total_amount DECIMAL(10, 2) NOT NULL,
        tax_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
        discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
        payment_method ENUM('cash', 'credit_card', 'bank_transfer', 'check') NOT NULL DEFAULT 'bank_transfer',
        payment_status ENUM('pending', 'partial', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        purchase_status ENUM('ordered', 'received', 'pending', 'cancelled') NOT NULL DEFAULT 'ordered',
        notes TEXT,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Purchase Items Table (line items for each purchase)
    CREATE TABLE purchase_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10, 2) NOT NULL,
        discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
        tax_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
        subtotal DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (purchase_id) REFERENCES purchases(purchase_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Payments Table
    CREATE TABLE payments (
        payment_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        purchase_id INT,
        amount DECIMAL(10, 2) NOT NULL,
        payment_method ENUM('cash', 'credit_card', 'debit_card', 'mobile_payment', 'check', 'bank_transfer') NOT NULL,
        payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        reference_number VARCHAR(50),
        status ENUM('completed', 'pending', 'failed', 'refunded') NOT NULL DEFAULT 'completed',
        notes TEXT,
        created_by INT NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL,
        FOREIGN KEY (purchase_id) REFERENCES purchases(purchase_id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(user_id),
        CHECK (order_id IS NOT NULL OR purchase_id IS NOT NULL)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- AI Insights Table
    CREATE TABLE ai_insights (
        insight_id INT AUTO_INCREMENT PRIMARY KEY,
        insight_type ENUM('sales_trend', 'inventory_recommendation', 'price_optimization', 'customer_behavior', 'supplier_performance', 'general') NOT NULL,
        title VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Settings Table
    CREATE TABLE settings (
        setting_id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL,
        setting_description TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    -- Insert Default Data
    INSERT INTO users (username, email, password, role, created_at) 
    VALUES ('admin', 'admin@bbims.com', '$2y$10$ixfvzZPZcXUL9bI0f9tCVe6S59JRDQxZuHQrMEBXNYP6kG18/y9Q2', 'admin', NOW());

    -- Insert sample categories
    INSERT INTO categories (name, description) VALUES
    ('Dairy', 'Milk, cheese, and other dairy products'),
    ('Beverages', 'Drinks including coffee, tea, and juices'),
    ('Grains', 'Rice, wheat, and other grain products'),
    ('Confectionery', 'Sweets, chocolates, and other confectionery items'),
    ('Oils', 'Cooking oils and related products');

    -- Insert sample suppliers
    INSERT INTO suppliers (company_name, contact_person, phone, email, address, city, country) VALUES
    ('WALDO', 'James Smith', '0911290', 'info@waldo.com', '123 Main St', 'Metro City', 'Philippines'),
    ('Global Foods Inc.', 'Maria Garcia', '0922456789', 'contact@globalfoods.com', '456 Market Ave', 'Metro City', 'Philippines'),
    ('Fresh Farms Co.', 'John Lee', '0933123456', 'sales@freshfarms.com', '789 Harvest Rd', 'Rural County', 'Philippines'),
    ('Prime Distributors', 'Emily Wang', '0944987654', 'orders@primedist.com', '101 Logistics Blvd', 'Warehouse District', 'Philippines'),
    ('Quality Goods Ltd.', 'David Chen', '0955654321', 'inquiries@qualitygoods.com', '202 Quality Lane', 'Business Park', 'Philippines');

    -- Insert sample settings
    INSERT INTO settings (setting_key, setting_value, setting_description) VALUES
    ('company_name', 'Business IMS', 'Company name displayed in the application'),
    ('company_email', 'info@bbims.com', 'Company contact email'),
    ('company_phone', '+639123456789', 'Company contact phone'),
    ('company_address', '123 Business Ave, Metro City', 'Company physical address'),
    ('currency', 'PHP', 'Default currency used in the application'),
    ('tax_rate', '10', 'Default tax rate percentage');

    -- Create indexes for better performance
    CREATE INDEX idx_products_category ON products(category_id);
    CREATE INDEX idx_products_supplier ON products(supplier_id);
    CREATE INDEX idx_products_sku ON products(sku);
    CREATE INDEX idx_orders_customer ON orders(customer_id);
    CREATE INDEX idx_orders_user ON orders(user_id);
    CREATE INDEX idx_purchases_supplier ON purchases(supplier_id);
    CREATE INDEX idx_inventory_product ON inventory_log(product_id);
    CREATE INDEX idx_order_items_order ON order_items(order_id);
    CREATE INDEX idx_order_items_product ON order_items(product_id);
    CREATE INDEX idx_purchase_items_purchase ON purchase_items(purchase_id);
    CREATE INDEX idx_purchase_items_product ON purchase_items(product_id);

    -- Create view for stock status
    CREATE VIEW view_stock_status AS
    SELECT 
        p.product_id,
        p.sku,
        p.name,
        p.brand,
        c.name AS category,
        s.company_name AS supplier,
        p.stock_quantity,
        p.reorder_level,
        CASE
            WHEN p.stock_quantity <= 0 THEN 'Out of stock'
            WHEN p.stock_quantity <= p.reorder_level THEN 'Low stock'
            ELSE 'In stock'
        END AS stock_status,
        p.expiry_date
    FROM 
        products p
    LEFT JOIN
        categories c ON p.category_id = c.category_id
    LEFT JOIN
        suppliers s ON p.supplier_id = s.supplier_id;

    -- Create view for sales summary
    CREATE VIEW view_sales_summary AS
    SELECT 
        DATE(o.order_date) AS sale_date,
        COUNT(DISTINCT o.order_id) AS total_orders,
        SUM(o.total_amount) AS total_sales,
        SUM(o.tax_amount) AS total_tax,
        SUM(o.discount_amount) AS total_discounts,
        SUM(oi.quantity) AS total_items_sold
    FROM 
        orders o
    JOIN 
        order_items oi ON o.order_id = oi.order_id
    WHERE 
        o.order_status = 'completed'
    GROUP BY 
        DATE(o.order_date); 