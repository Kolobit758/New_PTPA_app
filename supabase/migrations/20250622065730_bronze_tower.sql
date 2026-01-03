-- ฐานข้อมูลสำหรับระบบวางแผนท่องเที่ยวจังหวัดพัทลุง
-- Database for Phatthalung Trip Planning System

DROP DATABASE IF EXISTS phatthalung_trip;
CREATE DATABASE phatthalung_trip CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE phatthalung_trip;

-- ตารางผู้ใช้งาน / Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ตารางประเภทการท่องเที่ยว / Tourism types table
CREATE TABLE tourism_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    name_en VARCHAR(50) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    color VARCHAR(20)
);

-- ตารางสถานที่ท่องเที่ยว / Attractions table
CREATE TABLE attractions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    tourism_type_id INT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    website VARCHAR(255),
    opening_hours TEXT,
    entrance_fee DECIMAL(10, 2) DEFAULT 0,
    estimated_duration INT DEFAULT 120, -- นาที / minutes
    rating DECIMAL(2, 1) DEFAULT 0,
    image_url VARCHAR(255),
    historical_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tourism_type_id) REFERENCES tourism_types(id)
);

-- ตารางที่พัก / Accommodations table
CREATE TABLE accommodations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    type ENUM('hotel', 'resort', 'guesthouse', 'homestay', 'hostel') NOT NULL,
    description TEXT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    website VARCHAR(255),
    price_per_night DECIMAL(10, 2),
    rating DECIMAL(2, 1) DEFAULT 0,
    amenities JSON,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางแผนการเดินทาง / Trip plans table
CREATE TABLE trip_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    tourism_type_id INT,
    budget DECIMAL(12, 2),
    start_date DATE,
    end_date DATE,
    total_days INT,
    status ENUM('planning', 'confirmed', 'completed', 'cancelled') DEFAULT 'planning',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tourism_type_id) REFERENCES tourism_types(id)
);

-- ตารางรายการการเดินทางรายวัน / Daily itinerary table
CREATE TABLE trip_itinerary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_plan_id INT NOT NULL,
    day_number INT NOT NULL,
    attraction_id INT,
    accommodation_id INT,
    start_time TIME,
    end_time TIME,
    notes TEXT,
    estimated_cost DECIMAL(10, 2),
    order_index INT DEFAULT 0,
    FOREIGN KEY (trip_plan_id) REFERENCES trip_plans(id) ON DELETE CASCADE,
    FOREIGN KEY (attraction_id) REFERENCES attractions(id),
    FOREIGN KEY (accommodation_id) REFERENCES accommodations(id)
);

-- ตารางรีวิวและคะแนน / Reviews table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    attraction_id INT,
    accommodation_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (attraction_id) REFERENCES attractions(id),
    FOREIGN KEY (accommodation_id) REFERENCES accommodations(id)
);

-- ข้อมูลเริ่มต้น / Initial data

-- ประเภทการท่องเที่ยว
INSERT INTO tourism_types (name, name_en, description, icon, color) VALUES
('ธรรมชาติ', 'Nature', 'ท่องเที่ยวเชิงธรรมชาติ ป่าไผ่ ทะเลสาบ และธรรมชาติที่สวยงาม', 'fas fa-leaf', '#28a745'),
('วัฒนธรรม', 'Culture', 'ศึกษาวัฒนธรรม ประเพณี วิถีชีวิตชาวพัทลุง', 'fas fa-users', '#007bff'),
('ตำนาน', 'Legend', 'สถานที่ท่องเที่ยวที่เกี่ยวข้องกับตำนานและเรื่องราวในอดีต', 'fas fa-book-open', '#6f42c1');

-- สถานที่ท่องเที่ยวธรรมชาติ
INSERT INTO attractions (name, description, tourism_type_id, latitude, longitude, address, entrance_fee, estimated_duration, rating, historical_info) VALUES
('ทะเลสาบสงขลา', 'ทะเลสาบน้ำจืดที่ใหญ่ที่สุดในประเทศไทย มีความอุดมสมบูรณ์ทางธรรมชาติ', 1, 7.6238, 100.0758, 'ตำบลคูหาสวรรค์ อำเภอเมืองพัทลุง จังหวัดพัทลุง', 0, 180, 4.5, 'ทะเลสาบสงขลาเป็นแหล่งน้ำจืดธรรมชาติที่สำคัญของภาคใต้ มีประวัติความเป็นมายาวนาน เป็นแหล่งอาหารและการดำรงชีวิตของชาวบ้านมาช้านาน'),

('เขาปู่เขายา', 'ภูเขาคู่สัญลักษณ์ของจังหวัดพัทลุง มีถ้ำและวิวทิวทัศน์สวยงาม', 1, 7.6167, 100.0833, 'ตำบลคูหาสวรรค์ อำเภอเมืองพัทลุง จังหวัดพัทลุง', 20, 240, 4.7, 'เขาปู่เขายาเป็นสัญลักษณ์ของจังหวัดพัทลุง มีตำนานเล่าขานว่าเป็นคู่รักที่ถูกสาปให้กลายเป็นภูเขา'),

