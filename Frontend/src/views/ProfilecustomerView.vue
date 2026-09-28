<template>
<div class="profilecustomer-page-wrapper">


            <!-- TOPBAR -->
            


            <!-- CONTENT -->
            <section class="content">

                <!-- BREADCRUMB + ACTIONS -->
                <div class="breadcrumb-row">

                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="#" onclick="goCustomers(event)">
                                    Customers
                                </a>
                            </li>

                            <li class="breadcrumb-item active" aria-current="page">
                                Ahmad Khan
                            </li>
                        </ol>
                    </nav>


                    <div class="action-buttons">

                        <button class="btn-hbos" type="button" onclick="editCustomer()">

                            <i class="bi bi-pencil"></i>
                            Edit Customer
                        </button>

                        <button class="btn-hbos" type="button" onclick="newSale()">

                            <i class="bi bi-receipt"></i>
                            New Sale
                        </button>

                        <button class="btn-hbos btn-primary-hbos" type="button" onclick="recordPayment()">

                            <i class="bi bi-wallet2"></i>
                            Record Payment
                        </button>

                    </div>

                </div>


                <!-- =================================================
                 CUSTOMER PROFILE
            ================================================== -->
                <div class="profile-card">

                    <div class="profile-main">

                        <!-- Avatar -->
                        <div class="customer-avatar">
                            AK
                        </div>


                        <!-- Customer Information -->
                        <div class="customer-info">

                            <h2 class="customer-name">
                                Ahmad Khan
                            </h2>

                            <div class="customer-meta">

                                <span>
                                    <i class="bi bi-telephone"></i>
                                    +92 300 1234567
                                </span>

                                <span>
                                    <i class="bi bi-geo-alt"></i>
                                    Shop 42, Main Bazaar, Lahore
                                </span>

                            </div>

                            <p class="customer-since">
                                <i class="bi bi-calendar3"></i>
                                Customer since Jan 2023
                            </p>

                            <div class="badges">

                                <span class="badge-custom badge-wholesale">
                                    Wholesale
                                </span>

                                <span class="badge-custom badge-active">
                                    <i class="bi bi-check-circle"></i>
                                    Active
                                </span>

                            </div>

                        </div>


                        <!-- Financial Information -->
                        <div class="financial-boxes">

                            <div class="financial-box">

                                <div class="financial-label">
                                    Credit Limit
                                </div>

                                <div class="financial-value">
                                    Rs 20,000
                                </div>

                            </div>


                            <div class="financial-box danger">

                                <div class="financial-label">
                                    Outstanding
                                </div>

                                <div class="financial-value">
                                    Rs 15,400
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                 STATISTICS
            ================================================== -->
                <div class="stats-grid">

                    <div class="stat-card">

                        <div class="stat-title">
                            <i class="bi bi-bag"></i>
                            Total Purchases
                        </div>

                        <div class="stat-value">
                            Rs 125,400
                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-title">
                            <i class="bi bi-check-circle"></i>
                            Total Paid
                        </div>

                        <div class="stat-value">
                            Rs 110,000
                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-title">
                            <i class="bi bi-exclamation-triangle"></i>
                            Current Balance
                        </div>

                        <div class="stat-value danger">
                            Rs 15,400
                            <span class="small-dr">Dr</span>
                        </div>

                    </div>

                </div>


                <!-- =================================================
                 KHATA LEDGER
            ================================================== -->
                <div class="ledger-card">

                    <div class="ledger-header">

                        <h3 class="ledger-title">
                            <i class="bi bi-book"></i>
                            Khata Ledger
                        </h3>


                        <div class="ledger-controls">

                            <select class="ledger-select" id="periodSelect">

                                <option value="30">
                                    Last 30 Days
                                </option>

                                <option value="60">
                                    Last 60 Days
                                </option>

                                <option value="90">
                                    Last 90 Days
                                </option>

                                <option value="all">
                                    All Transactions
                                </option>

                            </select>


                            <button class="icon-btn" type="button" onclick="downloadLedger()" title="Download Ledger">

                                <i class="bi bi-download"></i>

                            </button>

                        </div>

                    </div>


                    <!-- TABLE -->
                    <div class="table-responsive">

                        <table class="ledger-table">

                            <thead>

                                <tr>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Description
                                    </th>

                                    <th class="text-end">
                                        Debit (Rs)
                                    </th>

                                    <th class="text-end">
                                        Credit (Rs)
                                    </th>

                                    <th class="text-end">
                                        Balance
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="ledgerBody">

                                <!-- Row 1 -->
                                <tr>

                                    <td>
                                        Oct 10, 2023
                                    </td>

                                    <td>

                                        <div class="description-main">
                                            Credit Sale - INV-001245
                                        </div>

                                        <div class="description-sub">
                                            Rice 50kg, Sugar 10kg...
                                        </div>

                                    </td>

                                    <td class="text-end">
                                        4,500
                                    </td>

                                    <td class="text-end">
                                        -
                                    </td>

                                    <td class="text-end balance">
                                        15,400
                                    </td>

                                </tr>


                                <!-- Row 2 -->
                                <tr>

                                    <td>
                                        Oct 08, 2023
                                    </td>

                                    <td>

                                        <div class="description-main">
                                            Payment Received
                                            <span class="cash-badge">
                                                CASH
                                            </span>
                                        </div>

                                        <div class="description-sub">
                                            Rec-992
                                        </div>

                                    </td>

                                    <td class="text-end">
                                        -
                                    </td>

                                    <td class="text-end credit">
                                        10,000
                                    </td>

                                    <td class="text-end balance">
                                        10,900
                                    </td>

                                </tr>


                                <!-- Row 3 -->
                                <tr>

                                    <td>
                                        Oct 05, 2023
                                    </td>

                                    <td>

                                        <div class="description-main">
                                            Credit Sale - INV-001198
                                        </div>

                                        <div class="description-sub">
                                            Grocery Items
                                        </div>

                                    </td>

                                    <td class="text-end">
                                        12,400
                                    </td>

                                    <td class="text-end">
                                        -
                                    </td>

                                    <td class="text-end balance">
                                        20,900
                                    </td>

                                </tr>


                                <!-- Row 4 -->
                                <tr>

                                    <td>
                                        Oct 01, 2023
                                    </td>

                                    <td>

                                        <div class="description-main">
                                            Opening Balance
                                        </div>

                                        <div class="description-sub">
                                            Previous balance
                                        </div>

                                    </td>

                                    <td class="text-end">
                                        8,500
                                    </td>

                                    <td class="text-end">
                                        -
                                    </td>

                                    <td class="text-end balance">
                                        8,500
                                    </td>

                                </tr>


                                <!-- TOTAL -->
                                <tr class="ledger-total">

                                    <td colspan="2" class="text-end">
                                        Totals (Period):
                                    </td>

                                    <td class="text-end">
                                        25,400
                                    </td>

                                    <td class="text-end credit">
                                        10,000
                                    </td>

                                    <td class="text-end balance">
                                        15,400
                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </section>

        
