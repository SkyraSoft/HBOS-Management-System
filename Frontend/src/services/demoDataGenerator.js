/**
 * HBOS Demo Data Generator & Rolling 7-Month Timeline Engine
 * 
 * Provides authentic, mathematically reconciled retail simulation data spanning 7 continuous months:
 * - 6 previous complete calendar months (M-6 through M-1)
 * - Current month-to-date (M0 up to today's date/time)
 * 
 * Includes the Dynamic Date-Shifting Engine:
 * When a user opens the demo in future months (e.g. 5 months later, or in 2027),
 * the engine automatically shifts the data forward so the demo always feels like
 * the past 7 months up to today.
 * 
 * STRICT ISOLATION:
 * Runs strictly inside the client demo sandbox. Never mutates live server databases.
 */

import manifest from '../../../docs/release/hbos_demo_fixture_manifest.json' with { type: 'json' };
import { 
  DEMO_PRODUCTS, 
  DEMO_CUSTOMERS, 
  DEMO_SUPPLIERS, 
  generateBranchInventories 
} from './demoCatalogMaster.js';

/**
 * Format a Date object to local YYYY-MM-DD string without UTC shifting.
 */
export function formatLocalDate(d) {
  if (!d) return '';
  const date = (d instanceof Date) ? d : new Date(d);
  if (isNaN(date.getTime())) return String(d);
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

const PRODUCTS = DEMO_PRODUCTS;
const CUSTOMERS = DEMO_CUSTOMERS;
const SUPPLIERS = DEMO_SUPPLIERS;

// Preset retail shopping baskets typical for Pakistani grocery retail
const BASKET_TEMPLATES = [
  // Basket 1: Morning Tea & Breakfast
  [
    { product_id: 14, qty: 1 }, // Khyber Gold Danedar Tea 450g
    { product_id: 23, qty: 2 }, // Indus Pure UHT Milk 1L
  ],
  // Basket 2: Rice & Dal Cooking Essentials
  [
    { product_id: 1, qty: 2 }, // DailyHarvest Super Basmati Rice 1kg
    { product_id: 6, qty: 1 }, // DailyHarvest Dal Chana 1kg
    { product_id: 7, qty: 1 }, // DailyHarvest Dal Masoor 1kg
  ],
  // Basket 3: Cooking Oil & Spices
  [
    { product_id: 31, qty: 2 }, // Indus Gold Canola Cooking Oil 1L
    { product_id: 19, qty: 2 }, // Khyber Bombay Biryani Masala 50g
    { product_id: 22, qty: 1 }, // Khyber Golden Turmeric Haldi Powder 200g
  ],
  // Basket 4: Personal Care & Cleaning
  [
    { product_id: 35, qty: 3 }, // PakClean Floral Glow Beauty Soap 140g
    { product_id: 37, qty: 1 }, // PakClean Anti-Dandruff Herbal Shampoo 360ml
    { product_id: 40, qty: 1 }, // PakClean Ultra White Washing Powder 1kg
  ],
  // Basket 5: Family Monthly Groceries
  [
    { product_id: 2, qty: 1 },  // DailyHarvest Super Basmati Rice 5kg
    { product_id: 9, qty: 1 },  // DailyHarvest Chakki Whole Wheat Atta 10kg
    { product_id: 28, qty: 2 }, // Indus Premium Banaspati Ghee 1kg Pouch
    { product_id: 15, qty: 1 }, // Khyber Gold Danedar Tea 900g
    { product_id: 23, qty: 4 }, // Indus Pure UHT Full Cream Milk 1L
    { product_id: 17, qty: 1 }, // Khyber Traditional Rooh-e-Sharbat Syrup 800ml
  ],
  // Basket 6: Quick Daily Refreshment
  [
    { product_id: 17, qty: 1 }, // Khyber Traditional Rooh-e-Sharbat
    { product_id: 23, qty: 1 }, // Indus Pure UHT Full Cream Milk
  ],
  // Basket 7: Afternoon Tea Pantry Restock
  [
    { product_id: 16, qty: 1 }, // Khyber Royal Peshawari Green Tea Kahwa 250g
    { product_id: 25, qty: 2 }, // Indus Fresh Heavy Dairy Cream 200ml
    { product_id: 11, qty: 2 }, // DailyHarvest Refined White Sugar 1kg
  ]
];

/**
 * Generate 7 continuous months of realistic sales, expenses, and payments.
 * @param {'single' | 'multi'} mode 
 * @param {Date} baseDate - Anchor date (defaults to current runtime date)
 */
export function generate7MonthDemoDataset(mode = 'single', baseDate = new Date()) {
  const isMulti = mode === 'multi';
  const now = baseDate || new Date();
  const currentYear = now.getFullYear();
  const currentMonth = now.getMonth();
  const currentDay = now.getDate();
  const currentHour = now.getHours();

  const sales = [];
  const expenses = [];
  const customerPayments = [];
  const supplierPayments = [];

  // Clone template products with initial stock
  const products = JSON.parse(JSON.stringify(PRODUCTS));
  // In demo, keep healthy stock levels
  products.forEach(p => {
    p.stock = isMulti ? p.stock * 3 : p.stock;
  });

  const customers = JSON.parse(JSON.stringify(CUSTOMERS));
  const suppliers = JSON.parse(JSON.stringify(SUPPLIERS));

  let saleCounter = 1000;
  let expCounter = 500;
  let custPayCounter = 300;
  let suppPayCounter = 200;

  // Generate for 7 months: offset m = -6 (6 months ago) up to m = 0 (current month)
  for (let m = -6; m <= 0; m++) {
    const targetDate = new Date(currentYear, currentMonth + m, 1);
    const year = targetDate.getFullYear();
    const month = targetDate.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // In current month (m === 0), only generate up to today
    const maxDay = (m === 0) ? Math.min(currentDay, daysInMonth) : daysInMonth;

    // Daily Sales Generation
    for (let day = 1; day <= maxDay; day++) {
      const dayOfWeek = new Date(year, month, day).getDay(); // 0 = Sun, 5 = Fri, 6 = Sat
      const isWeekend = (dayOfWeek === 0 || dayOfWeek === 5 || dayOfWeek === 6);

      // Determine number of sales for this day
      // Representative retail footfall: single-store ~7-9 sales/day, multi-store ~12-16 sales/day across 3 branches
      const baseDailySalesCount = isMulti 
        ? (isWeekend ? 16 : 12) 
        : (isWeekend ? 9 : 7);

      for (let s = 0; s < baseDailySalesCount; s++) {
        // If today and hour is past current hour, skip
        const saleHour = 9 + Math.floor((s / baseDailySalesCount) * 12) + (s % 2);
        if (m === 0 && day === maxDay && saleHour > currentHour) {
          continue;
        }

        const saleMinute = (s * 17 + day * 7) % 60;
        const saleDate = new Date(year, month, day, saleHour, saleMinute, 0);
        const isoDate = saleDate.toISOString();
        const dateStr = formatLocalDate(saleDate);

        // Select basket pattern
        const basketIndex = (day * 3 + s) % BASKET_TEMPLATES.length;
        const basketTemplate = BASKET_TEMPLATES[basketIndex];

        let saleSubtotal = 0;
        let saleCogs = 0;
        const saleItems = [];

        basketTemplate.forEach(itemDef => {
          const prod = products.find(p => p.id === itemDef.product_id);
          if (prod) {
            // Supermarket multi-store customers buy higher volume retail grocery baskets
            const qty = isMulti ? itemDef.qty * 2 : itemDef.qty;
            const unitPrice = prod.selling_price;
            const costPrice = prod.cost_price;
            const subtotal = unitPrice * qty;
            const cogs = costPrice * qty;

            saleSubtotal += subtotal;
            saleCogs += cogs;

            saleItems.push({
              product_id: prod.id,
              product_name: prod.name,
              sku: prod.sku,
              quantity: qty,
              unit_price: unitPrice,
              cost_price: costPrice,
              subtotal: subtotal,
              cogs: cogs
            });
          }
        });

        // Determine branch (in multi-store: Branch 1 ~50%, Branch 2 ~30%, Branch 3 ~20%)
        let branchId = 1;
        if (isMulti) {
          const brRand = (saleCounter + day) % 10;
          if (brRand < 5) branchId = 1;      // Clifton Main Store
          else if (brRand < 8) branchId = 2; // Saddar Express
          else branchId = 3;                 // Gulshan Outlet
        }

        // Determine payment method & customer
        // 65% cash, 20% khata, 15% card
        const payTypeMod = (saleCounter + day * 2) % 10;
        let paymentMethod = 'cash';
        let customerId = null;
        let customerName = 'Walk-in Customer';
        let paidAmount = saleSubtotal;
        let dueAmount = 0;

        if (payTypeMod === 7 || payTypeMod === 8) {
          // Khata Credit sale to regular customer
          paymentMethod = 'khata';
          const custIdx = (saleCounter + s) % customers.length;
          const cust = customers[custIdx];
          customerId = cust.id;
          customerName = cust.name;
          paidAmount = 0;
          dueAmount = saleSubtotal;
        } else if (payTypeMod === 9) {
          paymentMethod = 'card';
          paidAmount = saleSubtotal;
          dueAmount = 0;
        }

        const saleRecord = {
          id: `demo_sale_${saleCounter++}`,
          invoice_number: `INV-${year}${String(month + 1).padStart(2, '0')}-${String(saleCounter).slice(-4)}`,
          branch_id: branchId,
          customer_id: customerId,
          customer_name: customerName,
          date: dateStr,
          created_at: isoDate,
          total_amount: saleSubtotal,
          total: saleSubtotal,
          cogs: saleCogs,
          paid_amount: paidAmount,
          due_amount: dueAmount,
          payment_method: paymentMethod,
          status: 'completed',
          items: saleItems
        };

        sales.push(saleRecord);
      }
    }

    // Monthly Expenses Generation
    const addExpense = (day, category, description, singleAmount, multiAmounts) => {
      if (m === 0 && day > maxDay) return;

      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const isoDate = new Date(year, month, day, 11, 0, 0).toISOString();

      if (isMulti) {
        // Multi-store: generate expense per branch
        const branches = [
          { id: 1, amount: multiAmounts[0] },
          { id: 2, amount: multiAmounts[1] },
          { id: 3, amount: multiAmounts[2] },
        ];
        branches.forEach(b => {
          expenses.push({
            id: `demo_exp_${expCounter++}`,
            branch_id: b.id,
            category: category,
            description: `${description} (${b.id === 1 ? 'Clifton' : b.id === 2 ? 'Saddar' : 'Gulshan'})`,
            amount: b.amount,
            date: dateStr,
            payment_method: 'cash',
            created_at: isoDate
          });
        });
      } else {
        expenses.push({
          id: `demo_exp_${expCounter++}`,
          branch_id: 1,
          category: category,
          description: description,
          amount: singleAmount,
          date: dateStr,
          payment_method: 'cash',
          created_at: isoDate
        });
      }
    };

    // 1. Shop Rent (Day 2)
    addExpense(2, 'Shop Rent', 'Monthly Retail Outlet Lease', 18000, [35000, 24000, 20000]);

    // 2. Staff Salaries (Day 5)
    addExpense(5, 'Staff Salaries', 'Sales Cashier & Floor Staff Salary', 20000, [38000, 26000, 22000]);

    // 3. Electricity / WAPDA (Day 12)
    const elecVariance = (month % 3) * 800;
    addExpense(12, 'Electricity / WAPDA', 'K-Electric Commercial Electricity Bill', 8500 + elecVariance, [16000 + elecVariance, 11000 + elecVariance, 9500]);

    // 4. Packaging & Carry Bags (Day 18)
    addExpense(18, 'Packaging & Bags', 'Custom Grocery Shopper Bags & Cartons', 2500, [4500, 3000, 2500]);

    // 5. Shop Cleaning & Maintenance (Day 24)
    addExpense(24, 'Shop Maintenance', 'Pest Control & General Shop Upkeep', 1800, [3200, 2200, 1800]);

    // Customer Debt Payments (Khata Recoveries)
    const addCustPayment = (day, custIdx, amount) => {
      if (m === 0 && day > maxDay) return;
      const cust = customers[custIdx];
      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const isoDate = new Date(year, month, day, 16, 30, 0).toISOString();
      customerPayments.push({
        id: `demo_cust_pay_${custPayCounter++}`,
        customer_id: cust.id,
        customer_name: cust.name,
        amount: amount,
        payment_method: 'cash',
        date: dateStr,
        created_at: isoDate,
        notes: `Khata installment payment received`
      });
    };

    addCustPayment(8, 0, 1500);  // Tariq Mehmood
    addCustPayment(15, 1, 2000); // Haji Abdul Rehman
    addCustPayment(22, 2, 1200); // Chaudhry Aslam
    addCustPayment(27, 3, 1000); // Mohammad Imran

    // Supplier Invoice Payments
    const addSuppPayment = (day, suppIdx, amount) => {
      if (m === 0 && day > maxDay) return;
      const supp = suppliers[suppIdx];
      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const isoDate = new Date(year, month, day, 14, 0, 0).toISOString();
      supplierPayments.push({
        id: `demo_supp_pay_${suppPayCounter++}`,
        supplier_id: supp.id,
        supplier_name: supp.name,
        amount: amount,
        payment_method: 'cash',
        date: dateStr,
        created_at: isoDate,
        notes: `Wholesale restock payment`
      });
    };

    addSuppPayment(7, 0, isMulti ? 35000 : 12000);  // Metro
    addSuppPayment(17, 1, isMulti ? 25000 : 9000);  // Karachi Grains
    addSuppPayment(26, 2, isMulti ? 20000 : 8000);  // Unilever
  }

  // Calculate opening drawer cash realistically
  const baseDrawerCash = isMulti ? 35000.00 : 18500.00;

  // Sort newest first
  sales.sort((a, b) => new Date(b.created_at || b.date) - new Date(a.created_at || a.date));
  expenses.sort((a, b) => new Date(b.created_at || b.date) - new Date(a.created_at || a.date));

  return {
    sales,
    expenses,
    customer_payments: customerPayments,
    supplier_payments: supplierPayments,
    products,
    branch_inventories: isMulti ? generateBranchInventories(products) : [],
    customers,
    suppliers,
    drawer_cash: baseDrawerCash,
    anchor_year: currentYear,
    anchor_month: currentMonth,
    is_7_month_dataset: true,
  };
}

/**
 * Shifts an ISO or YYYY-MM-DD date forward by deltaMonths.
 * If shifted date lands in current calendar month and exceeds maxDayCurrentMonth,
 * wraps or scales the day within 1..maxDayCurrentMonth.
 */
export function shiftDateStringByMonths(dateStr, deltaMonths, currentYear, currentMonth, maxDayCurrentMonth, currentHour = 23) {
  if (!dateStr) return dateStr;
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;

  const originalDay = d.getDate();
  const hours = d.getHours();
  const minutes = d.getMinutes();
  const seconds = d.getSeconds();

  const currentTotalMonths = d.getFullYear() * 12 + d.getMonth();
  const targetTotalMonths = currentTotalMonths + deltaMonths;

  const targetYear = Math.floor(targetTotalMonths / 12);
  const targetMonth = targetTotalMonths % 12;

  const daysInTargetMonth = new Date(targetYear, targetMonth + 1, 0).getDate();
  let targetDay = Math.min(originalDay, daysInTargetMonth);
  let targetHour = hours;

  // If shifted into current month and exceeds today's date or hour:
  if (targetYear === currentYear && targetMonth === currentMonth && maxDayCurrentMonth) {
    if (targetDay > maxDayCurrentMonth) {
      targetDay = ((targetDay - 1) % maxDayCurrentMonth) + 1;
    }
    if (targetDay === maxDayCurrentMonth && targetHour > currentHour) {
      targetHour = Math.max(0, currentHour - 1);
    }
  }

  const newDate = new Date(targetYear, targetMonth, targetDay, targetHour, minutes, seconds);
  return newDate.toISOString();
}

/**
 * Dynamic Date Shifting Engine ("Moves or Attributed to Next Month")
 * 
 * Automatically shifts existing demo state forward when time has progressed.
 * If a user opens the demo months or years later, it automatically translates
 * the 7-month dataset forward so the latest month is always the user's current month.
 */
export function autoRollDemoData(state, mode = 'single', currentDate = new Date()) {
  const now = currentDate || new Date();
  const currentYear = now.getFullYear();
  const currentMonth = now.getMonth();
  const currentDay = now.getDate();

  // If state is unpopulated or from a legacy version before 7-month dataset (< 500 sales),
  // refresh immediately to full 7-month dataset
  if (!state.is_7_month_dataset || !state.sales || state.sales.length < 500) {
    const fresh = generate7MonthDemoDataset(mode, now);
    state.sales = fresh.sales;
    state.expenses = fresh.expenses;
    state.customer_payments = fresh.customer_payments;
    state.supplier_payments = fresh.supplier_payments;
    state.drawer_cash = fresh.drawer_cash;
    state.anchor_year = currentYear;
    state.anchor_month = currentMonth;
    state.is_7_month_dataset = true;
    return state;
  }

  let anchorYear = state.anchor_year !== undefined ? Number(state.anchor_year) : null;
  let anchorMonth = state.anchor_month !== undefined ? Number(state.anchor_month) : null;

  if (anchorYear === null || anchorMonth === null) {
    let maxTs = 0;
    for (const s of state.sales) {
      const t = new Date(s.created_at || s.date).getTime();
      if (!isNaN(t) && t > maxTs) maxTs = t;
    }
    if (maxTs > 0) {
      const latestD = new Date(maxTs);
      anchorYear = latestD.getFullYear();
      anchorMonth = latestD.getMonth();
    } else {
      anchorYear = currentYear;
      anchorMonth = currentMonth;
    }
  }

  const deltaMonths = (currentYear * 12 + currentMonth) - (anchorYear * 12 + anchorMonth);

  // If already anchored to the current month
  if (deltaMonths === 0) {
    // Check if within current month, new days have elapsed
    let maxRecordedDay = 0;
    for (const s of state.sales) {
      const d = new Date(s.created_at || s.date);
      if (d.getFullYear() === currentYear && d.getMonth() === currentMonth) {
        if (d.getDate() > maxRecordedDay) maxRecordedDay = d.getDate();
      }
    }

    if (currentDay > maxRecordedDay && maxRecordedDay > 0) {
      // Top up sales for elapsed days (maxRecordedDay + 1) through currentDay
      const fresh = generate7MonthDemoDataset(mode, now);
      const topUpSales = fresh.sales.filter(s => {
        const d = new Date(s.created_at || s.date);
        return d.getFullYear() === currentYear && d.getMonth() === currentMonth && d.getDate() > maxRecordedDay && d.getDate() <= currentDay;
      });
      const topUpExpenses = fresh.expenses.filter(e => {
        const d = new Date(e.created_at || e.date);
        return d.getFullYear() === currentYear && d.getMonth() === currentMonth && d.getDate() > maxRecordedDay && d.getDate() <= currentDay;
      });

      if (topUpSales.length > 0) {
        state.sales.unshift(...topUpSales);
        state.sales.sort((a, b) => new Date(b.created_at || b.date) - new Date(a.created_at || a.date));
      }
      if (topUpExpenses.length > 0) {
        state.expenses.unshift(...topUpExpenses);
        state.expenses.sort((a, b) => new Date(b.created_at || b.date) - new Date(a.created_at || a.date));
      }
    }
    state.anchor_year = currentYear;
    state.anchor_month = currentMonth;
    state.is_7_month_dataset = true;
    return state;
  }

  // If delta is 7 months or more, or negative (clock skew), regenerate clean fresh 7-month dataset anchored to today
  if (deltaMonths >= 7 || deltaMonths < 0) {
    const fresh = generate7MonthDemoDataset(mode, now);
    state.sales = fresh.sales;
    state.expenses = fresh.expenses;
    state.customer_payments = fresh.customer_payments;
    state.supplier_payments = fresh.supplier_payments;
    state.drawer_cash = fresh.drawer_cash;
    state.anchor_year = currentYear;
    state.anchor_month = currentMonth;
    state.is_7_month_dataset = true;
    return state;
  }

  // If 1 <= deltaMonths <= 6:
  // Move all existing transactions forward by deltaMonths months!
  const shiftRecord = (rec) => {
    if (rec.created_at) {
      rec.created_at = shiftDateStringByMonths(rec.created_at, deltaMonths, currentYear, currentMonth, currentDay, now.getHours());
      rec.date = formatLocalDate(rec.created_at);
    }
  };

  state.sales.forEach(shiftRecord);
  state.expenses.forEach(shiftRecord);
  (state.customer_payments || []).forEach(shiftRecord);
  (state.supplier_payments || []).forEach(shiftRecord);

  // Prune any records that shifted older than 7 months (prior to Month -6)
  const earliestAllowed = new Date(currentYear, currentMonth - 6, 1);
  state.sales = state.sales.filter(s => new Date(s.created_at || s.date) >= earliestAllowed);
  state.expenses = state.expenses.filter(e => new Date(e.created_at || e.date) >= earliestAllowed);
  state.customer_payments = (state.customer_payments || []).filter(p => new Date(p.created_at || p.date) >= earliestAllowed);
  state.supplier_payments = (state.supplier_payments || []).filter(p => new Date(p.created_at || p.date) >= earliestAllowed);

  // If needed, top up gap months at the tail (months -6 down to -6 + deltaMonths)
  // to ensure all 7 months always have rich transaction history
  const fresh = generate7MonthDemoDataset(mode, now);
  const existingMonths = new Set(state.sales.map(s => {
    const d = new Date(s.created_at || s.date);
    return `${d.getFullYear()}-${d.getMonth()}`;
  }));

  fresh.sales.forEach(s => {
    const d = new Date(s.created_at);
    const key = `${d.getFullYear()}-${d.getMonth()}`;
    if (!existingMonths.has(key)) {
      state.sales.push(s);
    }
  });

  fresh.expenses.forEach(e => {
    const d = new Date(e.created_at);
    const key = `${d.getFullYear()}-${d.getMonth()}`;
    if (!existingMonths.has(key)) {
      state.expenses.push(e);
    }
  });

  // Sort sales and expenses newest first
  state.sales.sort((a, b) => new Date(b.created_at || b.date) - new Date(a.created_at || a.date));
  state.expenses.sort((a, b) => new Date(b.created_at || b.date) - new Date(a.created_at || a.date));

  // Update anchor coordinates
  state.anchor_year = currentYear;
  state.anchor_month = currentMonth;
  state.is_7_month_dataset = true;

  return state;
}

/**
 * Filter records by date preset matching DashboardService.php logic.
 */
export function resolvePresetBoundaries(preset = 'this_month', customFrom = null, customTo = null, baseDate = new Date()) {
  const now = baseDate || new Date();
  const year = now.getFullYear();
  const month = now.getMonth();
  const day = now.getDate();

  let startDate, endDate;
  let prevStartDate, prevEndDate;

  switch (preset) {
    case 'today':
      startDate = new Date(year, month, day, 0, 0, 0);
      endDate = new Date(year, month, day, 23, 59, 59);
      prevStartDate = new Date(year, month, day - 1, 0, 0, 0);
      prevEndDate = new Date(year, month, day - 1, 23, 59, 59);
      break;
    case 'yesterday':
      startDate = new Date(year, month, day - 1, 0, 0, 0);
      endDate = new Date(year, month, day - 1, 23, 59, 59);
      prevStartDate = new Date(year, month, day - 2, 0, 0, 0);
      prevEndDate = new Date(year, month, day - 2, 23, 59, 59);
      break;
    case 'last_7_days':
      startDate = new Date(year, month, day - 6, 0, 0, 0);
      endDate = new Date(year, month, day, 23, 59, 59);
      prevStartDate = new Date(year, month, day - 13, 0, 0, 0);
      prevEndDate = new Date(year, month, day - 7, 23, 59, 59);
      break;
    case 'last_30_days':
      startDate = new Date(year, month, day - 29, 0, 0, 0);
      endDate = new Date(year, month, day, 23, 59, 59);
      prevStartDate = new Date(year, month, day - 59, 0, 0, 0);
      prevEndDate = new Date(year, month, day - 30, 23, 59, 59);
      break;
    case 'last_month':
    case 'previous_month':
      startDate = new Date(year, month - 1, 1, 0, 0, 0);
      endDate = new Date(year, month, 0, 23, 59, 59);
      prevStartDate = new Date(year, month - 2, 1, 0, 0, 0);
      prevEndDate = new Date(year, month - 1, 0, 23, 59, 59);
      break;
    case 'last_3_months':
      startDate = new Date(year, month - 2, 1, 0, 0, 0);
      endDate = new Date(year, month, day, 23, 59, 59);
      prevStartDate = new Date(year, month - 5, 1, 0, 0, 0);
      prevEndDate = new Date(year, month - 2, 0, 23, 59, 59);
      break;
    case 'last_6_months':
      startDate = new Date(year, month - 5, 1, 0, 0, 0);
      endDate = new Date(year, month, day, 23, 59, 59);
      prevStartDate = new Date(year, month - 11, 1, 0, 0, 0);
      prevEndDate = new Date(year, month - 5, 0, 23, 59, 59);
      break;
    case 'this_year':
      startDate = new Date(year, 0, 1, 0, 0, 0);
      endDate = new Date(year, month, day, 23, 59, 59);
      prevStartDate = new Date(year - 1, 0, 1, 0, 0, 0);
      prevEndDate = new Date(year - 1, month, day, 23, 59, 59);
      break;
    case 'last_year':
      startDate = new Date(year - 1, 0, 1, 0, 0, 0);
      endDate = new Date(year - 1, 11, 31, 23, 59, 59);
      prevStartDate = new Date(year - 2, 0, 1, 0, 0, 0);
      prevEndDate = new Date(year - 2, 11, 31, 23, 59, 59);
      break;
    case 'all':
    case 'all_time':
      startDate = new Date(1970, 0, 1, 0, 0, 0);
      endDate = new Date(year, month, day, 23, 59, 59);
      prevStartDate = new Date(1970, 0, 1, 0, 0, 0);
      prevEndDate = new Date(year, month, day, 23, 59, 59);
      break;
    case 'custom':
      if (customFrom && customTo) {
        startDate = new Date(`${customFrom}T00:00:00`);
        endDate = new Date(`${customTo}T23:59:59`);
        const diffMs = endDate - startDate;
        prevEndDate = new Date(startDate.getTime() - 1000);
        prevStartDate = new Date(prevEndDate.getTime() - diffMs);
        break;
      }
      // Fallback to this_month
    case 'this_month':
    default:
      startDate = new Date(year, month, 1, 0, 0, 0);
      endDate = new Date(year, month, day, 23, 59, 59);
      prevStartDate = new Date(year, month - 1, 1, 0, 0, 0);
      prevEndDate = new Date(year, month, 0, 23, 59, 59);
      break;
  }

  return { startDate, endDate, prevStartDate, prevEndDate };
}
