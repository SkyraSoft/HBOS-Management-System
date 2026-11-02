<template>
<div class="reorder-page-wrapper">


            <div class="main-inner">


                <!-- =================================================
                     PAGE HEADER
                ================================================== -->

                <section class="page-header" id="smart-reorder">

                    <div>

                        <h2 class="page-title">
                            Smart Reorder
                        </h2>

                        <p class="page-description">
                            Identify products that need attention and optimize your inventory reorder activity.
                        </p>

                    </div>


                    <div class="header-actions">

                        <button type="button" class="icon-button" title="Notifications">
                            <i class="bi bi-bell"></i>
                        </button>

                        <button type="button" class="icon-button" title="History">
                            <i class="bi bi-clock-history"></i>
                        </button>

                        <button type="button" class="profile-button" title="Profile">
                            <i class="bi bi-person"></i>
                        </button>

                    </div>

                </section>


                <!-- =================================================
                     TABS
                ================================================== -->

                <!-- =================================================
                     TABS & PERIOD SELECTOR (SRT-GAP-014)
                ================================================== -->

                <nav class="reorder-tabs" aria-label="Reorder navigation" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">

                    <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                        <button class="reorder-tab" :class="{ active: activeTab === 'smart' }" type="button" @click="activeTab = 'smart'">
                            Smart Reorder & Deficits ({{ summary.reorder_analytics?.total_requiring_reorder || 0 }})
                        </button>

                        <button class="reorder-tab" :class="{ active: activeTab === 'velocity' }" type="button" @click="activeTab = 'velocity'">
                            Sales Velocity Rankings ({{ summary.sales_velocity?.products_count || 0 }})
                        </button>

                        <button class="reorder-tab" :class="{ active: activeTab === 'dead' }" type="button" @click="activeTab = 'dead'">
                            Dead Stock & Frozen Capital ({{ summary.dead_stock?.dead_stock_count || 0 }})
                        </button>
                    </div>

                    <!-- Velocity & Dead Stock Analysis Window Selector (SRT-GAP-014) -->
                    <div class="period-selector" style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 13px; color: #64748b; font-weight: 600;">Window:</span>
                        <button 
                            type="button" 
                            v-for="d in [30, 60, 90]" 
                            :key="d"
                            @click="selectedDays = d"
                            :style="{
                                padding: '4px 10px',
                                borderRadius: '6px',
                                border: '1px solid ' + (selectedDays === d ? 'var(--primary, #2448b8)' : '#cbd0df'),
                                background: selectedDays === d ? 'var(--primary, #2448b8)' : '#fff',
                                color: selectedDays === d ? '#fff' : '#4f5260',
                                fontSize: '12px',
                                fontWeight: selectedDays === d ? '700' : '500',
                                cursor: 'pointer'
                            }"
                        >
                            {{ d }} Days
                        </button>
                    </div>

                </nav>


                <!-- =================================================
                     KPI CARDS
                ================================================== -->

                <section class="kpi-grid">

                    <article class="kpi-card">
                        <div class="kpi-icon blue">
                            <i class="bi bi-box"></i>
                        </div>
                        <div class="kpi-label">
                            Requiring Reorder
                        </div>
                        <div class="kpi-value">
                            {{ summary.reorder_analytics?.total_requiring_reorder || 0 }}
                        </div>
                    </article>

                    <article class="kpi-card">
                        <div class="kpi-icon red">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="kpi-label">
                            Critical (Out of Stock)
                        </div>
                        <div class="kpi-value">
                            {{ summary.reorder_analytics?.critical_count || 0 }}
                        </div>
                    </article>

                    <article class="kpi-card">
                        <div class="kpi-icon orange">
                            <i class="bi bi-graph-down-arrow"></i>
                        </div>
                        <div class="kpi-label">
                            Total Deficit Units
                        </div>
                        <div class="kpi-value">
                            {{ formatQty(summary.reorder_analytics?.total_deficit_units || 0) }}
                        </div>
                    </article>

                    <article class="kpi-card" v-if="canViewCost">
                        <div class="kpi-icon money">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div class="kpi-label">
                            Estimated Reorder Value
                        </div>
                        <div class="kpi-value">
                            PKR {{ formatNumber(summary.reorder_analytics?.estimated_reorder_value || 0) }}
                        </div>
                    </article>

                    <article class="kpi-card" v-if="canViewCost">
                        <div class="kpi-icon money" style="background: #fef2f2; color: #dc2626;">
                            <i class="bi bi-snow"></i>
                        </div>
                        <div class="kpi-label">
                            Dead Stock Frozen Capital
                        </div>
                        <div class="kpi-value">
                            PKR {{ formatNumber(summary.dead_stock?.total_locked_capital || 0) }}
                        </div>
                    </article>

                </section>


                <!-- =================================================
                     FILTERS
                ================================================== -->

                <section class="filter-card">

                    <div class="filter-row">

                        <div class="search-box" style="flex: 1;">
                            <i class="bi bi-search"></i>
                            <input type="search" class="search-input" v-model="searchQuery" placeholder="Search by Product Name, SKU, or Branch...">
                        </div>

                        <button class="filter-button" type="button" @click="fetchStockIntelligence" :disabled="isLoading">
                            <i class="bi bi-arrow-clockwise" :class="{ 'fa-spin': isLoading }"></i>
                            {{ isLoading ? 'Refreshing...' : 'Refresh' }}
                        </button>

                    </div>

                </section>


                <!-- =================================================
                     TAB 1: SMART REORDER & DEFICITS TABLE (SRT-GAP-014)
                ================================================== -->

                <section class="table-card" v-if="activeTab === 'smart'">

                    <div class="table-wrapper">

                        <table class="reorder-table">

                            <thead>

                                <tr>
                                    <th>Product</th>
                                    <th>Branch</th>
                                    <th style="text-align: right;">Current Stock</th>
                                    <th style="text-align: right;">Threshold / Min</th>
                                    <th style="text-align: right;">Reorder Deficit</th>
                                    <th>Explainable Formula Breakdown</th>
                                    <th v-if="canViewCost" style="text-align: right;">Est. Reorder Cost</th>
                                    <th style="text-align: center;">Priority</th>
                                    <th style="text-align: center;">Action</th>
                                </tr>

                            </thead>

                            <tbody>

                                <tr v-for="item in filteredReorderItems" :key="item.inventory_id">

                                    <td class="product-name">
                                        <div style="font-weight: 600;">{{ item.product_name }}</div>
                                        <small style="color: #64748b; font-size: 11px;">SKU: {{ item.sku }} | Unit: {{ item.unit }}</small>
                                    </td>

                                    <td>{{ item.branch_name }}</td>

                                    <td style="text-align: right;" :class="item.is_out_of_stock ? 'stock-danger' : 'stock-warning'">
                                        {{ formatQty(item.quantity_on_hand) }}
                                    </td>

                                    <td style="text-align: right;">{{ formatQty(item.threshold) }}</td>

                                    <td style="text-align: right; font-weight: 700; color: #dc2626;">
                                        {{ formatQty(item.deficit) }}
                                    </td>

                                    <td>
                                        <span class="formula-chip" style="font-size: 11px; background: #f1f5f9; padding: 3px 6px; border-radius: 4px; font-family: monospace; color: #334155;">
                                            {{ item.formula_explanation }}
                                        </span>
                                    </td>

                                    <td v-if="canViewCost" style="text-align: right; font-weight: 600;">
                                        PKR {{ formatNumber(item.estimated_reorder_cost) }}
                                    </td>

                                    <td style="text-align: center;">
                                        <span class="priority" :class="item.is_out_of_stock ? 'high' : 'medium'">
                                            {{ item.is_out_of_stock ? 'Critical (Out of Stock)' : 'Needs Reorder' }}
                                        </span>
                                    </td>

                                    <td style="text-align: center;">
                                        <button class="view-button" type="button" @click="createPurchaseOrder" title="Create Purchase Order">
                                            + Order
                                        </button>
                                    </td>

                                </tr>

                                <tr v-if="filteredReorderItems.length === 0">
                                    <td :colspan="canViewCost ? 9 : 8" style="text-align: center; padding: 28px; color: #64748b;">
                                        {{ isLoading ? 'Loading stock intelligence...' : 'No products requiring reorder in this view.' }}
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </section>


                <!-- =================================================
                     TAB 2: SALES VELOCITY RANKINGS TABLE (SRT-GAP-014)
                ================================================== -->

                <section class="table-card" v-else-if="activeTab === 'velocity'">

                    <div class="table-wrapper">

                        <table class="reorder-table">

                            <thead>

                                <tr>
                                    <th style="width: 60px; text-align: center;">Rank</th>
                                    <th>Product</th>
                                    <th style="text-align: right;">Units Sold ({{ selectedDays }}d)</th>
                                    <th style="text-align: right;">Daily Velocity</th>
                                    <th style="text-align: right;">Transactions</th>
                                    <th style="text-align: right;">Total Revenue</th>
                                </tr>

                            </thead>

                            <tbody>

                                <tr v-for="item in filteredVelocityItems" :key="item.product_id">

                                    <td style="text-align: center; font-weight: 700;">
                                        <span style="display: inline-block; width: 26px; height: 26px; line-height: 26px; border-radius: 50%; background: #e0e7ff; color: #4338ca; font-size: 12px;">
                                            #{{ item.rank }}
                                        </span>
                                    </td>

                                    <td class="product-name">
                                        <div style="font-weight: 600;">{{ item.product_name }}</div>
                                        <small style="color: #64748b; font-size: 11px;">SKU: {{ item.sku }} | Unit: {{ item.unit }}</small>
                                    </td>

                                    <td style="text-align: right; font-weight: 600;">
                                        {{ formatQty(item.total_sold_quantity) }}
                                    </td>

                                    <td style="text-align: right; color: #059669; font-weight: 600;">
                                        {{ item.daily_velocity }} / day
                                    </td>

                                    <td style="text-align: right;">
                                        {{ item.transactions_count }}
                                    </td>

                                    <td style="text-align: right; font-weight: 700;">
                                        PKR {{ formatNumber(item.total_revenue) }}
                                    </td>

                                </tr>

                                <tr v-if="filteredVelocityItems.length === 0">
                                    <td colspan="6" style="text-align: center; padding: 28px; color: #64748b;">
                                        {{ isLoading ? 'Analyzing sales velocity...' : 'No sales recorded in the selected ' + selectedDays + '-day window.' }}
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </section>


                <!-- =================================================
                     TAB 3: DEAD STOCK & FROZEN CAPITAL TABLE (SRT-GAP-014)
                ================================================== -->

                <section class="table-card" v-else-if="activeTab === 'dead'">

                    <div class="table-wrapper">

                        <table class="reorder-table">

                            <thead>

                                <tr>
                                    <th>Product</th>
                                    <th>Branch</th>
                                    <th style="text-align: right;">Quantity on Hand</th>
                                    <th style="text-align: center;">Inactivity Window</th>
                                    <th v-if="canViewCost" style="text-align: right;">Cost Price</th>
                                    <th v-if="canViewCost" style="text-align: right;">Frozen Capital</th>
                                    <th style="text-align: center;">Action</th>
                                </tr>

                            </thead>

                            <tbody>

                                <tr v-for="item in filteredDeadStockItems" :key="item.inventory_id">

                                    <td class="product-name">
                                        <div style="font-weight: 600;">{{ item.product_name }}</div>
                                        <small style="color: #64748b; font-size: 11px;">SKU: {{ item.sku }} | Unit: {{ item.unit }}</small>
                                    </td>

                                    <td>{{ item.branch_name }}</td>

                                    <td style="text-align: right; font-weight: 600;">
                                        {{ formatQty(item.quantity_on_hand) }}
                                    </td>

                                    <td style="text-align: center;">
                                        <span style="background: #fee2e2; color: #b91c1c; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                            0 sales in {{ item.days_without_sale }} days
                                        </span>
                                    </td>

                                    <td v-if="canViewCost" style="text-align: right;">
                                        PKR {{ formatNumber(item.cost_price) }}
                                    </td>

                                    <td v-if="canViewCost" style="text-align: right; font-weight: 700; color: #b91c1c;">
                                        PKR {{ formatNumber(item.locked_capital) }}
                                    </td>

                                    <td style="text-align: center;">
                                        <button class="view-button" type="button" @click="createPurchaseOrder" title="Stock Transfer / Discount Action">
                                            Action
                                        </button>
                                    </td>

                                </tr>

                                <tr v-if="filteredDeadStockItems.length === 0">
                                    <td :colspan="canViewCost ? 7 : 5" style="text-align: center; padding: 28px; color: #64748b;">
                                        {{ isLoading ? 'Checking inactive inventory...' : 'No dead stock detected in the selected ' + selectedDays + '-day window.' }}
                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </section>


            </div>

        
