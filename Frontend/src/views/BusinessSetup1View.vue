<template>
<div class="businesssetup1-page-wrapper">



            <!-- =================================
                 PROGRESS
            ================================== -->

            <div class="progress-wrapper">

                <div class="progress">


                    <!-- ACCOUNT -->

                    <div class="progress-step active">

                        <div class="step-circle">
                            <i class="bi bi-check"></i>
                        </div>

                        <div class="step-number">
                            Account
                        </div>

                    </div>


                    <div class="progress-line active"></div>


                    <!-- BUSINESS -->

                    <div class="progress-step active current">

                        <div class="step-circle">
                            <span>2</span>
                        </div>

                        <div class="step-number">
                            Business
                        </div>

                    </div>


                    <div class="progress-line"></div>


                    <!-- PREFERENCES -->

                    <div class="progress-step">

                        <div class="step-circle">
                            <span>3</span>
                        </div>

                        <div class="step-number">
                            Preferences
                        </div>

                    </div>


                    <div class="progress-line"></div>


                    <!-- COMPLETE -->

                    <div class="progress-step">

                        <div class="step-circle">
                            <span>4</span>
                        </div>

                        <div class="step-number">
                            Complete
                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================
                 CARD
            ================================== -->

            <section class="setup-card">


                <!-- CARD CONTENT -->

                <div class="card-content">

                    <h1 class="card-title">
                        Set Up Your Business
                    </h1>

                    <p class="card-subtitle">
                        Tell us about your retail operation to tailor your experience.
                    </p>


                    <!-- FORM -->

                    <form class="setup-form" id="businessSetupForm" novalidate="">


                        <!-- BUSINESS NAME + TYPE -->

                        <div class="form-row">


                            <!-- BUSINESS NAME -->

                            <div class="form-group">

                                <label for="businessName" class="form-label">
                                    Business Name
                                </label>

                                <div class="input-wrapper has-icon">

                                    <i class="bi bi-shop input-icon"></i>

                                    <input type="text" id="businessName" class="form-control-custom" placeholder="e.g. Hanif General Store" autocomplete="organization" required="">

                                </div>

                                <div class="error-message">
                                    Please enter your business name.
                                </div>

                            </div>


                            <!-- BUSINESS TYPE -->

                            <div class="form-group">

                                <label for="businessType" class="form-label">
                                    Business Type
                                </label>

                                <div class="select-wrapper">

                                    <i class="bi bi-diagram-3 select-icon"></i>

                                    <select id="businessType" class="form-control-custom" required="">

                                        <option value="" selected="" disabled="">
                                            Select Type
                                        </option>

                                        <option value="General Store">
                                            General Store
                                        </option>

                                        <option value="Grocery Store">
                                            Grocery Store
                                        </option>

                                        <option value="Supermarket">
                                            Supermarket
                                        </option>

                                        <option value="Electronics">
                                            Electronics Store
                                        </option>

                                        <option value="Pharmacy">
                                            Pharmacy
                                        </option>

                                        <option value="Clothing">
                                            Clothing Store
                                        </option>

                                        <option value="Other">
                                            Other
                                        </option>

                                    </select>

                                    <i class="bi bi-chevron-down select-arrow"></i>

                                </div>

                                <div class="error-message">
                                    Please select a business type.
                                </div>

                            </div>

                        </div>


                        <!-- BUSINESS PHONE -->

                        <div class="form-group">

                            <label for="businessPhone" class="form-label">
                                Business Phone
                            </label>

                            <div class="input-wrapper has-icon">

                                <i class="bi bi-telephone input-icon"></i>

                                <input type="tel" id="businessPhone" class="form-control-custom" placeholder="+92 300 1234567" autocomplete="tel" required="">

                            </div>

                            <div class="error-message">
                                Please enter your business phone number.
                            </div>

                        </div>


                        <!-- DIVIDER -->

                        <div class="form-divider"></div>


                        <!-- STREET ADDRESS -->

                        <div class="form-group">

                            <label for="streetAddress" class="form-label">
                                Street Address
                            </label>

                            <div class="input-wrapper has-icon">

                                <i class="bi bi-geo-alt input-icon"></i>

                                <input type="text" id="streetAddress" class="form-control-custom" placeholder="123 Market Street" autocomplete="street-address" required="">

                            </div>

                            <div class="error-message">
                                Please enter your street address.
                            </div>

                        </div>


                        <!-- CITY + COUNTRY -->

                        <div class="form-row">


                            <!-- CITY -->

                            <div class="form-group">

                                <label for="city" class="form-label">
                                    City
                                </label>

                                <input type="text" id="city" class="form-control-custom" placeholder="Lahore" autocomplete="address-level2" required="">

                                <div class="error-message">
                                    Please enter your city.
                                </div>

                            </div>


                            <!-- COUNTRY -->

                            <div class="form-group">

                                <label for="country" class="form-label">
                                    Country
                                </label>

                                <div class="country-wrapper">

                                    <input type="text" id="country" class="form-control-custom country-input" value="Pakistan" readonly="">

                                    <i class="bi bi-bag-check country-lock"></i>

                                </div>

                            </div>

                        </div>

                    </form>

                </div>


                <!-- =================================
                     FOOTER
                ================================== -->

                <div class="card-footer-custom">


                    <!-- BACK -->

                    <button type="button" class="back-button" id="backButton">

                        <i class="bi bi-arrow-left"></i>

                        <span>
                            Back
                        </span>

                    </button>


                    <!-- CONTINUE -->

                    <button type="submit" form="businessSetupForm" class="continue-button" id="continueButton">

                        <span>
                            Continue to Preferences
                        </span>

                        <i class="bi bi-arrow-right"></i>

                    </button>

                </div>

            </section>

        
