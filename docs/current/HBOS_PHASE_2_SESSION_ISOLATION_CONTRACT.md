# HBOS Phase 2 Session Isolation Contract

## Same-Device Session Cleanup

- Logout purges user auth token, Pinia auth memory, and Axios auth headers.
- Tenant IndexedDB instance closed immediately (`closeTenantDatabase()`).
- Unsynced local sales remain safely preserved in user-scoped IndexedDB outbox (`ACTOR_ID_SOURCE_PROVEN`).
