<template>
  <div class="roles-permissions-container">
    <!-- Main Title & Subtitle Header -->
    <div class="header-section text-center mb-4">
      <h1 class="main-title">HBOS Retail Management – Role Based Permissions Setup</h1>
      <p class="main-subtitle">
        <span class="role-pill-admin">Business Owner (Full Control)</span>
        <span class="divider">|</span>
        <span class="role-pill-manager">Branch Manager (Operational Access)</span>
        <span class="divider">|</span>
        <span class="role-pill-cashier">Salesperson (POS &amp; Counter)</span>
      </p>
    </div>

    <!-- Main Role Permissions Matrix Card -->
    <div class="matrix-card shadow-sm mb-5">
      <div class="table-responsive">
        <table class="matrix-table">
          <thead>
            <tr>
              <!-- MODULES Header -->
              <th class="col-modules">
                <div class="header-content dark-header">
                  <span>MODULES</span>
                </div>
              </th>

              <!-- BUSINESS OWNER Header -->
              <th class="col-admin">
                <div class="header-content admin-header">
                  <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                    <i class="bi bi-shield-shaded header-icon"></i>
                    <span class="header-title">BUSINESS OWNER</span>
                  </div>
                  <div class="header-sub">Full Business Control</div>
                </div>
              </th>

              <!-- BRANCH MANAGER Header -->
              <th class="col-manager">
                <div class="header-content manager-header">
                  <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                    <i class="bi bi-person-badge-fill header-icon"></i>
                    <span class="header-title">BRANCH MANAGER</span>
                  </div>
                  <div class="header-sub">Branch Operational Access</div>
                </div>
              </th>

              <!-- SALESPERSON Header -->
              <th class="col-cashier">
                <div class="header-content cashier-header">
                  <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                    <i class="bi bi-person-fill header-icon"></i>
                    <span class="header-title">SALESPERSON</span>
                  </div>
                  <div class="header-sub">POS &amp; Counter Sales</div>
                </div>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in permissionMatrix" :key="row.module" :class="{ 'row-alt': idx % 2 === 1 }">
              <!-- Module Name & Icon -->
              <td class="module-cell">
                <div class="d-flex align-items-center gap-3">
                  <i :class="['bi', row.icon, 'module-icon']"></i>
                  <span class="module-name">{{ row.module }}</span>
                </div>
              </td>

              <!-- Business Owner Cell -->
              <td class="perm-cell">
                <div class="d-flex align-items-start gap-2">
                  <span class="status-icon icon-success">
                    <i class="bi bi-check-circle-fill"></i>
                  </span>
                  <div>
                    <div class="perm-main text-dark fw-bold">{{ row.owner.text }}</div>
                  </div>
                </div>
              </td>

              <!-- Manager Cell -->
              <td class="perm-cell">
                <div class="d-flex align-items-start gap-2">
                  <span :class="['status-icon', getStatusIconClass(row.manager.status)]">
                    <i :class="getStatusIconName(row.manager.status)"></i>
                  </span>
                  <div>
                    <div class="perm-main text-dark fw-bold">{{ row.manager.text }}</div>
                    <div 
                      v-if="row.manager.subtext" 
                      :class="['perm-sub', row.manager.subtextType === 'danger' ? 'text-danger' : 'text-muted']"
                    >
                      {{ row.manager.subtext }}
                    </div>
                  </div>
                </div>
              </td>

              <!-- Salesperson Cell -->
              <td class="perm-cell">
                <div class="d-flex align-items-start gap-2">
                  <span :class="['status-icon', getStatusIconClass(row.salesperson.status)]">
                    <i :class="getStatusIconName(row.salesperson.status)"></i>
                  </span>
                  <div>
                    <div class="perm-main text-dark fw-bold">{{ row.salesperson.text }}</div>
                    <div 
                      v-if="row.salesperson.subtext" 
                      :class="['perm-sub', row.salesperson.subtextType === 'danger' ? 'text-danger' : 'text-muted']"
                    >
                      {{ row.salesperson.subtext }}
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ACCESS LEVEL GUIDE Section -->
    <div class="guide-section mb-4">
      <div class="guide-header-title text-center mb-4">
        <span>ACCESS LEVEL GUIDE</span>
      </div>

      <div class="row g-4">
        <!-- Business Owner Card -->
        <div class="col-lg-4 col-md-12">
          <div class="guide-card card-admin h-100">
            <div class="guide-card-header d-flex align-items-center gap-2 mb-3">
              <span class="guide-role-badge admin-badge">
                <i class="bi bi-shield-shaded"></i>
              </span>
              <h3 class="guide-card-title text-admin mb-0">BUSINESS OWNER – Full Governance</h3>
            </div>
            <ul class="guide-list">
              <li>Complete authority over business settings, users, and branches</li>
              <li>Manage staff accounts, assign roles, and configure system rules</li>
              <li>View all financial accounts, full audit logs, and business reports</li>
              <li>Protected by Last Owner safety guarantees</li>
            </ul>
          </div>
        </div>

        <!-- Branch Manager Card -->
        <div class="col-lg-4 col-md-12">
          <div class="guide-card card-manager h-100">
            <div class="guide-card-header d-flex align-items-center gap-2 mb-3">
              <span class="guide-role-badge manager-badge">
                <i class="bi bi-person-badge-fill"></i>
              </span>
              <h3 class="guide-card-title text-manager mb-0">BRANCH MANAGER – Branch Operations</h3>
            </div>
            <ul class="guide-list">
              <li>Handle daily operations for the assigned branch</li>
              <li>Sales, returns, customer khata, and branch inventory access</li>
              <li>Branch-scoped expenses and assigned drawer settlements</li>
              <li>Cannot modify business settings, user roles, or view central security logs</li>
            </ul>
          </div>
        </div>

        <!-- Salesperson Card -->
        <div class="col-lg-4 col-md-12">
          <div class="guide-card card-cashier h-100">
            <div class="guide-card-header d-flex align-items-center gap-2 mb-3">
              <span class="guide-role-badge cashier-badge">
                <i class="bi bi-person-fill"></i>
              </span>
              <h3 class="guide-card-title text-cashier mb-0">SALESPERSON – POS &amp; Counter</h3>
            </div>
            <ul class="guide-list">
              <li>Perform counter sales and POS barcode scanning</li>
              <li>View customer khata and check branch stock availability</li>
              <li>No access to business settings, audit center, or user management</li>
              <li>Focused strictly on counter transactions and customer receipts</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Security Information Callout Box -->
    <div class="security-banner p-3 p-md-4 rounded-4 d-flex align-items-center justify-content-center gap-3">
      <div class="security-lock-icon">
        <i class="bi bi-lock-fill"></i>
      </div>
      <div class="security-text">
        HBOS role-based governance enforces business boundaries, branch-isolated operations, and immutable audit logs.
        Business Owner governs policy, Branch Manager directs local operations, and Salesperson handles counter transactions.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const permissionMatrix = ref([
  {
    module: 'Dashboard',
    icon: 'bi-grid-fill',
    owner: { status: 'success', text: 'Full Access' },
    manager: { status: 'success', text: 'Full Access' },
    salesperson: { status: 'success', text: 'View Only' }
  },
  {
    module: 'Sales & POS',
    icon: 'bi-cart-fill',
    owner: { status: 'success', text: 'Full Access' },
    manager: { status: 'success', text: 'View, Create, Edit, Returns', subtext: 'Delete Disabled', subtextType: 'danger' },
    salesperson: { status: 'success', text: 'Create Transactions (POS)', subtext: 'Edit/Delete Disabled', subtextType: 'danger' }
  },
  {
    module: 'Returns',
    icon: 'bi-arrow-counterclockwise',
    owner: { status: 'success', text: 'Full Access' },
    manager: { status: 'success', text: 'Process Returns' },
    salesperson: { status: 'success', text: 'Process Returns (POS Only)' }
  },
  {
    module: 'Customers',
    icon: 'bi-people-fill',
    owner: { status: 'success', text: 'Full Access' },
    manager: { status: 'success', text: 'Full Access', subtext: '(View, Add, Edit, Delete)', subtextType: 'muted' },
    salesperson: { status: 'success', text: 'View Only' }
  },
  {
    module: 'Inventory',
    icon: 'bi-box-seam-fill',
    owner: { status: 'success', text: 'Full Access' },
    manager: { status: 'success', text: 'View & Edit Products', subtext: 'Receive Shipments Disabled', subtextType: 'danger' },
    salesperson: { status: 'success', text: 'View Stock Levels Only' }
  },
  {
    module: 'Purchases',
    icon: 'bi-bag-fill',
    owner: { status: 'success', text: 'Full Access' },
    manager: { status: 'warning', text: 'View Purchases', subtext: 'Create Disabled', subtextType: 'danger' },
    salesperson: { status: 'danger', text: 'No Access' }
  },
  {
    module: 'Suppliers',
    icon: 'bi-truck',
    owner: { status: 'success', text: 'Full Access' },
    manager: { status: 'success', text: 'View Suppliers' },
    salesperson: { status: 'danger', text: 'No Access' }
  },
  {
    module: 'Expenses',
    icon: 'bi-wallet2',
    owner: { status: 'success', text: 'Full Access' },
    manager: { status: 'warning', text: 'View Expenses', subtext: 'Create/Edit Disabled', subtextType: 'danger' },
    salesperson: { status: 'danger', text: 'No Access' }
  },
  {
    module: 'Reports',
    icon: 'bi-bar-chart-fill',
    owner: { status: 'success', text: 'Full Access' },
    manager: { status: 'success', text: 'View Reports' },
    salesperson: { status: 'success', text: 'View Sales Reports Only' }
  },
  {
    module: 'Users & Roles',
    icon: 'bi-person-badge-fill',
    owner: { status: 'success', text: 'Full Access' },
    manager: { status: 'danger', text: 'No Access' },
    salesperson: { status: 'danger', text: 'No Access' }
  }
]);

