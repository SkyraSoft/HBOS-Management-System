<template>
<div class="busniesssetup-page-wrapper">



            <!-- =====================================
             PROGRESS STEPPER
        ====================================== -->

            <div class="progress-section">

                <div class="progress-stepper">

                    <!-- Step 1 -->
                    <div class="step active">
                        <i class="bi bi-check-lg"></i>
                    </div>

                    <div class="step-line"></div>


                    <!-- Step 2 -->
                    <div class="step active">
                        <i class="bi bi-check-lg"></i>
                    </div>

                    <div class="step-line"></div>


                    <!-- Step 3 -->
                    <div class="step active">
                        <i class="bi bi-check-lg"></i>
                    </div>

                    <div class="step-line inactive"></div>


                    <!-- Step 4 -->
                    <div class="step active">
                        4
                    </div>

                </div>

            </div>


            <!-- =====================================
             HEADER
        ====================================== -->

            <header class="page-header">

                <h1>
                    Review your business setup
                </h1>

                <p>
                    Everything looks good? You can change these settings later from the dashboard.
                </p>

            </header>


            <!-- =====================================
             CONTENT
        ====================================== -->

            <section class="content-area">


                <!-- =================================
                 BUSINESS INFORMATION
            ================================== -->

                <article class="info-card">

                    <div class="card-header-custom">

                        <div class="card-title">

                            <i class="bi bi-shop"></i>

                            <span>
                                Business Information
                            </span>

                        </div>

                        <button type="button" class="edit-button" data-section="Business Information">
                            <i class="bi bi-pencil-fill"></i>
                            <span>Edit</span>
                        </button>

                    </div>


                    <div class="data-grid">

                        <div class="data-item">

                            <div class="data-label">
                                Business Name
                            </div>

                            <div class="data-value">
                                Hanif General Store
                            </div>

                        </div>


                        <div class="data-item">

                            <div class="data-label">
                                Business Type
                            </div>

                            <div class="data-value">
                                General Store
                            </div>

                        </div>


                        <div class="data-item">

                            <div class="data-label">
                                Owner Name
                            </div>

                            <div class="data-value">
                                Muhammad Hanif
                            </div>

                        </div>

                    </div>

                </article>


                <!-- =================================
                 STORE DETAILS
            ================================== -->

                <article class="info-card store-card">

                    <div class="card-header-custom">

                        <div class="card-title">

                            <i class="bi bi-geo-alt-fill"></i>

                            <span>
                                Store Details
                            </span>

                        </div>

                        <button type="button" class="edit-button" data-section="Store Details">
                            <i class="bi bi-pencil-fill"></i>
                            <span>Edit</span>
                        </button>

                    </div>


                    <div class="data-grid">

                        <div class="data-item full-width">

                            <div class="data-label">
                                Address
                            </div>

                            <div class="data-value">
                                123 Main St, Block B, Nazimabad
                            </div>

                        </div>


                        <div class="data-item">

                            <div class="data-label">
                                City, Province
                            </div>

                            <div class="data-value">
                                Karachi, Sindh
                            </div>

                        </div>


                        <div class="data-item">

                            <div class="data-label">
                                Phone Number
                            </div>

                            <div class="data-value">
                                +92 300 1234567
                            </div>

                        </div>

                    </div>

                </article>


                <!-- =================================
                 PREFERENCES
            ================================== -->

                <article class="info-card preferences-card">

                    <div class="card-header-custom">

                        <div class="card-title">

                            <i class="bi bi-gear-fill"></i>

                            <span>
                                Preferences
                            </span>

                        </div>

                        <button type="button" class="edit-button" data-section="Preferences">
                            <i class="bi bi-pencil-fill"></i>
                            <span>Edit</span>
                        </button>

                    </div>


                    <div class="data-grid single-row">

                        <div class="data-item">

                            <div class="data-label">
                                Currency
                            </div>

                            <div class="data-value">
                                PKR
                            </div>

                        </div>


                        <div class="data-item">

                            <div class="data-label">
                                Language
                            </div>

                            <div class="data-value">
                                English
                            </div>

                        </div>


                        <div class="data-item">

                            <div class="data-label">
                                Opening Time
                            </div>

                            <div class="data-value">
                                09:00 AM
                            </div>

                        </div>


                        <div class="data-item">

                            <div class="data-label">
                                Tax Settings
                            </div>

                            <div class="data-value">
                                Included
                            </div>

                        </div>

                    </div>

                </article>


            </section>


            <!-- =====================================
             FOOTER
        ====================================== -->

            <footer class="setup-footer">

                <button type="button" class="back-button" id="backButton">
                    Back
                </button>


                <button type="button" class="complete-button" id="completeButton">
                    <span>
                        Complete Setup
                    </span>

                    <i class="bi bi-arrow-right"></i>
                </button>

            </footer>


        
