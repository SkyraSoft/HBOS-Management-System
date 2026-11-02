# HBOS Documentation Archive Manifest

**Program**: SkyraSoft HBOS Small Retail Transformation Program  
**Phase**: Phase 0 — Repository Cleanup & Baseline Normalization  
**Date Archived**: October 3, 2026  
**Status**: HISTORICAL ARCHIVE ONLY (Non-Authoritative Reference)

---

## 1. Overview

This manifest documents all historical audit reports, certification records, progress logs, and JSON gap registries archived during Phase 0 Prompt 2. These artifacts preserve the complete engineering and certification history of the 10-phase audit program while keeping active documentation clean and accessible.

---

## 2. Archive Summary Table

| Archive Category | Directory Location | File Count | Original Purpose | Current Authority? | Current Equivalent / Canonical Source |
|---|---|---|---|---|---|
| **10-Phase Certification** | `docs/archive/phase10-certification/` | 9 | Phase 1 to Phase 9 individual certification reports | No | [HBOS Final System Certification](../architecture/HBOS_FINAL_SYSTEM_CERTIFICATION.md) |
| **Functional Audits** | `docs/archive/functional-audit/` | 3 | Legacy page-level mapping and zero-omission master maps | No | [HBOS Reverse Data Lineage](../architecture/HBOS_REVERSE_DATA_LINEAGE.md) |
| **Phase 9 Remediation** | `docs/archive/phase9-remediation/` | 2 | Phase 9 bugfix blueprints and implementation reports | No | [HBOS Definitive Management System Operating Guide](../operations/HBOS_DEFINITIVE_MANAGEMENT_SYSTEM_OPERATING_GUIDE.md) |
| **Public Platform** | `docs/archive/public-platform/` | 1 | Historical public platform audit report | No | [HBOS Public Distribution Tenancy Architecture](../architecture/HBOS_PUBLIC_DISTRIBUTION_TENANCY_ARCHITECTURE_AUDIT.md) |
| **Retail Pre-Transformation**| `docs/archive/retail-pre-transformation/`| 1 | Retail Prompt 2 implementation log | No | [HBOS Retail Organization Architecture Audit](../architecture/HBOS_RETAIL_ORGANIZATION_ARCHITECTURE_AUDIT.md) |
| **Historical Progress Logs** | `docs/archive/progress/` | 12 | Phase 1 through 10 progress logs | No | [Phase 00 Repository Cleanup Progress](../current/phase-00-repository-cleanup-progress.md) |
| **Audit Evidence Registries** | `docs/archive/evidence/` | 121 | Historical JSON gap registers, test outputs, and discovery dumps | No | Live PHPUnit test suite & active Phase 0 registries |

---

## 3. Preservation & Integrity Guarantee

- All 28 historical markdown documents and 121 JSON evidence files were relocated with zero content alteration.
- Checksums (SHA256) were verified before and after relocation (`hash_before === hash_after`) in `scratch/phase0_moved_files_manifest.json`.
- Files in this archive are read-only historical records.
