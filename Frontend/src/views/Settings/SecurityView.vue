<template>
  <div class="security-view-container">
    <!-- Top Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <div class="header-icon-badge">
            <i class="bi bi-shield-check"></i>
          </div>
          <h1 class="page-title fw-bolder m-0">Security &amp; Audit Center</h1>
        </div>
        <p class="text-muted m-0" style="font-size: 0.95rem;">
          Real-time activity tracking, user session management, transaction audit trails, and security compliance.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2">
        <div class="system-status-pill">
          <span class="pulse-dot"></span>
          <span>Audit Engine: Active</span>
        </div>
        <button class="btn btn-outline-secondary btn-sm px-3 py-2 rounded-3 d-flex align-items-center gap-2 fw-semibold" @click="exportLogs">
          <i class="bi bi-download"></i> Export Log
        </button>
      </div>
    </div>

    <!-- Top KPI Grid -->
    <div class="row g-3 mb-4">
      <!-- 1. Account Security -->
      <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="kpi-card card border-0 shadow-sm rounded-4 p-3 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="kpi-label">System Security</span>
            <div class="kpi-icon-wrap bg-success-subtle text-success">
              <i class="bi bi-shield-fill-check"></i>
            </div>
          </div>
          <div class="kpi-value text-success">Protected</div>
          <div class="kpi-subtext text-muted">2FA &amp; Firewall Enforced</div>
        </div>
      </div>

      <!-- 2. Active Users -->
      <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="kpi-card card border-0 shadow-sm rounded-4 p-3 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="kpi-label">Active Users</span>
            <div class="kpi-icon-wrap bg-primary-subtle text-primary">
              <i class="bi bi-people-fill"></i>
            </div>
          </div>
          <div class="kpi-value text-dark">{{ activeUsersCount }}</div>
          <div class="kpi-subtext text-muted">2 Authenticated Sessions</div>
        </div>
      </div>

      <!-- 3. Tracked Events -->
      <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="kpi-card card border-0 shadow-sm rounded-4 p-3 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="kpi-label">Filtered Events</span>
            <div class="kpi-icon-wrap bg-info-subtle text-info">
              <i class="bi bi-clock-history"></i>
            </div>
          </div>
          <div class="kpi-value text-dark">{{ filteredAuditLogs.length }}</div>
          <div class="kpi-subtext text-muted">Matching Active Filters</div>
        </div>
      </div>

      <!-- 4. Total Audit Logs -->
      <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="kpi-card card border-0 shadow-sm rounded-4 p-3 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="kpi-label">Audit History</span>
            <div class="kpi-icon-wrap bg-secondary-subtle text-secondary">
              <i class="bi bi-list-check"></i>
            </div>
          </div>
          <div class="kpi-value text-dark">248</div>
          <div class="kpi-subtext text-muted">Total Events Logged</div>
        </div>
      </div>

      <!-- 5. Backup Status -->
      <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="kpi-card card border-0 shadow-sm rounded-4 p-3 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="kpi-label">Cloud Backup</span>
            <div class="kpi-icon-wrap bg-success-subtle text-success">
              <i class="bi bi-cloud-arrow-up-fill"></i>
            </div>
          </div>
          <div class="kpi-value text-dark">Up to Date</div>
          <div class="kpi-subtext text-success">Today, 10:30 AM</div>
        </div>
      </div>

      <!-- 6. Failed Access -->
      <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="kpi-card card border-0 shadow-sm rounded-4 p-3 h-100" :class="{ 'card-alert': failedEventsCount > 0 }">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="kpi-label">Failed Access</span>
            <div class="kpi-icon-wrap bg-danger-subtle text-danger">
              <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
          </div>
          <div class="kpi-value text-danger">{{ failedEventsCount }}</div>
          <div class="kpi-subtext text-danger-emphasis">Blocked by Policy</div>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row g-4">
      <!-- Left Column: Primary Audit Log -->
      <div class="col-xl-8 col-lg-7">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden h-100 main-log-card">
          <!-- Card Header with Filters & Search -->
          <div class="card-header bg-white border-bottom p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
              <div>
                <h5 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                  <i class="bi bi-journal-text text-primary"></i>
                  Primary Audit Log
                </h5>
                <p class="text-muted m-0 small">
                  Chronological records of logins, sales transactions, inventory alterations, and system events.
                </p>
              </div>

              <div class="badge-count-pill">
                {{ filteredAuditLogs.length }} Records Found
              </div>
            </div>

            <!-- Filter Controls Bar -->
            <div class="row g-2 align-items-center">
              <!-- Search Bar -->
              <div class="col-md-5 col-12">
                <div class="input-group input-group-sm search-group">
                  <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="bi bi-search"></i>
                  </span>
                  <input 
                    type="text" 
                    v-model="searchQuery" 
                    class="form-control bg-light border-start-0 ps-0" 
                    placeholder="Search by User, Action, or Record..."
                  />
                  <button v-if="searchQuery" class="btn btn-light border-start-0 text-muted" @click="searchQuery = ''">
                    <i class="bi bi-x"></i>
                  </button>
                </div>
              </div>

              <!-- Module Filter -->
              <div class="col-md-4 col-6">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light text-muted border-end-0">
                    <i class="bi bi-folder2-open"></i>
                  </span>
                  <select v-model="selectedModule" class="form-select bg-light border-start-0">
                    <option value="All">All Modules</option>
                    <option value="Auth & Security">Auth &amp; Security</option>
                    <option value="Sales & POS">Sales &amp; POS</option>
                    <option value="Inventory">Inventory</option>
                    <option value="Expenses">Expenses</option>
                    <option value="System Activities">System Activities</option>
                  </select>
                </div>
              </div>

              <!-- Status Filter -->
              <div class="col-md-3 col-6">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light text-muted border-end-0">
                    <i class="bi bi-funnel"></i>
                  </span>
                  <select v-model="selectedStatus" class="form-select bg-light border-start-0">
                    <option value="All">All Statuses</option>
                    <option value="Successful">Successful</option>
                    <option value="Failed">Failed</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Table Body -->
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 custom-audit-table">
                <thead>
                  <tr class="table-head-row">
                    <th class="py-3 ps-4">DATE &amp; TIME</th>
                    <th class="py-3">USER</th>
                    <th class="py-3">ACTION</th>
                    <th class="py-3">MODULE</th>
                    <th class="py-3">RECORD</th>
                    <th class="py-3 pe-4 text-center">STATUS</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="log in filteredAuditLogs" :key="log.id" class="audit-row">
                    <!-- Date & Time -->
                    <td class="py-3 ps-4 date-cell">
                      <div class="fw-bold text-dark">{{ log.date }}</div>
                      <div class="text-muted small"><i class="bi bi-clock me-1"></i>{{ log.time }}</div>
                    </td>

                    <!-- User -->
                    <td class="py-3 user-cell">
                      <div class="d-flex align-items-center gap-2">
                        <div class="user-avatar" :class="getUserAvatarClass(log.userRole)">
                          {{ getInitials(log.user) }}
                        </div>
                        <div>
                          <div class="fw-bold text-dark line-clamp-1">{{ log.user }}</div>
                          <span class="user-role-badge" :class="getRoleBadgeClass(log.userRole)">{{ log.userRole }}</span>
                        </div>
                      </div>
                    </td>

                    <!-- Action -->
                    <td class="py-3 action-cell">
                      <div class="fw-semibold text-dark">{{ log.action }}</div>
                    </td>

                    <!-- Module -->
                    <td class="py-3 module-cell">
                      <span class="module-badge">
                        <i :class="['bi', log.moduleIcon, 'me-1']"></i>
                        {{ log.module }}
                      </span>
                    </td>

                    <!-- Record -->
                    <td class="py-3 record-cell">
                      <span class="record-code font-monospace" :title="log.record">
                        {{ log.record }}
                      </span>
                    </td>

                    <!-- Status -->
                    <td class="py-3 pe-4 text-center status-cell">
                      <span 
                        class="status-pill"
                        :class="log.status === 'Successful' ? 'status-success' : 'status-danger'"
                      >
                        <i :class="log.status === 'Successful' ? 'bi bi-check-circle-fill' : 'bi bi-x-circle-fill'"></i>
                        <span>{{ log.status }}</span>
                      </span>
                    </td>
                  </tr>

                  <!-- Empty State -->
                  <tr v-if="filteredAuditLogs.length === 0">
                    <td colspan="6" class="text-center py-5">
                      <div class="empty-state-box">
                        <i class="bi bi-search text-muted" style="font-size: 2.2rem;"></i>
                        <h6 class="mt-2 fw-bold text-dark">No Audit Events Found</h6>
                        <p class="text-muted small mb-3">No log records matched your search query or active filter settings.</p>
                        <button class="btn btn-sm btn-outline-primary px-3 rounded-3" @click="resetFilters">
                          <i class="bi bi-arrow-counterclockwise me-1"></i> Reset All Filters
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Card Footer -->
          <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="text-muted small">
              Displaying <strong class="text-dark">{{ filteredAuditLogs.length }}</strong> of <strong class="text-dark">{{ auditLogs.length }}</strong> audit events
            </span>
            <button v-if="selectedModule !== 'All' || selectedStatus !== 'All' || searchQuery" class="btn btn-sm btn-link text-primary text-decoration-none p-0 fw-semibold" @click="resetFilters">
              Clear All Filters
            </button>
          </div>
        </div>
      </div>

      <!-- Right Column: Active Sessions & Health -->
      <div class="col-xl-4 col-lg-5 d-flex flex-column gap-4">
        <!-- Active Sessions Card -->
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden side-panel-card">
          <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
              <i class="bi bi-laptop text-primary"></i>
              Active Sessions
            </h6>
            <span class="badge bg-success-subtle text-success fw-bold px-2 py-1 rounded-pill" style="font-size: 0.75rem;">
              {{ activeUsersCount }} Live
            </span>
          </div>
          <div class="card-body p-3 d-flex flex-column gap-2.5">
            <!-- Session 1: Current -->
            <div class="session-card current-session">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center gap-2">
                  <div class="session-device-icon bg-primary text-white">
                    <i class="bi bi-apple"></i>
                  </div>
                  <div>
                    <div class="fw-bold text-dark" style="font-size: 0.9rem;">Ahmed Khan</div>
                    <span class="session-badge-current">Current Session</span>
                  </div>
                </div>
                <span class="badge bg-light text-muted border" style="font-size: 0.72rem;">Super Admin</span>
              </div>
              <div class="session-meta">
                <div><i class="bi bi-browser-chrome me-1"></i>MacBook Pro • Chrome Browser</div>
                <div><i class="bi bi-geo-alt me-1"></i>Karachi, PK (IP: 192.168.1.10)</div>
              </div>
            </div>

            <!-- Session 2: Cashier Terminal -->
            <div class="session-card">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center gap-2">
                  <div class="session-device-icon bg-light text-secondary border">
                    <i class="bi bi-windows"></i>
                  </div>
                  <div>
                    <div class="fw-bold text-dark" style="font-size: 0.9rem;">Sara Ali</div>
                    <span class="text-muted small">POS Terminal 01</span>
                  </div>
                </div>
                <button class="btn btn-sm btn-outline-danger btn-signout" @click="handleSignOutUser('Sara Ali')">
                  Sign Out
                </button>
              </div>
              <div class="session-meta">
                <div><i class="bi bi-browser-edge me-1"></i>Windows 11 • Edge Browser</div>
                <div><i class="bi bi-geo-alt me-1"></i>Lahore, PK (IP: 192.168.1.25)</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Security Health Card -->
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden side-panel-card">
          <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
              <i class="bi bi-shield-lock text-success"></i>
              Security Health Check
            </h6>
            <span class="badge bg-success-subtle text-success fw-bold px-2 py-1 rounded-pill" style="font-size: 0.75rem;">
              100% Score
            </span>
          </div>
          <div class="card-body p-3 d-flex flex-column gap-2.5">
            <!-- Item 1 -->
            <div class="health-item">
              <div class="health-icon-box text-success">
                <i class="bi bi-check-circle-fill"></i>
              </div>
              <div class="flex-grow-1">
                <div class="health-title">Multi-Factor Authentication (MFA)</div>
                <div class="health-desc">Enforced on all administrative accounts</div>
              </div>
              <span class="badge bg-success-subtle text-success" style="font-size: 0.72rem;">Active</span>
            </div>

            <!-- Item 2 -->
            <div class="health-item">
              <div class="health-icon-box text-success">
                <i class="bi bi-check-circle-fill"></i>
              </div>
              <div class="flex-grow-1">
                <div class="health-title">Role-Based Access Control</div>
                <div class="health-desc">Super Admin, Manager &amp; Cashier isolated</div>
              </div>
              <span class="badge bg-success-subtle text-success" style="font-size: 0.72rem;">Strict</span>
            </div>

            <!-- Item 3 -->
            <div class="health-item">
              <div class="health-icon-box text-success">
                <i class="bi bi-check-circle-fill"></i>
              </div>
              <div class="flex-grow-1">
                <div class="health-title">Verbose Audit Trail</div>
                <div class="health-desc">Capturing authentication, POS &amp; financial logs</div>
              </div>
              <span class="badge bg-success-subtle text-success" style="font-size: 0.72rem;">Enabled</span>
            </div>

            <!-- Item 4 -->
            <div class="health-item">
              <div class="health-icon-box text-success">
                <i class="bi bi-check-circle-fill"></i>
              </div>
              <div class="flex-grow-1">
                <div class="health-title">Encrypted Cloud Backup</div>
                <div class="health-desc">Automatic daily incremental database snapshots</div>
              </div>
              <span class="badge bg-success-subtle text-success" style="font-size: 0.72rem;">Synced</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useToast } from 'vue-toastification';