</div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
  console.log('BusniessSetupView mounted');
});
</script>

<style scoped>

        /* =========================================
           GLOBAL
        ========================================= */

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
        }

        body {
            font-family: "Inter", Arial, sans-serif;
            background: #ffffff;
            color: #18181b;
            -webkit-font-smoothing: antialiased;
        }

        button,
        a {
            font-family: inherit;
        }


        /* =========================================
           MAIN APP
        ========================================= */

        .setup-page {
            width: 100%;
            min-height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            padding: 2px;
            background: #ffffff;
        }

        .setup-card {
            width: 100%;
            max-width: 676px;
            min-height: 875px;

            display: flex;
            flex-direction: column;

            overflow: hidden;

            background: #ffffff;

            border: 1px solid #aeb3bb;
            border-radius: 14px;

            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }


        /* =========================================
           PROGRESS STEPPER
        ========================================= */

        .progress-section {
            width: 100%;
            padding: 27px 27px 0;
        }

        .progress-stepper {
            width: 100%;

            display: flex;
            align-items: center;
        }

        .step {
            flex: 0 0 auto;

            width: 29px;
            height: 29px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #075bdc;
            color: #ffffff;

            font-size: 14px;
            font-weight: 500;

            position: relative;
            z-index: 2;
        }

        .step i {
            font-size: 17px;
            font-weight: 700;
        }

        .step-line {
            flex: 1;
            height: 2px;

            margin: 0 5px;

            background: #075bdc;
        }

        .step-line.inactive {
            background: #c7ccd4;
        }

        .step.active {
            background: #075bdc;
        }


        /* =========================================
           PAGE HEADER
        ========================================= */

        .page-header {
            text-align: center;

            padding: 35px 28px 17px;
        }

        .page-header h1 {
            margin: 0 0 10px;

            color: #0a0a0a;

            font-size: 24px;
            line-height: 1.25;

            font-weight: 700;

            letter-spacing: -0.5px;
        }

        .page-header p {
            margin: 0;

            color: #535760;

            font-size: 16px;
            line-height: 1.5;

            font-weight: 400;
        }


        /* =========================================
           CONTENT
        ========================================= */

        .content-area {
            flex: 1;

            padding: 7px 26px 22px;

            background: #ffffff;
        }


        /* =========================================
           INFORMATION CARD
        ========================================= */

        .info-card {
            width: 100%;

            margin-bottom: 23px;

            padding: 16px 16px 17px;

            border: 1px solid #c7cbd2;
            border-radius: 8px;

            background: #f8f9fa;
        }

        .info-card:last-child {
            margin-bottom: 0;
        }


        /* =========================================
           CARD HEADER
        ========================================= */

        .card-header-custom {
            min-height: 37px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding-bottom: 10px;

            border-bottom: 1px solid #c5c9d0;
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 5px;

            color: #101114;

            font-size: 20px;
            line-height: 1.2;

            font-weight: 700;

            letter-spacing: -0.3px;
        }

        .card-title i {
            color: #4b4f57;
            font-size: 16px;
        }

        .edit-button {
            display: inline-flex;
            align-items: center;
            gap: 4px;

            padding: 0;

            border: 0;
            background: transparent;

            color: #005bd8;

            font-size: 14px;
            font-weight: 500;

            cursor: pointer;

            text-decoration: none;

            transition: color 0.15s ease;
        }

        .edit-button:hover {
            color: #003f99;
        }

        .edit-button i {
            font-size: 14px;
        }


        /* =========================================
           DATA GRID
        ========================================= */

        .data-grid {
            display: grid;

            grid-template-columns: 1fr 1fr;

            column-gap: 30px;
            row-gap: 17px;

            padding-top: 15px;
        }

        .data-grid.single-row {
            grid-template-columns: repeat(4, 1fr);
            column-gap: 25px;
        }

        .data-item.full-width {
            grid-column: 1 / -1;
        }

        .data-label {
            margin-bottom: 6px;

            color: #50545c;

            font-size: 12px;
            line-height: 1.2;

            font-weight: 600;

            letter-spacing: 0.15px;
            text-transform: uppercase;
        }

        .data-value {
            color: #27292d;

            font-size: 16px;
            line-height: 1.4;

            font-weight: 400;
        }


        /* =========================================
           STORE DETAILS
        ========================================= */

        .store-card .data-grid {
            row-gap: 16px;
        }


        /* =========================================
           PREFERENCES
        ========================================= */

        .preferences-card {
            padding-bottom: 18px;
        }

        .preferences-card .data-value {
            white-space: nowrap;
        }


        /* =========================================
           FOOTER
        ========================================= */

        .setup-footer {
            width: 100%;

            min-height: 94px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 18px 25px;

            border-top: 1px solid #d0d3d8;

            background: #ffffff;
        }

        .back-button {
            border: 0;
            background: transparent;

            color: #4b4e55;

            font-size: 14px;
            font-weight: 400;

            cursor: pointer;

            padding: 10px 17px;

            transition: color 0.15s ease;
        }

        .back-button:hover {
            color: #075bdc;
        }

        .complete-button {
            height: 45px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            padding: 0 24px;

            border: 0;
            border-radius: 8px;

            background: #075bdc;
            color: #ffffff;

            font-size: 14px;
            font-weight: 500;

            cursor: pointer;

            transition:
                background 0.2s ease,
                box-shadow 0.2s ease,
                transform 0.1s ease;
        }

        .complete-button:hover {
            background: #064fbe;
            box-shadow: 0 4px 12px rgba(7, 91, 220, 0.18);
        }

        .complete-button:active {
            transform: translateY(1px);
        }

        .complete-button i {
            font-size: 17px;
        }


        /* =========================================
           EDIT MODAL
        ========================================= */

        .modal-content {
            border: 0;
            border-radius: 12px;
            overflow: hidden;
        }

        .modal-header {
            border-bottom: 1px solid #e1e4e8;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 700;
        }

        .modal-body {
            color: #4f535a;
            font-size: 14px;
            line-height: 1.6;
        }

        .modal-footer {
            border-top: 1px solid #e1e4e8;
        }


        /* =========================================
           SUCCESS TOAST
        ========================================= */

        .setup-toast {
            position: fixed;

            right: 20px;
            bottom: 20px;

            z-index: 9999;

            min-width: 280px;

            padding: 14px 17px;

            border-radius: 9px;

            background: #ffffff;

            border: 1px solid #d7dbe1;

            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.12);

            display: none;
        }

        .setup-toast.show {
            display: block;
            animation: toastIn 0.25s ease;
        }

        .toast-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toast-icon {
            width: 28px;
            height: 28px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #e8f1ff;
            color: #075bdc;
        }

        .toast-text strong {
            display: block;

            margin-bottom: 2px;

            color: #17191c;

            font-size: 13px;
        }

        .toast-text span {
            color: #666a72;
            font-size: 12px;
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }


        /* =========================================
           TABLET
        ========================================= */

        @media (max-width: 700px) {

            .setup-page {
                padding: 0;
                align-items: stretch;
            }

            .setup-card {
                max-width: none;
                min-height: 100vh;

                border-radius: 0;
                border: 0;
            }

            .progress-section {
                padding: 24px 24px 0;
            }

            .page-header {
                padding: 32px 24px 18px;
            }

            .content-area {
                padding: 7px 20px 25px;
            }

            .data-grid.single-row {
                grid-template-columns: repeat(2, 1fr);
                row-gap: 18px;
            }

            .setup-footer {
                padding: 18px 20px;
            }
        }


        /* =========================================
           MOBILE
        ========================================= */

        @media (max-width: 520px) {

            .progress-section {
                padding: 20px 18px 0;
            }

            .step {
                width: 28px;
                height: 28px;
            }

            .step-line {
                margin: 0 4px;
            }

            .page-header {
                padding: 28px 18px 19px;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .page-header p {
                font-size: 14px;
                line-height: 1.55;
            }

            .content-area {
                padding: 5px 15px 22px;
            }

            .info-card {
                margin-bottom: 18px;
                padding: 14px;
            }

            .card-title {
                font-size: 18px;
            }

            .card-header-custom {
                padding-bottom: 9px;
            }

            .data-grid,
            .data-grid.single-row {
                grid-template-columns: 1fr;
                column-gap: 0;
                row-gap: 15px;
                padding-top: 14px;
            }

            .data-item.full-width {
                grid-column: auto;
            }

            .data-label {
                font-size: 11px;
            }

            .data-value {
                font-size: 15px;
            }

            .preferences-card .data-value {
                white-space: normal;
            }

            .setup-footer {
                min-height: 82px;

                padding: 15px;

                gap: 10px;
            }

            .back-button {
                padding: 10px 8px;
            }

            .complete-button {
                height: 44px;
                padding: 0 17px;
                font-size: 13px;
            }

            .setup-toast {
                right: 15px;
                left: 15px;
                bottom: 15px;
                min-width: 0;
            }
        }


        /* =========================================
           VERY SMALL MOBILE
        ========================================= */

        @media (max-width: 360px) {

            .progress-section {
                padding-left: 14px;
                padding-right: 14px;
            }

            .step {
                width: 25px;
                height: 25px;
                font-size: 12px;
            }

            .step i {
                font-size: 14px;
            }

            .page-header h1 {
                font-size: 20px;
            }

            .page-header p {
                font-size: 13px;
            }

            .content-area {
                padding-left: 10px;
                padding-right: 10px;
            }

            .setup-footer {
                padding-left: 10px;
                padding-right: 10px;
            }

            .complete-button {
                padding: 0 13px;
            }
        }
    

</style>
