-- ============================================================
--  Cloud Storage Management System - Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS cloud_storage_db;
USE cloud_storage_db;

-- -----------------------------------------------
-- 1. StoragePlan
-- -----------------------------------------------
CREATE TABLE StoragePlan (
    plan_id       INT AUTO_INCREMENT PRIMARY KEY,
    plan_name     VARCHAR(100) NOT NULL,
    storage_limit_gb DECIMAL(10,2) NOT NULL,
    price         DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL,
    expiry_date   DATE,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------
-- 2. User
-- -----------------------------------------------
CREATE TABLE User (
    user_id      INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(100) NOT NULL UNIQUE,
    email        VARCHAR(150) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    full_name    VARCHAR(150),
    phone        VARCHAR(20),
    account_age  INT DEFAULT 0 COMMENT 'Days since registration',
    plan_id      INT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES StoragePlan(plan_id) ON DELETE SET NULL
);

-- -----------------------------------------------
-- 3. Payment
-- -----------------------------------------------
CREATE TABLE Payment (
    payment_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    plan_id      INT NOT NULL,
    amount       DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_mode VARCHAR(50) NOT NULL COMMENT 'UPI, Card, NetBanking, etc.',
    status       ENUM('Pending','Completed','Failed','Refunded') DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES StoragePlan(plan_id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- 4. Folder
-- -----------------------------------------------
CREATE TABLE Folder (
    folder_id        INT AUTO_INCREMENT PRIMARY KEY,
    folder_name      VARCHAR(255) NOT NULL,
    folder_path      TEXT,
    parent_folder_id INT DEFAULT NULL,
    user_id          INT NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_folder_id) REFERENCES Folder(folder_id) ON DELETE SET NULL
);

-- -----------------------------------------------
-- 5. File
-- -----------------------------------------------
CREATE TABLE File (
    file_id       INT AUTO_INCREMENT PRIMARY KEY,
    file_name     VARCHAR(255) NOT NULL,
    file_type     VARCHAR(50),
    file_size_mb  DECIMAL(10,4) NOT NULL DEFAULT 0,
    folder_id     INT,
    user_id       INT NOT NULL,
    tags          TEXT COMMENT 'Comma-separated tags',
    uploaded_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted    TINYINT(1) DEFAULT 0,
    FOREIGN KEY (folder_id) REFERENCES Folder(folder_id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- 6. SharedAccess
-- -----------------------------------------------
CREATE TABLE SharedAccess (
    share_id    INT AUTO_INCREMENT PRIMARY KEY,
    file_id     INT NOT NULL,
    shared_by   INT NOT NULL COMMENT 'User ID who shared',
    shared_with INT COMMENT 'User ID with whom shared (NULL = public link)',
    permission  ENUM('View','Edit','Download') DEFAULT 'View',
    shared_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_expired  TINYINT(1) DEFAULT 0,
    FOREIGN KEY (file_id) REFERENCES File(file_id) ON DELETE CASCADE,
    FOREIGN KEY (shared_by) REFERENCES User(user_id) ON DELETE CASCADE,
    FOREIGN KEY (shared_with) REFERENCES User(user_id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- 7. Trash
-- -----------------------------------------------
CREATE TABLE Trash (
    trash_id           INT AUTO_INCREMENT PRIMARY KEY,
    file_id            INT NOT NULL,
    user_id            INT NOT NULL,
    deleted_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    permanent_delete_at TIMESTAMP NULL COMMENT 'Auto-delete after 30 days',
    days_left          INT GENERATED ALWAYS AS (
                           DATEDIFF(permanent_delete_at, NOW())
                       ) VIRTUAL,
    FOREIGN KEY (file_id) REFERENCES File(file_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- 8. FileVersion
-- -----------------------------------------------
CREATE TABLE FileVersion (
    version_id       INT AUTO_INCREMENT PRIMARY KEY,
    file_id          INT NOT NULL,
    version_no       INT NOT NULL DEFAULT 1,
    version_size_kb  DECIMAL(10,2),
    saved_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    days_since_saved INT GENERATED ALWAYS AS (
                         DATEDIFF(NOW(), saved_at)
                     ) VIRTUAL,
    FOREIGN KEY (file_id) REFERENCES File(file_id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- 9. ActivityLog
-- -----------------------------------------------
CREATE TABLE ActivityLog (
    log_id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    file_id         INT DEFAULT NULL,
    action_type     ENUM('Upload','Download','Delete','Restore','Share','Login','Logout','CreateFolder','RenameFile') NOT NULL,
    action_date     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address      VARCHAR(45),
    days_since_action INT GENERATED ALWAYS AS (
                          DATEDIFF(NOW(), action_date)
                      ) VIRTUAL,
    FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE,
    FOREIGN KEY (file_id) REFERENCES File(file_id) ON DELETE SET NULL
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

INSERT INTO StoragePlan (plan_name, storage_limit_gb, price, duration_days, expiry_date) VALUES
('Free',    5.00,    0.00,  365, '2025-12-31'),
('Basic',  50.00,  199.00,   30, '2025-04-30'),
('Pro',   200.00,  499.00,   30, '2025-04-30'),
('Ultra', 1000.00, 999.00,   30, '2025-04-30');

INSERT INTO User (username, email, password, full_name, phone, plan_id) VALUES
('admin',     'admin@cloudstorage.com',    MD5('admin123'),    'Admin User',    '9000000001', 3),
('john_doe',  'john@example.com',          MD5('john123'),     'John Doe',      '9000000002', 2),
('jane_doe',  'jane@example.com',          MD5('jane123'),     'Jane Doe',      '9000000003', 1);

INSERT INTO Payment (user_id, plan_id, amount, payment_date, payment_mode, status) VALUES
(2, 2, 199.00, '2025-03-01', 'UPI',  'Completed'),
(3, 1,   0.00, '2025-03-01', 'None', 'Completed');