const toast = useToast();

const selectedModule = ref('All');
const selectedStatus = ref('All');
const searchQuery = ref('');
const activeUsersCount = ref(2);

const auditLogs = ref([
  {
    id: 1,
    date: 'Today',
    time: '01:42 PM',
    user: 'Ahmed Khan',
    userRole: 'Super Admin',
    action: 'Completed POS Sale',
    module: 'Sales & POS',
    moduleIcon: 'bi-cart-check-fill',
    record: 'INV-20260903-88',
    status: 'Successful'
  },
  {
    id: 2,
    date: 'Today',
    time: '01:15 PM',
    user: 'Sara Ali',
    userRole: 'Cashier',
    action: 'Failed Login Attempt',
    module: 'Auth & Security',
    moduleIcon: 'bi-shield-exclamation',
    record: 'IP: 192.168.1.45 (Invalid Password)',
    status: 'Failed'
  },
  {
    id: 3,
    date: 'Today',
    time: '12:50 PM',
    user: 'Bilal Tariq',
    userRole: 'Store Manager',
    action: 'Product Price / Stock Change',
    module: 'Inventory',
    moduleIcon: 'bi-box-seam-fill',
    record: 'PROD-016 (iPhone 16 Pro Max)',
    status: 'Successful'
  },
  {
    id: 4,
    date: 'Today',
    time: '11:30 AM',
    user: 'Zainab Fatima',
    userRole: 'Store Manager',
    action: 'Recorded Store Expense',
    module: 'Expenses',
    moduleIcon: 'bi-wallet2',
    record: 'EXP-1092 (Utility Bill - Electricity)',
    status: 'Successful'
  },
  {
    id: 5,
    date: 'Today',
    time: '10:30 AM',
    user: 'System Automated',
    userRole: 'System Cron',
    action: 'Database Cloud Backup',
    module: 'System Activities',
    moduleIcon: 'bi-cloud-arrow-up-fill',
    record: 'SYS-BKUP-20260903.sql.gz',
    status: 'Successful'
  },
  {
    id: 6,
    date: 'Yesterday',
    time: '05:40 PM',
    user: 'Ahmed Khan',
    userRole: 'Super Admin',
    action: 'User Role Privileges Modified',
    module: 'Auth & Security',
    moduleIcon: 'bi-person-gear',
    record: 'USR-CASHIER-02 (Sara Ali)',
    status: 'Successful'
  },
  {
    id: 7,
    date: 'Yesterday',
    time: '04:15 PM',
    user: 'Hamza Sheikh',
    userRole: 'Cashier',
    action: 'Processed POS Product Return',
    module: 'Sales & POS',
    moduleIcon: 'bi-arrow-counterclockwise',
    record: 'RET-8821 (Pepsi Bottle 1.5L)',
    status: 'Successful'
  },
  {
    id: 8,
    date: 'Yesterday',
    time: '02:10 PM',
    user: 'Unknown IP',
    userRole: 'External',
    action: 'Unauthorized API Access Attempt',
    module: 'Auth & Security',
    moduleIcon: 'bi-shield-slash-fill',
    record: 'IP: 103.255.4.19 (Rate Limited)',
    status: 'Failed'
  },
  {
    id: 9,
    date: 'Yesterday',
    time: '11:05 AM',
    user: 'Bilal Tariq',
    userRole: 'Store Manager',
    action: 'New Beverage Item Added',
    module: 'Inventory',
    moduleIcon: 'bi-plus-circle-fill',
    record: 'PROD-061 (Mineral Water Bottle 1.5L)',
    status: 'Successful'
  },
  {
    id: 10,
    date: '01 Sep 2026',
    time: '09:00 AM',
    user: 'System Automated',
    userRole: 'System',
    action: 'Daily Ledger Reconciliation',
    module: 'System Activities',
    moduleIcon: 'bi-journal-check',
    record: 'KHATA-RECON-SEP01',
    status: 'Successful'
  }
]);

