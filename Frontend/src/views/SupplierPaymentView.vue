<template>
<div class="supplierpayment-page-wrapper">



        <!-- =================================================
             TOPBAR
        ================================================= -->

        


        <!-- =================================================
             CONTENT
        ================================================= -->

        <div class="content">


            <!-- HEADER -->

            <section class="page-header">

                <div>

                    <div class="system-title">
                        HBOS Management System
                    </div>

                    <h1 class="page-title">
                        Supplier Directory
                    </h1>

                    <p class="page-description">
                        Manage vendors, view payables, and record settlements.
                    </p>

                </div>


                <button type="button" class="record-payment" id="recordPaymentButton">

                    <i class="bi bi-credit-card"></i>

                    Record Payment

                </button>

            </section>


            <!-- =================================================
                 CARDS
            ================================================= -->

            <section class="cards-grid">


                <!-- NO SUPPLIERS -->

                <article class="state-card">

                    <div class="state-icon">

                        <i class="bi bi-building-slash"></i>

                    </div>

                    <h2 class="state-title">
                        No Suppliers Yet
                    </h2>

                    <p class="state-text">
                        You haven't added any vendors or suppliers
                        to your directory. Start by adding your
                        first supplier.
                    </p>

                    <button type="button" class="card-button" id="addSupplierButton">

                        <i class="bi bi-building-add"></i>

                        Add Supplier

                    </button>

                </article>


                <!-- ALL SETTLED -->

                <article class="state-card">

                    <div class="state-icon green">

                        <i class="bi bi-check-circle"></i>

                    </div>

                    <h2 class="state-title">
                        All Settled Up
                    </h2>

                    <p class="state-text">
                        Excellent! You currently have no
                        outstanding supplier payments.
                        All accounts are clear.
                    </p>

                    <button type="button" class="card-button blue" id="paymentHistoryButton">
                        View Payment History
                    </button>

                </article>


                <!-- LIMITED HISTORY -->

                <article class="state-card limited">

                    <div class="state-icon">

                        <i class="bi bi-graph-up-arrow"></i>

                    </div>

                    <h2 class="state-title">
                        Limited Supplier History
                    </h2>

                    <p class="state-text">
                        Not enough transaction data is available
                        to generate meaningful spending insights
                        or trend charts at this time.
                    </p>

                </article>

            </section>


            <!-- =================================================
                 LOWER AREA
            ================================================= -->

            <section class="lower-grid">


                <!-- TABLE PLACEHOLDER -->

                <div class="supplier-table-card">

                    <div class="table-header">

                        <div class="skeleton large"></div>

                        <div class="skeleton small"></div>

                    </div>


                    <div class="skeleton-row">

                        <div class="skeleton long"></div>

                        <div class="skeleton medium"></div>

                        <div class="skeleton medium"></div>

                        <div class="skeleton small"></div>

                    </div>


                    <div class="skeleton-row">

                        <div class="skeleton long"></div>

                        <div class="skeleton medium"></div>

                        <div class="skeleton medium"></div>

                        <div class="skeleton small"></div>

                    </div>


                    <div class="skeleton-row">

                        <div class="skeleton long"></div>

                        <div class="skeleton medium"></div>

                        <div class="skeleton medium"></div>

                        <div class="skeleton small"></div>

                    </div>

                </div>


                <!-- PAYMENT -->

                <div class="payment-card">

                    <div class="payment-title">

                        <i class="bi bi-check-circle-fill"></i>

                        <span>
                            Payment Recorded
                        </span>

                    </div>


                    <p class="payment-description">
                        Transaction #TRX-9982 was
                        successfully logged.
                    </p>


                    <div class="payment-info">

                        <div>

                            <div class="payment-label">
                                Supplier
                            </div>

                            <div class="payment-value">
                                Acme<br>
                                Wholesalers
                            </div>

                        </div>


                        <div>

                            <div class="payment-label">
                                Amount
                            </div>

                            <div class="payment-value">
                                PKR 15,000
                            </div>

                        </div>

                    </div>


                    <a href="#" class="receipt-link" id="receiptLink">
                        View Receipt
                    </a>

                </div>

            </section>

        </div>

    
