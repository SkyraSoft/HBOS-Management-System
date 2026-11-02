# HBOS Phase 2 Audit Foundation Contract

## Material Activity Trail

- Captures `business_id`, `branch_id`, `causer_id`, event type, and attribute changes across 10 foundational events (`10 / 10 COMPLETE_EXISTING`).
- Historical causer attribution preserved (`IDENTITY_REFERENCE_PRESERVED`).
- `AuditLogController.php` (`GET /api/v1/audit-logs`) is `NO_CHANGE_PROTECTED`.
- Owner-facing Audit Viewer provided via new tab in `Settings/UsersView.vue` (`NEW_TAB_IN_EXISTING_SETTINGS_VIEW`).
