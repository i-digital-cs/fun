-- =============================================================================
-- Funnity stale-job quarantine — REQUIRES OWNER APPROVAL BEFORE EXECUTION
-- Hardened: 2026-11-04
-- Engine: MariaDB 11.x (production)
--
-- Effect: change queue of jobs #3 and #4 from `default` → `hold_stale_2026_10_27`
-- Never deletes. Never executes. Aborts (ROLLBACK) if preconditions fail.
--
-- Expected identity (from 2026-11-03 inspection):
--   id=3  uuid ccc78b0b-ac7a-4126-ac6a-003dc333dae9  NotifyFriendsOfPublishedEvent  eventId=603
--   id=4  uuid ba1b3d22-56bf-48ed-900d-e9c3f41cc6a3  NotifyFriendsOfPublishedEvent  eventId=604
--
-- NOTE: CREATE TABLE is DDL and would implicit-commit on MariaDB, so the backup
-- table is ensured BEFORE the transactional procedure body runs.
-- =============================================================================

-- Optional read-only preflight (run separately; safe):
--   SELECT id, queue, attempts, reserved_at,
--          FROM_UNIXTIME(available_at) AS available_at,
--          FROM_UNIXTIME(created_at) AS created_at,
--          JSON_UNQUOTE(JSON_EXTRACT(payload, '$.displayName')) AS display_name,
--          JSON_UNQUOTE(JSON_EXTRACT(payload, '$.uuid')) AS job_uuid
--   FROM jobs
--   WHERE id IN (3, 4);

