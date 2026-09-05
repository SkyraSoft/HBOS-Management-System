<template>
<div class="about-page-wrapper">


            <!-- HEADER -->

            <section class="setup-header">

                <h1>
                    Tell us about your business
                </h1>

                <p>
                    Let's start with the basic details of your store to customize your HBOS experience.
                </p>


                <!-- STEPPER -->

                <div class="stepper" aria-label="Setup progress">

                    <!-- STEP 1 -->

                    <div class="step-item active">

                        <div class="step-number">
                            1
                        </div>

                        <span class="step-text">
                            Business Information
                        </span>

                    </div>


                    <div class="step-line"></div>


                    <!-- STEP 2 -->

                    <div class="step-item">

                        <div class="step-number">
                            2
                        </div>

                        <span class="step-text">
                            Store Details
                        </span>

                    </div>


                    <div class="step-line"></div>


                    <!-- STEP 3 -->

                    <div class="step-item">

                        <div class="step-number">
                            3
                        </div>

                        <span class="step-text">
                            Preferences
                        </span>

                    </div>


                    <div class="step-line"></div>


                    <!-- STEP 4 -->

                    <div class="step-item">

                        <div class="step-number">
                            4
                        </div>

                        <span class="step-text">
                            Review
                        </span>

                    </div>

                </div>

            </section>


            <!-- =====================================
                 FORM
            ====================================== -->

            <form class="setup-form" id="businessForm" novalidate="">

                <!-- BUSINESS NAME -->

                <div class="field">

                    <label for="businessName" class="field-label">
                        Business Name
                    </label>

                    <input type="text" id="businessName" name="businessName" class="field-control" value="Hanif General Store" placeholder="Enter business name" autocomplete="organization" required="">

                    <div class="field-error">
                        Please enter your business name.
                    </div>

                </div>


                <!-- BUSINESS TYPE -->

                <div class="field">

                    <label for="businessType" class="field-label">
                        Business Type
                    </label>

                    <div class="select-wrapper">

                        <select id="businessType" name="businessType" class="field-control" required="">

                            <option value="General Store" selected="">
                                General Store
                            </option>

                            <option value="Grocery Store">
                                Grocery Store
                            </option>

                            <option value="Supermarket">
                                Supermarket
                            </option>

                            <option value="Electronics Store">
                                Electronics Store
                            </option>

                            <option value="Pharmacy">
                                Pharmacy
                            </option>

                            <option value="Clothing Store">
                                Clothing Store
                            </option>

                            <option value="Other">
                                Other
                            </option>

                        </select>

                        <i class="bi bi-chevron-down select-arrow"></i>

                    </div>

                    <div class="field-error">
                        Please select your business type.
                    </div>

                </div>


                <!-- OWNER NAME -->

                <div class="field">

                    <label for="ownerName" class="field-label">
                        Owner Name
                    </label>

                    <input type="text" id="ownerName" name="ownerName" class="field-control" value="Mr. Hanif" placeholder="Enter owner name" autocomplete="name" required="">

                    <div class="field-error">
                        Please enter the owner's name.
                    </div>

                </div>

            </form>


            <!-- =====================================
                 FOOTER
            ====================================== -->

            <footer class="form-footer">

                <button type="submit" form="businessForm" class="continue-button" id="continueButton">
                    <span>
                        Continue
                    </span>

                    <i class="bi bi-arrow-right"></i>
                </button>

            </footer>

        
</div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
  console.log('AboutView mounted');
});
</script>