</div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/api.js';

const router = useRouter();
const activeTab = ref('smart'); // 'smart', 'velocity', 'dead'
const selectedDays = ref(30); // 30, 60, 90
const searchQuery = ref('');
const isLoading = ref(false);
const errorMsg = ref('');

const summary = ref({
    reorder_analytics: {
        total_requiring_reorder: 0,
        critical_count: 0,
        total_deficit_units: 0,
        estimated_reorder_value: 0,
        items: []
    },
    sales_velocity: {
        days: 30,
        products_count: 0,
        rankings: []
    },
    dead_stock: {
        days: 30,
        dead_stock_count: 0,
        total_dead_quantity: 0,
        total_locked_capital: 0,
        items: []
    }
});

const user = JSON.parse(localStorage.getItem('user') || '{}');
const isCashier = user.role === 'Cashier' || (user.roles && user.roles.some(r => r.name === 'Cashier'));
const canViewCost = !isCashier;

const formatNumber = (val) => Number(val || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatQty = (val) => Number(val || 0).toLocaleString("en-US", { minimumFractionDigits: 0, maximumFractionDigits: 3 });

const fetchStockIntelligence = async () => {
    isLoading.value = true;
    errorMsg.value = '';
    try {
        const res = await api.get(`/inventory?intelligence=1&days=${selectedDays.value}`);
        summary.value = res.data;
    } catch (e) {
        console.error(e);
        errorMsg.value = e.response?.data?.message || 'Failed to load stock intelligence data.';
    } finally {
        isLoading.value = false;
    }
};

watch(selectedDays, () => {
    fetchStockIntelligence();
});

const filteredReorderItems = computed(() => {
    const items = summary.value?.reorder_analytics?.items || [];
    if (!searchQuery.value.trim()) return items;
    const q = searchQuery.value.toLowerCase().trim();
    return items.filter(it => 
        (it.product_name && it.product_name.toLowerCase().includes(q)) ||
        (it.sku && it.sku.toLowerCase().includes(q)) ||
        (it.branch_name && it.branch_name.toLowerCase().includes(q))
    );
});

const filteredVelocityItems = computed(() => {
    const items = summary.value?.sales_velocity?.rankings || [];
    if (!searchQuery.value.trim()) return items;
    const q = searchQuery.value.toLowerCase().trim();
    return items.filter(it => 
        (it.product_name && it.product_name.toLowerCase().includes(q)) ||
        (it.sku && it.sku.toLowerCase().includes(q))
    );
});

const filteredDeadStockItems = computed(() => {
    const items = summary.value?.dead_stock?.items || [];
    if (!searchQuery.value.trim()) return items;
    const q = searchQuery.value.toLowerCase().trim();
    return items.filter(it => 
        (it.product_name && it.product_name.toLowerCase().includes(q)) ||
        (it.sku && it.sku.toLowerCase().includes(q)) ||
        (it.branch_name && it.branch_name.toLowerCase().includes(q))
    );
});

const createPurchaseOrder = () => {
    router.push('/purchase?new=true');
};

onMounted(() => {
    fetchStockIntelligence();
});
</script>

<style scoped>

        /* =========================================================
           ROOT
        ========================================================= */

        :root {
            --primary: #2448b8;
            --primary-dark: #193a9c;
            --primary-soft: #dce8ff;

            --bg: #faf8ff;
            --surface: #ffffff;
            --border: #cbd0df;

            --text: #20212a;
            --muted: #555866;

            --success: #10a96e;
            --warning: #f4a300;
            --danger: #d62828;

            --sidebar-width: 374px;
            --radius: 10px;
        }


        /* =========================================================
           GLOBAL
        ========================================================= */

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family:
                Inter,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        button,
        input,
        select {
            font: inherit;
        }

        a {
            text-decoration: none;
        }


        /* =========================================================
           APP LAYOUT
        ========================================================= */

        .app {
            min-height: 100vh;
            display: flex;
        }


        /* =========================================================
           SIDEBAR
        ========================================================= */

        .sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            min-height: 100vh;

            background: #fbf9ff;
            border-right: 1px solid var(--border);

            display: flex;
            flex-direction: column;

            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1050;
        }

        .brand {
            min-height: 128px;
            padding: 28px 54px;

            display: flex;
            align-items: center;
            gap: 16px;

            border-bottom: 1px solid var(--border);
        }

        .brand-logo {
            width: 52px;
            height: 52px;

            border-radius: 50%;
            background: var(--primary);

            color: #fff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 18px;
            font-weight: 700;

            flex-shrink: 0;
        }

        .brand-info h1 {
            margin: 0;

            color: #16439f;
            font-size: 22px;
            line-height: 1.2;
            font-weight: 700;
        }

        .brand-info p {
            margin: 5px 0 0;

            color: #555866;
            font-size: 14px;
        }


        /* Sidebar navigation */

        .sidebar-navigation {
            padding-top: 0;
        }

        .sidebar-link {
            min-height: 64px;
            padding: 0 24px;

            display: flex;
            align-items: center;
            gap: 18px;

            color: #555866;
            font-size: 17px;
            font-weight: 500;

            border-left: 4px solid transparent;

            transition:
                background-color .2s ease,
                color .2s ease,
                border-color .2s ease;
        }

        .sidebar-link i {
            width: 24px;
            font-size: 21px;
            text-align: center;
        }

        .sidebar-link:hover {
            color: var(--primary);
            background: #f0f4ff;
        }

        .sidebar-link.active {
            color: #536580;
            background: #c9dcfb;
            border-left-color: var(--primary);
            font-weight: 700;
        }


        /* Sidebar bottom */

        .sidebar-bottom {
            margin-top: auto;
            padding: 16px 18px 28px;

            border-top: 1px solid var(--border);
        }

        .system-status {
            min-height: 40px;
            padding: 0 14px;

            display: flex;
            align-items: center;
            gap: 8px;

            background: #eeecf8;
            border-radius: 8px;

            color: #52617c;
            font-size: 13px;
            font-weight: 600;

            margin-bottom: 14px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #15b978;
            flex-shrink: 0;
        }

        .bottom-link {
            min-height: 48px;
            padding: 0 24px;

            display: flex;
            align-items: center;
            gap: 16px;

            color: #555866;
            font-size: 16px;
        }

        .bottom-link i {
            font-size: 20px;
        }

        .bottom-link:hover {
            color: var(--primary);
        }


        /* =========================================================
           MAIN
        ========================================================= */

        .main {
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            margin-left: var(--sidebar-width);

            padding: 74px 32px 60px;
        }

        .main-inner {
            width: 100%;
            max-width: 1320px;
            margin: 0 auto;
        }


        /* =========================================================
           MOBILE HEADER
        ========================================================= */

        .mobile-header {
            display: none;
        }


        /* =========================================================
           PAGE HEADER
        ========================================================= */

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;

            margin-bottom: 24px;
        }

        .page-title {
            margin: 0;

            font-size: 42px;
            line-height: 1.08;
            font-weight: 700;
            letter-spacing: -1.3px;
        }

        .page-description {
            margin: 10px 0 0;

            color: #4e5260;
            font-size: 17px;
            line-height: 1.5;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .icon-button {
            width: 42px;
            height: 42px;

            border: 0;
            background: transparent;

            color: #25334f;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;
        }

        .profile-button {
            width: 38px;
            height: 38px;

            border: 1px solid var(--border);
            border-radius: 50%;

            background: #f5f6fb;

            color: #5a6270;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 18px;
        }


        /* =========================================================
           TABS
        ========================================================= */

        .reorder-tabs {
            display: flex;
            align-items: center;
            gap: 38px;

            margin-bottom: 30px;

            border-bottom: 1px solid transparent;
        }

        .reorder-tab {
            position: relative;

            padding: 8px 0;

            border: 0;
            background: transparent;

            color: #4f5260;

            font-size: 17px;
            font-weight: 500;

            white-space: nowrap;

            cursor: pointer;
        }

        .reorder-tab::after {
            content: "";

            position: absolute;
            left: 0;
            right: 0;
            bottom: -3px;

            height: 3px;

            background: transparent;
            border-radius: 3px;
        }

        .reorder-tab.active {
            color: #20212a;
            font-weight: 700;
        }

        .reorder-tab.active::after {
            background: var(--primary);
        }


        /* =========================================================
           KPI CARDS
        ========================================================= */

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 30px;

            margin-bottom: 46px;
        }

        .kpi-card {
            min-height: 152px;

            padding: 22px 20px;

            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);

            position: relative;
        }

        .kpi-label {
            padding-right: 48px;

            color: #535767;
            font-size: 14px;
            line-height: 1.35;
            font-weight: 500;
            letter-spacing: .4px;
            text-transform: uppercase;
        }

        .kpi-value {
            margin-top: 24px;

            font-size: 34px;
            line-height: 1;
            font-weight: 500;
            letter-spacing: -.5px;
        }

        .kpi-icon {
            position: absolute;
            top: 22px;
            right: 20px;

            width: 38px;
            height: 38px;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 17px;
        }

        .kpi-icon.blue {
            color: var(--primary);
            background: #e8efff;
        }

        .kpi-icon.red {
            color: #e22b2b;
            background: #ffe2e2;
        }

        .kpi-icon.orange {
            color: #db7b1b;
            background: #ffe7d2;
        }

        .kpi-icon.money {
            color: var(--primary);
            background: #e2ecff;
        }


        /* =========================================================
           DISTRIBUTION
        ========================================================= */

        .distribution-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);

            padding: 26px 24px 28px;

            margin-bottom: 46px;
        }

        .section-title {
            margin: 0 0 16px;

            font-size: 19px;
            font-weight: 600;
        }

        .distribution-bar {
            height: 13px;

            display: flex;

            overflow: hidden;

            border-radius: 8px;

            background: #e5e5eb;
        }

        .distribution-segment {
            height: 100%;

            border-right: 1px solid rgba(255, 255, 255, .9);
        }

        .distribution-segment:last-child {
            border-right: 0;
        }

        .critical {
            width: 15%;
            background: #c61c1c;
        }

        .needs-reorder {
            width: 25%;
            background: #6d2400;
        }

        .monitor {
            width: 20%;
            background: #f7a400;
        }

        .healthy {
            width: 40%;
            background: #13b77d;
        }

        .distribution-labels {
            display: grid;
            grid-template-columns: 15% 25% 20% 40%;
            margin-top: 12px;

            color: #535767;
            font-size: 13px;
            font-weight: 600;
        }

        .distribution-label {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .distribution-label:nth-child(2),
        .distribution-label:nth-child(3) {
            justify-content: center;
        }

        .distribution-label:last-child {
            justify-content: flex-end;
        }

        .legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }


        /* =========================================================
           FILTER BAR
        ========================================================= */

        .filter-card {
            padding: 16px;

            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);

            margin-bottom: 24px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: minmax(260px, 1fr) repeat(4, auto);
            gap: 10px;
            align-items: center;
        }

        .search-box {
            position: relative;
        }

        .search-box i {
            position: absolute;
            top: 50%;
            left: 14px;

            transform: translateY(-50%);

            color: #596070;
            font-size: 20px;
        }

        .search-input {
            width: 100%;
            height: 42px;

            padding: 0 14px 0 42px;

            border: 1px solid #c6cad8;
            border-radius: 7px;

            outline: none;

            background: #fbfaff;
            color: var(--text);
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(36, 72, 184, .1);
        }

        .filter-button {
            height: 42px;

            padding: 0 16px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;

            border: 1px solid #c6cad8;
            border-radius: 7px;

            background: #fbfaff;
            color: #292b35;

            font-size: 14px;
            font-weight: 600;

            white-space: nowrap;
        }

        .filter-button:hover {
            border-color: var(--primary);
            color: var(--primary);
        }


        /* =========================================================
           TABLE
        ========================================================= */

        .table-card {
            background: #fff;

            border: 1px solid var(--border);
            border-radius: var(--radius);

            overflow: hidden;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .reorder-table {
            width: 100%;
            min-width: 980px;

            margin: 0;

            border-collapse: collapse;
        }

        .reorder-table thead th {
            padding: 14px 14px;

            background: #f5f3fc;

            border-bottom: 1px solid var(--border);

            color: #505466;

            font-size: 12px;
            line-height: 1.3;
            font-weight: 700;

            text-align: center;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .reorder-table tbody td {
            padding: 17px 14px;

            border-bottom: 1px solid #e0e2ea;

            color: #4c5060;

            font-size: 14px;

            text-align: center;
            vertical-align: middle;
        }

        .reorder-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .product-name {
            color: #20212a;
            font-weight: 600;
            text-align: left !important;
        }

        .stock-danger {
            color: #d71919 !important;
        }

        .stock-warning {
            color: #7a2a0a !important;
        }

        .days-danger {
            color: #d71919 !important;
            font-weight: 700;
        }

        .days-warning {
            color: #7a2a0a !important;
            font-weight: 700;
        }

        .suggested-qty {
            color: #0d399e !important;
            font-weight: 700;
        }

        .priority {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-width: 43px;
            min-height: 40px;

            padding: 0 10px;

            border-radius: 5px;

            font-size: 12px;
            font-weight: 700;
        }

        .priority.high {
            color: #a91616;
            background: #ffdcdc;
        }

        .priority.medium {
            color: #6b2b15;
            background: #ffe1d5;
        }

        .view-button {
            border: 0;
            background: transparent;

            color: #07399e;

            font-size: 14px;
            font-weight: 700;
        }

        .view-button:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }


        /* =========================================================
           PAGINATION
        ========================================================= */

        .pagination-area {
            min-height: 56px;

            padding: 0 20px;

            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;

            border-top: 1px solid var(--border);
        }

        .page-button {
            width: 34px;
            height: 34px;

            border: 0;
            border-radius: 5px;

            background: transparent;
            color: #222733;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 14px;
        }

        .page-button:hover {
            background: #edf1ff;
        }

        .page-button.active {
            background: var(--primary);
            color: #fff;
        }


        /* =========================================================
           MOBILE OVERLAY
        ========================================================= */

        .sidebar-overlay {
            display: none;
        }


        /* =========================================================
           RESPONSIVE - LARGE TABLET
        ========================================================= */

        @media (max-width: 1300px) {

            :root {
                --sidebar-width: 300px;
            }

            .brand {
                padding-left: 32px;
                padding-right: 32px;
            }

            .main {
                padding-left: 28px;
                padding-right: 28px;
            }

            .kpi-grid {
                gap: 18px;
            }

            .filter-row {
                grid-template-columns: 1fr 1fr 1fr;
            }

            .search-box {
                grid-column: 1 / -1;
            }
        }


        /* =========================================================
           RESPONSIVE - TABLET
        ========================================================= */

        @media (max-width: 992px) {

            :root {
                --sidebar-width: 270px;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform .25s ease;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay {
                position: fixed;
                inset: 0;
                z-index: 1040;

                background: rgba(17, 24, 39, .25);
            }

            .sidebar-overlay.show {
                display: block;
            }

            .main {
                width: 100%;
                margin-left: 0;

                padding-top: 78px;
            }

            .mobile-header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1030;

                height: 62px;

                padding: 0 18px;

                display: flex;
                align-items: center;
                justify-content: space-between;

                background: #fff;
                border-bottom: 1px solid var(--border);
            }

            .mobile-brand {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .mobile-brand-logo {
                width: 38px;
                height: 38px;

                border-radius: 50%;
                background: var(--primary);

                color: #fff;

                display: flex;
                align-items: center;
                justify-content: center;

                font-size: 14px;
                font-weight: 700;
            }

            .mobile-brand-text {
                font-size: 17px;
                font-weight: 700;
            }

            .mobile-menu-button {
                width: 40px;
                height: 40px;

                border: 1px solid var(--border);
                border-radius: 7px;

                background: #fff;
                color: #303440;

                display: flex;
                align-items: center;
                justify-content: center;

                font-size: 21px;
            }

            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .page-title {
                font-size: 36px;
            }

            .distribution-labels {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .distribution-label,
            .distribution-label:nth-child(2),
            .distribution-label:nth-child(3),
            .distribution-label:last-child {
                justify-content: flex-start;
            }
        }


        /* =========================================================
           RESPONSIVE - MOBILE
        ========================================================= */

        @media (max-width: 767px) {

            .main {
                padding: 80px 16px 40px;
            }

            .page-header {
                flex-direction: column;
                gap: 12px;
            }

            .page-title {
                font-size: 31px;
            }

            .page-description {
                font-size: 15px;
            }

            .header-actions {
                position: absolute;
                top: 74px;
                right: 16px;
            }

            .page-header {
                padding-right: 80px;
            }

            .reorder-tabs {
                width: 100%;

                gap: 24px;

                margin-top: 28px;
                margin-bottom: 24px;

                overflow-x: auto;
                scrollbar-width: none;

                padding-bottom: 7px;
            }

            .reorder-tabs::-webkit-scrollbar {
                display: none;
            }

            .reorder-tab {
                font-size: 15px;
            }

            .kpi-grid {
                grid-template-columns: 1fr;
                gap: 14px;

                margin-bottom: 28px;
            }

            .kpi-card {
                min-height: 138px;
            }

            .distribution-card {
                padding: 20px 16px;
                margin-bottom: 28px;
            }

            .distribution-labels {
                grid-template-columns: 1fr;
            }

            .filter-card {
                padding: 12px;
            }

            .filter-row {
                grid-template-columns: 1fr;
            }

            .search-box {
                grid-column: auto;
            }

            .filter-button {
                width: 100%;
                justify-content: space-between;
            }

            .pagination-area {
                justify-content: center;
                padding: 8px;
            }
        }


        /* =========================================================
           SMALL MOBILE
        ========================================================= */

        @media (max-width: 480px) {

            .main {
                padding-left: 12px;
                padding-right: 12px;
            }

            .page-title {
                font-size: 28px;
            }

            .header-actions {
                gap: 6px;
            }

            .icon-button {
                width: 34px;
                height: 34px;
            }

            .profile-button {
                width: 34px;
                height: 34px;
            }

            .reorder-tabs {
                gap: 20px;
            }

            .kpi-value {
                font-size: 30px;
            }

            .distribution-bar {
                height: 11px;
            }
        }
    

</style>
