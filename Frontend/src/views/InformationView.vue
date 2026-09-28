<template>
<div class="information-page-wrapper">


            <div class="content-inner">


                <h2 class="content-title">
                    Business Details
                </h2>


                <!-- =================================================
                 LOGO
            ================================================== -->

                <section class="logo-section">

                    <label class="field-label">
                        Business Logo (Optional)
                    </label>


                    <div class="logo-controls">

                        <div class="logo-upload-area">

                            <i class="bi bi-shop logo-icon"></i>


                            <label for="logoInput" class="upload-button">
                                Upload Logo
                            </label>


                            <input type="file" id="logoInput" accept="image/*" hidden="">

                        </div>


                        <button type="button" class="remove-button" id="removeLogo">
                            Remove
                        </button>

                    </div>

                </section>



                <!-- =================================================
                 BUSINESS FORM
            ================================================== -->

                <form class="business-form" id="businessForm">


                    <!-- Business Name -->

                    <div class="form-row">

                        <div class="form-field full">

                            <label for="businessName" class="field-label">
                                Business Name
                            </label>

                            <input type="text" id="businessName" class="form-control" value="Hanif General Store" placeholder="Enter business name" required="">

                        </div>

                    </div>



                    <!-- Business Type + Phone -->

                    <div class="form-row">

                        <div class="form-field">

                            <label for="businessType" class="field-label">
                                Business Type
                            </label>

                            <select id="businessType" class="form-select" required="">

                                <option selected="">
                                    General Store
                                </option>

                                <option>
                                    Supermarket
                                </option>

                                <option>
                                    Grocery Store
                                </option>

                                <option>
                                    Pharmacy
                                </option>

                                <option>
                                    Electronics
                                </option>

                                <option>
                                    Clothing
                                </option>

                                <option>
                                    Other
                                </option>

                            </select>

                        </div>


                        <div class="form-field">

                            <label for="businessPhone" class="field-label">
                                Business Phone
                            </label>

                            <input type="tel" id="businessPhone" class="form-control" value="+92 300 1234567" placeholder="+92 300 1234567" required="">

                        </div>

                    </div>



                    <!-- Street Address -->

                    <div class="form-row">

                        <div class="form-field full">

                            <label for="streetAddress" class="field-label">
                                Street Address
                            </label>

                            <input type="text" id="streetAddress" class="form-control" value="123 Main Market Road" placeholder="Enter street address" required="">

                        </div>

                    </div>



                    <!-- City + Province -->

                    <div class="form-row">

                        <div class="form-field">

                            <label for="city" class="field-label">
                                City
                            </label>

                            <input type="text" id="city" class="form-control" value="Lahore" placeholder="City" required="">

                        </div>


                        <div class="form-field">

                            <label for="province" class="field-label">
                                Province/State
                            </label>

                            <input type="text" id="province" class="form-control" value="Punjab" placeholder="Province / State" required="">

                        </div>

                    </div>



                    <!-- Country -->

                    <div class="form-row">

                        <div class="form-field">

                            <label for="country" class="field-label">
                                Country
                            </label>

                            <select id="country" class="form-select" required="">

                                <option selected="">
                                    Pakistan
                                </option>

                                <option>
                                    Afghanistan
                                </option>

                                <option>
                                    United Arab Emirates
                                </option>

                                <option>
                                    Saudi Arabia
                                </option>

                                <option>
                                    United Kingdom
                                </option>

                                <option>
                                    United States
                                </option>

                                <option>
                                    Other
                                </option>

                            </select>

                        </div>

                    </div>



                    <!-- =================================================
                     ACTIONS
                ================================================== -->

                    <div class="form-footer">

                        <button type="button" class="btn-back" id="backButton">
                            Back
                        </button>


                        <button type="submit" class="btn-continue" id="continueButton">
                            Continue to Preferences
                        </button>

                    </div>


                </form>

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
            --primary: #0639ad;
            --primary-hover: #052f91;

            --text: #20212a;
            --muted: #555766;

            --border: #c8cad8;
            --divider: #dedfe7;

            --sidebar: #f1f0fa;
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

            background: #ffffff;

            color: var(--text);

            font-family: "Inter", Arial, sans-serif;

            -webkit-font-smoothing: antialiased;

            overflow-x: hidden;
        }

        button,
        input,
        select {
            font-family: inherit;
        }


        /* =====================================================
           MAIN PAGE
        ===================================================== */

        .setup-page {
            width: 100%;
            min-height: 100vh;

            display: flex;

            background: #ffffff;

            border: 1px solid #9da0ab;

            border-radius: 7px;

            overflow: hidden;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .setup-sidebar {
            width: 34%;

            flex: 0 0 34%;

            min-height: 680px;

            padding: 34px 28px 30px;

            background: var(--sidebar);

            border-right: 1px solid var(--border);
        }


        /* =====================================================
           BRAND
        ===================================================== */

        .brand-title {
            margin: 0;

            color: #20212b;

            font-size: 34px;

            line-height: 1.1;

            font-weight: 700;

            letter-spacing: -1px;
        }

        .brand-description {
            max-width: 290px;

            margin: 12px 0 0;

            color: var(--muted);

            font-size: 15px;

            line-height: 1.55;
        }


        /* =====================================================
           STEPPER
        ===================================================== */

        .setup-steps {
            width: 100%;

            margin: 34px 0 0;

            padding: 0;

            display: flex;
            align-items: flex-start;

            list-style: none;
        }


        .setup-step {
            position: relative;

            display: flex;
            align-items: flex-start;

            flex: 1;

            min-width: 118px;
        }


        /* Connector */

        .setup-step:not(:last-child)::after {
            content: "";

            position: absolute;

            top: 5px;

            left: 16px;

            right: 16px;

            height: 1px;

            background: #c5c7d1;

            z-index: 0;
        }


        .setup-step.completed:not(:last-child)::after,
        .setup-step.active:not(:last-child)::after {
            background: var(--primary);
        }


        /* Dot */

        .step-dot {
            position: relative;

            z-index: 2;

            width: 11px;
            height: 11px;

            flex: 0 0 11px;

            margin-top: 0;

            border-radius: 50%;

            background: #ffffff;

            border: 1px solid #cdd0da;
        }


        .setup-step.completed .step-dot,
        .setup-step.active .step-dot {
            background: var(--primary);

            border-color: var(--primary);
        }


        /* Step label */

        .step-text {
            position: relative;

            z-index: 2;

            margin-left: 13px;

            padding-right: 18px;

            background: var(--sidebar);

            color: #555766;

            font-size: 13px;

            line-height: 1.2;

            font-weight: 500;

            white-space: nowrap;
        }


        .setup-step.completed .step-text {
            color: #30313a;

            font-weight: 600;
        }


        .setup-step.active .step-text {
            color: var(--primary);

            font-weight: 700;
        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .setup-content {
            flex: 1;

            min-width: 0;

            padding: 34px 32px 32px;

            background: #ffffff;
        }


        .content-inner {
            width: 100%;

            max-width: 704px;

            margin: 0 auto;
        }


        /* =====================================================
           TITLE
        ===================================================== */

        .content-title {
            margin: 0 0 26px;

            color: #24252e;

            font-size: 18px;

            line-height: 1.3;

            font-weight: 600;
        }


        /* =====================================================
           LOGO SECTION
        ===================================================== */

        .logo-section {
            padding-bottom: 24px;

            border-bottom: 1px solid var(--divider);
        }


        .field-label {
            display: block;

            margin-bottom: 7px;

            color: #292a33;

            font-size: 12px;

            line-height: 1.3;

            font-weight: 600;

            letter-spacing: .2px;
        }


        .logo-controls {
            display: flex;

            align-items: center;

            gap: 24px;
        }


        .logo-upload-area {
            display: flex;

            align-items: center;

            gap: 12px;
        }


        .logo-icon {
            color: #555c69;

            font-size: 20px;
        }


        .upload-button {
            height: 35px;

            padding: 0 16px;

            display: inline-flex;

            align-items: center;
            justify-content: center;

            border: 1px solid var(--border);

            border-radius: 4px;

            background: #ffffff;

            color: #292a33;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

            transition: .2s ease;
        }


        .upload-button:hover {
            background: #f7f7fa;

            border-color: #aeb1c0;
        }


        .remove-button {
            display: none;

            padding: 0;

            border: 0;

            background: transparent;

            color: #dc2626;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;
        }


        .remove-button.show {
            display: inline-block;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .business-form {
            padding-top: 23px;
        }


        .form-row {
            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 16px;

            margin-bottom: 16px;
        }


        .form-field {
            min-width: 0;
        }


        .form-field.full {
            grid-column: 1 / -1;
        }


        /* =====================================================
           INPUTS
        ===================================================== */

        .form-control,
        .form-select {
            width: 100%;

            height: 41px;

            padding: 0 12px;

            border: 1px solid var(--border);

            border-radius: 4px;

            background: #ffffff;

            color: #2d2e37;

            font-size: 14px;

            box-shadow: none;

            outline: none;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }


        .form-control::placeholder {
            color: #737687;

            opacity: 1;
        }


        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);

            box-shadow:
                0 0 0 2px rgba(6, 57, 173, .08);
        }


        .form-select {
            cursor: pointer;
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        .form-footer {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-top: 31px;

            padding-top: 24px;

            border-top: 1px solid var(--divider);
        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .btn-back,
        .btn-continue {
            min-height: 40px;

            border-radius: 4px;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

            transition: .2s ease;
        }


        .btn-back {
            min-width: 65px;

            padding: 0 16px;

            border: 1px solid var(--border);

            background: #ffffff;

            color: #30313a;
        }


        .btn-back:hover {
            background: #f7f7fa;
        }


        .btn-continue {
            min-width: 203px;

            padding: 0 20px;

            border: 1px solid var(--primary);

            background: var(--primary);

            color: #ffffff;
        }


        .btn-continue:hover {
            background: var(--primary-hover);

            border-color: var(--primary-hover);
        }


        .btn-back:active,
        .btn-continue:active {
            transform: translateY(1px);
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 950px) {

            .setup-sidebar {
                width: 31%;

                flex-basis: 31%;

                padding: 30px 22px;
            }


            .brand-title {
                font-size: 30px;
            }


            .brand-description {
                font-size: 13px;
            }


            .setup-content {
                padding: 30px 25px;
            }


            .step-text {
                font-size: 11px;

                margin-left: 10px;

                padding-right: 12px;
            }


            .setup-step {
                min-width: 105px;
            }

        }


        /* =====================================================
           TABLET / SMALL SCREEN
        ===================================================== */

        @media (max-width: 760px) {

            .setup-page {
                flex-direction: column;

                border-radius: 0;

                border-left: 0;

                border-right: 0;
            }


            /* Sidebar becomes top section */

            .setup-sidebar {
                width: 100%;

                flex: none;

                min-height: auto;

                padding: 25px 24px 22px;

                border-right: 0;

                border-bottom: 1px solid var(--border);
            }


            .brand-description {
                max-width: 500px;
            }


            /* Horizontal stepper */

            .setup-steps {
                margin-top: 26px;

                overflow-x: auto;

                padding-bottom: 5px;
            }


            .setup-step {
                flex: 0 0 145px;

                min-width: 145px;

                align-items: center;
            }


            .setup-step:not(:last-child)::after {
                top: 5px;

                left: 15px;

                right: 10px;
            }


            .step-text {
                font-size: 11px;

                margin-left: 9px;

                padding-right: 12px;
            }


            .setup-content {
                width: 100%;

                padding: 28px 24px 30px;
            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 576px) {

            .setup-sidebar {
                padding: 22px 17px 20px;
            }


            .brand-title {
                font-size: 28px;
            }


            .brand-description {
                margin-top: 9px;

                font-size: 13px;
            }


            .setup-steps {
                margin-top: 22px;
            }


            .setup-step {
                flex: 0 0 135px;

                min-width: 135px;
            }


            .step-text {
                font-size: 10px;
            }


            .setup-content {
                padding: 24px 17px 25px;
            }


            .content-title {
                font-size: 18px;

                margin-bottom: 22px;
            }


            .logo-section {
                padding-bottom: 20px;
            }


            .logo-controls {
                flex-wrap: wrap;

                gap: 12px;
            }


            .business-form {
                padding-top: 20px;
            }


            /* One column */

            .form-row {
                grid-template-columns: 1fr;

                gap: 0;

                margin-bottom: 0;
            }


            .form-field,
            .form-field.full {
                grid-column: auto;

                margin-bottom: 17px;
            }


            /* Buttons */

            .form-footer {
                flex-direction: column-reverse;

                align-items: stretch;

                gap: 10px;

                margin-top: 10px;

                padding-top: 20px;
            }


            .btn-back,
            .btn-continue {
                width: 100%;

                min-width: 0;

                min-height: 44px;
            }

        }


        /* =====================================================
           VERY SMALL MOBILE
        ===================================================== */

        @media (max-width: 380px) {

            .setup-sidebar {
                padding-left: 13px;

                padding-right: 13px;
            }


            .setup-content {
                padding-left: 13px;

                padding-right: 13px;
            }


            .brand-title {
                font-size: 26px;
            }


            .brand-description {
                font-size: 12px;
            }


            .setup-step {
                flex-basis: 120px;

                min-width: 120px;
            }


            .step-text {
                font-size: 9px;
            }


            .form-control,
            .form-select {
                height: 42px;

                font-size: 13px;
            }

        }
    

</style>