-- -------------------------------------------------------------------------
-- Backup table (DDL outside procedure / outside TX — safe to re-run)
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jobs_quarantine_backup_2026_11_04 (
  backup_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  backed_up_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id BIGINT UNSIGNED NOT NULL,
  queue VARCHAR(255) NOT NULL,
  payload LONGTEXT NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL,
  reserved_at INT UNSIGNED NULL,
  available_at INT UNSIGNED NOT NULL,
  created_at INT UNSIGNED NOT NULL,
  UNIQUE KEY uq_jobs_quarantine_backup_job_id (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP PROCEDURE IF EXISTS funnity_quarantine_stale_jobs_2026_10_27;

DELIMITER $$

CREATE PROCEDURE funnity_quarantine_stale_jobs_2026_10_27()
main: BEGIN
  DECLARE v_cnt INT DEFAULT 0;
  DECLARE v_ok INT DEFAULT 0;
  DECLARE v_updated INT DEFAULT 0;
  DECLARE v_on_default INT DEFAULT 0;
  DECLARE v_backup_rows INT DEFAULT 0;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  -- 1) Row-lock candidates
  SELECT COUNT(*) INTO v_cnt
  FROM jobs
  WHERE id IN (3, 4)
  FOR UPDATE;

  IF v_cnt <> 2 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ABORT: need exactly 2 rows for jobs id 3 and 4';
  END IF;

  -- 2) Verify job #3 identity + state
  -- Payload is JSON; PHP-serialized command embeds backslash-escaped quotes, so
  -- match class + uuid + eventId token + i:603; + publisherId (avoid brittle \").
  SELECT COUNT(*) INTO v_ok
  FROM jobs
  WHERE id = 3
    AND queue = 'default'
    AND reserved_at IS NULL
    AND attempts = 0
    AND payload LIKE '%NotifyFriendsOfPublishedEvent%'
    AND payload LIKE '%ccc78b0b-ac7a-4126-ac6a-003dc333dae9%'
    AND payload LIKE '%eventId%'
    AND payload LIKE '%i:603;%'
    AND payload LIKE '%publisherId%'
    AND payload LIKE '%i:321;%'
  FOR UPDATE;

  IF v_ok <> 1 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ABORT: job 3 failed class/uuid/queue/reserve checks';
  END IF;

  -- 3) Verify job #4 identity + state
  SELECT COUNT(*) INTO v_ok
  FROM jobs
  WHERE id = 4
    AND queue = 'default'
    AND reserved_at IS NULL
    AND attempts = 0
    AND payload LIKE '%NotifyFriendsOfPublishedEvent%'
    AND payload LIKE '%ba1b3d22-56bf-48ed-900d-e9c3f41cc6a3%'
    AND payload LIKE '%eventId%'
    AND payload LIKE '%i:604;%'
    AND payload LIKE '%publisherId%'
    AND payload LIKE '%i:102;%'
  FOR UPDATE;

  IF v_ok <> 1 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ABORT: job 4 failed class/uuid/queue/reserve checks';
  END IF;

  -- 4) Preserve full original rows (DML only — stays in TX)
  INSERT INTO jobs_quarantine_backup_2026_11_04
    (id, queue, payload, attempts, reserved_at, available_at, created_at)
  SELECT id, queue, payload, attempts, reserved_at, available_at, created_at
  FROM jobs
  WHERE id IN (3, 4)
  ON DUPLICATE KEY UPDATE
    backed_up_at = CURRENT_TIMESTAMP,
    queue = VALUES(queue),
    payload = VALUES(payload),
    attempts = VALUES(attempts),
    reserved_at = VALUES(reserved_at),
    available_at = VALUES(available_at),
    created_at = VALUES(created_at);

  SELECT COUNT(*) INTO v_backup_rows
  FROM jobs_quarantine_backup_2026_11_04
  WHERE id IN (3, 4);

  IF v_backup_rows <> 2 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ABORT: backup missing; expected 2 preserved rows';
  END IF;

  -- 5) Quarantine — queue column only
  UPDATE jobs
  SET queue = 'hold_stale_2026_10_27'
  WHERE id IN (3, 4)
    AND queue = 'default'
    AND reserved_at IS NULL
    AND attempts = 0
    AND payload LIKE '%NotifyFriendsOfPublishedEvent%';

  SET v_updated = ROW_COUNT();

  IF v_updated <> 2 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ABORT: UPDATE row count was not 2; rolled back';
  END IF;

  -- 6) Post-conditions
  SELECT COUNT(*) INTO v_on_default
  FROM jobs
  WHERE id IN (3, 4)
    AND queue = 'default';

  IF v_on_default <> 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ABORT: jobs 3/4 still on default after UPDATE';
  END IF;

  SELECT COUNT(*) INTO v_ok
  FROM jobs
  WHERE id IN (3, 4)
    AND queue = 'hold_stale_2026_10_27';

  IF v_ok <> 2 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ABORT: both jobs must be on hold_stale_2026_10_27';
  END IF;

  COMMIT;

  SELECT id, queue, attempts, reserved_at,
         FROM_UNIXTIME(available_at) AS available_at,
         FROM_UNIXTIME(created_at) AS created_at
  FROM jobs
  WHERE id IN (3, 4)
  ORDER BY id;

  SELECT id, queue AS backed_up_queue, backed_up_at
  FROM jobs_quarantine_backup_2026_11_04
  WHERE id IN (3, 4)
  ORDER BY id;
END$$

DELIMITER ;

-- Execute (owner-approved only):
CALL funnity_quarantine_stale_jobs_2026_10_27();

-- Optional: DROP PROCEDURE IF EXISTS funnity_quarantine_stale_jobs_2026_10_27;
-- Keep table jobs_quarantine_backup_2026_11_04 for recovery.

-- =============================================================================
-- RESTORATION PROCEDURE (DOCUMENTATION ONLY — DO NOT EXECUTE)
-- Restores queue from backup. Does not run the job logic.
-- WARNING: Restoring to `default` while a worker runs may EXECUTE job #4
-- (FriendEventNotification DB+FCM to eligible friends). Prefer leaving on hold
-- or deleting hold rows under a separate approval — not restoring.
-- =============================================================================
--
-- START TRANSACTION;
-- SELECT j.id, j.queue AS current_queue, b.queue AS backup_queue
-- FROM jobs j
-- JOIN jobs_quarantine_backup_2026_11_04 b ON b.id = j.id
-- WHERE j.id IN (3, 4)
-- FOR UPDATE;
-- UPDATE jobs j
-- JOIN jobs_quarantine_backup_2026_11_04 b ON b.id = j.id
-- SET j.queue = b.queue
-- WHERE j.id IN (3, 4)
--   AND j.queue = 'hold_stale_2026_10_27'
--   AND b.queue = 'default';
-- -- Require ROW_COUNT()=2 then COMMIT; else ROLLBACK.
