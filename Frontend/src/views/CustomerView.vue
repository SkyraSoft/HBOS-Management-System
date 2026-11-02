<template>
<div class="customer-view-container" style="width: 100%; height: 100%; overflow: auto; flex: 1; min-width: 0;">
<section v-if="!isModal && !selectedCustomerProfile" class="page">

                <!-- HEADER -->
                <div class="page-header">

                    <div>

                        <h1 class="page-title">
                            Customers & Khata Accounts
                        </h1>

                        <p class="page-description">
                            Manage client relationships, credit limits, and live Khata ledgers.
                        </p>

                    </div>

                    <div class="header-actions">

                        <button class="btn-custom" @click="openRecordPaymentModal">

                            <i class="bi bi-credit-card"></i>

                            Record Payment

                        </button>
                        <button class="btn-custom btn-primary-custom" @click="openAddModal">
                            <i class="bi bi-person-plus"></i>
                            Add Customer
                        </button>
                    </div>
                </div>

                <!-- =================================================
             STATISTICS
        ================================================== -->
                <div class="stats-grid">
                    <!-- TOTAL CUSTOMERS -->
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="stat-label">
                            Total<br />
                            Customers
                        </div>
                        <div class="stat-value">
                            {{ totalCustomersCount }}
                        </div>

                        <div class="stat-note success">
                            ↗ +{{ thisMonthNewCustomersCount }}
                            <span style="color:#555b64;">
                                this month
                            </span>
                        </div>

                    </div>

                    <!-- ACTIVE -->
                    <div class="stat-card active-card">

                        <div class="stat-icon">
                            <i class="bi bi-person-check"></i>
                        </div>

                        <div class="stat-label">
                            Active
                        </div>

                        <div class="stat-value">
                            {{ activeCustomersCount }}
                        </div>

                        <div class="stat-note">
                            {{ engagementRate }}% engagement rate
                        </div>

                    </div>

                    <!-- OUTSTANDING -->
                    <div class="stat-card outstanding-card">

                        <div class="stat-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>

                        <div class="stat-label">
                            Total<br />
                            Outstanding
                        </div>

                        <div class="stat-value currency">
                            PKR {{ totalOutstanding.toLocaleString() }}
                        </div>

                        <div class="stat-note">
                            Across {{ outstandingAccountsCount }} accounts
                        </div>

                    </div>

                    <!-- OVERDUE -->
                    <div class="stat-card overdue-card">

                        <div class="stat-icon">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>

                        <div class="stat-label">
                            Overdue
                        </div>

                        <div class="stat-value currency danger">
                            PKR {{ totalOverdue.toLocaleString() }}
                        </div>
                        <div class="stat-note danger">
                            Requires immediate<br />
                            attention
                        </div>

                    </div>

                </div>

                <!-- =================================================
             CUSTOMER TABLE
        ================================================== -->

                <div class="customer-panel">

                    <!-- TOOLBAR -->
                    <div class="table-toolbar">

                        <div class="customer-search">

                            <i class="bi bi-search"></i>

                            <input type="text" id="customerSearch" v-model="searchQuery"
                                placeholder="Search customer by name or phone number..." />

                        </div>

                        <select class="filter-select" id="customerType" v-model="filterType">

                            <option value="all">
                                Customer Type: All
                            </option>

                            <option value="regular">
                                Regular
                            </option>

                            <option value="wholesale">
                                Wholesale
                            </option>

                            <option value="business">
                                Business
                            </option>

                        </select>

                        <select class="filter-select" id="creditStatus" v-model="filterStatus">

                            <option value="all">
                                Credit Status: All
                            </option>

                            <option value="clear">
                                Clear
                            </option>

                            <option value="outstanding">
                                Outstanding
                            </option>

                            <option value="overdue">
                                Overdue
                            </option>

                        </select>

                        <div class="toolbar-spacer"></div>

                        <button class="icon-button" title="Filter">

                            <i class="bi bi-funnel"></i>

                        </button>

                        <button class="icon-button" title="Download" onclick="downloadCustomers()">

                            <i class="bi bi-download"></i>

                        </button>

                    </div>

                    <!-- TABLE -->
                    <div class="table-wrapper">

                        <table class="customers-table">

                            <thead>

                                <tr>

                                    <th>
                                        Customer
                                    </th>

                                    <th>
                                        Total<br />
                                        Purchases
                                    </th>

                                    <th>
                                        Outstanding
                                    </th>

                                    <th>
                                        Credit<br />
                                        Limit
                                    </th>

                                    <th>
                                        Last<br />
                                        Transaction
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Actions
                                    </th>

                                </tr>

                            </thead>

                            
                            <tbody id="customerTable">
                                <tr v-for="c in paginatedCustomers" :key="c.id" :data-type="c.type || 'regular'" :data-status="c.status || 'active'" style="cursor: pointer;" @click="openCustomerProfile(c)">
                                    <td>
                                        <div class="customer-info">
                                            <div class="customer-avatar">
                                                {{ c.name ? c.name.charAt(0).toUpperCase() : 'C' }}
                                            </div>
                                            <div>
                                                <div class="customer-name fw-bold text-primary">
                                                    {{ c.name }}
                                                </div>
                                                <div class="customer-phone font-monospace">
                                                    {{ c.phone }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="amount">
                                        PKR {{ getCustomerSales(c).toLocaleString() }}
                                    </td>
                                    <td :class="['amount', getCustomerOutstanding(c) > 0 ? 'danger fw-bold' : '']">
                                        PKR {{ getCustomerOutstanding(c).toLocaleString() }}
                                    </td>
                                    <td>
                                        PKR {{ getCustomerCreditLimit(c).toLocaleString() }}
                                    </td>
                                    <td>
                                        {{ c.created_at ? new Date(c.created_at).toLocaleDateString() : 'N/A' }}<br>
                                        {{ c.type || 'Regular' }}
                                    </td>
                                    <td>
                                        <span :class="['status', getCustomerOutstanding(c) > 0 ? 'outstanding' : (c.status || 'active')]">
                                            {{ getCustomerOutstanding(c) > 0 ? 'Outstanding' : ((c.status || 'active').charAt(0).toUpperCase() + (c.status || 'active').slice(1)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons" @click.stop>
                                            <button class="action-btn text-success" title="Record Payment" @click.stop="openRecordPaymentModal(c)">
                                                <i class="bi bi-wallet2"></i>
                                            </button>
                                            <button class="action-btn text-primary" title="View Customer Profile & Khata Ledger" @click.stop="openCustomerProfile(c)">
                                                <i class="bi bi-person-lines-fill"></i>
                                            </button>
                                            <button class="action-btn more" title="Edit Customer" @click.stop="openEditModal(c)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="action-btn more text-danger" title="Delete Customer" @click.stop="deleteCustomer(c.id)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="paginatedCustomers.length === 0">
                                    <td colspan="7" class="text-center py-4">No customers found.</td>
                                </tr>
                            </tbody>


                        </table>

                    </div>

                    <!-- FOOTER -->
                    
                    <div class="table-footer">
                        <div class="result-count" id="resultCount">
                            Showing {{ paginationStart }} to {{ paginationEnd }} of {{ filteredCustomers.length }} results
                        </div>
                        <div class="pagination-custom">
                            <button class="page-btn" @click="changePage(currentPage - 1)" :disabled="currentPage === 1">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            
                            <template v-if="visiblePages[0] > 1">
                                <button class="page-btn" @click="changePage(1)">1</button>
                                <button class="page-btn" disabled v-if="visiblePages[0] > 2">...</button>
                            </template>

                            <button class="page-btn" v-for="p in visiblePages" :key="p" :class="{active: currentPage === p}" @click="changePage(p)">
                                {{ p }}
                            </button>

                            <template v-if="visiblePages[visiblePages.length - 1] < totalPages">
                                <button class="page-btn" disabled v-if="visiblePages[visiblePages.length - 1] < totalPages - 1">...</button>
                                <button class="page-btn" @click="changePage(totalPages)">{{ totalPages }}</button>
                            </template>

                            <button class="page-btn" @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ====================================================
                 CUSTOMER DETAILED PROFILE & KHATA LEDGER VIEW
            ===================================================== -->
            <section v-else-if="!isModal && selectedCustomerProfile" class="page customer-profile-page p-4">
                <!-- TOP BREADCRUMB / BACK BAR -->
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <button class="btn btn-sm btn-light border rounded-3 fw-semibold px-3 py-1.5 shadow-sm d-flex align-items-center gap-2" @click="closeCustomerProfile">
                        <i class="bi bi-arrow-left"></i> Back to All Customers
                    </button>
                    <div class="text-end">
                        <span class="text-muted small font-monospace">Customer Account #{{ selectedCustomerProfile.id }}</span>
                    </div>
                </div>

                <!-- CUSTOMER PROFILE HEADER CARD -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="customer-profile-avatar" style="width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, #0757e5, #0046c7); color: #fff; font-size: 26px; font-weight: bold; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(7, 87, 229, 0.2);">
                                    {{ selectedCustomerProfile.name ? selectedCustomerProfile.name.charAt(0).toUpperCase() : 'C' }}
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                        <h2 class="fw-bold text-dark m-0 fs-4">{{ selectedCustomerProfile.name }}</h2>
                                        <span :class="['badge rounded-pill px-2.5 py-1 text-uppercase font-monospace', getCustomerOutstanding(selectedCustomerProfile) > 0 ? 'bg-danger text-white' : 'bg-success text-white']" style="font-size: 11px;">
                                            {{ getCustomerOutstanding(selectedCustomerProfile) > 0 ? 'Outstanding Loan' : 'Settled / Clear' }}
                                        </span>
                                        <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 11px;">
                                            {{ selectedCustomerProfile.type || 'Regular Customer' }}
                                        </span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3 text-muted small">
                                        <span v-if="selectedCustomerProfile.phone"><i class="bi bi-telephone me-1 text-primary"></i><a :href="'tel:' + selectedCustomerProfile.phone" class="text-decoration-none text-muted font-monospace">{{ selectedCustomerProfile.phone }}</a></span>
                                        <span v-if="selectedCustomerProfile.address"><i class="bi bi-geo-alt me-1 text-primary"></i>{{ selectedCustomerProfile.address }}</span>
                                        <span v-if="selectedCustomerProfile.email"><i class="bi bi-envelope me-1 text-primary"></i>{{ selectedCustomerProfile.email }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- QUICK ACTIONS BAR -->
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-success btn-sm rounded-3 fw-semibold px-3 py-2 shadow-sm d-flex align-items-center gap-1" @click="openRecordPaymentModal(selectedCustomerProfile)">
                                    <i class="bi bi-wallet2"></i> Record Payment
                                </button>
                                <button class="btn btn-primary btn-sm rounded-3 fw-semibold px-3 py-2 shadow-sm d-flex align-items-center gap-1" @click="router.push('/pos')">
                                    <i class="bi bi-cart-plus"></i> New Sale
                                </button>
                                <button class="btn btn-outline-success btn-sm rounded-3 fw-semibold px-3 py-2 d-flex align-items-center gap-1" @click="shareCustomerStatementWhatsApp">
                                    <i class="bi bi-whatsapp"></i> WhatsApp
                                </button>
                                <button class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold px-3 py-2 d-flex align-items-center gap-1" @click="printCustomerLedger">
                                    <i class="bi bi-printer"></i> Print Statement
                                </button>
                                <button class="btn btn-outline-primary btn-sm rounded-3 fw-semibold px-2.5 py-2" title="Edit Customer" @click="openEditModal(selectedCustomerProfile)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4 FINANCIAL KPI CARDS -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                            <span class="text-muted small fw-semibold text-uppercase">Total Purchases</span>
                            <div class="fs-4 fw-bold text-dark mt-1">PKR {{ getCustomerSales(selectedCustomerProfile).toLocaleString() }}</div>
                            <span class="text-muted small mt-auto pt-1 font-monospace" style="font-size: 11px;">Cumulative sales</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100" style="border-left: 4px solid #d32222 !important;">
                            <span class="text-danger small fw-bold text-uppercase">Khata Balance (Loan Owed)</span>
                            <div class="fs-4 fw-bold text-danger mt-1">PKR {{ getCustomerOutstanding(selectedCustomerProfile).toLocaleString() }}</div>
                            <span class="text-muted small mt-auto pt-1 font-monospace" style="font-size: 11px;">Current due debt</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100" style="border-left: 4px solid #079b68 !important;">
                            <span class="text-success small fw-bold text-uppercase">Total Payments Settled</span>
                            <div class="fs-4 fw-bold text-success mt-1">PKR {{ profileTotalPayments.toLocaleString() }}</div>
                            <span class="text-muted small mt-auto pt-1 font-monospace" style="font-size: 11px;">Recovered payments</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                            <span class="text-muted small fw-semibold text-uppercase">Credit Limit</span>
                            <div class="fs-4 fw-bold text-dark mt-1">PKR {{ getCustomerCreditLimit(selectedCustomerProfile).toLocaleString() }}</div>
                            <span class="text-muted small mt-auto pt-1 font-monospace" style="font-size: 11px;">Max loan allowance</span>
                        </div>
                    </div>
                </div>

                <!-- CHRONOLOGICAL KHATA LEDGER & PAYMENT REVERSALS TABLE -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                    <div class="card-header bg-white border-0 px-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h3 class="fw-bold text-dark fs-5 m-0 d-flex align-items-center gap-2">
                                <i class="bi bi-journal-text text-primary"></i>
                                Khata Ledger & Payment History
                            </h3>
                            <p class="text-muted small m-0 mt-1">Every credit purchase (loan taken), payment received, and reversal chronologically audited.</p>
                        </div>
                        <!-- FILTER PILLS -->
                        <div class="d-flex gap-1 bg-light p-1 rounded-3 border">
                            <button :class="['btn btn-sm rounded-2 px-3 fw-semibold', ledgerFilter === 'all' ? 'btn-primary shadow-sm' : 'btn-light text-secondary border-0']" @click="ledgerFilter = 'all'">All</button>
                            <button :class="['btn btn-sm rounded-2 px-3 fw-semibold', ledgerFilter === 'sales' ? 'btn-primary shadow-sm' : 'btn-light text-secondary border-0']" @click="ledgerFilter = 'sales'">Credit Sales</button>
                            <button :class="['btn btn-sm rounded-2 px-3 fw-semibold', ledgerFilter === 'payments' ? 'btn-primary shadow-sm' : 'btn-light text-secondary border-0']" @click="ledgerFilter = 'payments'">Payments</button>
                            <button :class="['btn btn-sm rounded-2 px-3 fw-semibold', ledgerFilter === 'voided' ? 'btn-primary shadow-sm' : 'btn-light text-secondary border-0']" @click="ledgerFilter = 'voided'">Reversals</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0" style="font-size: 13.5px;">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="ps-4" style="width: 120px;">Date</th>
                                    <th style="width: 150px;">Transaction Type</th>
                                    <th style="width: 140px;">Reference #</th>
                                    <th>Details / Description</th>
                                    <th class="text-end" style="width: 130px;">Debit (Took Loan)</th>
                                    <th class="text-end" style="width: 130px;">Credit (Paid Back)</th>
                                    <th class="text-end" style="width: 140px;">Running Balance</th>
                                    <th class="text-end pe-4" style="width: 110px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="isLoadingLedger">
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <span class="spinner-border spinner-border-sm me-2"></span> Loading customer Khata ledger...
                                    </td>
                                </tr>
                                <tr v-else-if="filteredLedger.length === 0">
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary opacity-50"></i>
                                        No transactions found for this customer account.
                                    </td>
                                </tr>
                                <tr v-for="entry in filteredLedger" :key="entry.id">
                                    <td class="ps-4 font-monospace text-secondary">{{ entry.date || (entry.created_at ? entry.created_at.split('T')[0] : '—') }}</td>
                                    <td>
                                        <span v-if="entry.type === 'Credit Sale'" class="badge bg-danger-subtle text-danger px-2 py-1 rounded-pill fw-semibold">
                                            <i class="bi bi-cart-dash me-1"></i> Credit Sale
                                        </span>
                                        <span v-else-if="entry.type === 'Payment Received'" class="badge bg-success-subtle text-success px-2 py-1 rounded-pill fw-semibold">
                                            <i class="bi bi-check-circle me-1"></i> Payment
                                        </span>
                                        <span v-else-if="entry.type === 'Payment Voided' || entry.status === 'voided'" class="badge bg-dark-subtle text-dark px-2 py-1 rounded-pill fw-semibold">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Voided
                                        </span>
                                        <span v-else class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill fw-semibold">
                                            {{ entry.type || 'Transaction' }}
                                        </span>
                                    </td>
                                    <td>
                                        <code class="fw-semibold text-primary">{{ entry.reference || '—' }}</code>
                                    </td>
                                    <td class="text-dark">
                                        <div>{{ entry.notes || '—' }}</div>
                                        <span v-if="entry.payment_method" class="badge bg-light text-secondary border font-monospace" style="font-size: 10px;">{{ entry.payment_method }}</span>
                                    </td>
                                    <td class="text-end fw-bold" :class="Number(entry.debit) > 0 ? 'text-danger' : 'text-muted'">
                                        {{ Number(entry.debit) > 0 ? ('PKR ' + Number(entry.debit).toLocaleString()) : '—' }}
                                    </td>
                                    <td class="text-end fw-bold" :class="Number(entry.credit) > 0 ? 'text-success' : 'text-muted'">
                                        {{ Number(entry.credit) > 0 ? ('PKR ' + Number(entry.credit).toLocaleString()) : '—' }}
                                    </td>
                                    <td class="text-end fw-bold" :class="Number(entry.running_balance) > 0 ? 'text-danger' : 'text-success'">
                                        PKR {{ Number(entry.running_balance || 0).toLocaleString() }}
                                    </td>
                                    <td class="text-end pe-4">
                                        <button 
                                            v-if="entry.is_payment && entry.status === 'posted'"
                                            class="btn btn-sm btn-outline-danger px-2.5 py-1 rounded-2 fw-semibold"
                                            style="font-size: 11px;"
                                            @click="openReverseModal(entry)"
                                            title="Reverse this payment entry"
                                        >
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reverse
                                        </button>
                                        <span v-else-if="entry.status === 'voided' || entry.type === 'Payment Voided'" class="badge bg-danger-subtle text-danger" style="font-size: 10px;">
                                            Reversed
                                        </span>
                                        <span v-else class="text-muted small">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

<!-- MODALS -->
    
    
    <!-- ADD CUSTOMER MODAL -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Customer</h5>
                    <button type="button" class="btn-close" @click="closeModal('addCustomerModal')"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customer Name *</label>
                            <input type="text" class="form-control" v-model="newCustomer.name" placeholder="Enter full name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone *</label>
                            <input type="text" class="form-control" v-model="newCustomer.phone" placeholder="03XX-XXXXXXX">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" v-model="newCustomer.email" placeholder="email@example.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customer Type</label>
                            <select class="form-select" v-model="newCustomer.type">
                                <option value="regular">Regular</option>
                                <option value="business">Business / B2B</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" rows="2" v-model="newCustomer.address" placeholder="Physical address"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Credit Limit (PKR)</label>
                            <input type="number" class="form-control" v-model.number="newCustomer.creditLimit" placeholder="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Opening Balance (PKR)</label>
                            <input type="number" class="form-control" v-model.number="newCustomer.openingBalance" placeholder="0">
                            <small class="text-muted">Current outstanding amount.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal('addCustomerModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="saveNewCustomer">Save Customer</button>
                </div>
            </div>
        </div>
    </div>


    <!-- EDIT CUSTOMER MODAL -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Customer</h5>
                    <button type="button" class="btn-close" @click="closeModal('editCustomerModal')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Customer Name</label>
                        <input type="text" class="form-control" v-model="editingCustomer.name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" v-model="editingCustomer.phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal('editCustomerModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="updateCustomer">Update Customer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW CUSTOMER MODAL -->
    <div class="modal fade" id="viewCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 bg-light px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark m-0 d-flex align-items-center">
                        <i class="bi bi-person-badge text-primary me-2"></i>
                        Customer Details
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal('viewCustomerModal')"></button>
                </div>
                <div class="modal-body px-4 py-3" v-if="viewingCustomer">
                    <div class="d-flex align-items-center gap-3 p-3 mb-3 bg-light rounded-3 border">
                        <div class="customer-avatar" style="width: 48px; height: 48px; font-size: 20px;">
                            {{ viewingCustomer.name ? viewingCustomer.name.charAt(0).toUpperCase() : 'C' }}
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark m-0">{{ viewingCustomer.name }}</h5>
                            <span class="text-muted small font-monospace"><i class="bi bi-telephone me-1"></i>{{ viewingCustomer.phone || 'No phone' }}</span>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <span class="text-muted small">Total Purchases:</span>
                            <div class="fw-semibold text-dark">PKR {{ getCustomerSales(viewingCustomer).toLocaleString() }}</div>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small">Credit Limit:</span>
                            <div class="fw-semibold text-dark">PKR {{ getCustomerCreditLimit(viewingCustomer).toLocaleString() }}</div>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small">Outstanding Balance:</span>
                            <div class="fw-bold fs-6" :class="getCustomerOutstanding(viewingCustomer) > 0 ? 'text-danger' : 'text-success'">
                                PKR {{ getCustomerOutstanding(viewingCustomer).toLocaleString() }}
                            </div>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small">Account Status:</span>
                            <div>
                                <span :class="['badge px-2 py-1 rounded-pill', getCustomerOutstanding(viewingCustomer) > 0 ? 'bg-danger text-white' : 'bg-success text-white']">
                                    {{ getCustomerOutstanding(viewingCustomer) > 0 ? 'Outstanding Debt' : (viewingCustomer.status || 'Active') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light px-4 py-2.5 d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-success rounded-3 px-3 fw-semibold shadow-sm d-flex align-items-center gap-1" @click="closeModal('viewCustomerModal'); openRecordPaymentModal(viewingCustomer)">
                        <i class="bi bi-wallet2 me-1"></i> Record Payment
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary rounded-3 px-3" @click="closeModal('viewCustomerModal')">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- RECORD PAYMENT MODAL -->
    <div class="modal fade" id="recordPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 bg-light px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark m-0 d-flex align-items-center">
                        <i class="bi bi-wallet2 text-success me-2"></i>
                        Record Customer Payment
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal('recordPaymentModal')"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <!-- Error Alert -->
                    <div v-if="paymentError" class="alert alert-danger py-2 px-3 small rounded-3 mb-3 d-flex align-items-center justify-content-between">
                        <div><i class="bi bi-exclamation-circle-fill me-1"></i> {{ paymentError }}</div>
                        <button type="button" class="btn-close btn-sm" @click="paymentError = ''"></button>
                    </div>

                    <!-- Customer Selector -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Select Customer <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3" v-model="paymentForm.customerId">
                            <option :value="null" disabled>-- Choose a customer --</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">
                                {{ c.name }} ({{ c.phone || 'No Phone' }}) — Due: PKR {{ getCustomerOutstanding(c).toLocaleString() }}
                            </option>
                        </select>
                    </div>

                    <!-- Selected Customer Info Card -->
                    <div v-if="selectedPaymentCustomer" class="p-3 mb-3 rounded-3 border bg-light">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-bold text-dark fs-6">{{ selectedPaymentCustomer.name }}</div>
                                <div class="text-muted small font-monospace"><i class="bi bi-telephone me-1"></i>{{ selectedPaymentCustomer.phone || 'No phone' }}</div>
                            </div>
                            <span :class="['badge px-2.5 py-1 rounded-pill text-uppercase font-monospace', getCustomerOutstanding(selectedPaymentCustomer) > 0 ? 'bg-danger text-white' : 'bg-success text-white']" style="font-size: 11px;">
                                {{ getCustomerOutstanding(selectedPaymentCustomer) > 0 ? 'Outstanding' : 'Settled' }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <div>
                                <span class="text-muted small">Outstanding Balance:</span>
                                <div class="fw-bold fs-5" :class="getCustomerOutstanding(selectedPaymentCustomer) > 0 ? 'text-danger' : 'text-success'">
                                    PKR {{ getCustomerOutstanding(selectedPaymentCustomer).toLocaleString() }}
                                </div>
                            </div>
                            <button 
                                v-if="getCustomerOutstanding(selectedPaymentCustomer) > 0" 
                                type="button" 
                                class="btn btn-sm btn-outline-danger rounded-2 fw-semibold px-2 py-1"
                                style="font-size: 12px;"
                                @click="setFullOutstandingAmount"
                            >
                                Pay Full Due
                            </button>
                        </div>
                    </div>

                    <!-- Payment Amount -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Payment Amount (PKR) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-secondary">PKR</span>
                            <input 
                                type="number" 
                                min="1" 
                                step="0.01" 
                                class="form-control rounded-end-3" 
                                placeholder="Enter amount received..." 
                                v-model.number="paymentForm.amount"
                                @keydown.enter.prevent="savePayment"
                            />
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <!-- Payment Method -->
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Payment Method</label>
                            <select class="form-select rounded-3" v-model="paymentForm.method">
                                <option value="Cash">Cash</option>
                                <option value="Online">Online / Bank / Wallet</option>
                            </select>
                        </div>

                        <!-- Payment Date -->
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Date</label>
                            <input type="date" class="form-control rounded-3" v-model="paymentForm.date" />
                        </div>
                    </div>

                    <!-- Notes / Reference -->
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Notes / Reference (Optional)</label>
                        <input type="text" class="form-control rounded-3" placeholder="e.g. Cash received, Raast slip #102..." v-model="paymentForm.notes" />
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light px-4 py-2.5 d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-3 px-3" @click="closeModal('recordPaymentModal')">
                        Cancel
                    </button>
                    <button 
                        type="button" 
                        class="btn btn-success rounded-3 px-4 fw-semibold shadow-sm d-flex align-items-center gap-1"
                        :disabled="isSubmittingPayment" 
                        @click="savePayment"
                    >
                        <span v-if="isSubmittingPayment" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-check-circle me-1"></i>
                        Record Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- REVERSE PAYMENT CONFIRMATION MODAL -->
    <div v-if="showReverseModal && selectedPaymentForReversal" class="modal-backdrop fade show" style="z-index: 1060; background: rgba(0,0,0,0.5);"></div>
    <div v-if="showReverseModal && selectedPaymentForReversal" class="modal fade show d-block" tabindex="-1" style="z-index: 1070;">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 bg-danger text-white px-4 py-3">
                    <h5 class="modal-title fw-bold m-0 d-flex align-items-center">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>
                        Reverse Customer Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeReverseModal"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <p class="text-secondary small mb-3">
                        Are you sure you want to reverse this payment? This will restore the customer's outstanding balance and mark the payment audit record as voided.
                    </p>
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Reference #:</span>
                            <span class="font-monospace fw-bold">{{ selectedPaymentForReversal.reference || selectedPaymentForReversal.id }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Amount:</span>
                            <span class="fw-bold text-success fs-6">PKR {{ Number(selectedPaymentForReversal.credit || selectedPaymentForReversal.amount || 0).toLocaleString() }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Date:</span>
                            <span class="font-monospace text-secondary">{{ selectedPaymentForReversal.date || '—' }}</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Reason for Reversal <span class="text-danger">*</span></label>
                        <input 
                            type="text" 
                            class="form-control rounded-3" 
                            v-model="reversalReason" 
                            placeholder="e.g. Returned cheque, wrong entry, duplicate payment..." 
                            required
                        />
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light px-4 py-2.5 d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-3 px-3" @click="closeReverseModal" :disabled="isReversingPayment">
                        Cancel
                    </button>
                    <button 
                        type="button" 
                        class="btn btn-danger rounded-3 px-3 fw-semibold shadow-sm d-flex align-items-center gap-1"
                        :disabled="isReversingPayment || !reversalReason.trim()"
                        @click="confirmReversePayment"
                    >
                        <span v-if="isReversingPayment" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-arrow-counterclockwise me-1"></i>
                        Confirm Reversal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div v-if="toastMessage" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
        <div :class="['toast show align-items-center text-white border-0 shadow-lg rounded-3', toastType === 'success' ? 'bg-success' : 'bg-danger']" role="alert">
            <div class="d-flex">
                <div class="toast-body fw-semibold">
                    <i :class="['bi me-2', toastType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill']"></i>
                    {{ toastMessage }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="toastMessage = ''"></button>
            </div>
        </div>
    </div>

</div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
const props = defineProps({
  isModal: { type: Boolean, default: false },
  modalPage: { type: String, default: '' }
})
const emit = defineEmits(['success', 'close', 'open-purchase', 'open-return-debt'])
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCustomersStore } from '@/stores/customers';
import { useKhataStore } from '@/stores/khata';

const store = useCustomersStore();
const khataStore = useKhataStore();
const route = useRoute();
const router = useRouter();
const customers = computed(() => store.customers);

const searchQuery = ref('');
const filterType = ref('all');
const filterStatus = ref('all');

const currentPage = ref(1);
const itemsPerPage = ref(10);

const paymentForm = ref({ customerId: null, amount: null, method: 'Cash', date: new Date().toISOString().split('T')[0], notes: '' });
const isSubmittingPayment = ref(false);
const paymentError = ref('');
const toastMessage = ref('');
const toastType = ref('success');
let toastTimer = null;

const showToast = (msg, type = 'success') => {
    toastMessage.value = msg;
    toastType.value = type;
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toastMessage.value = '';
    }, 4000);
};

const getCustomerOutstanding = (c) => {
    if (!c) return 0;
    return Number(c.outstanding ?? c.current_balance ?? c.balance ?? 0);
};

const getCustomerCreditLimit = (c) => {
    if (!c) return 0;
    return Number(c.credit_limit ?? c.creditLimit ?? 0);
};

const getCustomerSales = (c) => {
    if (!c) return 0;
    return Number(c.sales ?? c.total_purchases ?? 0);
};

const selectedPaymentCustomer = computed(() => {
    if (!paymentForm.value.customerId) return null;
    return (customers.value || []).find(c => c.id === paymentForm.value.customerId || String(c.id) === String(paymentForm.value.customerId)) || null;
});

const setFullOutstandingAmount = () => {
    if (selectedPaymentCustomer.value) {
        const due = getCustomerOutstanding(selectedPaymentCustomer.value);
        if (due > 0) {
            paymentForm.value.amount = due;
        }
    }
};

const newCustomer = ref({ name: '', phone: '', email: '', type: 'regular', address: '', creditLimit: 0, openingBalance: 0 });
const editingCustomer = ref({ id: null, name: '', phone: '' });
const viewingCustomer = ref(null);

// Customer Profile & Khata Ledger State
const selectedCustomerProfile = ref(null);
const customerLedger = ref([]);
const isLoadingLedger = ref(false);
const ledgerFilter = ref('all');

// Payment Reversal State
const showReverseModal = ref(false);
const selectedPaymentForReversal = ref(null);
const reversalReason = ref('');
const isReversingPayment = ref(false);

const profileTotalPayments = computed(() => {
    return (customerLedger.value || []).reduce((sum, entry) => {
        if (entry.status !== 'voided' && entry.type !== 'Payment Voided' && Number(entry.credit) > 0) {
            return sum + Number(entry.credit);
        }
        return sum;
    }, 0);
});

const filteredLedger = computed(() => {
    const list = customerLedger.value || [];
    if (ledgerFilter.value === 'all') return list;
    if (ledgerFilter.value === 'sales') {
        return list.filter(e => e.type === 'Credit Sale' || Number(e.debit) > 0);
    }
    if (ledgerFilter.value === 'payments') {
        return list.filter(e => e.type === 'Payment Received' || (e.is_payment && e.status !== 'voided'));
    }
    if (ledgerFilter.value === 'voided') {
        return list.filter(e => e.status === 'voided' || e.type === 'Payment Voided');
    }
    return list;
});

onMounted(async () => {
    if (!props.isModal && !route.query.tab) {
        router.replace({ path: '/customer', query: { ...route.query, tab: 'customers' } });
    }
    await store.fetchCustomers();

    // Deep link directly to a customer's detailed profile and ledger if id passed in URL
    const targetCustId = route.query.id || route.query.customer_id;
    if (targetCustId && customers.value && customers.value.length) {
        const found = customers.value.find(c => String(c.id) === String(targetCustId));
        if (found) {
            await openCustomerProfile(found);
        }
    }

    if ((props.isModal && props.modalPage === 'payment') || route.query.action === 'payment') {
        openRecordPaymentModal();
    }
});

const totalCustomersCount = computed(() => (customers.value || []).length);
const activeCustomersCount = computed(() => (customers.value || []).filter(c => (c.status || 'active').toLowerCase() === 'active').length);
const totalOutstanding = computed(() => (customers.value || []).reduce((sum, c) => sum + (getCustomerOutstanding(c) > 0 ? getCustomerOutstanding(c) : 0), 0));
const totalOverdue = computed(() => (customers.value || []).filter(c => (c.status || '').toLowerCase() === 'overdue').reduce((sum, c) => sum + (getCustomerOutstanding(c) > 0 ? getCustomerOutstanding(c) : 0), 0));
const outstandingAccountsCount = computed(() => (customers.value || []).filter(c => getCustomerOutstanding(c) > 0).length);
const thisMonthNewCustomersCount = computed(() => {
    const currentMonth = new Date().toISOString().slice(0, 7);
    return (customers.value || []).filter(c => (c.created_at || '').startsWith(currentMonth)).length;
});
const engagementRate = computed(() => {
    if (!customers.value || !customers.value.length) return 0;
    return Math.round((activeCustomersCount.value / customers.value.length) * 100);
});

const filteredCustomers = computed(() => {
    let result = customers.value;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(c => (c.name || '').toLowerCase().includes(q) || (c.phone || '').includes(q));
    }
    if (filterType.value !== 'all') {
        result = result.filter(c => (c.type || 'regular') === filterType.value);
    }
    if (filterStatus.value !== 'all') {
        result = result.filter(c => (c.status || 'active') === filterStatus.value);
    }
    
    // Reset to page 1 if search changes
    // (A watcher is better, but this works fine for a simple app)
    return result;
});

const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredCustomers.value.length / itemsPerPage.value));
});

const paginatedCustomers = computed(() => {
    if (currentPage.value > totalPages.value) {
        currentPage.value = 1;
    }
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredCustomers.value.slice(start, end);
});

const paginationStart = computed(() => {
    return filteredCustomers.value.length === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1;
});

const paginationEnd = computed(() => {
    return Math.min(currentPage.value * itemsPerPage.value, filteredCustomers.value.length);
});

const visiblePages = computed(() => {
    let start = Math.max(1, currentPage.value - 2);
    let end = Math.min(totalPages.value, currentPage.value + 2);
    let pages = [];
    for (let i = start; i <= end; i++) pages.push(i);
    return pages;
});

function changePage(p) {
    if (p >= 1 && p <= totalPages.value) {
        currentPage.value = p;
    }
}

// Modals
function openModal(id) {
    const el = document.getElementById(id);
    if(el) {
        el.style.display = 'block';
        el.style.zIndex = '1060';
        el.classList.add('show');
        if (props.isModal) {
            el.style.background = 'none';
            el.style.pointerEvents = 'none';
            const dialog = el.querySelector('.modal-dialog');
            if (dialog) dialog.style.pointerEvents = 'auto';
        } else {
            const bd = document.createElement('div');
            bd.className = 'modal-backdrop fade show custom-bd-' + id;
            document.body.appendChild(bd);
        }
    }
}

function closeModal(id) {
    const el = document.getElementById(id);
    if(el) {
        el.style.display = 'none';
        el.classList.remove('show');
        if (props.isModal) {
            el.style.background = '';
            el.style.pointerEvents = '';
        } else {
            const bd = document.querySelector('.custom-bd-' + id);
            if(bd) bd.remove();
        }
    }
}

function openAddModal() {
    newCustomer.value = { name: '', phone: '', email: '', type: 'regular', address: '', creditLimit: 0, openingBalance: 0 };
    openModal('addCustomerModal');
}


async function saveNewCustomer() {
    if(!newCustomer.value.name || !newCustomer.value.phone) {
        alert("Name and Phone are required.");
        return;
    }
    
    await store.addCustomer({
        name: newCustomer.value.name,
        phone: newCustomer.value.phone,
        email: newCustomer.value.email,
        address: newCustomer.value.address,
        credit_limit: newCustomer.value.creditLimit || 0,
        opening_balance: newCustomer.value.openingBalance || 0,
        balance: newCustomer.value.openingBalance || 0,
        type: newCustomer.value.type
    });
    
    closeModal('addCustomerModal');
}


function openEditModal(c) {
    editingCustomer.value = { id: c.id, name: c.name, phone: c.phone };
    openModal('editCustomerModal');
}

async function updateCustomer() {
    await store.updateCustomer(editingCustomer.value.id, {
        name: editingCustomer.value.name,
        phone: editingCustomer.value.phone
    });
    closeModal('editCustomerModal');
}

function openViewModal(c) {
    viewingCustomer.value = c;
    openModal('viewCustomerModal');
}

async function deleteCustomer(id) {
    if(confirm('Are you sure you want to delete this customer?')) {
        await store.deleteCustomer(id);
    }
}

function openRecordPaymentModal(customer = null) {
    paymentError.value = '';
    if (customer && customer.id) {
        paymentForm.value.customerId = customer.id;
        const due = getCustomerOutstanding(customer);
        paymentForm.value.amount = due > 0 ? due : null;
    } else if (customers.value && customers.value.length > 0) {
        // Prefer first customer with outstanding balance
        const debtor = customers.value.find(c => getCustomerOutstanding(c) > 0) || customers.value[0];
        paymentForm.value.customerId = debtor.id;
        const due = getCustomerOutstanding(debtor);
        paymentForm.value.amount = due > 0 ? due : null;
    } else {
        paymentForm.value.customerId = null;
        paymentForm.value.amount = null;
    }
    paymentForm.value.method = 'Cash';
    paymentForm.value.date = new Date().toISOString().split('T')[0];
    paymentForm.value.notes = '';
    openModal('recordPaymentModal');
}

function downloadCustomers() {
    const csv = "Customer,Phone,Total Purchases,Outstanding,Credit Limit,Status\n" +
                "Ali Hassan,0300-1234567,PKR 142000,PKR 25500,PKR 50000,Overdue\n" +
                "Fatima Ahmed,0333-7654321,PKR 85400,PKR 12000,PKR 30000,Outstanding\n" +
                "Zain Qureshi,0345-9988776,PKR 210000,PKR 0,PKR 100000,Clear\n" +
                "Sara Tariq,0321-4455667,PKR 45000,PKR 0,PKR 20000,Clear";
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "HBOS-Customers.csv";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

async function savePayment() {
    paymentError.value = '';
    if (!paymentForm.value.customerId) {
        paymentError.value = 'Please select a customer.';
        return;
    }
    const amt = parseFloat(paymentForm.value.amount);
    if (!amt || amt <= 0) {
        paymentError.value = 'Please enter a valid payment amount greater than zero.';
        return;
    }

    isSubmittingPayment.value = true;
    try {
        const payload = {
            customer_id: paymentForm.value.customerId,
            type: 'got',
            amount: amt,
            payment_method: paymentForm.value.method || 'Cash',
            date: paymentForm.value.date || new Date().toISOString().split('T')[0],
            notes: paymentForm.value.notes ? `${paymentForm.value.notes} (${paymentForm.value.method})` : `Payment received (${paymentForm.value.method})`
        };

        const res = await khataStore.addTransaction(payload);
        if (res.success) {
            await store.fetchCustomers();
            if (selectedCustomerProfile.value) {
                const refreshed = (customers.value || []).find(c => c.id === selectedCustomerProfile.value.id || String(c.id) === String(selectedCustomerProfile.value.id));
                if (refreshed) selectedCustomerProfile.value = refreshed;
                await loadCustomerLedger(selectedCustomerProfile.value.id);
            }
            closeModal('recordPaymentModal');
            showToast(`Payment of PKR ${amt.toLocaleString()} recorded successfully!`, 'success');
            if (props.isModal) {
                emit('success', res.message || 'Payment recorded successfully!');
                return;
            }
            paymentForm.value = {
                customerId: null,
                amount: null,
                method: 'Cash',
                date: new Date().toISOString().split('T')[0],
                notes: ''
            };
        } else {
            paymentError.value = res.message || 'Failed to record payment.';
        }
    } catch (err) {
        paymentError.value = err.response?.data?.message || err.message || 'Error processing payment.';
    } finally {
        isSubmittingPayment.value = false;
    }
}

async function openCustomerProfile(customer) {
    if (!customer) return;
    selectedCustomerProfile.value = customer;
    ledgerFilter.value = 'all';
    await loadCustomerLedger(customer.id);
}

function closeCustomerProfile() {
    selectedCustomerProfile.value = null;
    customerLedger.value = [];
    if (route.query.id || route.query.customer_id) {
        const q = { ...route.query };
        delete q.id;
        delete q.customer_id;
        router.replace({ path: '/customer', query: q });
    }
}

async function loadCustomerLedger(customerId) {
    isLoadingLedger.value = true;
    try {
        const res = await store.fetchCustomerLedger(customerId);
        if (res.success && res.data) {
            const data = res.data;
            if (Array.isArray(data)) {
                customerLedger.value = data;
            } else if (data.ledger && Array.isArray(data.ledger)) {
                customerLedger.value = data.ledger;
                if (data.customer && selectedCustomerProfile.value) {
                    selectedCustomerProfile.value = { ...selectedCustomerProfile.value, ...data.customer };
                }
            } else {
                customerLedger.value = [];
            }
        } else {
            customerLedger.value = [];
        }
    } catch (e) {
        console.error('Error loading ledger:', e);
        customerLedger.value = [];
    } finally {
        isLoadingLedger.value = false;
    }
}

function shareCustomerStatementWhatsApp() {
    if (!selectedCustomerProfile.value) return;
    const c = selectedCustomerProfile.value;
    const phone = (c.phone || '').replace(/[^0-9]/g, '');
    const cleanPhone = phone.startsWith('0') ? '92' + phone.slice(1) : phone;
    const balance = getCustomerOutstanding(c);
    const purchases = getCustomerSales(c);
    const limit = getCustomerCreditLimit(c);
    const dateStr = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

    const msg = `*ACCOUNT STATEMENT - HBOS*\n` +
                `Customer: ${c.name}\n` +
                `Phone: ${c.phone || 'N/A'}\n` +
                `Date: ${dateStr}\n` +
                `------------------------\n` +
                `Total Purchases: PKR ${purchases.toLocaleString()}\n` +
                `Khata Balance (Due): PKR ${balance.toLocaleString()}\n` +
                `Credit Limit: PKR ${limit.toLocaleString()}\n` +
                `Status: ${balance > 0 ? 'Payment Outstanding' : 'Account Settled / Clear'}\n` +
                `------------------------\n` +
                `Thank you for your business!`;

    const url = cleanPhone 
        ? `https://api.whatsapp.com/send?phone=${cleanPhone}&text=${encodeURIComponent(msg)}`
        : `https://api.whatsapp.com/send?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
}

function printCustomerLedger() {
    window.print();
}

function openReverseModal(entry) {
    selectedPaymentForReversal.value = entry;
    reversalReason.value = 'Mistaken entry / adjustment';
    showReverseModal.value = true;
}

function closeReverseModal() {
    showReverseModal.value = false;
    selectedPaymentForReversal.value = null;
    reversalReason.value = '';
    isReversingPayment.value = false;
}

async function confirmReversePayment() {
    if (!selectedPaymentForReversal.value) return;
    const entry = selectedPaymentForReversal.value;
    const rawId = entry.payment_id || (typeof entry.id === 'string' && entry.id.startsWith('payment-') ? entry.id.replace('payment-', '') : entry.id);
    
    isReversingPayment.value = true;
    try {
        const res = await khataStore.deleteTransaction(rawId, reversalReason.value || 'Payment reversed');
        if (res.success) {
            showToast('Payment reversed successfully and customer balance restored!', 'success');
            closeReverseModal();
            await store.fetchCustomers();
            if (selectedCustomerProfile.value) {
                const updated = customers.value.find(c => c.id === selectedCustomerProfile.value.id || String(c.id) === String(selectedCustomerProfile.value.id));
                if (updated) {
                    selectedCustomerProfile.value = updated;
                }
                await loadCustomerLedger(selectedCustomerProfile.value.id);
            }
        } else {
            showToast(res.message || 'Failed to reverse payment', 'danger');
        }
    } catch (err) {
        showToast(err.message || 'Error reversing payment', 'danger');
    } finally {
        isReversingPayment.value = false;
    }
}

</script>

<style scoped>

        /* =========================================================
       GLOBAL
    ========================================================= */

        :root {
            --primary: #0757e5;
            --primary-dark: #0046c7;
            --text: #17191d;
            --muted: #626873;
            --border: #cbd0d8;
            --bg: #f6f8fa;
            --white: #ffffff;
            --danger: #d32222;
            --success: #079b68;
            --warning: #d89000;
            --sidebar-width: 258px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
            padding: 0;
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;
            color: var(--text);
            background: var(--bg);
        }

        body {
            overflow-x: hidden;
        }

        button,
        input,
        select {
            font: inherit;
        }

        /* =========================================================
       APP LAYOUT
    ========================================================= */

        .app {
            min-height: 100vh;
            display: flex;
            background: var(--bg);
        }

        /* =========================================================
       SIDEBAR
    ========================================================= */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: #fff;
            border-right: 1px solid var(--border);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease;
        }

        .brand {
            height: 128px;
            padding: 28px 26px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .brand-icon {
            width: 50px;
            height: 50px;
            border-radius: 3px;
            background: #050505;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .brand-content {
            line-height: 1;
        }

        .brand-title {
            font-size: 27px;
            line-height: 1.1;
            font-weight: 700;
            letter-spacing: -0.8px;
        }

        .brand-subtitle {
            margin-top: 7px;
            font-size: 15px;
            color: #20242a;
            font-weight: 500;
        }

        /* Sidebar navigation */

        .sidebar-nav {
            padding: 20px 10px;
            flex: 1;
        }

        .nav-link-custom {
            width: 100%;
            min-height: 44px;
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: 7px;
            color: #4d525a;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 17px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-link-custom i {
            width: 20px;
            text-align: center;
            font-size: 18px;
        }

        .nav-link-custom:hover {
            background: #f1f4f9;
            color: var(--primary);
        }

        .nav-link-custom.active {
            color: var(--primary);
            background: #e8efff;
            border-right: 4px solid var(--primary);
        }

        .sidebar-bottom {
            padding: 0 10px 20px;
        }

        .sidebar-divider {
            height: 1px;
            background: var(--border);
            margin: 0 6px 15px;
        }

        /* =========================================================
       MAIN
    ========================================================= */

        .main {
            width: calc(100% - var(--sidebar-width));
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        /* =========================================================
       TOPBAR
    ========================================================= */

        .topbar {
            height: 86px;
            background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 28px;
            gap: 20px;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .mobile-menu {
            display: none;
            width: 42px;
            height: 42px;
            border: 1px solid var(--border);
            background: #fff;
            border-radius: 7px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }

        .global-search {
            width: min(650px, 100%);
            height: 48px;
            border: 1px solid #c9ced7;
            border-radius: 7px;
            background: #fff;
            display: flex;
            align-items: center;
            padding: 0 15px;
        }

        .global-search i {
            font-size: 21px;
            margin-right: 13px;
            color: #252a31;
        }

        .global-search input {
            width: 100%;
            border: 0;
            outline: 0;
            font-size: 16px;
            color: var(--text);
        }

        .global-search input::placeholder {
            color: #747b85;
        }

        .topbar-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .top-icon {
            position: relative;
            border: 0;
            background: transparent;
            width: 38px;
            height: 38px;
            font-size: 22px;
            color: #30343b;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .notification-dot {
            position: absolute;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #d52323;
            top: 4px;
            right: 4px;
            border: 1px solid #fff;
        }

        .profile-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #e6ebf7;
            border: 1px solid #bdc7db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #252b38;
        }

        /* =========================================================
       PAGE
    ========================================================= */

        .page {
            padding: 24px 30px 40px;
            width: 100% !important;
            min-width: calc(100vw - var(--sidebar-width, 258px)) !important;
            max-width: none !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 22px;
        }

        .page-title {
            font-size: 31px;
            line-height: 1.15;
            font-weight: 700;
            letter-spacing: -0.8px;
            margin: 0 0 6px;
        }

        .page-description {
            color: #555c66;
            font-size: 18px;
            margin: 0;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .btn-custom {
            min-height: 48px;
            padding: 0 20px;
            border-radius: 7px;
            font-size: 16px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border: 1px solid var(--border);
            background: #fff;
            color: #17191d;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-custom:hover {
            background: #f4f6f9;
        }

        .btn-primary-custom {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .btn-primary-custom:hover {
            background: var(--primary-dark);
            color: #fff;
        }

        /* =========================================================
       STAT CARDS
    ========================================================= */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            min-height: 180px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 25px 25px 20px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: "";
            position: absolute;
            width: 95px;
            height: 95px;
            border-radius: 50%;
            right: -8px;
            top: -20px;
            background: #f0f1f3;
            opacity: 0.8;
        }

        .stat-card.active-card::after {
            background: #edf9f5;
        }

        .stat-card.outstanding-card::after {
            background: #edf3ff;
        }

        .stat-card.overdue-card::after {
            background: #fff0f0;
        }

        .stat-icon {
            position: absolute;
            z-index: 2;
            right: 25px;
            top: 25px;
            width: 39px;
            height: 39px;
            border-radius: 9px;
            background: #f1f2f4;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #555c65;
            font-size: 19px;
        }

        .stat-card.active-card .stat-icon {
            background: #edf5f2;
            color: #527f71;
        }

        .stat-card.outstanding-card .stat-icon {
            background: #edf3ff;
            color: #536f9e;
        }

        .stat-card.overdue-card .stat-icon {
            background: #ffd9d9;
            color: #df2020;
        }

        .stat-label {
            width: 65%;
            font-size: 15px;
            line-height: 1.35;
            letter-spacing: 1px;
            font-weight: 500;
            color: #4e545d;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 35px;
            line-height: 1;
            font-weight: 700;
            letter-spacing: -1px;
            margin-bottom: 12px;
        }

        .stat-value.currency {
            font-size: 29px;
        }

        .stat-value.danger {
            color: var(--danger);
        }

        .stat-note {
            font-size: 16px;
            color: #5d626b;
        }

        .stat-note.success {
            color: var(--success);
        }

        .stat-note.danger {
            color: #e11b1b;
            line-height: 1.45;
            max-width: 210px;
        }

        /* =========================================================
       CUSTOMER TABLE
    ========================================================= */

        .customer-panel {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }

        .table-toolbar {
            min-height: 70px;
            padding: 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customer-search {
            height: 40px;
            width: 385px;
            max-width: 100%;
            border: 1px solid #c5cad2;
            border-radius: 6px;
            display: flex;
            align-items: center;
            padding: 0 12px;
        }

        .customer-search i {
            color: #4c535c;
            margin-right: 12px;
        }

        .customer-search input {
            border: 0;
            outline: 0;
            width: 100%;
            min-width: 0;
            font-size: 15px;
        }

        .filter-select {
            height: 40px;
            min-width: 180px;
            border: 1px solid #c5cad2;
            border-radius: 6px;
            background: #fff;
            padding: 0 35px 0 12px;
            font-size: 15px;
            color: #282c31;
            outline: none;
        }

        .toolbar-spacer {
            flex: 1;
        }

        .icon-button {
            width: 40px;
            height: 40px;
            border: 1px solid #c5cad2;
            border-radius: 6px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .icon-button:hover {
            background: #f3f5f8;
        }

        /* Table */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .customers-table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
        }

        .customers-table th {
            height: 55px;
            background: #fafbfc;
            border-bottom: 1px solid var(--border);
            padding: 10px 16px;
            text-align: left;
            color: #4d535d;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .customers-table td {
            height: 74px;
            border-bottom: 1px solid var(--border);
            padding: 10px 16px;
            font-size: 15px;
            color: #4b5058;
            vertical-align: middle;
        }

        .customers-table tbody tr:hover {
            background: #fafcff;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 180px;
        }

        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid #c7cbd1;
            background: #f0f1f3;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5c626b;
            font-weight: 600;
            flex-shrink: 0;
            overflow: hidden;
        }

        .customer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .customer-name {
            color: #202328;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .customer-phone {
            color: #5f656e;
            font-size: 14px;
        }

        .amount {
            white-space: nowrap;
        }

        .amount.danger {
            color: #e32222;
            font-weight: 500;
        }

        .status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 13px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status.overdue {
            background: #ffe2e2;
            color: #c51e1e;
        }

        .status.outstanding {
            background: #fff0bd;
            color: #a36300;
        }

        .status.clear {
            background: #d9f7e9;
            color: #087a54;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .action-btn {
            border: 0;
            background: transparent;
            color: var(--primary);
            font-size: 19px;
            padding: 4px;
            cursor: pointer;
        }

        .action-btn.more {
            color: #434951;
        }

        /* =========================================================
       TABLE FOOTER
    ========================================================= */

        .table-footer {
            min-height: 62px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            gap: 15px;
        }

        .result-count {
            color: #555b64;
            font-size: 15px;
        }

        .pagination-custom {
            display: flex;
            align-items: center;
        }

        .page-btn {
            width: 40px;
            height: 38px;
            border: 1px solid #cbd0d8;
            border-left: 0;
            background: #fff;
            color: #41464e;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-btn:first-child {
            border-left: 1px solid #cbd0d8;
            border-radius: 6px 0 0 6px;
        }

        .page-btn:last-child {
            border-radius: 0 6px 6px 0;
        }

        .page-btn.active {
            background: #101b37;
            color: #fff;
            border-color: #101b37;
        }

        /* =========================================================
       MOBILE OVERLAY
    ========================================================= */

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.35);
            z-index: 999;
        }

        /* =========================================================
       RESPONSIVE
    ========================================================= */

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .page {
                padding-left: 22px;
                padding-right: 22px;
            }

            .page-header {
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
            }

            .header-actions .btn-custom {
                flex: 1;
            }

            .table-toolbar {
                flex-wrap: wrap;
            }

            .customer-search {
                width: 100%;
            }

            .toolbar-spacer {
                display: none;
            }

            .filter-select {
                flex: 1;
            }
        }

        @media (max-width: 650px) {
            .page {
                padding: 18px 14px 30px;
            }

            .topbar {
                gap: 8px;
            }

            .global-search {
                flex: 1;
                min-width: 0;
            }

            .global-search input {
                font-size: 14px;
            }

            .top-icon {
                display: none;
            }

            .profile-circle {
                width: 38px;
                height: 38px;
            }

            .page-title {
                font-size: 27px;
            }

            .page-description {
                font-size: 15px;
            }

            .header-actions {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .stat-card {
                min-height: 155px;
                padding: 21px;
            }

            .stat-value {
                font-size: 32px;
            }

            .stat-value.currency {
                font-size: 26px;
            }

            .table-toolbar {
                padding: 12px;
            }

            .filter-select {
                width: 100%;
                flex: 1 1 100%;
            }

            .table-footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .pagination-custom {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 420px) {
            .brand {
                padding-left: 20px;
            }

            .page {
                padding-left: 10px;
                padding-right: 10px;
            }

            .stat-card {
                border-radius: 10px;
            }

            .page-title {
                font-size: 25px;
            }

            .btn-custom {
                width: 100%;
            }
        }

        @media print {
            body * {
                visibility: hidden;
            }
            .customer-profile-page, .customer-profile-page * {
                visibility: visible;
            }
            .customer-profile-page {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 0 !important;
                margin: 0 !important;
                background: #ffffff !important;
            }
            .customer-profile-page button,
            .btn,
            .header-actions,
            .filter-select,
            .table-toolbar {
                display: none !important;
            }
        }
    
</style>