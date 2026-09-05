<template>
<div class="reorderdetail-page-wrapper">


            <!-- ==========================================
             TOP BAR
        ========================================== -->

            <header class="topbar">

                <div class="topbar-left">

                    <button class="menu-btn" onclick="toggleMobileMenu()">
                        <i class="bi bi-list"></i>
                    </button>

                    <div class="topbar-title">
                        Smart Reorder Intelligence
                    </div>

                </div>

            </header>


            <!-- ==========================================
             PAGE
        ========================================== -->

            <div class="page">

                <!-- MODULE HEADER -->

                <div class="module-header">

                    <h1 class="module-title">
                        Smart Reorder Intelligence
                    </h1>

                    <p class="module-subtitle">
                        Monitor stock levels and review intelligent
                        reorder recommendations.
                    </p>

                </div>


                <!-- ======================================
                 THREE INTERNAL PAGES
            ======================================= -->

                <nav class="module-tabs">

                    <button id="tab-recommendations" class="module-tab active" onclick="showRecommendations()">
                        Reorder Recommendations
                    </button>

                    <button id="tab-details" class="module-tab" onclick="showDetails()">
                        Reorder Recommendation Details
                    </button>

                    <button id="tab-review" class="module-tab" onclick="showReview()">
                        Review Reorder
                    </button>

                </nav>


                <!-- PAGE CONTENT -->

                <div id="pageContent"></div>

            </div>

        
</div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
  console.log('ReorderDetailView mounted');
});
</script>

