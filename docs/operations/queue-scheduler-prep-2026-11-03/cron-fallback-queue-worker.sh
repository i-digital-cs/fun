#!/bin/bash
# Funnity — cron-managed queue worker (INACTIVE until owner activates)
# Hardened: 2026-11-04
#
# Approved cron (exactly one line; only after quarantine verified):
#   * * * * * /bin/bash /home/d14098/funnity.com/docs/operations/queue-scheduler-prep-2026-11-03/cron-fallback-queue-worker.sh >> /dev/null 2>&1
#
# Safety properties:
# - Exclusive flock acquired BEFORE any worker-log I/O or rotation
# - Lock FD held for the entire child `queue:work` lifetime (no overlapping workers)
# - Consumes connection `database`, queue `default` only (ignores hold_*)
# - Does NOT run schedule:run
# - Does NOT kill in-flight jobs to fit the cron minute
# - --max-time is a soft stop (no new jobs after budget); current job may finish first
# - --timeout=60 must stay < DB retry_after (90)
#
# Observability:
# - storage/logs/queue-cron.log      (holder only; under flock)
# - storage/logs/queue-cron-skip.log (non-holders; separate flock)

set -euo pipefail

# Cron often ships a minimal PATH; keep tools + alt-php wrappers resolvable.
export PATH="/usr/local/bin:/usr/bin:/bin"

PROJECT_DIR="/home/d14098/funnity.com"
PHP_BIN="/usr/local/bin/php"
ARTISAN="${PROJECT_DIR}/artisan"
LOCK_FILE="${PROJECT_DIR}/storage/logs/queue-cron.lock"
LOG_FILE="${PROJECT_DIR}/storage/logs/queue-cron.log"
SKIP_LOG="${PROJECT_DIR}/storage/logs/queue-cron-skip.log"
MAX_LOG_BYTES=5242880

# Job timeout (seconds) — must be < config queue.connections.database.retry_after (90)
JOB_TIMEOUT=60
# Soft worker lifetime budget. Not a hard wall-clock kill.
MAX_TIME=55
MEMORY_MB=128
TRIES=3

ts() { date -u +%Y-%m-%dT%H:%M:%SZ; }

log_skip() {
  # Best-effort skip log with its own short lock (never touches LOG_FILE).
  mkdir -p "${PROJECT_DIR}/storage/logs" 2>/dev/null || true
  (
    flock -n 8 || exit 0
    printf '%s %s\n' "$(ts)" "$*" >> "${SKIP_LOG}"
  ) 8>"${SKIP_LOG}.lock" 2>/dev/null || true
}

die_preflight() {
  log_skip "PREFLIGHT_FAIL: $*"
  # Also emit to stderr for interactive runs / cron MAILTO
  printf '%s PREFLIGHT_FAIL: %s\n' "$(ts)" "$*" >&2
  exit 1
}

# -----------------------------------------------------------------------------
# Preflight (before lock — fail closed if environment is wrong)
# -----------------------------------------------------------------------------
[[ -d "${PROJECT_DIR}" ]] || die_preflight "PROJECT_DIR missing: ${PROJECT_DIR}"
[[ -f "${ARTISAN}" ]] || die_preflight "artisan missing: ${ARTISAN}"
[[ -x "${PHP_BIN}" ]] || die_preflight "PHP_BIN not executable: ${PHP_BIN}"

# Reject cgi SAPI; require cli
PHP_SAPI="$("${PHP_BIN}" -r 'echo PHP_SAPI;' 2>/dev/null || true)"
[[ "${PHP_SAPI}" == "cli" ]] || die_preflight "PHP SAPI is '${PHP_SAPI:-unknown}', need cli (${PHP_BIN})"

# Required extensions — use extension_loaded() (reliable under cron; avoids php -m|grep flakiness)
for ext in pdo pdo_mysql mbstring openssl json tokenizer xml ctype fileinfo; do
  if ! "${PHP_BIN}" -r "exit(extension_loaded('${ext}') ? 0 : 1);" 2>/dev/null; then
    die_preflight "missing PHP extension: ${ext}"
  fi
done

mkdir -p "${PROJECT_DIR}/storage/logs" \
  || die_preflight "cannot create storage/logs"
[[ -w "${PROJECT_DIR}/storage/logs" ]] \
  || die_preflight "storage/logs not writable"

# -----------------------------------------------------------------------------
# Exclusive worker lock — BEFORE any worker-log touch/rotation
# -----------------------------------------------------------------------------
exec 9>"${LOCK_FILE}"
if ! flock -n 9; then
  log_skip "SKIP: another Funnity queue worker holds ${LOCK_FILE}"
  exit 0
fi

# From here until process exit, FD 9 holds the exclusive lock (including during
# the child queue:work process, which is foreground — flock remains held).

rotate_log_if_needed() {
  # Called only while holding flock → no concurrent writers to LOG_FILE.
  if [[ -f "${LOG_FILE}" ]]; then
    local bytes
    bytes="$(wc -c < "${LOG_FILE}" | tr -d ' ')"
    if [[ "${bytes:-0}" -gt "${MAX_LOG_BYTES}" ]]; then
      mv -f "${LOG_FILE}" "${LOG_FILE}.1"
    fi
  fi
}

rotate_log_if_needed

{
  echo "[$(ts)] Starting queue worker connection=database queue=default"
  echo "[$(ts)] php=$("${PHP_BIN}" -r 'echo PHP_VERSION;' 2>/dev/null) sapi=${PHP_SAPI}"
  echo "[$(ts)] flags: --stop-when-empty --max-time=${MAX_TIME} --timeout=${JOB_TIMEOUT} --tries=${TRIES} --memory=${MEMORY_MB}"
  echo "[$(ts)] note: --max-time is soft; in-flight job may finish after budget (no SIGKILL)"
} >> "${LOG_FILE}"

# timeout (60) < retry_after (90): documented invariant — do not raise JOB_TIMEOUT
# above 89 without also raising DB_QUEUE_RETRY_AFTER.
set +e
"${PHP_BIN}" "${ARTISAN}" queue:work database \
  --queue=default \
  --stop-when-empty \
  --sleep=3 \
  --tries="${TRIES}" \
  --timeout="${JOB_TIMEOUT}" \
  --max-time="${MAX_TIME}" \
  --memory="${MEMORY_MB}" \
  >> "${LOG_FILE}" 2>&1
EXIT_CODE=$?
set -e

{
  echo "[$(ts)] Worker exited code=${EXIT_CODE}"
  if [[ "${EXIT_CODE}" -ne 0 ]]; then
    echo "[$(ts)] FAILURE: non-zero exit — inspect laravel.log / failed_jobs / this log"
  fi
} >> "${LOG_FILE}"

# Exit 0 on empty-queue success paths; preserve non-zero for visibility.
# Cron with >> /dev/null still leaves durable logs above.
exit "${EXIT_CODE}"