const filteredAuditLogs = computed(() => {
  return auditLogs.value.filter(log => {
    const matchesModule = selectedModule.value === 'All' || log.module === selectedModule.value;
    const matchesStatus = selectedStatus.value === 'All' || log.status === selectedStatus.value;
    
    let matchesSearch = true;
    if (searchQuery.value && searchQuery.value.trim()) {
      const q = searchQuery.value.trim().toLowerCase();
      matchesSearch = (log.user && log.user.toLowerCase().includes(q)) ||
                      (log.action && log.action.toLowerCase().includes(q)) ||
                      (log.record && log.record.toLowerCase().includes(q)) ||
                      (log.module && log.module.toLowerCase().includes(q));
    }

    return matchesModule && matchesStatus && matchesSearch;
  });
});

const failedEventsCount = computed(() => {
  return auditLogs.value.filter(log => log.status === 'Failed').length;
});

const getInitials = (name) => {
  if (!name) return 'U';
  const parts = name.split(' ');
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
  return name.slice(0, 2).toUpperCase();
};

const getUserAvatarClass = (role) => {
  switch (role) {
    case 'Super Admin': return 'avatar-admin';
    case 'Store Manager': return 'avatar-manager';
    case 'Cashier': return 'avatar-cashier';
    default: return 'avatar-system';
  }
};

