<template>
  <div class="reports-page-wrapper">
    <div class="reports-main-container">
      <!-- PAGE HEADER -->
      <div class="reports-topbar">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="reports-badge-pill">Business Intelligence</span>
            <span class="text-muted small">• Executive Overview & Analytics</span>
          </div>
          <h1 class="reports-main-title">Reports &amp; Business Insights</h1>
          <p class="reports-main-sub">
            Monitor real-time sales trends, estimated profitability, stock alerts, and financial health at a glance.
          </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
          <select class="reports-filter-select" v-model="selectedTimeRange">
            <option value="7d">Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
            <option value="90d">Last 90 Days</option>
            <option value="year">This Year</option>
            <option value="custom">Custom Range</option>
          </select>
          <button class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold px-3 bg-white shadow-sm" @click="exportReport" type="button" style="height: 38px;">
            <i class="bi bi-download me-1"></i> Export Report
          </button>
        </div>
      </div>

      <!-- 5 TOP KPI CARDS -->
      <div class="reports-kpi-grid mb-4">
        <!-- 1. Total Sales -->
        <div class="reports-kpi-card">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="kpi-label">Total Revenue</span>
            <div class="kpi-icon-pill bg-primary-subtle text-primary">
              <i class="bi bi-graph-up-arrow"></i>
            </div>
          </div>
          <div class="kpi-number text-dark">PKR 485,000</div>
          <div class="d-flex align-items-center gap-1.5 mt-2">
            <span class="trend-badge trend-up">
              <i class="bi bi-arrow-up-short"></i> +8.4%
            </span>
            <span class="kpi-subtext text-muted">vs previous period</span>
          </div>
        </div>

        <!-- 2. Estimated Profit -->
        <div class="reports-kpi-card">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="kpi-label">Estimated Profit</span>
            <div class="kpi-icon-pill bg-success-subtle text-success">
              <i class="bi bi-cash-stack"></i>
            </div>
          </div>
          <div class="kpi-number text-success">PKR 142,500</div>
          <div class="d-flex align-items-center gap-1.5 mt-2">
            <span class="trend-badge trend-up">
              <i class="bi bi-arrow-up-short"></i> +12.1%
            </span>
            <span class="kpi-subtext text-muted">29.4% profit margin</span>
          </div>
        </div>

        <!-- 3. Operating Expenses -->
        <div class="reports-kpi-card">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="kpi-label">Total Expenses</span>
            <div class="kpi-icon-pill bg-info-subtle text-info">
              <i class="bi bi-wallet2"></i>
            </div>
          </div>
          <div class="kpi-number text-dark">PKR 42,000</div>
          <div class="d-flex align-items-center gap-1.5 mt-2">
            <span class="trend-badge trend-up">
              <i class="bi bi-arrow-down-short"></i> -2.1%
            </span>
            <span class="kpi-subtext text-muted">cost savings</span>
          </div>
        </div>

        <!-- 4. Outstanding Customer Credit -->
        <div class="reports-kpi-card">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="kpi-label">Outstanding Credit</span>
            <div class="kpi-icon-pill bg-warning-subtle text-warning">
              <i class="bi bi-person-exclamation"></i>
            </div>
          </div>
          <div class="kpi-number text-dark">PKR 86,400</div>
          <div class="d-flex align-items-center gap-1.5 mt-2">
            <span class="trend-badge trend-down">
              <i class="bi bi-arrow-up-short"></i> +5.0%
            </span>
            <span class="kpi-subtext text-muted">pending receivables</span>
          </div>
        </div>

        <!-- 5. Inventory Asset Value -->
        <div class="reports-kpi-card">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="kpi-label">Inventory Valuation</span>
            <div class="kpi-icon-pill bg-purple-subtle text-purple">
              <i class="bi bi-boxes"></i>
            </div>
          </div>
          <div class="kpi-number text-dark">PKR 485,000</div>
          <div class="d-flex align-items-center gap-1.5 mt-2">
            <span class="trend-badge trend-up">
              <i class="bi bi-check2"></i> 1,245
            </span>
            <span class="kpi-subtext text-muted">total products in stock</span>
          </div>
        </div>
      </div>

      <!-- MAIN CHART & ALERT DASHBOARD ROW -->
      <div class="row g-4 mb-4">
        <!-- SALES & PROFIT CHART -->
        <div class="col-12 col-xl-8">
          <div class="reports-panel-card h-100">
            <div class="reports-panel-header">
              <div>
                <h3 class="reports-panel-title m-0">Revenue &amp; Profit Velocity</h3>
                <p class="text-muted small m-0">Daily gross revenue comparison against net estimated profitability</p>
              </div>
              <div class="d-flex align-items-center gap-3">
                <span class="d-inline-flex align-items-center gap-1.5 small fw-semibold text-dark">
                  <span class="legend-indicator bg-primary"></span> Gross Sales
                </span>
                <span class="d-inline-flex align-items-center gap-1.5 small fw-semibold text-dark">
                  <span class="legend-indicator bg-success"></span> Estimated Profit
                </span>
              </div>
            </div>
            <div class="p-4">
              <div style="height: 320px; position: relative;">
                <canvas id="trendsChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: ALERTS & HEALTH -->
        <div class="col-12 col-xl-4">
          <div class="d-flex flex-column gap-3 h-100">
            <!-- CRITICAL ACTION ALERTS -->
            <div class="reports-panel-card">
              <div class="reports-panel-header py-3">
                <h4 class="reports-panel-title fs-6 m-0">
                  <i class="bi bi-bell me-1.5 text-warning"></i> Operational Attention
                </h4>
              </div>
              <div class="p-3">
                <div class="alert-item pb-2.5 mb-2.5 border-bottom">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="alert-badge badge-danger">Low Stock Warning</span>
                    <strong class="text-danger small">12 items</strong>
                  </div>
                  <p class="alert-desc mb-0 text-muted small">Products running below minimum safety reorder thresholds.</p>
                </div>

                <div class="alert-item pb-2.5 mb-2.5 border-bottom">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="alert-badge badge-warning">Overdue Khata Debt</span>
                    <strong class="text-warning small">3 accounts</strong>
                  </div>
                  <p class="alert-desc mb-0 text-muted small">Customer credit accounts exceeding standard payment cycle.</p>
                </div>

                <div class="alert-item">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="alert-badge badge-success">Inventory Health</span>
                    <strong class="text-success small">98.2%</strong>
                  </div>
                  <p class="alert-desc mb-0 text-muted small">Store catalog availability and fulfillment index.</p>
                </div>
              </div>
            </div>

            <!-- REORDER RECOMMENDATION CALLOUT -->
            <div class="reorder-callout-card p-3.5">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-arrow-repeat text-primary fs-5"></i>
                  <strong class="text-dark">Reorder Intelligence</strong>
                </div>
                <span class="badge bg-primary rounded-pill px-2.5">12 items recommended</span>
              </div>
              <p class="text-muted small mb-3">AI reorder forecast identified fast-moving items approaching stockout.</p>
              <button class="btn btn-primary btn-sm w-100 rounded-3 fw-semibold py-2" @click="$router.push('/inventry?tab=products')" type="button">
                Review Restock Recommendations
              </button>
            </div>

            <!-- SYSTEM HEALTH SUMMARY -->
            <div class="reports-panel-card p-3.5">
              <h5 class="reports-panel-title fs-6 mb-3">Store Health Index</h5>
              <div class="health-row mb-2">
                <span class="text-muted small fw-medium">Sales Momentum</span>
                <span class="stock-pill badge-in"><span class="pill-dot"></span> Healthy (+8.4%)</span>
              </div>
              <div class="health-row mb-2">
                <span class="text-muted small fw-medium">Inventory Turnover</span>
                <span class="stock-pill badge-warning"><span class="pill-dot"></span> Needs Review</span>
              </div>
              <div class="health-row">
                <span class="text-muted small fw-medium">Cash Flow Liquidity</span>
                <span class="stock-pill badge-in"><span class="pill-dot"></span> Stable</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- MIDDLE ROW: CATEGORIES, MARGINS & TOP PRODUCTS -->
      <div class="row g-4 mb-4">
        <!-- SALES BY CATEGORY -->
        <div class="col-12 col-md-4">
          <div class="reports-panel-card h-100">
            <div class="reports-panel-header">
              <h4 class="reports-panel-title fs-6 m-0">Revenue by Category</h4>
            </div>
            <div class="p-3.5">
              <div style="height: 190px; position: relative;">
                <canvas id="categoryChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- PROFITABILITY OVERVIEW -->
        <div class="col-12 col-md-4">
          <div class="reports-panel-card h-100">
            <div class="reports-panel-header">
              <h4 class="reports-panel-title fs-6 m-0">Profitability Breakdown</h4>
            </div>
            <div class="p-3.5">
              <div class="metric-list-item pb-2.5 mb-2.5 border-bottom">
                <span class="text-muted small fw-medium">Gross Profit</span>
                <strong class="text-dark fs-6">PKR 142,500</strong>
              </div>
              <div class="metric-list-item pb-2.5 mb-2.5 border-bottom">
                <span class="text-muted small fw-medium">Overall Profit Margin</span>
                <strong class="text-success fs-6">29.4%</strong>
              </div>
              <div class="metric-list-item pb-2.5 mb-2.5 border-bottom">
                <span class="text-muted small fw-medium">Top Margin Sector</span>
                <strong class="text-primary">Beverages (38%)</strong>
              </div>
              <div class="metric-list-item">
                <span class="text-muted small fw-medium">Lowest Margin Sector</span>
                <strong class="text-secondary">Wholesale Staples (12%)</strong>
              </div>
            </div>
          </div>
        </div>

        <!-- TOP PERFORMING PRODUCTS -->
        <div class="col-12 col-md-4">
          <div class="reports-panel-card h-100">
            <div class="reports-panel-header">
              <h4 class="reports-panel-title fs-6 m-0">Top Performing SKUs</h4>
            </div>
            <div class="p-3.5">
              <!-- Item 1 -->
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-semibold text-dark small">Lipton Tea 250g</span>
                  <span class="badge bg-success-subtle text-success rounded-pill small fw-bold">45% Margin</span>
                </div>
                <div class="progress" style="height: 6px; background: #f1f5f9; border-radius: 3px;">
                  <div class="progress-bar bg-success rounded-pill" style="width: 85%;"></div>
                </div>
              </div>

              <!-- Item 2 -->
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-semibold text-dark small">Sugar 1kg Refined</span>
                  <span class="badge bg-primary-subtle text-primary rounded-pill small fw-bold">12% Margin</span>
                </div>
                <div class="progress" style="height: 6px; background: #f1f5f9; border-radius: 3px;">
                  <div class="progress-bar bg-primary rounded-pill" style="width: 65%;"></div>
                </div>
              </div>

              <!-- Item 3 -->
              <div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-semibold text-dark small">Fresh Sandwich Bread</span>
                  <span class="badge bg-info-subtle text-info rounded-pill small fw-bold">18% Margin</span>
                </div>
                <div class="progress" style="height: 6px; background: #f1f5f9; border-radius: 3px;">
                  <div class="progress-bar bg-info rounded-pill" style="width: 50%;"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- BOTTOM ROW: OPERATIONAL INSIGHTS QUADRANT -->
      <div class="row g-4">
        <!-- Inventory & Customers -->
        <div class="col-12 col-xl-6">
          <div class="reports-panel-card p-4">
            <div class="row g-4">
              <div class="col-6 border-end">
                <h5 class="fw-bold text-dark fs-6 mb-3">
                  <i class="bi bi-box me-1.5 text-primary"></i> Inventory Summary
                </h5>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted small">Total SKUs</span><strong class="text-dark">1,245</strong></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted small">Low Stock Warning</span><strong class="text-warning">12</strong></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted small">Out of Stock</span><strong class="text-danger">3</strong></div>
                <div class="d-flex justify-content-between pt-1"><span class="text-muted small">Asset Value</span><strong class="text-dark">PKR 485K</strong></div>
              </div>

              <div class="col-6">
                <h5 class="fw-bold text-dark fs-6 mb-3">
                  <i class="bi bi-people me-1.5 text-info"></i> Customer Relations
                </h5>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted small">Total Profiles</span><strong class="text-dark">342</strong></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted small">Active (30 Days)</span><strong class="text-success">89</strong></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted small">New (30 Days)</span><strong class="text-primary">14</strong></div>
                <div class="d-flex justify-content-between pt-1"><span class="text-muted small">Open Khata Debt</span><strong class="text-danger">PKR 86K</strong></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Suppliers & Overhead -->
        <div class="col-12 col-xl-6">
          <div class="reports-panel-card p-4">
            <div class="row g-4">
              <div class="col-6 border-end">
                <h5 class="fw-bold text-dark fs-6 mb-3">
                  <i class="bi bi-truck me-1.5 text-primary"></i> Supplier Pipeline
                </h5>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted small">Registered Vendors</span><strong class="text-dark">45</strong></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted small">Pending POs</span><strong class="text-primary">4 Orders</strong></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted small">Payables Due</span><strong class="text-danger">PKR 52K</strong></div>
                <div class="d-flex justify-content-between pt-1"><span class="text-muted small">Fulfillment Rate</span><strong class="text-success">94.2%</strong></div>
              </div>

              <div class="col-6">
                <h5 class="fw-bold text-dark fs-6 mb-3">
                  <i class="bi bi-wallet2 me-1.5 text-warning"></i> Operational Cost
                </h5>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted small">Total Spend (30d)</span><strong class="text-dark">PKR 42K</strong></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted small">Major Category</span><strong class="text-warning">Utilities</strong></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted small">Cost Variance</span><strong class="text-success">-2.1% lower</strong></div>
                <div class="d-flex justify-content-between pt-1"><span class="text-muted small">Budget Utilization</span><strong class="text-primary">68.4%</strong></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const selectedTimeRange = ref('30d');