</div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
});
</script>

<style scoped>

        /* =====================================================
           ROOT
        ===================================================== */

        :root {
            --primary: #2146b7;
            --primary-dark: #173995;

            --text: #20212a;
            --muted: #596780;

            --border: #c9d0df;
            --background: #faf9fd;

            --sidebar-width: 280px;
        }


        /* =====================================================
           RESET
        ===================================================== */

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;

            min-height: 100%;

            font-family:
                Inter,
                "Segoe UI",
                Arial,
                sans-serif;

            background: var(--background);
            color: var(--text);
        }

        body {
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
        }

        button,
        input {
            font-family: inherit;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {
            position: fixed;

            top: 0;
            left: 0;
            bottom: 0;

            width: var(--sidebar-width);

            background: #ffffff;

            border-right: 1px solid var(--border);

            z-index: 1050;

            display: flex;
            flex-direction: column;

            transition: transform .25s ease;
        }


        /* BRAND */

        .brand {
            min-height: 105px;

            padding: 22px 10px;

            display: flex;
            align-items: center;
        }

        .brand-name {
            color: #073b97;

            font-size: 21px;
            font-weight: 800;

            line-height: 1;
        }

        .brand-subtitle {
            margin-top: 6px;

            color: #596780;

            font-size: 12px;
            font-weight: 700;

            letter-spacing: .7px;
        }


        /* =====================================================
           SIDEBAR NAV
           
           ONLY:
           Suppliers
           Settings
           Support
        ===================================================== */

        .sidebar-nav {
            padding: 15px 8px;
        }

        .sidebar-bottom {
            margin-top: auto;

            padding: 10px 8px 15px;
        }

        .sidebar-link {
            position: relative;

            min-height: 48px;

            padding: 0 13px;

            margin-bottom: 4px;

            border-radius: 7px;

            display: flex;
            align-items: center;

            gap: 13px;

            color: #52627c;

            font-size: 15px;
            font-weight: 500;

            transition: all .2s ease;
        }

        .sidebar-link i {
            width: 21px;

            font-size: 18px;

            text-align: center;
        }

        .sidebar-link:hover {
            background: #f1f3f8;

            color: var(--primary);
        }

        .sidebar-link.active {
            background: #e7edfc;

            color: #073c9e;

            font-weight: 700;
        }

        .sidebar-link.active::before {
            content: "";

            position: absolute;

            left: 0;
            top: 0;
            bottom: 0;

            width: 4px;

            background: var(--primary);

            border-radius: 0 4px 4px 0;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main {
            min-height: 100vh;

            margin-left: var(--sidebar-width);
        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {
            height: 64px;

            padding: 0 10px;

            background: #ffffff;

            border-bottom: 1px solid var(--border);

            display: flex;
            align-items: center;

            gap: 10px;
        }


        /* MOBILE MENU */

        .mobile-menu {
            display: none;

            width: 40px;
            height: 40px;

            border: 1px solid var(--border);

            border-radius: 8px;

            background: #ffffff;

            color: #29334a;

            align-items: center;
            justify-content: center;

            font-size: 20px;

            cursor: pointer;
        }


        /* SEARCH */

        .search-box {
            width: min(425px, 100%);

            height: 40px;

            padding: 0 12px;

            background: #f6f4fb;

            border: 1px solid #c7ccda;

            border-radius: 8px;

            display: flex;
            align-items: center;

            gap: 10px;
        }

        .search-box i {
            color: #4f596d;

            font-size: 17px;
        }

        .search-box input {
            width: 100%;

            border: 0;
            outline: 0;

            background: transparent;

            color: var(--text);

            font-size: 14px;
        }

        .search-box input::placeholder {
            color: #667087;
        }


        /* TOP ACTIONS */

        .top-actions {
            margin-left: auto;

            display: flex;
            align-items: center;

            gap: 7px;
        }

        .top-button {
            min-height: 40px;

            padding: 0 13px;

            border: 1px solid #c9cfdb;

            border-radius: 8px;

            background: #ffffff;

            color: #24262d;

            font-size: 13px;
            font-weight: 600;

            cursor: pointer;
        }

        .top-button:hover {
            background: #f3f4f7;
        }

        .top-button.primary {
            background: var(--primary);

            border-color: var(--primary);

            color: #ffffff;
        }

        .top-button.primary:hover {
            background: var(--primary-dark);
        }

        .top-icon {
            width: 40px;
            height: 40px;

            border: 0;

            background: transparent;

            color: #4c566b;

            font-size: 19px;

            display: flex;
            align-items: center;
            justify-content: center;

            cursor: pointer;
        }

        .user-name {
            color: #58647a;

            font-size: 15px;
            font-weight: 600;
        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .content {
            padding: 0 8px 40px;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .page-header {
            min-height: 55px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;
        }

        .system-title {
            margin: 0;

            color: #07388f;

            font-size: 19px;
            font-weight: 750;
        }

        .page-title {
            margin: 0;

            font-size: 35px;
            font-weight: 750;

            letter-spacing: -1px;
        }

        .page-description {
            margin: 8px 0 0;

            color: var(--muted);

            font-size: 17px;
        }


        /* =====================================================
           RECORD PAYMENT
        ===================================================== */

        .record-payment {
            min-height: 41px;

            padding: 0 14px;

            border: 0;

            border-radius: 8px;

            background: var(--primary);

            color: #ffffff;

            font-size: 13px;
            font-weight: 700;

            display: flex;
            align-items: center;

            gap: 7px;

            cursor: pointer;

            transition: .2s ease;
        }

        .record-payment:hover {
            background: var(--primary-dark);
        }


        /* =====================================================
           TOP CARDS
        ===================================================== */

        .cards-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 23px;

            margin-top: 22px;
        }

        .state-card {
            min-height: 320px;

            padding: 30px 22px;

            background: #ffffff;

            border: 1px solid var(--border);

            border-radius: 12px;

            display: flex;
            flex-direction: column;

            align-items: center;

            text-align: center;
        }

        .state-card.limited {
            background: #f7f5fd;

            border-style: dashed;
        }


        /* CARD ICON */

        .state-icon {
            height: 62px;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #58667f;

            font-size: 30px;
        }

        .state-icon.green {
            color: #27833b;
        }


        /* CARD TEXT */

        .state-title {
            margin: 10px 0 9px;

            font-size: 20px;
            font-weight: 700;
        }

        .state-text {
            max-width: 320px;

            margin: 0;

            color: #586983;

            font-size: 17px;

            line-height: 1.42;
        }


        /* CARD BUTTON */

        .card-button {
            margin-top: 12px;

            min-height: 42px;

            padding: 0 12px;

            border: 1px solid #c7cedc;

            border-radius: 8px;

            background: #ffffff;

            color: #24262c;

            font-size: 13px;
            font-weight: 600;

            display: inline-flex;
            align-items: center;

            gap: 7px;

            cursor: pointer;
        }

        .card-button:hover {
            background: #f3f4f7;
        }

        .card-button.blue {
            border: 0;

            background: #e9eefc;

            color: #073a99;
        }


        /* =====================================================
           LOWER GRID
        ===================================================== */

        .lower-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 2fr) minmax(280px, 1fr);

            gap: 23px;

            margin-top: 23px;
        }


        /* =====================================================
           SUPPLIER TABLE PLACEHOLDER
        ===================================================== */

        .supplier-table-card {
            min-height: 218px;

            background: #ffffff;

            border: 1px solid var(--border);

            border-radius: 12px;

            overflow: hidden;
        }

        .table-header {
            height: 66px;

            padding: 0 24px;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .skeleton {
            background: #e0e1e8;

            border-radius: 5px;
        }

        .skeleton.large {
            width: 48px;
            height: 24px;
        }

        .skeleton.small {
            width: 25px;
            height: 10px;
        }

        .skeleton.medium {
            width: 80px;
            height: 9px;
        }

        .skeleton.long {
            width: 115px;
            height: 9px;
        }

        .skeleton-row {
            height: 36px;

            padding: 0 7px;

            border-top: 1px solid #d9dce4;

            display: grid;

            grid-template-columns:
                1.5fr 1fr 1fr 30px;

            align-items: center;

            gap: 20px;
        }


        /* =====================================================
           PAYMENT CARD
        ===================================================== */

        .payment-card {
            min-height: 218px;

            padding: 22px 24px;

            background: #ffffff;

            border: 1px solid var(--border);

            border-left: 4px solid #c9cfdb;

            border-radius: 10px;
        }

        .payment-title {
            display: flex;
            align-items: center;

            gap: 7px;

            font-size: 18px;
            font-weight: 700;
        }

        .payment-title i {
            color: #2d8b3f;

            font-size: 20px;
        }

        .payment-description {
            margin: 4px 0 8px 25px;

            color: #596981;

            font-size: 15px;

            line-height: 1.4;
        }

        .payment-info {
            padding: 7px;

            background: #f2effa;

            border-radius: 8px;

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 10px;
        }

        .payment-label {
            margin-bottom: 5px;

            color: #536079;

            font-size: 11px;
            font-weight: 700;

            text-transform: uppercase;
        }

        .payment-value {
            color: #20232b;

            font-size: 16px;
            font-weight: 500;

            line-height: 1.35;
        }

        .receipt-link {
            display: inline-block;

            margin-top: 9px;

            color: #063a99;

            font-size: 13px;
            font-weight: 700;
        }


        /* =====================================================
           MOBILE OVERLAY
        ===================================================== */

        .sidebar-overlay {
            position: fixed;

            inset: 0;

            background: rgba(0, 0, 0, .4);

            z-index: 1040;

            opacity: 0;

            visibility: hidden;

            transition: .2s ease;
        }

        .sidebar-overlay.show {
            opacity: 1;

            visibility: visible;
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 1100px) {

            :root {
                --sidebar-width: 235px;
            }

            .cards-grid {
                gap: 14px;
            }

            .lower-grid {
                gap: 14px;
            }

            .page-title {
                font-size: 31px;
            }

            .page-description {
                font-size: 15px;
            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 768px) {

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;
            }

            .mobile-menu {
                display: flex;
            }

            .topbar {
                height: 60px;

                padding: 0 10px;
            }

            .search-box {
                flex: 1;

                width: auto;
            }

            .top-actions {
                display: none;
            }

            .content {
                padding: 0 12px 35px;
            }

            .page-header {
                padding-top: 18px;

                align-items: flex-start;

                flex-direction: column;

                gap: 15px;
            }

            .system-title {
                font-size: 16px;
            }

            .page-title {
                font-size: 29px;
            }

            .page-description {
                font-size: 14px;

                margin-top: 5px;
            }

            .record-payment {
                width: 100%;

                justify-content: center;
            }

            .cards-grid {
                grid-template-columns: 1fr;

                gap: 14px;

                margin-top: 18px;
            }

            .state-card {
                min-height: 255px;

                padding: 25px 18px;
            }

            .state-text {
                font-size: 15px;
            }

            .lower-grid {
                grid-template-columns: 1fr;

                gap: 14px;

                margin-top: 14px;
            }

        }


        /* =====================================================
           SMALL MOBILE
        ===================================================== */

        @media (max-width: 480px) {

            .page-title {
                font-size: 27px;
            }

            .state-title {
                font-size: 19px;
            }

            .state-card {
                min-height: 240px;
            }

            .payment-card {
                padding: 18px;
            }

            .payment-info {
                grid-template-columns: 1fr;
            }

        }
    

</style>
