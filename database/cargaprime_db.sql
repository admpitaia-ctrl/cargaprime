-- CargaPrime 3.6.3 - base de dados consolidada para a entrega final.
-- IMPORTANTE: este ficheiro NÃO repõe o stock de produtos que já existam.
-- Assim, pode ser usado sem transformar novamente 5 t em 24 t, por exemplo.

CREATE DATABASE IF NOT EXISTS cargaprime_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cargaprime_db;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  birth_date DATE NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(300) NOT NULL,
  category VARCHAR(60) NOT NULL DEFAULT 'Outros',
  factory_name VARCHAR(140) NOT NULL DEFAULT 'Fábrica não identificada',
  factory_address VARCHAR(300) NULL,
  price_per_ton DECIMAL(10,2) NOT NULL,
  stock_tons DECIMAL(10,2) NOT NULL DEFAULT 0,
  image VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CHECK (price_per_ton >= 0),
  CHECK (stock_tons >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  customer_name VARCHAR(120) NOT NULL,
  birth_date DATE NOT NULL,
  address VARCHAR(300) NOT NULL,
  collection_date DATE NOT NULL,
  collection_time TIME NOT NULL DEFAULT '09:00:00',
  total DECIMAL(12,2) NOT NULL,
  status ENUM('Pendente','Aprovada','Pronta','A caminho','Concluída','Cancelada') DEFAULT 'Pendente',
  tracking_url VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart_items (
  user_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity_tons DECIMAL(10,2) NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, product_id),
  CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS support_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NULL,
  order_item_id INT UNSIGNED NULL,
  factory_name VARCHAR(140) NULL,
  factory_address VARCHAR(300) NULL,
  subject VARCHAR(120) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('Aberto','Respondido','Fechado') NOT NULL DEFAULT 'Aberto',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_support_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_support_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS support_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id INT UNSIGNED NOT NULL,
  sender_role ENUM('customer','admin') NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_support_message_request FOREIGN KEY (request_id) REFERENCES support_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  product_name VARCHAR(120) NOT NULL,
  factory_name VARCHAR(140) NOT NULL DEFAULT 'Fábrica não identificada',
  factory_address VARCHAR(300) NULL,
  quantity_tons DECIMAL(10,2) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_item_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;


-- Compatibilidade com uma base já criada numa versão anterior.
-- Estes ALTERs apenas acrescentam os campos se ainda não existirem.
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS factory_name VARCHAR(140) NOT NULL DEFAULT 'Fábrica não identificada' AFTER category,
  ADD COLUMN IF NOT EXISTS factory_address VARCHAR(300) NULL AFTER factory_name;

ALTER TABLE support_requests
  ADD COLUMN IF NOT EXISTS order_item_id INT UNSIGNED NULL AFTER order_id,
  ADD COLUMN IF NOT EXISTS factory_name VARCHAR(140) NULL AFTER order_item_id,
  ADD COLUMN IF NOT EXISTS factory_address VARCHAR(300) NULL AFTER factory_name;

ALTER TABLE order_items
  ADD COLUMN IF NOT EXISTS factory_name VARCHAR(140) NOT NULL DEFAULT 'Fábrica não identificada' AFTER product_name,
  ADD COLUMN IF NOT EXISTS factory_address VARCHAR(300) NULL AFTER factory_name;

-- Seed final: três produtos de demonstração. Só são inseridos quando ainda não existe um produto com o mesmo nome.
-- Não existe UPDATE de stock aqui; o stock consumido pelas encomendas é preservado.
INSERT INTO products (name, description, category, factory_name, factory_address, price_per_ton, stock_tons, image)
SELECT 'Gesso ensacado', 'Gesso industrial protegido e paletizado, pronto para transporte em camião-baú.', 'Construção', 'Fábrica Parceira - Gesso', 'Origem industrial de demonstração', 118.00, 24.0, 'assets/images/products/gesso-ensacado.png'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Gesso ensacado');

INSERT INTO products (name, description, category, factory_name, factory_address, price_per_ton, stock_tons, image)
SELECT 'Algodão prensado', 'Fardos compactos e protegidos para fornecimento industrial de grande volume.', 'Têxtil', 'Indústria Parceira - Algodão', 'Origem industrial de demonstração', 1640.00, 31.0, 'assets/images/products/algodao-prensado.png'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Algodão prensado');

INSERT INTO products (name, description, category, factory_name, factory_address, price_per_ton, stock_tons, image)
SELECT 'Celulose em fardos', 'Celulose branca acondicionada em fardos para uma logística limpa e segura.', 'Papel', 'Indústria Parceira - Celulose', 'Origem industrial de demonstração', 735.00, 18.0, 'assets/images/products/celulose-fardos.png'
WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = 'Celulose em fardos');


-- Se a base já existia antes da versão 3.5, preencher apenas a identificação
-- da fábrica dos produtos de demonstração. O stock não é alterado.
UPDATE products SET factory_name='Fábrica Parceira - Gesso', factory_address='Origem industrial de demonstração'
WHERE name='Gesso ensacado' AND factory_name='Fábrica não identificada';

UPDATE products SET factory_name='Indústria Parceira - Algodão', factory_address='Origem industrial de demonstração'
WHERE name='Algodão prensado' AND factory_name='Fábrica não identificada';

UPDATE products SET factory_name='Indústria Parceira - Celulose', factory_address='Origem industrial de demonstração'
WHERE name='Celulose em fardos' AND factory_name='Fábrica não identificada';

-- Administrador inicial: só é criado se o username admin ainda não existir.
-- Palavra-passe inicial: Admin@123
INSERT INTO users (name, username, password_hash, role)
SELECT 'Administrador CargaPrime', 'admin', '$2b$12$WWkqONi85QogxO76fNn5l.dDu0.XWIG2k45fc0xiwvovygFoq0GGG', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');