const getRoleBadgeClass = (role) => {
  switch (role) {
    case 'Super Admin': return 'badge-role-admin';
    case 'Store Manager': return 'badge-role-manager';
    case 'Cashier': return 'badge-role-cashier';
    default: return 'badge-role-system';
  }
};

const resetFilters = () => {
  selectedModule.value = 'All';
  selectedStatus.value = 'All';
  searchQuery.value = '';
};

const handleSignOutUser = (userName) => {
  toast.info(`Terminated active session for ${userName}.`);
};

const exportLogs = () => {
  toast.success('Audit Log exported successfully (CSV generated).');
};
</script>

<style scoped>
.security-view-container {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: #1e293b;
}

/* Header Icon */
.header-icon-badge {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.page-title {
  font-size: 1.85rem;
  color: #0f172a;
  letter-spacing: -0.5px;
}

.system-status-pill {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 0.82rem;
  font-weight: 700;
}

.pulse-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
  animation: pulse 1.8s infinite cubic-bezier(0.66, 0, 0, 1);
}

@keyframes pulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}

/* KPI Cards */
.kpi-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  background: #ffffff;
  border: 1px solid #e2e8f0 !important;
}

.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.08) !important;
}

.kpi-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.kpi-icon-wrap {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
}

.kpi-value {
  font-size: 1.35rem;
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 2px;
}

