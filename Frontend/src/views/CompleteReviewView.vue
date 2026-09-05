<template>
<div class="completereview-page-wrapper">


            <div class="content">

                <h1 class="page-title">
                    Review &amp; Complete
                </h1>

                <p class="page-description">
                    Please review your information before completing the setup.
                    You can go back to edit any section if needed.
                </p>


                <!-- =====================================================
                 ACCOUNT DETAILS
            ====================================================== -->

                <section class="review-card" id="accountCard" data-section="Account Details">

                    <div class="review-card-header">

                        <h2 class="review-card-title">
                            <i class="bi bi-person"></i>
                            Account Details
                        </h2>

                        <button type="button" class="edit-btn" data-edit-section="account">
                            <i class="bi bi-pencil"></i>
                            <span>Edit</span>
                        </button>

                    </div>

                    <div class="card-divider"></div>

                    <div class="review-card-body">

                        <div class="review-grid">

                            <div class="field" data-field="fullName">
                                <span class="field-label">Full Name</span>
                                <span class="field-value">Jane Doe</span>
                            </div>

                            <div class="field" data-field="email">
                                <span class="field-label">Email Address</span>
                                <span class="field-value">
                                    jane.doe@example.com
                                </span>
                            </div>

                            <div class="field" data-field="phone">
                                <span class="field-label">Phone Number</span>
                                <span class="field-value">
                                    +1 (555) 123-4567
                                </span>
                            </div>

                            <div class="field" data-field="role">
                                <span class="field-label">Role</span>
                                <span class="field-value">Store Owner</span>
                            </div>

                        </div>

                    </div>
                </section>


                <!-- =====================================================
                 BUSINESS INFO
            ====================================================== -->

                <section class="review-card business-card" id="businessCard" data-section="Business Info">

                    <div class="review-card-header">

                        <h2 class="review-card-title">
                            <i class="bi bi-shop"></i>
                            Business Info
                        </h2>

                        <button type="button" class="edit-btn" data-edit-section="business">
                            <i class="bi bi-pencil"></i>
                            <span>Edit</span>
                        </button>

                    </div>

                    <div class="card-divider"></div>

                    <div class="review-card-body">

                        <div class="business-grid">

                            <div class="field store-name" data-field="storeName">
                                <span class="field-label">Store Name</span>
                                <span class="field-value">
                                    Downtown Convenience
                                </span>
                            </div>

                            <div class="field" data-field="businessType">
                                <span class="field-label">
                                    Business Type
                                </span>

                                <span class="field-value">
                                    Retail - Grocery
                                </span>
                            </div>

                            <div class="field" data-field="taxId">
                                <span class="field-label">
                                    Tax ID / GSTIN
                                </span>

                                <span class="field-value">
                                    27ABCDE1234F1Z5
                                </span>
                            </div>

                            <div class="field address" data-field="address">
                                <span class="field-label">
                                    Store Address
                                </span>

                                <span class="field-value address-value">
                                    123 Main Street, Suite 200<br>
                                    Metropolis, NY 10001<br>
                                    United States
                                </span>
                            </div>

                        </div>

                    </div>
                </section>


                <!-- =====================================================
                 PREFERENCES
            ====================================================== -->

                <section class="review-card preferences-card" id="preferencesCard" data-section="Preferences">

                    <div class="review-card-header">

                        <h2 class="review-card-title">
                            <i class="bi bi-sliders2"></i>
                            Preferences
                        </h2>

                        <button type="button" class="edit-btn" data-edit-section="preferences">
                            <i class="bi bi-pencil"></i>
                            <span>Edit</span>
                        </button>

                    </div>

                    <div class="card-divider"></div>

                    <div class="review-card-body">

                        <div class="review-grid">

                            <div class="field" data-field="currency">
                                <span class="field-label">
                                    Currency
                                </span>

                                <span class="field-value">
                                    USD ($)
                                </span>
                            </div>

                            <div class="field" data-field="timezone">
                                <span class="field-label">
                                    Timezone
                                </span>

                                <span class="field-value">
                                    Eastern Time (ET)
                                </span>
                            </div>

                        </div>

                    </div>
                </section>


                <!-- =====================================================
                 COMPLETION
            ====================================================== -->

                <section class="completion-panel">

                    <div class="confirm-row">

                        <input type="checkbox" id="confirmInfo" class="confirm-checkbox">

                        <label for="confirmInfo" class="confirm-label">
                            I confirm that the information provided for my
                            business is correct and I agree to the
                            <a href="#" class="terms-link" id="termsLink">
                                Terms of Service
                            </a>.
                        </label>

                    </div>


                    <div class="actions">

                        <button type="button" class="btn-back" id="backButton">
                            Back
                        </button>

                        <button type="button" class="btn-complete" id="completeButton" disabled="">
                            <span>Complete Business Setup</span>
                            <i class="bi bi-arrow-right"></i>
                        </button>

                    </div>

                </section>

            </div>

        