const getStatusIconClass = (status) => {
  if (status === 'success') return 'icon-success';
  if (status === 'warning') return 'icon-warning';
  if (status === 'danger') return 'icon-danger';
  return '';
};

const getStatusIconName = (status) => {
  if (status === 'success') return 'bi bi-check-circle-fill';
  if (status === 'warning') return 'bi bi-circle';
  if (status === 'danger') return 'bi bi-x-circle-fill';
  return 'bi bi-dash';
};
</script>

<style scoped>
.roles-permissions-container {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: #1e293b;
  max-width: 1320px;
  margin: 0 auto;
  padding: 10px 15px 40px;
}

/* Header */
.main-title {
  font-size: 1.85rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.5px;
  margin-bottom: 8px;
}

.main-subtitle {
  font-size: 0.95rem;
  font-weight: 600;
  color: #475569;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
}

.divider {
  color: #94a3b8;
  font-weight: 400;
}

.role-pill-admin { color: #1d4ed8; }
.role-pill-manager { color: #059669; }
.role-pill-cashier { color: #d97706; }

/* Matrix Table Card */
.matrix-card {
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
}

.matrix-table {
  width: 100%;
  border-collapse: collapse;
}

.matrix-table thead th {
  padding: 0;
  border: none;
  vertical-align: stretch;
}

.header-content {
  padding: 18px 16px;
  text-align: center;
  color: #ffffff;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.dark-header {
  background-color: #0f172a;
  font-size: 0.95rem;
  font-weight: 800;
  letter-spacing: 0.5px;
}

.admin-header {
  background-color: #1e40af; /* Rich Blue */
}

.manager-header {
  background-color: #059669; /* Emerald Green */
}

.cashier-header {
  background-color: #d97706; /* Warm Amber / Orange */
}

.header-title {
  font-size: 1.05rem;
  font-weight: 800;
  letter-spacing: 0.5px;
}

.header-icon {
  font-size: 1.15rem;
}

.header-sub {
  font-size: 0.8rem;
  font-weight: 500;
  opacity: 0.92;
}

/* Columns */
.col-modules { width: 22%; }
.col-admin { width: 26%; }
.col-manager { width: 26%; }
.col-cashier { width: 26%; }

/* Table Body Rows */
.matrix-table tbody tr {
  border-bottom: 1px solid #edf2f7;
  transition: background-color 0.15s ease;
}

.matrix-table tbody tr:hover {
  background-color: #f8fafc;
}

.matrix-table tbody tr:last-child {
  border-bottom: none;
}

.module-cell {
  padding: 14px 20px;
  background-color: #ffffff;
  border-right: 1px solid #edf2f7;
}

.module-icon {
  font-size: 1.2rem;
  color: #475569;
  width: 24px;
  text-align: center;
}

.module-name {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
}

.perm-cell {
  padding: 13px 20px;
  border-right: 1px solid #edf2f7;
  vertical-align: middle;
}

.perm-cell:last-child {
  border-right: none;
}

.perm-main {
  font-size: 0.9rem;
  line-height: 1.35;
}

.perm-sub {
  font-size: 0.78rem;
  margin-top: 2px;
  font-weight: 600;
}

/* Status Icons */
.status-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  line-height: 1;
  flex-shrink: 0;
  margin-top: 1px;
}

.icon-success {
  color: #16a34a;
}

.icon-warning {
  color: #ea580c;
  font-size: 1rem;
  border: 2px solid #ea580c;
  border-radius: 50%;
  width: 15px;
  height: 15px;
  margin-top: 3px;
}

.icon-danger {
  color: #dc2626;
}

/* Access Level Guide */
.guide-header-title {
  font-size: 0.85rem;
  font-weight: 800;
  letter-spacing: 1px;
  color: #334155;
  text-transform: uppercase;
  position: relative;
}

.guide-header-title::before,
.guide-header-title::after {
  content: "";
  display: inline-block;
  width: 60px;
  height: 1px;
  background: #cbd5e1;
  vertical-align: middle;
  margin: 0 14px;
}

.guide-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 24px 22px;
  border: 1.5px solid #e2e8f0;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.guide-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
}

.card-admin { border-color: #bfdbfe; background: linear-gradient(180deg, #f8faff 0%, #ffffff 100%); }
.card-manager { border-color: #bbf7d0; background: linear-gradient(180deg, #f8fdf9 0%, #ffffff 100%); }
.card-cashier { border-color: #fed7aa; background: linear-gradient(180deg, #fffaf5 0%, #ffffff 100%); }

.guide-role-badge {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  color: #ffffff;
}

.admin-badge { background-color: #1e40af; }
.manager-badge { background-color: #059669; }
.cashier-badge { background-color: #d97706; }

.text-admin { color: #1e40af; }
.text-manager { color: #059669; }
.text-cashier { color: #d97706; }

.guide-card-title {
  font-size: 1rem;
  font-weight: 800;
  letter-spacing: -0.2px;
}

.guide-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.guide-list li {
  position: relative;
  padding-left: 18px;
  margin-bottom: 10px;
  font-size: 0.88rem;
  color: #334155;
  line-height: 1.45;
}

.guide-list li::before {
  content: "•";
  position: absolute;
  left: 4px;
  top: -1px;
  font-size: 1.1rem;
  font-weight: bold;
  color: #64748b;
}

/* Security Callout Banner */
.security-banner {
  background: #f5f3ff;
  border: 1.5px solid #ddd6fe;
}

.security-lock-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #ede9fe;
  color: #7c3aed;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.security-text {
  font-size: 0.9rem;
  color: #4c1d95;
  font-weight: 500;
  line-height: 1.5;
}

@media (max-width: 992px) {
  .matrix-table {
    min-width: 780px;
  }
}
</style>
