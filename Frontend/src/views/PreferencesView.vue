<template>
<div class="preferences-page-wrapper">



            <!-- =================================================
             STEPPER
        ================================================== -->

            <div class="stepper">


                <!-- PROFILE -->

                <div class="step completed">

                    <div class="step-marker">
                        <i class="bi bi-check"></i>
                    </div>

                    <div class="step-label">
                        Profile
                    </div>

                </div>


                <div class="step-line completed"></div>


                <!-- STORE DETAILS -->

                <div class="step completed">

                    <div class="step-marker">
                        <i class="bi bi-check"></i>
                    </div>

                    <div class="step-label">
                        Store Details
                    </div>

                </div>


                <div class="step-line completed"></div>


                <!-- PREFERENCES -->

                <div class="step active">

                    <div class="step-marker">
                        3
                    </div>

                    <div class="step-label">
                        Preferences
                    </div>

                </div>


                <div class="step-line"></div>


                <!-- REVIEW -->

                <div class="step">

                    <div class="step-marker">
                        4
                    </div>

                    <div class="step-label">
                        Review
                    </div>

                </div>

            </div>


            <!-- =================================================
             PAGE HEADING
        ================================================== -->

            <div class="page-heading">

                <h1>
                    Business Preferences
                </h1>

                <p>
                    Configure how your business operates regionally and how you present to customers.
                </p>

            </div>


            <!-- =================================================
             REGIONAL SETTINGS
        ================================================== -->

            <section class="preference-card">

                <div class="card-heading">

                    <i class="bi bi-globe2"></i>

                    <h2>
                        Regional Settings
                    </h2>

                </div>


                <div class="form-grid">


                    <!-- CURRENCY -->

                    <div class="form-group">

                        <label for="currency" class="form-label">
                            Base Currency
                        </label>

                        <div class="input-wrapper">

                            <input type="text" id="currency" class="form-control-custom locked-input" value="PKR - Pakistani Rupee" readonly="">

                            <i class="bi bi-lock input-lock"></i>

                        </div>

                        <div class="help-text">
                            Defaulted based on your selected region.
                        </div>

                    </div>


                    <!-- TIMEZONE -->

                    <div class="form-group">

                        <label for="timezone" class="form-label">
                            Timezone
                        </label>

                        <div class="select-wrapper">

                            <select id="timezone" class="form-control-custom">

                                <option selected="">
                                    PKT (Asia/Karachi)
                                </option>

                                <option>
                                    UTC
                                </option>

                                <option>
                                    GMT
                                </option>

                                <option>
                                    GST (Asia/Dubai)
                                </option>

                            </select>

                            <i class="bi bi-chevron-down select-arrow"></i>

                        </div>

                    </div>

                </div>


                <!-- LANGUAGE -->

                <div class="form-group mb-0">

                    <label class="form-label language-label">
                        Preferred Interface Language
                    </label>

                    <div class="language-toggle">

                        <button type="button" class="language-button active" data-language="English">
                            English
                        </button>

                        <button type="button" class="language-button" data-language="Urdu">
                            Urdu
                        </button>

                    </div>

                </div>

            </section>


            <!-- =================================================
             FORMATTING
        ================================================== -->

            <section class="preference-card">

                <div class="card-heading">

                    <i class="bi bi-calendar3"></i>

                    <h2>
                        Formatting
                    </h2>

                </div>


                <div class="form-group mb-0">

                    <label for="dateFormat" class="form-label">
                        Date Format
                    </label>

                    <div class="select-wrapper date-select">

                        <select id="dateFormat" class="form-control-custom">

                            <option selected="">
                                12 Aug 2026 (DD MMM YYYY)
                            </option>

                            <option>
                                12/08/2026 (DD/MM/YYYY)
                            </option>

                            <option>
                                08/12/2026 (MM/DD/YYYY)
                            </option>

                            <option>
                                2026-08-12 (YYYY-MM-DD)
                            </option>

                        </select>

                        <i class="bi bi-chevron-down select-arrow"></i>

                    </div>

                </div>

            </section>


            <!-- =================================================
             RECEIPT PRESENTATION
        ================================================== -->

            <section class="preference-card">

                <div class="card-heading">

                    <i class="bi bi-receipt"></i>

                    <h2>
                        Receipt Presentation
                    </h2>

                </div>


                <div class="receipt-grid">


                    <!-- BUSINESS NAME -->

                    <div class="form-group receipt-group">

                        <label for="receiptName" class="form-label">
                            Business Name on Receipts
                        </label>

                        <input type="text" id="receiptName" class="form-control-custom" value="Acme Corp">

                    </div>


                    <!-- CONTACT -->

                    <div class="form-group receipt-group">

                        <label for="receiptPhone" class="form-label">
                            Contact Number on Receipts
                        </label>

                        <input type="tel" id="receiptPhone" class="form-control-custom" value="+92 300 1234567">

                    </div>

                </div>


                <!-- LOGO -->

                <div class="logo-row">

                    <div>

                        <p class="logo-title">
                            Show Business Logo
                        </p>

                        <p class="logo-description">
                            Print logo at the top of customer receipts
                        </p>

                    </div>


                    <label class="switch">

                        <input type="checkbox" id="logoToggle" checked="">

                        <span class="switch-slider"></span>

                    </label>

                </div>

            </section>


            <!-- =================================================
             ACTIONS
        ================================================== -->

            <div class="actions">

                <button type="button" class="back-button" id="backButton">

                    <i class="bi bi-arrow-left"></i>

                    Back

                </button>


                <button type="button" class="continue-button" id="continueButton">

                    Continue to Review

                    <i class="bi bi-arrow-right"></i>

                </button>

            </div>

        