</div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
  console.log('CompleteReviewView mounted');
});
</script>

<style scoped>

        /* =========================================================
           RESET / GLOBAL
        ========================================================= */

        :root {
            --blue: #123bb5;
            --blue-hover: #0e329c;
            --text: #20212a;
            --muted: #555766;
            --label: #50515d;
            --border: #c9cbdc;
            --border-light: #dedfec;
            --sidebar: #ffffff;
            --page: #f9f7ff;
            --card: #ffffff;
            --line: #1e47c3;
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
        }

        body {
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Helvetica,
                Arial,
                sans-serif;

            color: var(--text);
            background: var(--page);

            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        button,
        input {
            font-family: inherit;
        }


        /* =========================================================
           APPLICATION LAYOUT
        ========================================================= */

        .app-shell {
            min-height: 100vh;
            display: flex;
            width: 100%;
        }


        /* =========================================================
           SIDEBAR
        ========================================================= */

        .sidebar {
            width: 255px;
            min-width: 255px;
            min-height: 100vh;
            background: var(--sidebar);
            border-right: 1px solid #e0e0e5;
            padding: 27px 24px;
            position: relative;
            z-index: 5;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 4px;
        }

        .brand-icon {
            width: 19px;
            height: 19px;
            color: #123db4;
            font-size: 19px;
            line-height: 19px;
        }

        .brand-name {
            font-size: 17px;
            font-weight: 700;
            letter-spacing: -0.15px;
            color: #292a31;
            line-height: 20px;
        }

        .brand-subtitle {
            color: #555664;
            font-size: 15px;
            line-height: 21px;
            margin-top: 2px;
            margin-bottom: 43px;
        }


        /* =========================================================
           STEPPER
        ========================================================= */

        .stepper {
            position: relative;
        }

        .step {
            position: relative;
            min-height: 67px;
            padding-left: 12px;
        }

        .step:last-child {
            min-height: 55px;
        }

        /*
         * Vertical blue line
         */
        .step:not(:last-child)::before {
            content: "";
            position: absolute;
            width: 2px;
            left: 3px;
            top: 16px;
            bottom: -2px;
            background: var(--line);
        }

        .step-marker {
            position: absolute;
            left: -2px;
            top: 0;
            width: 11px;
            height: 11px;
            border: 2px solid var(--line);
            border-radius: 50%;
            background: white;
            z-index: 2;
        }

        .step.completed .step-marker::after {
            content: "";
            position: absolute;
            width: 5px;
            height: 5px;
            left: 1px;
            top: 1px;
            border-radius: 50%;
            background: var(--line);
        }

        .step.active .step-marker {
            width: 12px;
            height: 12px;
            left: -2px;
            top: 0;
        }

        .step.active .step-marker::before {
            content: "";
            position: absolute;
            width: 4px;
            height: 4px;
            left: 2px;
            top: 2px;
            border-radius: 50%;
            background: var(--line);
        }

        .step-number {
            display: block;
            font-size: 12px;
            line-height: 13px;
            font-weight: 700;
            letter-spacing: 0.4px;
            color: #545563;
            margin-bottom: 3px;
        }

        .step-name {
            display: block;
            font-size: 16px;
            line-height: 20px;
            font-weight: 600;
            color: #252630;
        }

        .step.active .step-number,
        .step.active .step-name {
            color: var(--line);
        }


        /* =========================================================
           MAIN CONTENT
        ========================================================= */

        .main {
            flex: 1;
            min-width: 0;
            min-height: 100vh;
            background: var(--page);
        }

        .content {
            width: 100%;
            max-width: 849px;
            margin-left: 176px;
            padding: 48px 0 49px;
        }

        .page-title {
            margin: 0 0 7px;
            font-size: 34px;
            line-height: 42px;
            font-weight: 700;
            letter-spacing: -1.15px;
            color: #202127;
        }

        .page-description {
            margin: 0 0 26px;
            color: #555664;
            font-size: 16px;
            line-height: 23px;
            letter-spacing: -0.05px;
        }


        /* =========================================================
           REVIEW CARDS
        ========================================================= */

        .review-card {
            background: var(--card);
            border: 1px solid #c8c9db;
            border-radius: 7px;
            margin-bottom: 24px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(22, 29, 50, 0.02);
        }

        .review-card-header {
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            margin: 0 0 0;
        }

        .review-card-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            font-size: 19px;
            line-height: 24px;
            font-weight: 600;
            color: #2b2c34;
            letter-spacing: -0.25px;
        }

        .review-card-title i {
            color: #526078;
            font-size: 18px;
            line-height: 1;
        }

        .edit-btn {
            appearance: none;
            border: 0;
            background: transparent;
            padding: 3px 0;
            display: flex;
            align-items: center;
            gap: 6px;
            color: #073aaf;
            font-size: 14px;
            line-height: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: color 0.15s ease;
        }

        .edit-btn:hover {
            color: #001f83;
        }

        .edit-btn i {
            font-size: 14px;
        }

        .card-divider {
            height: 1px;
            background: #dadbe5;
            margin: 0 24px;
        }

        .review-card-body {
            padding: 16px 24px 25px;
        }

        .review-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: 68px;
            row-gap: 21px;
        }

        .field-label {
            display: block;
            color: #555662;
            font-size: 12px;
            line-height: 16px;
            font-weight: 600;
            letter-spacing: 0.55px;
            margin-bottom: 3px;
        }

        .field-value {
            display: block;
            color: #292a32;
            font-size: 16px;
            line-height: 23px;
            font-weight: 400;
            letter-spacing: -0.1px;
        }


        /* =========================================================
           BUSINESS INFO
        ========================================================= */

        .business-card .review-card-body {
            padding-top: 16px;
            padding-bottom: 25px;
        }

        .business-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: 68px;
            row-gap: 21px;
        }

        .business-grid .store-name {
            grid-column: 1 / -1;
        }

        .business-grid .address {
            grid-column: 1 / 2;
        }

        .address-value {
            line-height: 24px;
        }


        /* =========================================================
           PREFERENCES
        ========================================================= */

        .preferences-card {
            margin-bottom: 31px;
        }

        .preferences-card .review-card-body {
            padding-top: 16px;
            padding-bottom: 26px;
        }


        /* =========================================================
           CONFIRMATION / ACTION AREA
        ========================================================= */

        .completion-panel {
            background: #f7f5fd;
            border: 1px solid #e1dfea;
            border-radius: 7px;
            min-height: 171px;
            padding: 27px 24px 23px;
        }

        .confirm-row {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            margin-bottom: 25px;
        }

        .confirm-checkbox {
            appearance: none;
            width: 15px;
            height: 15px;
            min-width: 15px;
            margin: 4px 0 0;
            border: 1px solid #bdc0cf;
            border-radius: 2px;
            background: white;
            cursor: pointer;
            position: relative;
            transition: all 0.12s ease;
        }

        .confirm-checkbox:hover {
            border-color: #4262bd;
        }

        .confirm-checkbox:checked {
            background: var(--blue);
            border-color: var(--blue);
        }

        .confirm-checkbox:checked::after {
            content: "";
            position: absolute;
            width: 5px;
            height: 8px;
            left: 4px;
            top: 1px;
            border-right: 2px solid white;
            border-bottom: 2px solid white;
            transform: rotate(45deg);
        }

        .confirm-label {
            color: #3d3e48;
            font-size: 16px;
            line-height: 23px;
            font-weight: 400;
            cursor: pointer;
            max-width: 720px;
        }

        .terms-link {
            color: #003bb4;
            text-decoration: none;
        }

        .terms-link:hover {
            text-decoration: underline;
        }

        .actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 15px;
        }

        .btn-back {
            height: 50px;
            min-width: 88px;
            border: 1px solid #c7cad9;
            border-radius: 7px;
            background: #ffffff;
            color: #2a2b34;
            font-size: 16px;
            font-weight: 600;
            padding: 0 22px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-back:hover {
            background: #f5f6fa;
            border-color: #afb3c5;
        }

        .btn-complete {
            height: 50px;
            min-width: 267px;
            border: 1px solid var(--blue);
            border-radius: 7px;
            background: var(--blue);
            color: white;
            font-size: 16px;
            font-weight: 600;
            padding: 0 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition:
                background 0.15s ease,
                border-color 0.15s ease,
                transform 0.05s ease;
        }

        .btn-complete:hover:not(:disabled) {
            background: var(--blue-hover);
            border-color: var(--blue-hover);
        }

        .btn-complete:active:not(:disabled) {
            transform: translateY(1px);
        }

        .btn-complete:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .btn-complete i {
            font-size: 18px;
            line-height: 1;
        }


        /* =========================================================
           EDIT MODE
        ========================================================= */

        .editing {
            outline: 2px solid rgba(18, 59, 181, 0.12);
            outline-offset: -2px;
        }

        .edit-input {
            width: 100%;
            max-width: 280px;
            height: 36px;
            border: 1px solid #aeb3c8;
            border-radius: 5px;
            padding: 5px 9px;
            color: #252630;
            font-size: 15px;
            outline: none;
            background: #fff;
        }

        .edit-input:focus {
            border-color: #2d53bd;
            box-shadow: 0 0 0 3px rgba(45, 83, 189, 0.11);
        }

        .edit-actions {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-top: 7px;
        }

        .small-action {
            border: 0;
            border-radius: 4px;
            padding: 3px 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .small-save {
            background: #123bb5;
            color: white;
        }

        .small-cancel {
            background: #ececf2;
            color: #333541;
        }


        /* =========================================================
           TOAST
        ========================================================= */

        .toast-container-custom {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 9999;
        }

        .custom-toast {
            min-width: 300px;
            background: white;
            border: 1px solid #d4d6e1;
            border-radius: 8px;
            box-shadow: 0 10px 35px rgba(20, 28, 52, 0.16);
            padding: 15px 17px;
            display: flex;
            align-items: center;
            gap: 11px;
            opacity: 0;
            transform: translateY(15px);
            pointer-events: none;
            transition: all 0.22s ease;
        }

        .custom-toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast-icon {
            color: #123bb5;
            font-size: 19px;
        }

        .toast-text {
            color: #2c2d35;
            font-size: 14px;
            line-height: 20px;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1250px) {
            .content {
                margin-left: 80px;
                margin-right: 40px;
            }
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 215px;
                min-width: 215px;
                padding-left: 20px;
                padding-right: 20px;
            }

            .content {
                margin-left: 40px;
                margin-right: 30px;
            }
        }

        @media (max-width: 700px) {
            .app-shell {
                display: block;
            }

            .sidebar {
                width: 100%;
                min-width: 100%;
                min-height: auto;
                border-right: 0;
                border-bottom: 1px solid #e0e0e5;
                padding: 20px;
            }

            .brand-subtitle {
                margin-bottom: 22px;
            }

            .stepper {
                display: flex;
                gap: 0;
                overflow-x: auto;
                padding-bottom: 5px;
            }

            .step {
                flex: 1;
                min-width: 105px;
                min-height: auto;
                padding: 20px 10px 0 0;
            }

            .step:not(:last-child)::before {
                width: calc(100% - 15px);
                height: 2px;
                left: 9px;
                top: 5px;
                bottom: auto;
            }

            .step-marker {
                left: 0;
                top: 0;
            }

            .step.active .step-marker {
                left: 0;
                top: 0;
            }

            .main {
                min-height: auto;
            }

            .content {
                margin: 0;
                padding: 30px 18px 40px;
                max-width: none;
            }

            .page-title {
                font-size: 29px;
                line-height: 36px;
            }

            .review-grid,
            .business-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .business-grid .store-name,
            .business-grid .address {
                grid-column: auto;
            }

            .review-card-header {
                padding: 0 17px;
            }

            .card-divider {
                margin: 0 17px;
            }

            .review-card-body {
                padding-left: 17px;
                padding-right: 17px;
            }

            .completion-panel {
                padding: 20px 17px;
            }

            .actions {
                justify-content: stretch;
            }

            .btn-back,
            .btn-complete {
                flex: 1;
                min-width: 0;
            }
        }

        @media (max-width: 430px) {
            .step-name {
                font-size: 14px;
            }

            .step-number {
                font-size: 10px;
            }

            .actions {
                flex-direction: column-reverse;
                width: 100%;
            }

            .btn-back,
            .btn-complete {
                width: 100%;
            }

            .custom-toast {
                min-width: 0;
                width: calc(100vw - 36px);
            }
        }
    

</style>
