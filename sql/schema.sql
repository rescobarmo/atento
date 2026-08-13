CREATE DATABASE IF NOT EXISTS marketing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE marketing;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(50) DEFAULT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    rol_id INT DEFAULT 1,
    sucursal VARCHAR(255) DEFAULT NULL,
    activo TINYINT(1) DEFAULT 1,
    ultimo_acceso DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS canales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('social','email','seo','paid','referral','direct','other') DEFAULT 'other',
    icono VARCHAR(50) DEFAULT 'globe',
    color VARCHAR(7) DEFAULT '#6B7280',
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS campanas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    canal_id INT DEFAULT NULL,
    tipo ENUM('email','social','display','search','evento','otros') DEFAULT 'otros',
    presupuesto DECIMAL(12,2) DEFAULT 0.00,
    inversion DECIMAL(12,2) DEFAULT 0.00,
    fecha_inicio DATE DEFAULT NULL,
    fecha_fin DATE DEFAULT NULL,
    estado ENUM('planificada','activa','pausada','completada','cancelada') DEFAULT 'planificada',
    objetivo VARCHAR(500) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (canal_id) REFERENCES canales(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    empresa VARCHAR(200) DEFAULT NULL,
    cargo VARCHAR(100) DEFAULT NULL,
    fuente VARCHAR(100) DEFAULT NULL,
    campana_id INT DEFAULT NULL,
    canal_id INT DEFAULT NULL,
    estado ENUM('nuevo','contactado','calificado','propuesta','negociacion','ganado','perdido') DEFAULT 'nuevo',
    score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campana_id) REFERENCES campanas(id) ON DELETE SET NULL,
    FOREIGN KEY (canal_id) REFERENCES canales(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS metricas_campana (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campana_id INT NOT NULL,
    fecha DATE NOT NULL,
    impresiones INT DEFAULT 0,
    clicks INT DEFAULT 0,
    conversiones INT DEFAULT 0,
    ingresos DECIMAL(12,2) DEFAULT 0.00,
    gasto DECIMAL(12,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campana_id) REFERENCES campanas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_campaign_date (campana_id, fecha)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS conversiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT DEFAULT NULL,
    campana_id INT DEFAULT NULL,
    canal_id INT DEFAULT NULL,
    tipo ENUM('venta',' registro','descarga','demo','contacto','suscripcion') DEFAULT 'venta',
    valor DECIMAL(12,2) DEFAULT 0.00,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    FOREIGN KEY (campana_id) REFERENCES campanas(id) ON DELETE SET NULL,
    FOREIGN KEY (canal_id) REFERENCES canales(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS visitas_sitio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    visitantes INT DEFAULT 0,
    visitas INT DEFAULT 0,
    paginas_vistas INT DEFAULT 0,
    tasa_rebote DECIMAL(5,2) DEFAULT 0.00,
    duracion_media INT DEFAULT 0,
    canal_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (canal_id) REFERENCES canales(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO roles (id, nombre) VALUES (1, 'Administrador'), (2, 'Editor'), (3, 'Visualizador'), (4, 'Agente de Ventas') ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

INSERT INTO usuarios (nombre, email, password, rol_id) VALUES
('Admin Marketing', 'admin@redsalud.cl', '$2y$10$.oWKnlMbgojApr/2n4WBnuCmKSd/FV4kEmRMxghaTw7oxVATcjMO6', 1),
('Editor Marketing', 'editor@redsalud.cl', '$2y$10$.oWKnlMbgojApr/2n4WBnuCmKSd/FV4kEmRMxghaTw7oxVATcjMO6', 2)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

INSERT INTO canales (nombre, tipo, icono, color) VALUES
('Google Ads', 'paid', 'google', '#4285F4'),
('Facebook Ads', 'social', 'facebook', '#1877F2'),
('Instagram', 'social', 'instagram', '#E4405F'),
('LinkedIn', 'social', 'linkedin', '#0A66C2'),
('Email Marketing', 'email', 'mail', '#EA4335'),
('SEO Orgánico', 'seo', 'search', '#34A853'),
('Referidos', 'referral', 'users', '#FBBC04'),
('Twitter/X', 'social', 'twitter', '#1DA1F2'),
('YouTube', 'social', 'youtube', '#FF0000'),
('Display Ads', 'paid', 'monitor', '#8B5CF6');

INSERT INTO campanas (nombre, descripcion, canal_id, tipo, presupuesto, inversion, fecha_inicio, fecha_fin, estado, objetivo) VALUES
('Lanzamiento Q3 2026', 'Campaña de lanzamiento de nuevos servicios', 1, 'search', 5000000, 4200000, '2026-01-01', '2026-03-31', 'completada', 'Generar 200 leads calificados'),
('Newsletter Abril', 'Campaña de email marketing mensual', 5, 'email', 500000, 380000, '2026-04-01', '2026-04-30', 'activa', 'Aumentar tasa de apertura a 30%'),
('Branding LinkedIn', 'Campaña de branding corporativo', 4, 'social', 2000000, 1500000, '2026-02-01', '2026-06-30', 'activa', 'Alcanzar 100K impresiones'),
('Oferta Especial Verano', 'Descuentos por temporada de verano', 2, 'social', 3000000, 2800000, '2026-05-01', '2026-07-31', 'activa', 'Vender 50 paquetes'),
('SEO Blog', 'Estrategia de contenido orgánico', 6, 'otros', 800000, 650000, '2026-01-15', '2026-12-31', 'activa', 'Posicionar 20 keywords en top 10'),
('Webinar Productividad', 'Webinar gratuito para captación de leads', 7, 'evento', 1200000, 950000, '2026-03-01', '2026-03-15', 'completada', 'Captar 300 registros'),
('Retargeting Display', 'Campaña de remarketing display', 10, 'display', 1500000, 1100000, '2026-04-15', '2026-06-15', 'activa', 'Recuperar 100 carritos abandonados'),
('Campaña YouTube', 'Video campaña institucional', 9, 'social', 2500000, 1800000, '2026-05-01', '2026-08-31', 'planificada', '1M de reproducciones'),
('Lanzamiento App', 'Campaña de lanzamiento app móvil', 3, 'social', 4000000, 0, '2026-07-01', '2026-09-30', 'planificada', '10K descargas primer mes'),
('Google Ads Local', 'Campaña geolocalizada Google Ads', 1, 'search', 1800000, 1200000, '2026-04-01', '2026-06-30', 'activa', 'Atraer 500 visitas a locales');

INSERT INTO leads (nombre, apellido, email, telefono, empresa, cargo, fuente, campana_id, canal_id, estado, score) VALUES
('Carlos', 'Muñoz', 'carlos.m@ejemplo.cl', '+56912345678', 'TechCorp', 'CTO', 'Webinar Productividad', 6, 7, 'ganado', 95),
('María', 'González', 'maria.g@ejemplo.cl', '+56923456789', 'DataSys', 'Marketing Manager', 'Google Ads', 1, 1, 'negociacion', 82),
('Pedro', 'Ramírez', 'pedro.r@ejemplo.cl', '+56934567890', 'InnovaChile', 'CEO', 'LinkedIn', 3, 4, 'calificado', 70),
('Ana', 'López', 'ana.l@ejemplo.cl', '+56945678901', 'CloudNet', 'Directora TI', 'Newsletter', 2, 5, 'contactado', 45),
('Diego', 'Martínez', 'diego.m@ejemplo.cl', '+56956789012', 'EcommSolution', 'E-commerce Manager', 'Google Ads Local', 10, 1, 'nuevo', 30),
('Valentina', 'Torres', 'valentina.t@ejemplo.cl', '+56967890123', 'FintechPro', 'CMO', 'Referido', 7, 7, 'ganado', 98),
('Francisco', 'Soto', 'francisco.s@ejemplo.cl', '+56978901234', 'GreenEnergy', 'Gerente', 'SEO Blog', 5, 6, 'propuesta', 60),
('Javiera', 'Rojas', 'javiera.r@ejemplo.cl', '+56989012345', 'HealthPlus', 'Directora Marketing', 'Facebook Ads', 4, 2, 'calificado', 75),
('Matías', 'Castro', 'matias.c@ejemplo.cl', '+56990123456', 'LegalTech', 'Abogado', 'Retargeting Display', 7, 10, 'contactado', 40),
('Camila', 'Fernández', 'camila.f@ejemplo.cl', '+56901234567', 'EduOnline', 'Product Manager', 'Webinar Productividad', 6, 7, 'ganado', 92),
('Sebastián', 'Álvarez', 'sebastian.a@ejemplo.cl', '+56911223344', 'SmartBuild', 'Arquitecto', 'YouTube', 8, 9, 'nuevo', 15),
('Constanza', 'Herrera', 'constanza.h@ejemplo.cl', '+56922334455', 'MediaPulse', 'Analista Marketing', 'Instagram', 4, 3, 'calificado', 68),
('Felipe', 'Díaz', 'felipe.d@ejemplo.cl', '+56933445566', 'LogisTrack', 'Logistics Manager', 'Google Ads', 1, 1, 'negociacion', 78),
('Isidora', 'Vargas', 'isidora.v@ejemplo.cl', '+56944556677', 'BioHealth', 'CEO', 'LinkedIn', 3, 4, 'perdido', 25),
('Joaquín', 'Peña', 'joaquin.p@ejemplo.cl', '+56955667788', 'RetailNow', 'Director Ventas', 'Newsletter Abril', 2, 5, 'propuesta', 55);

INSERT INTO metricas_campana (campana_id, fecha, impresiones, clicks, conversiones, ingresos, gasto) VALUES
(1, '2026-01-15', 15000, 450, 12, 3500000, 1200000),
(1, '2026-02-15', 18000, 520, 15, 4200000, 1500000),
(1, '2026-03-15', 22000, 680, 20, 5100000, 1500000),
(2, '2026-04-01', 5000, 250, 8, 1200000, 95000),
(2, '2026-04-08', 4800, 230, 6, 980000, 95000),
(2, '2026-04-15', 5200, 260, 9, 1350000, 95000),
(2, '2026-04-22', 4900, 240, 7, 1100000, 95000),
(3, '2026-02-01', 25000, 380, 5, 800000, 375000),
(3, '2026-03-01', 28000, 420, 6, 950000, 375000),
(3, '2026-04-01', 30000, 450, 7, 1100000, 375000),
(3, '2026-05-01', 32000, 480, 8, 1250000, 375000),
(4, '2026-05-01', 35000, 1200, 25, 4500000, 700000),
(4, '2026-06-01', 38000, 1350, 28, 5200000, 700000),
(4, '2026-07-01', 40000, 1500, 30, 5800000, 700000),
(5, '2026-01-15', 8000, 200, 4, 500000, 162500),
(5, '2026-02-15', 12000, 280, 6, 750000, 162500),
(5, '2026-03-15', 15000, 320, 7, 900000, 162500),
(5, '2026-04-15', 18000, 380, 9, 1100000, 162500),
(6, '2026-03-01', 10000, 500, 30, 900000, 316667),
(6, '2026-03-08', 12000, 580, 35, 1050000, 316667),
(6, '2026-03-15', 15000, 650, 40, 1200000, 316666),
(7, '2026-04-15', 20000, 400, 8, 1500000, 366667),
(7, '2026-05-15', 22000, 450, 10, 1800000, 366667),
(7, '2026-06-15', 25000, 500, 12, 2100000, 366666),
(10, '2026-04-01', 12000, 360, 10, 1200000, 300000),
(10, '2026-05-01', 14000, 400, 12, 1500000, 300000),
(10, '2026-06-01', 16000, 450, 14, 1800000, 300000);

INSERT INTO conversiones (lead_id, campana_id, canal_id, tipo, valor, fecha) VALUES
(1, 6, 7, 'venta', 2500000, '2026-03-20'),
(2, 1, 1, 'demo', 0, '2026-04-15'),
(6, 7, 7, 'venta', 5000000, '2026-05-10'),
(7, 5, 6, 'contacto', 0, '2026-06-01'),
(10, 6, 7, 'venta', 3200000, '2026-03-25'),
(3, 3, 4, 'demo', 0, '2026-05-20'),
(13, 1, 1, 'suscripcion', 150000, '2026-02-20'),
(8, 4, 2, 'venta', 1800000, '2026-06-15'),
(11, 8, 9, 'registro', 0, '2026-05-10');

INSERT INTO visitas_sitio (fecha, visitantes, visitas, paginas_vistas, tasa_rebote, duracion_media, canal_id) VALUES
('2026-01-15', 1200, 1500, 4500, 45.2, 180, 1),
('2026-01-15', 800, 950, 2800, 38.5, 210, 6),
('2026-02-15', 1400, 1700, 5100, 42.1, 195, 1),
('2026-02-15', 900, 1100, 3300, 36.8, 225, 6),
('2026-03-15', 1800, 2200, 6600, 40.5, 200, 1),
('2026-03-15', 1100, 1300, 3900, 35.2, 240, 6),
('2026-04-01', 2000, 2500, 7500, 38.9, 215, 2),
('2026-04-01', 1500, 1800, 5400, 41.3, 190, 5),
('2026-04-15', 2200, 2700, 8100, 37.5, 220, 2),
('2026-04-15', 1600, 1900, 5700, 39.8, 200, 5),
('2026-05-01', 2500, 3100, 9300, 36.1, 230, 2),
('2026-05-01', 1800, 2200, 6600, 37.4, 215, 5),
('2026-05-15', 2800, 3500, 10500, 35.8, 240, 2),
('2026-05-15', 2000, 2400, 7200, 36.5, 225, 5);