<style scoped>

        :root {
            --primary: #3156c8;
            --primary-dark: #2447b5;

            --text: #20232d;
            --muted: #697184;

            --border: #dfe3eb;
            --bg: #f7f8fb;
            --white: #ffffff;

            --danger: #c92323;
            --danger-bg: #ffe0dc;

            --soft-blue: #f3f5ff;
            --soft-purple: #f8f6ff;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            background: var(--bg);
            color: var(--text);

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;
        }

        button {
            font-family: inherit;
        }

        /* ==========================================
           APP
        ========================================== */

        .app {
            min-height: 100vh;
        }

        .main-content {
            min-height: 100vh;
            width: 100%;
        }

        /* ==========================================
           TOP NAV
        ========================================== */

        .topbar {
            height: 70px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 30px;

            background: #fff;
            border-bottom: 1px solid var(--border);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-title {
            font-size: 18px;
            font-weight: 700;
        }

        .menu-btn {
            display: none;

            width: 40px;
            height: 40px;

            border: 0;
            background: transparent;

            font-size: 22px;
            cursor: pointer;
        }

        /* ==========================================
           PAGE CONTAINER
        ========================================== */

        .page {
            width: 100%;
            max-width: 1400px;

            margin: 0 auto;

            padding: 30px;
        }

        /* ==========================================
           MODULE HEADER
        ========================================== */

        .module-header {
            margin-bottom: 25px;
        }

        .module-title {
            margin: 0;

            font-size: 28px;
            font-weight: 700;
            letter-spacing: -.5px;
        }

        .module-subtitle {
            margin: 6px 0 0;

            color: var(--muted);

            font-size: 14px;
        }

        /* ==========================================
           MODULE TABS
        ========================================== */

        .module-tabs {
            display: flex;
            align-items: center;

            gap: 5px;

            margin-bottom: 25px;

            padding: 5px;

            width: fit-content;

            border: 1px solid var(--border);
            border-radius: 9px;

            background: #fff;
        }

        .module-tab {
            border: 0;
            border-radius: 6px;

            padding: 10px 17px;

            background: transparent;
            color: #606879;

            font-size: 13px;
            font-weight: 600;

            cursor: pointer;

            transition: .2s ease;
        }

        .module-tab:hover {
            background: #f4f6fa;
        }

        .module-tab.active {
            background: #eef2ff;
            color: var(--primary);
        }

        /* ==========================================
           CARDS
        ========================================== */

        .card {
            background: var(--white);

            border: 1px solid var(--border);
            border-radius: 9px;

            box-shadow:
                0 1px 2px rgba(20, 25, 40, .03);
        }

        /* ==========================================
           RECOMMENDATION SUMMARY
        ========================================== */

        .summary-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 18px;

            margin-bottom: 22px;
        }

        .summary-card {
            padding: 20px;
        }

        .summary-label {
            color: #697184;

            font-size: 11px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .summary-value {
            margin-top: 8px;

            font-size: 29px;
            font-weight: 700;
        }

        .summary-value.red {
            color: var(--danger);
        }

        .summary-value.blue {
            color: var(--primary);
        }

        /* ==========================================
           TABLE
        ========================================== */

        .table-card {
            overflow: hidden;
        }

        .table-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 20px 22px;

            border-bottom: 1px solid var(--border);
        }

        .table-title {
            margin: 0;

            font-size: 18px;
            font-weight: 700;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .recommendation-table {
            width: 100%;
            min-width: 900px;

            border-collapse: collapse;
        }

        .recommendation-table th {
            padding: 14px 20px;

            background: #fafbfc;

            color: #697184;

            font-size: 11px;
            font-weight: 700;

            text-align: left;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .recommendation-table td {
            padding: 17px 20px;

            border-top: 1px solid #edf0f4;

            font-size: 14px;
        }

        .recommendation-table tbody tr:hover {
            background: #fafbff;
        }

        .product-name {
            font-weight: 600;
        }

        .product-sku {
            margin-top: 3px;

            color: var(--muted);

            font-size: 12px;
        }

        /* ==========================================
           BADGES
        ========================================== */

        .critical-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;

            padding: 5px 9px;

            border-radius: 999px;

            background: #ffe4e4;
            color: #c52222;

            font-size: 11px;
            font-weight: 700;
        }

        .warning-badge {
            padding: 5px 9px;

            border-radius: 999px;

            background: #fff3d7;
            color: #a46a00;

            font-size: 11px;
            font-weight: 700;
        }

        .success-badge {
            padding: 5px 9px;

            border-radius: 999px;

            background: #e7f8ed;
            color: #21834a;

            font-size: 11px;
            font-weight: 700;
        }

        /* ==========================================
           BUTTONS
        ========================================== */

        .btn-primary-custom {
            min-height: 42px;

            padding: 0 22px;

            border: 1px solid var(--primary);
            border-radius: 7px;

            background: var(--primary);
            color: #fff;

            font-size: 14px;
            font-weight: 600;

            cursor: pointer;

            transition: .2s ease;
        }

        .btn-primary-custom:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-outline-custom {
            min-height: 42px;

            padding: 0 22px;

            border: 1px solid #d0d5df;
            border-radius: 7px;

            background: #fff;
            color: #252832;

            font-size: 14px;
            font-weight: 500;

            cursor: pointer;

            transition: .2s ease;
        }

        .btn-outline-custom:hover {
            background: #f5f6f8;
        }

        .detail-button {
            padding: 0;

            border: 0;
            background: transparent;

            color: var(--primary);

            font-size: 13px;
            font-weight: 600;

            cursor: pointer;
        }

        /* ==========================================
           DETAIL PAGE HEADER
        ========================================== */

        .detail-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;
        }

        .detail-title-wrapper {
            display: flex;
            align-items: center;

            gap: 15px;
        }

        .back-button {
            width: 42px;
            height: 42px;
            min-width: 42px;

            display: flex;
            align-items: center;
            justify-content: center;

            border: 1px solid #ccd2de;
            border-radius: 7px;

            background: #fff;
            color: #343946;

            font-size: 20px;

            cursor: pointer;
        }

        .back-button:hover {
            background: #f5f6f9;
        }

        .detail-title {
            margin: 0;

            font-size: 25px;
            font-weight: 700;
        }

        .detail-subtitle {
            margin: 5px 0 0;

            color: var(--muted);

            font-size: 14px;
        }

        .detail-actions {
            display: flex;
            gap: 10px;
        }

        /* ==========================================
           RISK ALERT
        ========================================== */

        .risk-alert {
            display: flex;
            align-items: flex-start;

            gap: 12px;

            padding: 17px 18px;

            margin-bottom: 23px;

            border: 1px solid #ffb4ac;
            border-radius: 6px;

            background: var(--danger-bg);
            color: #ae1f1f;
        }

        .risk-alert-icon {
            font-size: 20px;
        }

        .risk-alert-title {
            margin-bottom: 4px;

            font-size: 15px;
            font-weight: 700;
        }

        .risk-alert-text {
            font-size: 14px;
            line-height: 1.5;
        }

        /* ==========================================
           DETAIL TOP GRID
        ========================================== */

        .detail-top-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 2fr) minmax(320px, 1fr);

            gap: 22px;

            margin-bottom: 22px;
        }

        .stock-card {
            padding: 24px;
        }

        .stock-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));
        }

        .stock-item {
            padding: 0 24px;

            border-right: 1px solid #d4d9e3;
        }

        .stock-item:first-child {
            padding-left: 0;
        }

        .stock-item:last-child {
            padding-right: 0;
            border-right: 0;
        }

        .field-label {
            margin-bottom: 7px;

            color: #555c6c;

            font-size: 11px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .field-value {
            color: #252832;

            font-size: 18px;
            font-weight: 600;
        }

        .danger-number {
            color: var(--danger);

            font-size: 34px;
            line-height: 1;

            font-weight: 700;
        }

        .stock-divider {
            height: 1px;

            margin: 25px 0;

            background: #d9dde6;
        }

        .stock-bottom {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 25px;
        }

        /* ==========================================
           WHY CARD
        ========================================== */

        .why-card {
            padding: 25px;
        }

        .section-title {
            display: flex;
            align-items: center;

            gap: 8px;

            margin: 0 0 20px;

            font-size: 18px;
            font-weight: 700;
        }

        .section-title i {
            color: var(--primary);
            font-size: 20px;
        }

        .why-text {
            margin-bottom: 25px;

            color: #525968;

            font-size: 15px;
            line-height: 1.65;
        }

        .target-box {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            padding: 17px;

            border: 1px dashed #c9c6df;
            border-radius: 8px;

            background: var(--soft-purple);
        }

        .target-label {
            color: #555b6b;

            font-size: 11px;
            font-weight: 700;

            letter-spacing: .4px;
        }

        .target-value {
            font-size: 18px;
            font-weight: 700;
        }

        /* ==========================================
           DETAIL BOTTOM GRID
        ========================================== */

        .detail-bottom-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 1fr) minmax(0, 1fr);

            gap: 22px;
        }

        .section-card {
            overflow: hidden;
        }

        .section-card-header {
            display: flex;
            align-items: center;

            gap: 9px;

            padding: 22px 24px;

            border-bottom: 1px solid var(--border);

            background: #fbf9ff;
        }

        .section-card-header i {
            font-size: 19px;
        }

        .section-card-header h2 {
            margin: 0;

            font-size: 18px;
            font-weight: 700;
        }

        /* ==========================================
           SUPPLIER
        ========================================== */

        .supplier-body {
            padding: 20px 24px 24px;
        }

        .supplier-row {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            padding: 15px 0;

            border-bottom: 1px solid #d9dde6;
        }

        .supplier-row:last-of-type {
            border-bottom: 0;
        }

        .supplier-label {
            color: #555c6c;

            font-size: 15px;
        }

        .supplier-value {
            color: #252832;

            font-size: 15px;
            font-weight: 600;

            text-align: right;
        }

        .supplier-value.red {
            color: #d52525;
        }

        .suggested-box {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;

            margin-top: 15px;
            padding: 17px;

            border: 1px solid #d4dcff;
            border-radius: 8px;

            background: var(--soft-blue);
        }

        .suggested-label {
            margin-bottom: 5px;

            color: #50576a;

            font-size: 11px;
            font-weight: 700;

            letter-spacing: .4px;
        }

        .suggested-note {
            color: #50576a;

            font-size: 15px;
        }

        .suggested-number {
            color: #2e50ba;

            font-size: 34px;
            line-height: 1;

            font-weight: 700;

            white-space: nowrap;
        }

        .suggested-number small {
            color: #4c5363;

            font-size: 14px;
            font-weight: 500;
        }

        /* ==========================================
           CHART
        ========================================== */

        .chart-body {
            height: 310px;

            display: flex;
            flex-direction: column;

            padding: 24px 24px 0;
        }

        .velocity-grid {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 25px;
        }

        .velocity-label {
            margin-bottom: 7px;

            color: #555d6e;

            font-size: 11px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .velocity-number {
            font-size: 34px;
            line-height: 1;

            font-weight: 700;
        }

        .velocity-number span {
            color: #4f5665;

            font-size: 14px;
            font-weight: 500;
        }

        .trend-up {
            color: #315ed5 !important;
            font-size: 17px !important;
        }

        .chart {
            width: 100%;
            height: 190px;

            margin-top: auto;
        }

        .chart svg {
            display: block;

            width: 100%;
            height: 100%;
        }

        .chart-fill {
            fill: rgba(56, 89, 200, .12);
        }

        .chart-line {
            fill: none;

            stroke: #3859c8;
            stroke-width: 5;

            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* ==========================================
           REVIEW PAGE
        ========================================== */

        .review-wrapper {
            max-width: 900px;
            margin: 0 auto;
        }

        .review-card {
            padding: 30px;
        }

        .review-card h2 {
            margin: 0 0 7px;

            font-size: 23px;
            font-weight: 700;
        }

        .review-description {
            margin: 0 0 25px;

            color: var(--muted);

            font-size: 14px;
        }

        .review-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 15px;

            margin-bottom: 25px;
        }

        .review-box {
            padding: 18px;

            border: 1px solid var(--border);
            border-radius: 8px;

            background: #fafbff;
        }

        .review-box-label {
            margin-bottom: 6px;

            color: var(--muted);

            font-size: 11px;
            font-weight: 700;

            text-transform: uppercase;
        }

        .review-box-value {
            font-size: 21px;
            font-weight: 700;
        }

        .review-info {
            padding: 18px;

            margin-bottom: 25px;

            border: 1px solid #dfe4f8;
            border-radius: 8px;

            background: #f8f9ff;
        }

        .review-info-row {
            display: flex;
            justify-content: space-between;

            gap: 20px;

            padding: 11px 0;

            border-bottom: 1px solid #e3e6ef;
        }

        .review-info-row:last-child {
            border-bottom: 0;
        }

        .review-info-label {
            color: var(--muted);
            font-size: 14px;
        }

        .review-info-value {
            font-size: 14px;
            font-weight: 600;
        }

        .review-actions {
            display: flex;
            justify-content: flex-end;

            gap: 10px;
        }

        /* ==========================================
           EMPTY / PLACEHOLDER
        ========================================== */

        .placeholder-page {
            padding: 50px 25px;

            text-align: center;
        }

        .placeholder-icon {
            width: 55px;
            height: 55px;

            margin: 0 auto 15px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #eef2ff;
            color: var(--primary);

            font-size: 24px;
        }

        .placeholder-page h2 {
            margin-bottom: 8px;

            font-size: 21px;
        }

        .placeholder-page p {
            margin: 0;

            color: var(--muted);
        }

        /* ==========================================
           RESPONSIVE
        ========================================== */

        @media (max-width: 1100px) {

            .summary-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .detail-top-grid,
            .detail-bottom-grid {
                grid-template-columns: 1fr;
            }

            .stock-grid {
                grid-template-columns:
                    repeat(2, 1fr);

                gap: 25px 0;
            }

            .stock-item:nth-child(2) {
                border-right: 0;
            }

            .stock-item:nth-child(3),
            .stock-item:nth-child(4) {
                padding-top: 20px;

                border-top: 1px solid #d4d9e3;
            }

            .stock-item:nth-child(3) {
                padding-left: 0;
            }

        }

        @media (max-width: 800px) {

            .topbar {
                padding: 0 18px;
            }

            .menu-btn {
                display: block;
            }

            .page {
                padding: 22px 18px;
            }

            .module-tabs {
                width: 100%;

                overflow-x: auto;

                scrollbar-width: none;
            }

            .module-tabs::-webkit-scrollbar {
                display: none;
            }

            .module-tab {
                white-space: nowrap;
            }

            .detail-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .detail-actions {
                width: 100%;
            }

            .detail-actions button {
                flex: 1;
            }

        }

        @media (max-width: 650px) {

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .stock-grid {
                grid-template-columns: 1fr;
            }

            .stock-item,
            .stock-item:nth-child(2),
            .stock-item:nth-child(3),
            .stock-item:nth-child(4) {
                padding: 15px 0;

                border: 0;
                border-bottom: 1px solid #d4d9e3;
            }

            .stock-item:first-child {
                padding-top: 0;
            }

            .stock-item:last-child {
                border-bottom: 0;
            }

            .stock-bottom {
                grid-template-columns: 1fr;

                gap: 20px;
            }

            .review-grid {
                grid-template-columns: 1fr;
            }

            .detail-title {
                font-size: 21px;
            }

        }

        @media (max-width: 480px) {

            .page {
                padding: 18px 13px;
            }

            .module-title {
                font-size: 23px;
            }

            .detail-actions {
                flex-direction: column;
            }

            .detail-actions button {
                width: 100%;
            }

            .detail-title-wrapper {
                align-items: flex-start;
            }

            .risk-alert {
                padding: 14px;
            }

            .stock-card,
            .why-card {
                padding: 18px;
            }

            .supplier-body,
            .chart-body {
                padding-left: 18px;
                padding-right: 18px;
            }

            .suggested-box {
                align-items: flex-start;
                flex-direction: column;
            }

            .suggested-number {
                align-self: flex-end;
            }

            .review-card {
                padding: 20px;
            }

            .review-actions {
                flex-direction: column;
            }

            .review-actions button {
                width: 100%;
            }

        }
    

</style>