</div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
  console.log('PreferencesView mounted');
});
</script>

<style scoped>

        /* =====================================================
           ROOT
        ===================================================== */

        :root {
            --primary: #0739ad;
            --primary-hover: #052f92;

            --text: #20212a;
            --muted: #555866;

            --border: #c8cad9;
            --divider: #dedfea;

            --page-bg: #ffffff;
            --card-bg: #ffffff;
            --input-bg: #ffffff;

            --radius: 7px;
        }


        /* =====================================================
           RESET
        ===================================================== */

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
            min-height: 100vh;

            background: var(--page-bg);

            color: var(--text);

            font-family: "Inter", Arial, sans-serif;

            -webkit-font-smoothing: antialiased;
        }

        button,
        input,
        select {
            font-family: inherit;
        }


        /* =====================================================
           PAGE
        ===================================================== */

        .page {
            width: 100%;
            min-height: 100vh;

            padding: 48px 20px 55px;
        }


        /* =====================================================
           MAIN WRAPPER
        ===================================================== */

        .main-wrapper {
            width: 100%;
            max-width: 754px;

            margin: 0 auto;
        }


        /* =====================================================
           STEPPER
        ===================================================== */

        .stepper {
            width: 100%;

            display: flex;
            align-items: flex-start;

            margin-bottom: 30px;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;

            flex-shrink: 0;

            min-width: 65px;
        }

        .step-marker {
            width: 14px;
            height: 14px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #d9dce6;

            color: #4d5060;

            font-size: 8px;
            font-weight: 700;

            position: relative;

            z-index: 2;
        }

        .step.completed .step-marker,
        .step.active .step-marker {
            background: var(--primary);
            color: #fff;
        }

        .step.active .step-marker {
            box-shadow: 0 0 0 3px rgba(7, 57, 173, .14);
        }

        .step-label {
            margin-top: 7px;

            color: #4d5060;

            font-size: 14px;
            line-height: 1.1;

            font-weight: 500;

            white-space: nowrap;
        }

        .step.active .step-label {
            color: var(--primary);
            font-weight: 700;
        }

        .step-line {
            flex: 1;

            height: 2px;

            margin-top: 6px;

            background: #dedfe6;
        }

        .step-line.completed {
            background: var(--primary);
        }


        /* =====================================================
           PAGE TITLE
        ===================================================== */

        .page-heading {
            text-align: center;

            margin-bottom: 31px;
        }

        .page-heading h1 {
            margin: 0 0 9px;

            color: #1f2028;

            font-size: 29px;
            line-height: 1.2;

            font-weight: 700;

            letter-spacing: -.8px;
        }

        .page-heading p {
            margin: 0;

            color: #555866;

            font-size: 15px;
            line-height: 1.5;
        }


        /* =====================================================
           CARD
        ===================================================== */

        .preference-card {
            width: 100%;

            margin-bottom: 22px;

            padding: 23px 25px 25px;

            background: var(--card-bg);

            border: 1px solid var(--border);

            border-radius: 7px;

            box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
        }


        /* =====================================================
           CARD HEADER
        ===================================================== */

        .card-heading {
            display: flex;
            align-items: center;
            gap: 11px;

            padding-bottom: 13px;

            border-bottom: 1px solid var(--divider);

            margin-bottom: 25px;
        }

        .card-heading i {
            color: #52637e;

            font-size: 20px;
        }

        .card-heading h2 {
            margin: 0;

            color: #252630;

            font-size: 18px;
            line-height: 1.2;

            font-weight: 600;
        }


        /* =====================================================
           FORM GRID
        ===================================================== */

        .form-grid {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 23px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;

            margin-bottom: 8px;

            color: #565866;

            font-size: 15px;
            line-height: 1.2;

            font-weight: 400;
        }


        /* =====================================================
           INPUT
        ===================================================== */

        .input-wrapper {
            position: relative;
        }

        .form-control-custom {
            width: 100%;
            height: 40px;

            padding: 0 12px;

            border: 1px solid var(--border);

            border-radius: 5px;

            background: var(--input-bg);

            color: #282932;

            font-size: 15px;

            outline: none;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }

        .form-control-custom:focus {
            border-color: var(--primary);

            box-shadow:
                0 0 0 3px rgba(7, 57, 173, .08);
        }

        .form-control-custom::placeholder {
            color: #777a87;
        }


        /* =====================================================
           LOCKED INPUT
        ===================================================== */

        .locked-input {
            padding-right: 40px;

            color: #555866;

            background: #ffffff;
        }

        .input-lock {
            position: absolute;

            top: 50%;
            right: 12px;

            transform: translateY(-50%);

            color: #777987;

            font-size: 16px;

            pointer-events: none;
        }


        /* =====================================================
           SELECT
        ===================================================== */

        .select-wrapper {
            position: relative;
        }

        .select-wrapper select {
            appearance: none;
            -webkit-appearance: none;

            padding-right: 40px;

            cursor: pointer;
        }

        .select-arrow {
            position: absolute;

            top: 50%;
            right: 12px;

            transform: translateY(-50%);

            color: #565968;

            font-size: 15px;

            pointer-events: none;
        }


        /* =====================================================
           HELP TEXT
        ===================================================== */

        .help-text {
            margin-top: 8px;

            color: #626573;

            font-size: 12px;
            line-height: 1.4;
        }


        /* =====================================================
           LANGUAGE TOGGLE
        ===================================================== */

        .language-label {
            margin-bottom: 8px;
        }

        .language-toggle {
            display: inline-flex;

            padding: 4px;

            border: 1px solid var(--border);

            border-radius: 6px;

            background: #f7f6fc;
        }

        .language-button {
            min-width: 102px;
            height: 37px;

            padding: 0 18px;

            border: 0;

            border-radius: 4px;

            background: transparent;

            color: #555866;

            font-size: 15px;

            cursor: pointer;

            transition: all .2s ease;
        }

        .language-button.active {
            background: var(--primary);

            color: #ffffff;

            box-shadow: 0 1px 2px rgba(0, 0, 0, .1);
        }

        .language-button:hover:not(.active) {
            background: #ececf4;
        }


        /* =====================================================
           DATE FORMAT
        ===================================================== */

        .date-select {
            width: 340px;
            max-width: 100%;
        }


        /* =====================================================
           RECEIPT FORM
        ===================================================== */

        .receipt-grid {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 23px;
        }

        .receipt-group {
            margin-bottom: 0;
        }


        /* =====================================================
           LOGO TOGGLE
        ===================================================== */

        .logo-row {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-top: 22px;
        }

        .logo-title {
            margin: 0 0 4px;

            color: #272832;

            font-size: 15px;

            font-weight: 600;
        }

        .logo-description {
            margin: 0;

            color: #555866;

            font-size: 12px;
        }


        /* =====================================================
           SWITCH
        ===================================================== */

        .switch {
            position: relative;

            width: 44px;
            height: 24px;

            flex-shrink: 0;
        }

        .switch input {
            width: 0;
            height: 0;

            opacity: 0;
        }

        .switch-slider {
            position: absolute;

            inset: 0;

            border-radius: 50px;

            background: #c7cad4;

            cursor: pointer;

            transition: background .2s ease;
        }

        .switch-slider::before {
            content: "";

            position: absolute;

            width: 18px;
            height: 18px;

            left: 3px;
            top: 3px;

            border-radius: 50%;

            background: #ffffff;

            box-shadow: 0 1px 3px rgba(0, 0, 0, .2);

            transition: transform .2s ease;
        }

        .switch input:checked+.switch-slider {
            background: var(--primary);
        }

        .switch input:checked+.switch-slider::before {
            transform: translateX(20px);
        }


        /* =====================================================
           FOOTER ACTIONS
        ===================================================== */

        .actions {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding-top: 24px;

            border-top: 1px solid var(--divider);

            margin-top: 46px;
        }

        .back-button,
        .continue-button {
            height: 41px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;

            border-radius: 4px;

            font-size: 14px;

            cursor: pointer;

            transition: all .2s ease;
        }

        .back-button {
            min-width: 88px;

            padding: 0 16px;

            border: 1px solid var(--border);

            background: #ffffff;

            color: #282932;
        }

        .back-button:hover {
            background: #f7f7fa;
        }

        .continue-button {
            min-width: 195px;

            padding: 0 18px;

            border: 0;

            background: var(--primary);

            color: #ffffff;

            font-weight: 500;
        }

        .continue-button:hover {
            background: var(--primary-hover);

            box-shadow: 0 4px 12px rgba(7, 57, 173, .18);
        }

        .continue-button:active,
        .back-button:active {
            transform: translateY(1px);
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 768px) {

            .page {
                padding: 35px 18px 45px;
            }

            .main-wrapper {
                max-width: 680px;
            }

            .page-heading h1 {
                font-size: 27px;
            }

            .preference-card {
                padding: 21px 20px 23px;
            }

            .form-grid,
            .receipt-grid {
                gap: 18px;
            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .page {
                padding: 25px 12px 35px;
            }

            .stepper {
                margin-bottom: 24px;
            }

            .step {
                min-width: 42px;
            }

            .step-label {
                font-size: 10px;
            }

            .step-line {
                margin-top: 6px;
            }

            .page-heading {
                margin-bottom: 24px;
            }

            .page-heading h1 {
                font-size: 24px;
            }

            .page-heading p {
                font-size: 13px;

                max-width: 420px;

                margin-left: auto;
                margin-right: auto;
            }

            .preference-card {
                padding: 18px 16px 20px;

                margin-bottom: 16px;
            }

            .card-heading {
                margin-bottom: 21px;
            }

            .card-heading h2 {
                font-size: 17px;
            }

            .form-grid,
            .receipt-grid {
                grid-template-columns: 1fr;

                gap: 0;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .date-select {
                width: 100%;
            }

            .language-button {
                min-width: 90px;
            }

            .logo-row {
                margin-top: 8px;
            }

            .actions {
                margin-top: 30px;

                padding-top: 18px;

                gap: 12px;
            }

            .back-button,
            .continue-button {
                min-width: 0;

                flex: 1;
            }

        }


        /* =====================================================
           SMALL MOBILE
        ===================================================== */

        @media (max-width: 400px) {

            .page {
                padding-left: 8px;
                padding-right: 8px;
            }

            .step-label {
                font-size: 9px;
            }

            .step-marker {
                width: 12px;
                height: 12px;
            }

            .page-heading h1 {
                font-size: 22px;
            }

            .page-heading p {
                font-size: 12px;
            }

            .preference-card {
                padding-left: 14px;
                padding-right: 14px;
            }

            .actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .back-button,
            .continue-button {
                width: 100%;
            }

        }
    

</style>
