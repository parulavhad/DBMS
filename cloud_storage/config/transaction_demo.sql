-- ============================================================
--  CloudVault — Transaction Management Demo (FA-2)
--  Demonstrates: ACID, Commit, Rollback, Deadlock Scenarios
-- ============================================================

USE cloud_storage_db;

-- ──────────────────────────────────────────────────────────
-- DEMO 1: ATOMICITY — Payment + Plan Upgrade (All or Nothing)
-- ──────────────────────────────────────────────────────────

DELIMITER $$

DROP PROCEDURE IF EXISTS upgrade_user_plan$$
CREATE PROCEDURE upgrade_user_plan(
    IN p_user_id    INT,
    IN p_new_plan   INT,
    IN p_amount     DECIMAL(10,2),
    IN p_pay_mode   VARCHAR(50)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        -- On any error: rollback everything (Atomicity)
        ROLLBACK;
        SELECT 'ERROR: Transaction rolled back. No changes made.' AS result;
    END;

    START TRANSACTION;

    -- Step 1: Validate the plan exists
    IF NOT EXISTS (SELECT 1 FROM StoragePlan WHERE plan_id = p_new_plan) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid plan_id';
    END IF;

    -- Step 2: Insert payment record (Pending)
    INSERT INTO Payment (user_id, plan_id, amount, payment_date, payment_mode, status)
    VALUES (p_user_id, p_new_plan, p_amount, CURDATE(), p_pay_mode, 'Pending');

    -- Step 3: Simulate gateway approval — set Completed
    UPDATE Payment
    SET status = 'Completed'
    WHERE payment_id = LAST_INSERT_ID();

    -- Step 4: Upgrade user plan
    UPDATE User SET plan_id = p_new_plan WHERE user_id = p_user_id;

    -- Step 5: Log the activity
    INSERT INTO ActivityLog (user_id, action_type, ip_address)
    VALUES (p_user_id, 'Login', '192.168.1.1');  -- reuse Login type for demo

    COMMIT;
    SELECT 'SUCCESS: Plan upgraded and payment recorded.' AS result;
END$$

DELIMITER ;

-- Test: Upgrade john_doe (user_id=2) to Pro (plan_id=3)
CALL upgrade_user_plan(2, 3, 499.00, 'UPI');


-- ──────────────────────────────────────────────────────────
-- DEMO 2: CONSISTENCY — File Upload with Quota Check
-- ──────────────────────────────────────────────────────────

DELIMITER $$

DROP PROCEDURE IF EXISTS upload_file_safe$$
CREATE PROCEDURE upload_file_safe(
    IN p_user_id    INT,
    IN p_folder_id  INT,
    IN p_file_name  VARCHAR(255),
    IN p_file_type  VARCHAR(50),
    IN p_size_mb    DECIMAL(10,4)
)
BEGIN
    DECLARE v_free_mb DECIMAL(12,4);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'ERROR: Upload failed — rolled back.' AS result;
    END;

    START TRANSACTION;

    -- Check available storage (Consistency enforcement)
    SELECT ((sp.storage_limit_gb * 1024) - COALESCE(SUM(f.file_size_mb), 0))
    INTO v_free_mb
    FROM User u
    JOIN StoragePlan sp ON u.plan_id = sp.plan_id
    LEFT JOIN File f ON f.user_id = u.user_id AND f.is_deleted = 0
    WHERE u.user_id = p_user_id
    GROUP BY sp.storage_limit_gb;

    IF v_free_mb IS NULL OR v_free_mb < p_size_mb THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Storage quota exceeded. Upload rejected.';
    END IF;

    -- Insert file record
    INSERT INTO File (file_name, file_type, file_size_mb, folder_id, user_id, is_deleted)
    VALUES (p_file_name, p_file_type, p_size_mb, p_folder_id, p_user_id, 0);

    -- Auto-create version 1
    INSERT INTO FileVersion (file_id, version_no, version_size_kb)
    VALUES (LAST_INSERT_ID(), 1, p_size_mb * 1024);

    -- Log the upload
    INSERT INTO ActivityLog (user_id, file_id, action_type, ip_address)
    VALUES (p_user_id, LAST_INSERT_ID(), 'Upload', '192.168.1.1');

    COMMIT;
    SELECT CONCAT('SUCCESS: File "', p_file_name, '" uploaded. Free space was ', ROUND(v_free_mb, 2), ' MB.') AS result;
END$$

DELIMITER ;

-- Test: Upload a 10MB file for john_doe
CALL upload_file_safe(2, 1, 'lecture_slides.pptx', 'pptx', 10.00);

-- Test: Attempt oversized upload (will fail for free plan users)
CALL upload_file_safe(3, NULL, 'huge_backup.iso', 'iso', 9999.00);


-- ──────────────────────────────────────────────────────────
-- DEMO 3: ISOLATION LEVELS — Viewing their effect
-- ──────────────────────────────────────────────────────────

-- Show current isolation level
SELECT @@transaction_isolation AS current_isolation_level;

-- Set SERIALIZABLE for payment transactions
SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE;
SELECT @@transaction_isolation AS isolation_after_set;

-- Reset to default (REPEATABLE READ) for normal operations
SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ;
SELECT @@transaction_isolation AS isolation_reset;


-- ──────────────────────────────────────────────────────────
-- DEMO 4: SOFT DELETE (Move to Trash) with ROLLBACK example
-- ──────────────────────────────────────────────────────────

DELIMITER $$

DROP PROCEDURE IF EXISTS move_to_trash$$
CREATE PROCEDURE move_to_trash(
    IN p_file_id INT,
    IN p_user_id INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'ERROR: Trash operation rolled back.' AS result;
    END;

    START TRANSACTION;

    -- Verify file belongs to user and is not already trashed
    IF NOT EXISTS (
        SELECT 1 FROM File
        WHERE file_id = p_file_id AND user_id = p_user_id AND is_deleted = 0
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'File not found or already in trash.';
    END IF;

    -- Soft delete: mark as deleted
    UPDATE File SET is_deleted = 1 WHERE file_id = p_file_id;

    -- Insert into Trash with 30-day expiry
    INSERT INTO Trash (file_id, user_id, permanent_delete_at)
    VALUES (p_file_id, p_user_id, TIMESTAMPADD(DAY, 30, NOW()));

    -- Log the delete action
    INSERT INTO ActivityLog (user_id, file_id, action_type, ip_address)
    VALUES (p_user_id, p_file_id, 'Delete', '192.168.1.1');

    COMMIT;
    SELECT 'SUCCESS: File moved to trash. Auto-deletes in 30 days.' AS result;
END$$

DELIMITER ;

-- Test: Move file_id=1 to trash for user_id=2
CALL move_to_trash(1, 2);


-- ──────────────────────────────────────────────────────────
-- DEMO 5: DEADLOCK PREVENTION — Consistent lock ordering
-- ──────────────────────────────────────────────────────────
-- The following shows the CORRECT lock acquisition order to
-- prevent deadlocks. Always lock: User → File → Payment

DELIMITER $$

DROP PROCEDURE IF EXISTS share_file_safe$$
CREATE PROCEDURE share_file_safe(
    IN p_file_id      INT,
    IN p_shared_by    INT,
    IN p_shared_with  INT,
    IN p_permission   VARCHAR(20)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'ERROR: Share operation rolled back.' AS result;
    END;

    START TRANSACTION;

    -- Lock User first (global order rule: User before File)
    SELECT user_id FROM User
    WHERE user_id IN (p_shared_by, p_shared_with)
    ORDER BY user_id
    FOR UPDATE;

    -- Lock File next
    SELECT file_id FROM File
    WHERE file_id = p_file_id AND user_id = p_shared_by AND is_deleted = 0
    FOR UPDATE;

    -- Validate permission value
    IF p_permission NOT IN ('View', 'Edit', 'Download') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid permission value';
    END IF;

    -- Create the share
    INSERT INTO SharedAccess (file_id, shared_by, shared_with, permission)
    VALUES (p_file_id, p_shared_by, p_shared_with, p_permission);

    -- Log the share
    INSERT INTO ActivityLog (user_id, file_id, action_type, ip_address)
    VALUES (p_shared_by, p_file_id, 'Share', '192.168.1.1');

    COMMIT;
    SELECT 'SUCCESS: File shared successfully.' AS result;
END$$

DELIMITER ;

-- Note: file_id=1 was moved to trash in Demo 4, so sharing will fail (as expected)
-- This demonstrates proper error handling
CALL share_file_safe(1, 2, 3, 'View');

-- ──────────────────────────────────────────────────────────
-- DEMO 6: TRANSACTION SCHEDULE ANALYSIS QUERIES
-- ──────────────────────────────────────────────────────────

-- View current storage usage per user (part of serializability demo)
SELECT
    u.username,
    u.email,
    sp.plan_name,
    sp.storage_limit_gb,
    COALESCE(SUM(f.file_size_mb), 0) AS used_mb,
    ROUND(
        COALESCE(SUM(f.file_size_mb), 0) / (sp.storage_limit_gb * 1024) * 100, 2
    ) AS usage_pct
FROM User u
JOIN StoragePlan sp ON u.plan_id = sp.plan_id
LEFT JOIN File f ON f.user_id = u.user_id AND f.is_deleted = 0
GROUP BY u.user_id, sp.plan_name, sp.storage_limit_gb
ORDER BY used_mb DESC;

-- View all trash items with days remaining
SELECT
    t.trash_id,
    f.file_name,
    u.username,
    t.deleted_at,
    t.permanent_delete_at,
    t.days_left
FROM Trash t
JOIN File f ON t.file_id = f.file_id
JOIN User u ON t.user_id = u.user_id
ORDER BY t.days_left ASC;

-- View recent activity log
SELECT
    al.log_id,
    u.username,
    al.action_type,
    al.action_date,
    al.ip_address
FROM ActivityLog al
JOIN User u ON al.user_id = u.user_id
ORDER BY al.action_date DESC
LIMIT 20;

-- ============================================================
-- END OF TRANSACTION MANAGEMENT DEMO
-- ============================================================