</div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
  console.log('BusinessSetup1View mounted');
});
</script>

<style scoped>

        /* =========================================
           ROOT
        ========================================= */

        :root {
            --primary: #173fb5;
            --primary-light: #e8edff;
            --text: #20212a;
            --muted: #565866;
            --border: #c9cadb;
            --line: #dedfea;
            --background: #faf8ff;
            --input-bg: #fbf9ff;
        }


        /* =========================================
           RESET
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
            min-height: 100vh;

            background: var(--background);

            color: var(--text);

            font-family: "Inter", Arial, sans-serif;

            -webkit-font-smoothing: antialiased;
        }

        button,
        input,
        select {
            font-family: inherit;
        }


        /* =========================================
           PAGE
        ========================================= */

        .page {
            min-height: 100vh;
            width: 100%;

            padding: 0 24px 60px;
        }


        /* =========================================
           BRAND
        ========================================= */

        .brand {
            position: absolute;
            top: 25px;
            left: 24px;

            display: flex;
            align-items: center;
            gap: 10px;

            color: #171821;

            font-size: 19px;
            font-weight: 700;
        }

        .brand-icon {
            color: #0739a5;
            font-size: 25px;
            line-height: 1;
        }


        /* =========================================
           MAIN CONTENT
        ========================================= */

        .main-content {
            width: 100%;
            max-width: 672px;

            margin: 0 auto;
            padding-top: 187px;
        }


        /* =========================================
           PROGRESS STEPPER
        ========================================= */

        .progress-wrapper {
            width: 100%;

            padding: 0 8px;
            margin-bottom: 31px;
        }

        .progress {
            width: 100%;

            display: flex;
            align-items: flex-start;

            background: transparent;
            overflow: visible;
        }

        .progress-step {
            flex: 0 0 auto;

            display: flex;
            flex-direction: column;
            align-items: center;

            min-width: 48px;

            color: #505261;
        }

        .step-circle {
            width: 12px;
            height: 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #dfe1e9;

            color: #ffffff;

            font-size: 8px;
            font-weight: 700;

            position: relative;

            z-index: 2;
        }

        .progress-step.active .step-circle {
            background: var(--primary);
        }

        .progress-step.current .step-circle {
            width: 14px;
            height: 14px;

            margin-top: -1px;

            background: var(--primary);

            box-shadow: 0 0 0 3px rgba(23, 63, 181, 0.14);
        }

        .step-number {
            margin-top: 7px;

            font-size: 12px;
            line-height: 1;

            font-weight: 600;

            letter-spacing: .6px;

            text-transform: uppercase;
        }

        .progress-step.active .step-number {
            color: var(--primary);
        }

        .progress-line {
            flex: 1;

            height: 2px;

            margin-top: 5px;

            background: var(--line);
        }

        .progress-line.active {
            background: var(--primary);
        }


        /* =========================================
           CARD
        ========================================= */

        .setup-card {
            width: 100%;

            background: #ffffff;

            border: 1px solid #c7c9d8;

            border-radius: 12px;

            box-shadow: 0 1px 2px rgba(20, 20, 30, 0.04);

            overflow: hidden;
        }


        /* =========================================
           CARD HEADER
        ========================================= */

        .card-content {
            padding: 34px 32px 0;
        }

        .card-title {
            margin: 0 0 8px;

            color: #1e1f27;

            font-size: 32px;
            line-height: 1.2;

            font-weight: 700;

            letter-spacing: -1.1px;
        }

        .card-subtitle {
            margin: 0;

            color: var(--muted);

            font-size: 16px;
            line-height: 1.5;

            font-weight: 400;
        }


        /* =========================================
           FORM
        ========================================= */

        .setup-form {
            margin-top: 26px;
        }

        .form-row {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 24px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;

            margin-bottom: 7px;

            color: #20212a;

            font-size: 12px;
            line-height: 1.2;

            font-weight: 700;

            letter-spacing: .35px;

            text-transform: uppercase;
        }


        /* =========================================
           INPUT
        ========================================= */

        .input-wrapper {
            position: relative;
        }

        .form-control-custom {
            width: 100%;
            height: 40px;

            padding: 0 14px;

            border: 1px solid var(--border);

            border-radius: 7px;

            background: var(--input-bg);

            color: #30313b;

            font-size: 16px;

            outline: none;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }

        .form-control-custom::placeholder {
            color: #7c7d8b;
        }

        .form-control-custom:focus {
            border-color: var(--primary);

            background: #ffffff;

            box-shadow: 0 0 0 3px rgba(23, 63, 181, .08);
        }


        /* =========================================
           INPUT WITH ICON
        ========================================= */

        .has-icon .form-control-custom {
            padding-left: 40px;
        }

        .input-icon {
            position: absolute;

            left: 12px;
            top: 50%;

            transform: translateY(-50%);

            color: #575968;

            font-size: 18px;

            pointer-events: none;
        }


        /* =========================================
           SELECT
        ========================================= */

        .select-wrapper {
            position: relative;
        }

        .select-wrapper select {
            appearance: none;
            -webkit-appearance: none;

            cursor: pointer;

            padding-right: 40px;
        }

        .select-icon {
            position: absolute;

            left: 12px;
            top: 50%;

            transform: translateY(-50%);

            color: #575968;

            font-size: 18px;

            pointer-events: none;
        }

        .select-arrow {
            position: absolute;

            right: 12px;
            top: 50%;

            transform: translateY(-50%);

            color: #686a76;

            font-size: 17px;

            pointer-events: none;
        }


        /* =========================================
           DIVIDER
        ========================================= */

        .form-divider {
            height: 1px;

            margin: 0 0 26px;

            background: var(--line);
        }


        /* =========================================
           COUNTRY
        ========================================= */

        .country-wrapper {
            position: relative;
        }

        .country-lock {
            position: absolute;

            right: 12px;
            top: 50%;

            transform: translateY(-50%);

            color: #777987;

            font-size: 17px;

            pointer-events: none;
        }

        .country-input {
            padding-right: 40px;
        }


        /* =========================================
           FOOTER
        ========================================= */

        .card-footer-custom {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-top: 16px;

            padding: 16px 32px 31px;

            border-top: 0;
        }


        /* =========================================
           BACK BUTTON
        ========================================= */

        .back-button {
            height: 41px;

            padding: 0 20px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;

            border: 1px solid var(--border);

            border-radius: 7px;

            background: #ffffff;

            color: #252630;

            font-size: 13px;
            font-weight: 500;

            cursor: pointer;

            transition: all .18s ease;
        }

        .back-button:hover {
            background: #f7f7fa;

            border-color: #adb0c0;
        }


        /* =========================================
           CONTINUE BUTTON
        ========================================= */

        .continue-button {
            height: 41px;

            min-width: 220px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            padding: 0 18px;

            border: 0;

            border-radius: 7px;

            background: var(--primary);

            color: #ffffff;

            font-size: 12px;
            font-weight: 600;

            letter-spacing: .2px;

            cursor: pointer;

            transition:
                background .2s ease,
                box-shadow .2s ease,
                transform .1s ease;
        }

        .continue-button:hover {
            background: #12369d;

            box-shadow: 0 5px 14px rgba(23, 63, 181, .17);
        }

        .continue-button:active {
            transform: translateY(1px);
        }

        .continue-button i {
            font-size: 15px;
        }


        /* =========================================
           VALIDATION
        ========================================= */

        .form-control-custom.invalid {
            border-color: #dc3545;
        }

        .error-message {
            display: none;

            margin-top: 5px;

            color: #dc3545;

            font-size: 11px;
        }

        .form-group.has-error .error-message {
            display: block;
        }


        /* =========================================
           TABLET
        ========================================= */

        @media (max-width: 768px) {

            .page {
                padding: 0 18px 40px;
            }

            .brand {
                position: relative;

                top: auto;
                left: auto;

                padding-top: 22px;
            }

            .main-content {
                max-width: 672px;

                padding-top: 90px;
            }

            .card-title {
                font-size: 28px;
            }

            .card-content {
                padding-left: 26px;
                padding-right: 26px;
            }

            .card-footer-custom {
                padding-left: 26px;
                padding-right: 26px;
            }

        }


        /* =========================================
           MOBILE
        ========================================= */

        @media (max-width: 600px) {

            .page {
                padding: 0 12px 25px;
            }

            .brand {
                padding-top: 18px;

                font-size: 18px;
            }

            .brand-icon {
                font-size: 23px;
            }

            .main-content {
                padding-top: 65px;
            }

            .progress-wrapper {
                padding: 0;

                margin-bottom: 23px;
            }

            .progress-step {
                min-width: 35px;
            }

            .step-number {
                font-size: 9px;

                letter-spacing: .3px;
            }

            .progress-line {
                margin-left: 5px;
                margin-right: 5px;
            }

            .card-content {
                padding: 25px 19px 0;
            }

            .card-title {
                font-size: 25px;

                letter-spacing: -.7px;
            }

            .card-subtitle {
                font-size: 14px;
            }

            .setup-form {
                margin-top: 24px;
            }

            .form-row {
                grid-template-columns: 1fr;

                gap: 0;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-divider {
                margin-top: 2px;
                margin-bottom: 23px;
            }

            .card-footer-custom {
                flex-direction: column-reverse;
                align-items: stretch;

                gap: 12px;

                margin-top: 8px;

                padding: 15px 19px 23px;
            }

            .back-button,
            .continue-button {
                width: 100%;
            }

            .continue-button {
                min-width: 0;
            }
        }


        /* =========================================
           SMALL MOBILE
        ========================================= */

        @media (max-width: 400px) {

            .page {
                padding-left: 8px;
                padding-right: 8px;
            }

            .main-content {
                padding-top: 50px;
            }

            .step-number {
                font-size: 8px;
            }

            .progress-step {
                min-width: 30px;
            }

            .card-title {
                font-size: 23px;
            }

            .card-subtitle {
                font-size: 13px;
            }

            .form-control-custom {
                height: 43px;

                font-size: 15px;
            }

        }
    

</style>
