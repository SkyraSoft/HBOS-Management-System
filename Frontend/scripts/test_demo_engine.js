// Unit test for demoSandboxEngine dynamic 7-month rolling window and date shifting
class LocalStorageMock {
  constructor() {
    this.store = {};
  }
  getItem(key) {
    return this.store[key] || null;
  }
  setItem(key, value) {
    this.store[key] = String(value);
  }
  removeItem(key) {
    delete this.store[key];
  }
  clear() {
    this.store = {};
  }
}

globalThis.localStorage = new LocalStorageMock();
globalThis.window = { localStorage: globalThis.localStorage };

import { demoSandboxEngine } from '../src/services/demoSandboxEngine.js';

console.log('--- Testing Single-Store Demo Initialization ---');
demoSandboxEngine.setMode('single');
const singleState = demoSandboxEngine.getState();
console.log(`Single-store initialized. Branch count: ${singleState.branches.length}`);
console.log(`Total Sales: ${singleState.sales.length}`);
console.log(`Total Expenses: ${singleState.expenses.length}`);

// Check 7-month date span of generated sales
const saleDates = singleState.sales.map(s => s.created_at.substring(0, 7));
const uniqueMonths = [...new Set(saleDates)].sort();
console.log(`Unique months in sales:`, uniqueMonths);

if (uniqueMonths.length !== 7) {
  console.error(`FAIL: Expected 7 unique months, got ${uniqueMonths.length}`);
  process.exit(1);
} else {
  console.log(`PASS: Exact 7 rolling months generated!`);
}

// Check Dashboard metrics for various presets
console.log('\n--- Testing Dashboard Metrics (Single-Store) ---');
const presets = ['this_month', 'last_month', 'last_3_months', 'last_6_months', 'this_year'];
for (const preset of presets) {
  const dash = demoSandboxEngine.getDashboard({ preset });
  console.log(`Preset [${preset}]: Net Sales = PKR ${dash.sales.net_sales.toLocaleString()}, Gross Profit = PKR ${dash.gross_profit.amount.toLocaleString()}, Expenses = PKR ${dash.expenses.total.toLocaleString()}`);
  if (dash.sales.net_sales <= 0) {
    console.error(`FAIL: net_sales should be > 0 for preset ${preset}`);
    process.exit(1);
  }
}
console.log('PASS: All presets return rich positive sales and profit metrics!');

// Check dynamic date-shifting: Simulate returning 5 months in the future
console.log('\n--- Testing Dynamic Date-Shifting (Time-travel 5 months forward) ---');
// Tamper with anchor date and transactions by shifting them 5 months back
const stored = JSON.parse(localStorage.getItem('HBOS_Store_Biz_demo_single'));
const pastDate = new Date();
pastDate.setMonth(pastDate.getMonth() - 5);
stored.anchor_year = pastDate.getFullYear();
stored.anchor_month = pastDate.getMonth();

// Shift all sales 5 months back
for (const s of stored.sales) {
  const d = new Date(s.created_at);
  d.setMonth(d.getMonth() - 5);
  s.created_at = d.toISOString();
}
localStorage.setItem('HBOS_Store_Biz_demo_single', JSON.stringify(stored));

// Call setMode or getState - ensure auto-shifting detects the 5-month gap
demoSandboxEngine.setMode('single');
const refreshedEngineState = demoSandboxEngine.getState();
const refreshedSaleDates = refreshedEngineState.sales.map(s => s.created_at.substring(0, 7));
const refreshedUniqueMonths = [...new Set(refreshedSaleDates)].sort();
console.log(`Months after automatic recalibration:`, refreshedUniqueMonths);

const currentMonthStr = `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}`;
if (!refreshedUniqueMonths.includes(currentMonthStr)) {
  console.error(`FAIL: Shifted state does not include current month ${currentMonthStr}`);
  process.exit(1);
} else {
  console.log(`PASS: Auto-recalibration shifted historical sales to include ${currentMonthStr}!`);
}

// Test Multi-Store
console.log('\n--- Testing Multi-Store Demo Initialization ---');
demoSandboxEngine.setMode('multi');
const multiState = demoSandboxEngine.getState();
console.log(`Multi-store initialized. Branch count: ${multiState.branches.length}`);
console.log(`Total Sales: ${multiState.sales.length}`);
console.log(`Total Transfers: ${multiState.transfers.length}`);
console.log(`Total Expenses: ${multiState.expenses.length}`);

const multiDash = demoSandboxEngine.getDashboard({ preset: 'this_month' });
console.log(`Multi-Store [this_month]: Net Sales = PKR ${multiDash.sales.net_sales.toLocaleString()}, Branches = ${multiDash.branch_comparison.length}`);
if (multiDash.branch_comparison.length < 2) {
  console.error(`FAIL: Multi-store should have at least 2 branches in branch_comparison`);
  process.exit(1);
}
console.log('PASS: Multi-store verified!');

console.log('\nALL DEMO SANDBOX TESTS PASSED SUCCESSFULLY!');
