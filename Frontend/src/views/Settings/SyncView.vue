<template>
  <div class="sync-view">
    <div class="mb-4">
      <h1 class="page-title fw-bolder mb-2" style="font-size: 2rem; color: #1e293b;">Offline & Sync Center</h1>
      <p class="text-muted" style="font-size: 1rem; color: #64748b; max-width: 800px;">Continue essential business operations during internet interruptions and automatically synchronize data when the connection returns.</p>
    </div>

    <!-- Top Grid -->
    <div class="row g-3 mb-4">
      <!-- Left Main Card -->
      <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4 h-100 p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="fw-bold mb-0 text-dark">Status</h5>
              <span class="badge rounded-pill" style="background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 0.75rem; padding: 6px 12px;">
                <i class="bi bi-wifi-off me-1"></i> Local Mode Active
              </span>
            </div>
            <div class="text-muted" style="font-size: 0.85rem;">Last Successful Sync:</div>
            <div class="fw-bold text-dark fs-5 mt-1">12 Aug 2026, 10:55 AM</div>
          </div>
          
          <div class="mt-4 pt-4 border-top">
            <div class="d-flex justify-content-between align-items-end">
              <div>
                <div class="text-muted mb-1" style="font-size: 0.85rem;">Pending Transactions</div>
                <div class="fw-bolder text-dark" style="font-size: 2.2rem; line-height: 1;">8</div>
              </div>
              <button class="btn btn-primary rounded-3 px-4 fw-semibold" style="background-color: #818cf8; border: none;">Sync Now</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Grid Cards -->
      <div class="col-md-8">
        <div class="row g-3 h-100">
          <div class="col-sm-6">
            <div class="card shadow-sm border-0 rounded-4 h-100 p-3">
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-clipboard-data text-secondary fs-5"></i>
                <span class="text-secondary fw-semibold" style="font-size: 0.9rem;">Pending</span>
              </div>
              <div class="fw-bolder text-dark" style="font-size: 2rem;">8</div>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="card shadow-sm border-0 rounded-4 h-100 p-3">
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-cloud-check text-primary fs-5"></i>
                <span class="text-secondary fw-semibold" style="font-size: 0.9rem;">Synchronized Today</span>
              </div>
              <div class="fw-bolder text-dark" style="font-size: 2rem;">248</div>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="card shadow-sm border-0 rounded-4 h-100 p-3">
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-exclamation-circle text-danger fs-5"></i>
                <span class="text-secondary fw-semibold" style="font-size: 0.9rem;">Failed</span>
              </div>
              <div class="fw-bolder text-dark" style="font-size: 2rem;">0</div>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="card shadow-sm border-0 rounded-4 h-100 p-3">
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-exclamation-triangle text-warning fs-5"></i>
                <span class="text-secondary fw-semibold" style="font-size: 0.9rem;">Conflicts</span>
              </div>
              <div class="fw-bolder text-dark" style="font-size: 2rem;">0</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Section -->
    <div class="row g-3">
      <!-- Transactions Table -->
      <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-4 h-100 p-4">
          <div class="mb-4">
            <h5 class="fw-bold text-dark mb-1">Pending Transactions</h5>
            <div class="d-flex justify-content-between text-muted" style="font-size: 0.85rem;">
              <span>Waiting for connection... 8 Transactions pending</span>
              <span class="fw-bold text-dark">0%</span>
            </div>
            <div class="progress mt-2" style="height: 6px; background-color: #f1f5f9;">
              <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0">
              <thead class="border-bottom">
                <tr class="text-muted" style="font-size: 0.85rem;">
                  <th class="fw-semibold pb-3">ID</th>
                  <th class="fw-semibold pb-3">Type</th>
                  <th class="fw-semibold pb-3">Amount</th>
                  <th class="fw-semibold pb-3">Time</th>
                  <th class="fw-semibold pb-3 text-end">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr class="border-bottom">
                  <td class="py-3 fw-semibold text-dark">OFF-1045</td>
                  <td class="py-3 text-secondary">Sale</td>
                  <td class="py-3 text-secondary">PKR 2,450</td>
                  <td class="py-3 text-secondary">10:45 AM</td>
                  <td class="py-3 text-end">
                    <span class="badge rounded-pill" style="background-color: #fef3c7; color: #b45309; font-weight: 500;">
                      <i class="bi bi-clock me-1"></i> Pending
                    </span>
                  </td>
                </tr>
                <tr>
                  <td class="py-3 fw-semibold text-dark">OFF-1046</td>
                  <td class="py-3 text-secondary">Sale</td>
                  <td class="py-3 text-secondary">PKR 1,120</td>
                  <td class="py-3 text-secondary">10:52 AM</td>
                  <td class="py-3 text-end">
                    <span class="badge rounded-pill" style="background-color: #fef3c7; color: #b45309; font-weight: 500;">
                      <i class="bi bi-clock me-1"></i> Pending
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Settings -->
      <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-4 h-100 p-4">
          <h5 class="fw-bold text-dark mb-4">Sync Settings</h5>
          
          <div class="d-flex justify-content-between align-items-center mb-4 pb-4 border-bottom">
            <div>
              <div class="fw-bold text-dark fs-6 mb-1">Automatic Synchronization</div>
              <div class="text-muted" style="font-size: 0.85rem; max-width: 200px;">Sync in background when connected</div>
            </div>
            <div class="form-check form-switch custom-switch">
              <input class="form-check-input" type="checkbox" role="switch" checked>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-bold text-dark fs-6 mb-1">Sync over Mobile Data</div>
              <div class="text-muted" style="font-size: 0.85rem; max-width: 200px;">Allow sync when Wi-Fi is unavailable</div>
            </div>
            <div class="form-check form-switch custom-switch">
              <input class="form-check-input" type="checkbox" role="switch">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.sync-view {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.custom-switch .form-check-input {
  width: 3rem;
  height: 1.5rem;
  margin-top: 0;
  cursor: pointer;
}
.custom-switch .form-check-input:checked {
  background-color: #0f172a;
  border-color: #0f172a;
}

.card {
  border: 1px solid rgba(0, 0, 0, 0.08) !important;
}
</style>
