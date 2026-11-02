# HBOS Phase 2 Main Store and Branch Contract

## Primary Store Identity & Protection

- Every Business registers with exactly 1 primary Main Store (`is_primary = true`).
- Status storage classification: `CURRENT_STATUS_COLUMN_ALREADY_SUPPORTS_INCOMPLETE`.
- `BranchController.php` update and destroy endpoints already enforce primary branch protection.
- Classification: `NO_CHANGE_REQUIRED_PHASE2`.