('ป่าไผ่บางแสน', 'ป่าไผ่ธรรมชาติที่มีความสวยงามและเป็นแหล่งท่องเที่ยวเชิงนิเวศ', 1, 7.5500, 100.1200, 'ตำบลบางแสน อำเภอป่าบอน จังหวัดพัทลุง', 10, 120, 4.2, 'ป่าไผ่บางแสนเป็นป่าไผ่ธรรมชาติที่มีอายุหลายร้อยปี เป็นแหล่งเรียนรู้เรื่องระบบนิเวศป่าไผ่'),

-- สถานที่ท่องเที่ยววัฒนธรรม
('วัดวังใหม่', 'วัดเก่าแก่ที่มีสถาปัตยกรรมแบบจีนผสมไทย', 2, 7.6167, 100.0750, 'ตำบลคูหาสวรรค์ อำเภอเมืองพัทลุง จังหวัดพัทลุง', 0, 90, 4.3, 'วัดวังใหม่ก่อตั้งขึ้นในสมัยกรุงศรีอยุธยา มีพระพุทธรูปศักดิ์สิทธิ์และสถาปัตยกรรมที่งดงาม'),

('พิพิธภัณฑ์พัทลุง', 'พิพิธภัณฑ์ที่รวบรวมประวัติศาสตร์และวัฒนธรรมท้องถิ่น', 2, 7.6200, 100.0800, 'อำเภอเมืองพัทลุง จังหวัดพัทลุง', 30, 120, 4.1, 'พิพิธภัณฑ์พัทลุงจัดแสดงเรื่องราวประวัติศาสตร์ วัฒนธรรม และวิถีชีวิตของชาวพัทลุงตั้งแต่อดีตจนถึงปัจจุบัน'),

-- สถานที่ท่องเที่ยวตำนาน
('ถ้ำกูหาสวรรค์', 'ถ้ำที่มีตำนานและเรื่องราวลึกลับ', 3, 7.6300, 100.0700, 'ตำบลคูหาสวรรค์ อำเภอเมืองพัทลุง จังหวัดพัทลุง', 40, 150, 4.6, 'ถ้ำกูหาสวรรค์มีตำนานเล่าขานว่าเป็นทางไปสู่สวรรค์ ภายในถ้ำมีหินงอกหินย้อยที่สวยงาม'),

('วัดถ้ำแก้ว', 'วัดในถ้ำที่มีความศักดิ์สิทธิ์และตำนานโบราณ', 3, 7.5800, 100.1100, 'อำเภอป่าบอน จังหวัดพัทลุง', 20, 100, 4.4, 'วัดถ้ำแก้วเป็นวัดในถ้ำที่มีพระพุทธรูปประจำถ้ำ มีตำนานเล่าขานเรื่องนางแก้วงามที่มาปฏิบัติธรรม');

-- ที่พัก
INSERT INTO accommodations (name, type, description, latitude, longitude, address, phone, price_per_night, rating, amenities) VALUES
('พัทลุง รีสอร์ท', 'resort', 'รีสอร์ทติดทะเลสาบสงขลา บรรยากาศดี วิวสวย', 7.6200, 100.0780, 'ตำบลคูหาสวรรค์ อำเภอเมืองพัทลุง', '074-611234', 2500.00, 4.3, '["wifi", "pool", "restaurant", "parking", "lake_view"]'),

('บ้านสวนโฮมสเตย์', 'homestay', 'โฮมสเตย์ในสวนผลไม้ บรรยากาศธรรมชาติ', 7.5900, 100.0900, 'ตำบลบางแสน อำเภอป่าบอน', '074-612345', 800.00, 4.1, '["wifi", "garden", "breakfast", "parking", "local_experience"]'),

('พัทลุง ซิตี้ โฮเทล', 'hotel', 'โรงแรมใจกลางเมือง สะดวกสบาย', 7.6150, 100.0780, 'อำเภอเมืองพัทลุง จังหวัดพัทลุง', '074-613456', 1200.00, 3.9, '["wifi", "air_con", "restaurant", "parking", "city_center"]'),

('เกสต์เฮาส์ธรรมชาติ', 'guesthouse', 'เกสต์เฮาส์เล็กๆ บรรยากาศดี ราคาประหยัด', 7.6000, 100.1000, 'ตำบลป่าบอน อำเภอป่าบอน', '074-614567', 600.00, 4.0, '["wifi", "fan", "shared_bathroom", "parking", "nature_view"]');

-- ข้อมูลผู้ใช้ตัวอย่าง (รหัสผ่าน: password123)
INSERT INTO users (username, email, password, full_name, phone) VALUES
('admin', 'admin@phatthalung-trip.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', '074-123456'),
('testuser', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ใช้ทดสอบ', '081-234567');