<style scoped>

        :root {
            --primary: #075bdc;
            --primary-hover: #064fbe;
            --text: #17181b;
            --muted: #555960;
            --border: #c8ccd3;
            --line: #dfe2e7;
            --bg: #ffffff;
        }

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
            background: #ffffff;
            color: var(--text);
            font-family: "Inter", Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        button,
        input,
        select {
            font: inherit;
        }

        /* =========================================
           PAGE
        ========================================= */

        .setup-page {
            min-height: 100vh;
            width: 100%;

            display: flex;
            flex-direction: column;
            align-items: center;

            padding: 0 2px 2px;
        }

        /* =========================================
           TOP BAR
        ========================================= */

        .top-bar {
            width: 100%;
            max-width: 800px;
            height: 55px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 8px;
        }

        .brand-icon {
            width: 27px;
            height: 27px;

            display: flex;
            align-items: center;
            justify-content: center;

            color: var(--primary);
            font-size: 22px;
        }

        .step-counter {
            padding: 5px 10px;

            border-radius: 999px;

            background: #f0f1f3;
            color: #4d5158;

            font-size: 12px;
            font-weight: 600;
            line-height: 1;
        }

        /* =========================================
           MAIN CONTAINER
        ========================================= */

        .setup-container {
            width: 100%;
            max-width: 800px;

            background: var(--bg);

            border: 1px solid #9fa4ab;
            border-radius: 13px;

            overflow: hidden;
        }

        /* =========================================
           HEADER
        ========================================= */

        .setup-header {
            padding: 33px 33px 0;
        }

        .setup-header h1 {
            margin: 0 0 16px;

            color: #111214;

            font-size: 21px;
            line-height: 1.25;
            font-weight: 700;

            letter-spacing: -0.4px;
        }

        .setup-header p {
            margin: 0;

            color: var(--muted);

            font-size: 16px;
            line-height: 1.5;
            font-weight: 400;
        }

        /* =========================================
           STEPPER
        ========================================= */

        .stepper {
            width: 100%;

            display: flex;
            align-items: center;

            margin-top: 25px;
            padding-bottom: 29px;

            border-bottom: 1px solid var(--line);
        }

        .step-item {
            display: flex;
            align-items: center;
            flex-shrink: 0;

            color: #a3a5aa;

            font-size: 14px;
            white-space: nowrap;
        }

        .step-number {
            width: 24px;
            height: 24px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-right: 8px;

            border-radius: 50%;

            background: #f0f1f3;
            color: #999ca2;

            font-size: 12px;
            font-weight: 500;
        }

        .step-item.active {
            color: var(--primary);
        }

        .step-item.active .step-number {
            background: var(--primary);
            color: #ffffff;
        }

        .step-line {
            flex: 1;

            height: 2px;

            min-width: 30px;
            margin: 0 13px;

            background: #e0e2e5;
        }

        .step-line.active {
            background: #d9dce1;
        }

        /* =========================================
           FORM
        ========================================= */

        .setup-form {
            padding: 25px 33px 0;
        }

        .field {
            margin-bottom: 17px;
        }

        .field:last-child {
            margin-bottom: 0;
        }

        .field-label {
            display: block;

            margin-bottom: 6px;

            color: #1c1d20;

            font-size: 14px;
            line-height: 1.3;
            font-weight: 500;
        }

        .field-control {
            width: 100%;
            height: 43px;

            padding: 0 16px;

            border: 1px solid #bfc3ca;
            border-radius: 6px;

            background: #ffffff;

            color: #303237;

            font-size: 16px;

            outline: none;

            transition:
                border-color .18s ease,
                box-shadow .18s ease;
        }

        .field-control::placeholder {
            color: #7d8087;
        }

        .field-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(7, 91, 220, .08);
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

            padding-right: 45px;

            cursor: pointer;
        }

        .select-arrow {
            position: absolute;

            top: 50%;
            right: 15px;

            transform: translateY(-50%);

            color: #5b6068;

            font-size: 16px;

            pointer-events: none;
        }

        /* =========================================
           FOOTER
        ========================================= */

        .form-footer {
            margin-top: 31px;

            padding: 16px 33px 31px;

            border-top: 1px solid var(--line);

            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .continue-button {
            min-width: 125px;
            height: 37px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;

            padding: 0 18px;

            border: 0;
            border-radius: 7px;

            background: var(--primary);
            color: #ffffff;

            font-size: 14px;
            font-weight: 500;

            cursor: pointer;

            transition:
                background .18s ease,
                box-shadow .18s ease,
                transform .1s ease;
        }

        .continue-button:hover {
            background: var(--primary-hover);
            box-shadow: 0 4px 12px rgba(7, 91, 220, .16);
        }

        .continue-button:active {
            transform: translateY(1px);
        }

        .continue-button i {
            font-size: 16px;
        }

        /* =========================================
           VALIDATION
        ========================================= */

        .field-error {
            display: none;

            margin-top: 5px;

            color: #dc3545;

            font-size: 12px;
        }

        .field-control.error {
            border-color: #dc3545;
        }

        .field-control.error:focus {
            box-shadow: 0 0 0 2px rgba(220, 53, 69, .08);
        }

        /* =========================================
           RESPONSIVE — TABLET
        ========================================= */

        @media (max-width: 700px) {

            .top-bar {
                height: 52px;

                padding-left: 15px;
                padding-right: 15px;
            }

            .setup-page {
                padding: 0;
            }

            .setup-container {
                max-width: none;

                border-radius: 0;
                border-left: 0;
                border-right: 0;
            }

            .setup-header {
                padding-left: 25px;
                padding-right: 25px;
            }

            .setup-form {
                padding-left: 25px;
                padding-right: 25px;
            }

            .form-footer {
                padding-left: 25px;
                padding-right: 25px;
            }
        }

        /* =========================================
           RESPONSIVE — MOBILE
        ========================================= */

        @media (max-width: 540px) {

            .top-bar {
                height: 52px;
            }

            .setup-header {
                padding: 28px 18px 0;
            }

            .setup-header h1 {
                font-size: 20px;
                margin-bottom: 12px;
            }

            .setup-header p {
                font-size: 14px;
                line-height: 1.55;
            }

            .stepper {
                margin-top: 23px;
                padding-bottom: 24px;
            }

            .step-item {
                font-size: 12px;
            }

            .step-number {
                width: 23px;
                height: 23px;

                margin-right: 5px;
            }

            .step-line {
                min-width: 12px;
                margin: 0 7px;
            }

            .setup-form {
                padding: 23px 18px 0;
            }

            .field {
                margin-bottom: 18px;
            }

            .field-label {
                font-size: 13px;
            }

            .field-control {
                height: 44px;
                font-size: 15px;
            }

            .form-footer {
                margin-top: 28px;

                padding: 15px 18px 24px;
            }

            .continue-button {
                width: 100%;
                height: 44px;
            }
        }

        /* =========================================
           SMALL MOBILE
        ========================================= */

        @media (max-width: 390px) {

            .step-item span.step-text {
                display: none;
            }

            .step-number {
                margin-right: 0;
            }

            .step-line {
                margin: 0 8px;
            }

            .setup-header h1 {
                font-size: 19px;
            }

            .setup-header p {
                font-size: 13px;
            }
        }

        /* =========================================
           ACCESSIBILITY
        ========================================= */

        button:focus-visible,
        input:focus-visible,
        select:focus-visible {
            outline: 2px solid rgba(7, 91, 220, .35);
            outline-offset: 2px;
        }
    

</style>