</div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
});
</script>

<style scoped>

        /* =========================================================
           ROOT
        ========================================================= */
        :root {
            --primary: #0758d8;
            --primary-dark: #0647b3;
            --text: #202124;
            --muted: #5f6368;
            --border: #c9ced6;
            --bg: #f5f7fa;
            --white: #ffffff;
            --danger: #c81e1e;
            --success: #08a66a;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100%;
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
            font-family: inherit;
        }

        /* =========================================================
           APP
        ========================================================= */
        .app {
            min-height: 100vh;
            display: flex;
        }

        /* =========================================================
           SIDEBAR
        ========================================================= */
        .sidebar {
            width: 258px;
            min-width: 258px;
            min-height: 100vh;
            background: #fff;
            border-right: 1px solid #cdd2da;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
        }

        .brand {
            height: 96px;
            padding: 27px 25px;
            border-bottom: 1px solid #d8dce2;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand-logo {
            width: 42px;
            height: 42px;
            border-radius: 4px;
            background: #050505;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
        }

        .brand-info h1 {
            margin: 0;
            font-size: 25px;
            line-height: 1;
            font-weight: 750;
            letter-spacing: -0.5px;
        }

        .brand-info span {
            display: block;
            margin-top: 5px;
            font-size: 13px;
            font-weight: 600;
            color: #3f4146;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding: 18px 16px;
            border-top: 1px solid #d8dce2;
        }

        .settings-link {
            width: 100%;
            border: 0;
            background: transparent;
            padding: 12px 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 14px;
            color: #4e5158;
            font-size: 16px;
            text-align: left;
            transition: 0.2s ease;
        }

        .settings-link:hover {
            background: #f0f3f8;
            color: var(--primary);
        }

        .settings-link i {
            font-size: 20px;
        }

        /* =========================================================
           MAIN
        ========================================================= */
        .main {
            margin-left: 258px;
            width: calc(100% - 258px);
            min-height: 100vh;
            background: var(--bg);
        }

        /* =========================================================
           TOPBAR
        ========================================================= */
        .topbar {
            height: 86px;
            background: #fff;
            border-bottom: 1px solid #d1d6dd;
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 20px;
        }

        .mobile-menu-btn {
            display: none;
            width: 42px;
            height: 42px;
            border: 1px solid #cdd2da;
            background: #fff;
            border-radius: 8px;
            font-size: 20px;
        }

        .search-box {
            width: min(650px, 65%);
            height: 48px;
            border: 1px solid #c8ced7;
            border-radius: 7px;
            background: #fff;
            display: flex;
            align-items: center;
            padding: 0 14px;
        }

        .search-box i {
            font-size: 21px;
            color: #34373d;
            margin-right: 12px;
        }

        .search-box input {
            width: 100%;
            border: 0;
            outline: 0;
            font-size: 15px;
            color: #222;
        }

        .search-box input::placeholder {
            color: #777e88;
        }

        .top-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .top-icon {
            position: relative;
            width: 38px;
            height: 38px;
            border: 0;
            background: transparent;
            color: #383c43;
            font-size: 21px;
        }

        .notification-dot {
            position: absolute;
            right: 5px;
            top: 4px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #c71919;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #151c2d;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        /* =========================================================
           CONTENT
        ========================================================= */
        .content {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 22px 24px 45px;
        }

        /* =========================================================
           BREADCRUMB
        ========================================================= */
        .breadcrumb-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 17px;
        }

        .breadcrumb {
            margin: 0;
            font-size: 14px;
        }

        .breadcrumb a {
            color: #4c535c;
            text-decoration: none;
        }

        .breadcrumb .active {
            color: #25282d;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-hbos {
            height: 39px;
            padding: 0 17px;
            border-radius: 7px;
            border: 1px solid #aeb5bf;
            background: #fff;
            color: #17191d;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: 0.2s ease;
        }

        .btn-hbos:hover {
            background: #f3f5f8;
        }

        .btn-primary-hbos {
            background: #000;
            border-color: #000;
            color: #fff;
        }

        .btn-primary-hbos:hover {
            background: #222;
            color: #fff;
        }

        /* =========================================================
           PROFILE CARD
        ========================================================= */
        .profile-card {
            background: #fff;
            border: 1px solid #c9ced6;
            border-radius: 12px;
            padding: 31px 24px;
            margin-bottom: 16px;
        }

        .profile-main {
            display: flex;
            align-items: center;
            gap: 23px;
        }

        .customer-avatar {
            width: 80px;
            height: 80px;
            min-width: 80px;
            border-radius: 50%;
            background: #3269ef;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            font-weight: 700;
        }

        .customer-info {
            min-width: 0;
            flex: 1;
        }

        .customer-name {
            margin: 0 0 7px;
            font-size: 32px;
            line-height: 1.1;
            font-weight: 750;
            letter-spacing: -0.7px;
        }

        .customer-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 22px;
            color: #4e535b;
            font-size: 15px;
            margin-bottom: 13px;
        }

        .customer-meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .customer-meta i {
            font-size: 15px;
        }

        .customer-since {
            margin: 0 0 13px;
            color: #555b64;
            font-size: 15px;
        }

        .badges {
            display: flex;
            gap: 8px;
        }

        .badge-custom {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 9px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-wholesale {
            background: #e6e8eb;
            color: #53575d;
        }

        .badge-active {
            background: #54edb4;
            color: #073e2a;
        }

        .financial-boxes {
            display: flex;
            gap: 15px;
        }

        .financial-box {
            width: 185px;
            min-height: 87px;
            border-radius: 8px;
            padding: 17px 16px;
            background: #eef0f2;
        }

        .financial-box.danger {
            background: #fff8f8;
            border: 1px solid #ffd0d0;
        }

        .financial-label {
            color: #52565d;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .financial-box.danger .financial-label {
            color: #c51f1f;
        }

        .financial-value {
            font-size: 26px;
            line-height: 1;
            font-weight: 750;
        }

        .financial-box.danger .financial-value {
            color: #c51f1f;
        }

        /* =========================================================
           STAT CARDS
        ========================================================= */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 16px;
        }

        .stat-card {
            min-height: 120px;
            background: #fff;
            border: 1px solid #c9ced6;
            border-radius: 11px;
            padding: 23px 18px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-title {
            color: #555a62;
            font-size: 16px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-title i {
            font-size: 19px;
        }

        .stat-value {
            font-size: 32px;
            line-height: 1;
            font-weight: 750;
        }

        .stat-value.danger {
            color: #c51f1f;
        }

        .stat-value .small-dr {
            font-size: 14px;
            color: #c51f1f;
            font-weight: 600;
            margin-left: 5px;
        }

        /* =========================================================
           KHATA
        ========================================================= */
        .ledger-card {
            background: #fff;
            border: 1px solid #c9ced6;
            border-radius: 11px;
            overflow: hidden;
        }

        .ledger-header {
            min-height: 66px;
            padding: 0 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            border-bottom: 1px solid #d4d8de;
        }

        .ledger-title {
            margin: 0;
            font-size: 21px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ledger-title i {
            color: var(--primary);
        }

        .ledger-controls {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .ledger-select {
            height: 37px;
            border: 1px solid #c5cad2;
            border-radius: 7px;
            background: #fff;
            padding: 0 32px 0 12px;
            color: #353941;
            font-size: 13px;
            outline: none;
        }

        .icon-btn {
            width: 37px;
            height: 37px;
            border: 1px solid #c5cad2;
            border-radius: 7px;
            background: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        /* =========================================================
           TABLE
        ========================================================= */
        .table-responsive {
            overflow-x: auto;
        }

        .ledger-table {
            width: 100%;
            min-width: 800px;
            margin: 0;
            border-collapse: collapse;
        }

        .ledger-table thead th {
            height: 53px;
            padding: 0 15px;
            background: #fafbfc;
            border-bottom: 1px solid #d5d9df;
            color: #53575f;
            font-size: 13px;
            font-weight: 700;
            text-transform: none;
            white-space: nowrap;
        }

        .ledger-table tbody td {
            height: 67px;
            padding: 9px 15px;
            border-bottom: 1px solid #d8dce1;
            color: #26292e;
            font-size: 14px;
            vertical-align: middle;
        }

        .ledger-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .description-main {
            font-weight: 600;
            color: #25272b;
        }

        .description-sub {
            margin-top: 3px;
            color: #737981;
            font-size: 12px;
        }

        .credit {
            color: #079765 !important;
        }

        .balance {
            color: #c51f1f !important;
            font-weight: 700;
        }

        .cash-badge {
            display: inline-block;
            margin-left: 5px;
            padding: 2px 5px;
            background: #4ce9af;
            color: #064c35;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
        }

        .ledger-total {
            background: #f0f1f3;
            border-top: 2px solid #777b81;
        }

        .ledger-total td {
            font-weight: 700 !important;
        }

        .ledger-total .balance {
            font-size: 20px;
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

        @media (max-width: 1100px) {

            .sidebar {
                width: 230px;
                min-width: 230px;
            }

            .main {
                margin-left: 230px;
                width: calc(100% - 230px);
            }

            .financial-box {
                width: 165px;
            }

            .customer-name {
                font-size: 28px;
            }

            .financial-value {
                font-size: 23px;
            }
        }

        @media (max-width: 900px) {

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.25s ease;
                width: 258px;
                min-width: 258px;
                box-shadow: 5px 0 25px rgba(0, 0, 0, .12);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay.show {
                display: block;
            }

            .main {
                margin-left: 0;
                width: 100%;
            }

            .mobile-menu-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .topbar {
                padding: 0 15px;
            }

            .search-box {
                flex: 1;
                width: auto;
                max-width: none;
            }

            .profile-main {
                align-items: flex-start;
                flex-direction: column;
            }

            .financial-boxes {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(2, 1fr);
            }

            .financial-box {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 700px) {

            .top-actions {
                gap: 7px;
            }

            .top-icon.store-icon {
                display: none;
            }

            .content {
                padding: 17px 13px 35px;
            }

            .breadcrumb-row {
                align-items: flex-start;
                flex-direction: column;
            }

            .action-buttons {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
            }

            .btn-hbos {
                padding: 0 8px;
                font-size: 12px;
            }

            .profile-card {
                padding: 22px 17px;
            }

            .customer-avatar {
                width: 70px;
                height: 70px;
                min-width: 70px;
            }

            .customer-name {
                font-size: 27px;
            }

            .customer-meta {
                flex-direction: column;
                gap: 7px;
            }

            .financial-boxes {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-card {
                min-height: 105px;
            }

            .ledger-header {
                padding: 12px 14px;
                align-items: flex-start;
                flex-direction: column;
            }

            .ledger-controls {
                width: 100%;
            }

            .ledger-select {
                flex: 1;
            }
        }

        @media (max-width: 500px) {

            .topbar {
                height: 72px;
                gap: 9px;
            }

            .search-box {
                height: 43px;
                padding: 0 10px;
            }

            .search-box i {
                font-size: 18px;
                margin-right: 8px;
            }

            .search-box input {
                font-size: 13px;
            }

            .user-avatar {
                width: 35px;
                height: 35px;
            }

            .top-icon {
                display: none;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }

            .btn-hbos {
                width: 100%;
            }

            .profile-card {
                border-radius: 9px;
            }

            .customer-name {
                font-size: 25px;
            }

            .financial-value {
                font-size: 22px;
            }

            .stat-value {
                font-size: 27px;
            }

            .ledger-title {
                font-size: 19px;
            }
        }
    

</style>