.kpi-subtext {
  font-size: 0.75rem;
  font-weight: 500;
}

.card-alert {
  border-color: #fecaca !important;
  background: linear-gradient(180deg, #fffafa 0%, #ffffff 100%);
}

/* Main Log Card */
.main-log-card, .side-panel-card {
  border: 1px solid #e2e8f0 !important;
  background: #ffffff;
}

.badge-count-pill {
  font-size: 0.78rem;
  font-weight: 700;
  background: #f1f5f9;
  color: #334155;
  padding: 4px 12px;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
}

.search-group {
  border-radius: 6px;
  overflow: hidden;
}

/* Custom Table */
.custom-audit-table {
  border-collapse: separate;
  border-spacing: 0;
}

.table-head-row th {
  background-color: #f8fafc;
  color: #475569;
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.6px;
  border-bottom: 1px solid #e2e8f0;
}

.audit-row {
  transition: background-color 0.15s ease;
}

.audit-row:hover {
  background-color: #f8fafc;
}

.date-cell {
  font-size: 0.85rem;
  white-space: nowrap;
}

.user-avatar {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.78rem;
  color: #ffffff;
  flex-shrink: 0;
}

.avatar-admin { background: linear-gradient(135deg, #1e40af, #3b82f6); }
.avatar-manager { background: linear-gradient(135deg, #059669, #10b981); }
.avatar-cashier { background: linear-gradient(135deg, #d97706, #f59e0b); }
.avatar-system { background: linear-gradient(135deg, #334155, #64748b); }

.user-role-badge {
  font-size: 0.68rem;
  font-weight: 700;
  display: inline-block;
  padding: 1px 6px;
  border-radius: 4px;
}

.badge-role-admin { background: #dbeafe; color: #1e40af; }
.badge-role-manager { background: #dcfce7; color: #166534; }
.badge-role-cashier { background: #fef3c7; color: #b45309; }
.badge-role-system { background: #f1f5f9; color: #475569; }

.action-cell {
  font-size: 0.88rem;
}

.module-badge {
  display: inline-flex;
  align-items: center;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #334155;
  font-size: 0.78rem;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 6px;
  white-space: nowrap;
}

.record-code {
  display: inline-block;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  color: #0f172a;
  padding: 2px 7px;
  border-radius: 4px;
  font-size: 0.78rem;
  font-weight: 600;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.75rem;
  font-weight: 800;
  padding: 4px 10px;
  border-radius: 20px;
  letter-spacing: 0.2px;
}

.status-success {
  background-color: #dcfce7;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.status-danger {
  background-color: #fee2e2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

/* Side Panels */
.session-card {
  padding: 12px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #ffffff;
  transition: all 0.2s ease;
}

.session-card:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.current-session {
  background: #f8fafc;
  border-color: #bfdbfe;
}

.session-device-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.session-badge-current {
  font-size: 0.7rem;
  font-weight: 700;
  color: #2563eb;
  background: #dbeafe;
  padding: 1px 6px;
  border-radius: 4px;
}

.session-meta {
  font-size: 0.76rem;
  color: #64748b;
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-top: 4px;
}

.btn-signout {
  font-size: 0.72rem;
  padding: 2px 8px;
  font-weight: 600;
}

/* Health Items */
.health-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 10px;
  border-radius: 8px;
  transition: background-color 0.15s ease;
}

.health-item:hover {
  background-color: #f8fafc;
}

.health-icon-box {
  font-size: 1.15rem;
  display: flex;
  align-items: center;
}

.health-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #1e293b;
}

.health-desc {
  font-size: 0.74rem;
  color: #64748b;
}

@media (max-width: 992px) {
  .custom-audit-table {
    min-width: 720px;
  }
}
</style>