const exportReport = () => {
  const data = [
    ['Metric', 'Value', 'Status'],
    ['Total Revenue', 'PKR 485,000', '+8.4% vs prev'],
    ['Estimated Profit', 'PKR 142,500', '+12.1% vs prev'],
    ['Total Expenses', 'PKR 42,000', '-2.1% savings'],
    ['Outstanding Credit', 'PKR 86,400', 'Pending receivables'],
    ['Inventory Valuation', 'PKR 485,000', '1,245 SKUs']
  ];
  const csvContent = 'data:text/csv;charset=utf-8,' + data.map(e => e.join(',')).join('\n');
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `business_report_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

onMounted(() => {
  if (!route.query.tab) {
    router.replace({ path: '/reports', query: { ...route.query, tab: 'reports' } });
  }
  if (window.Chart) {
    // Trends Chart
    const trendsCtx = document.getElementById('trendsChart');
    if (trendsCtx) {
      new window.Chart(trendsCtx, {
        type: 'line',
        data: {
          labels: ['Aug 10', 'Aug 11', 'Aug 12', 'Aug 13', 'Aug 14', 'Aug 15', 'Aug 16'],
          datasets: [
            {
              label: 'Gross Sales',
              data: [42000, 38000, 55000, 48000, 62000, 85000, 95000],
              borderColor: '#2563eb',
              backgroundColor: 'rgba(37, 99, 235, 0.08)',
              borderWidth: 2.5,
              tension: 0.35,
              fill: true,
              pointRadius: 4,
              pointBackgroundColor: '#2563eb'
            },
            {
              label: 'Estimated Profit',
              data: [8400, 7600, 11000, 9600, 12400, 17000, 19000],
              borderColor: '#10b981',
              backgroundColor: 'transparent',
              borderWidth: 2.5,
              borderDash: [4, 4],
              tension: 0.35,
              pointRadius: 4,
              pointBackgroundColor: '#10b981'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: { 
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return context.dataset.label + ': PKR ' + context.parsed.y.toLocaleString();
                }
              }
            }
          },
          scales: {
            y: { 
              beginAtZero: true, 
              grid: { color: '#f1f5f9' },
              ticks: {
                callback: function(val) { return 'Rs ' + (val / 1000) + 'k'; }
              }
            },
            x: { 
              grid: { display: false } 
            }
          }
        }
      });
    }

    // Category Doughnut Chart
    const catCtx = document.getElementById('categoryChart');
    if (catCtx) {
      new window.Chart(catCtx, {
        type: 'doughnut',
        data: {
          labels: ['Grocery (42%)', 'Beverages (23%)', 'Snacks (18%)', 'Household (11%)', 'Other (6%)'],
          datasets: [{
            data: [42, 23, 18, 11, 6],
            backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#94a3b8'],
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { 
              position: 'right', 
              labels: { 
                boxWidth: 10, 
                padding: 10,
                font: { size: 11, family: 'Inter' } 
              } 
            }
          },
          cutout: '72%'
        }
      });
    }
  }
});
</script>

<style scoped>
.reports-page-wrapper {
  width: 100%;
  min-height: 100%;
  background: #f8fafc;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: #0f172a;
}

.reports-main-container {
  padding: 24px 32px;
}

/* TOPBAR */
.reports-topbar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
}

.reports-badge-pill {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  padding: 2px 8px;
  border-radius: 6px;
  background: #e0e7ff;
  color: #4338ca;
}

.reports-main-title {
  font-size: 1.65rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0 0 4px 0;
}

.reports-main-sub {
  font-size: 0.88rem;
  color: #64748b;
  margin: 0;
  max-width: 700px;
}

.reports-filter-select {
  height: 38px;
  padding: 0 32px 0 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 13.5px;
  font-weight: 600;
  color: #334155;
  background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 10px center;
  appearance: none;
  cursor: pointer;
  outline: none;
}

/* 5 KPI GRID */
.reports-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.reports-kpi-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 18px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.reports-kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
}

.kpi-label {
  font-size: 11.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
}

.kpi-icon-pill {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}

.bg-purple-subtle { background-color: #f3e8ff !important; }
.text-purple { color: #7e22ce !important; }

.kpi-number {
  font-size: 1.4rem;
  font-weight: 800;
  line-height: 1.2;
}

.trend-badge {
  display: inline-flex;
  align-items: center;
  font-size: 11.5px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 6px;
}

.trend-up {
  background: #ecfdf5;
  color: #059669;
}

.trend-down {
  background: #fef2f2;
  color: #dc2626;
}

.kpi-subtext {
  font-size: 11.5px;
}

/* PANELS */
.reports-panel-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
  overflow: hidden;
}

.reports-panel-header {
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  background: #ffffff;
}

.reports-panel-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}

.legend-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

/* ALERTS & HEALTH */
.alert-badge {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  padding: 2px 6px;
  border-radius: 4px;
}

.badge-danger { background: #fee2e2; color: #b91c1c; }
.badge-warning { background: #fef3c7; color: #b45309; }
.badge-success { background: #dcfce7; color: #15803d; }

.reorder-callout-card {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 12px;
}

.health-row, .metric-list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* STATUS PILLS */
.stock-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 8px;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 600;
}

.pill-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.badge-in {
  background: #ecfdf5;
  color: #059669;
}
.badge-in .pill-dot { background: #10b981; }

.badge-warning {
  background: #fffbeb;
  color: #d97706;
}
.badge-warning .pill-dot { background: #f59e0b; }
</style>
