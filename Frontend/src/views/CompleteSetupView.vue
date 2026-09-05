<template>
<div class="completesetup-page-wrapper">


        <!-- Success Icon -->
        <div class="success-icon" aria-hidden="true">
            <i class="bi bi-check-lg"></i>
        </div>


        <!-- Heading -->
        <h1 id="pageTitle" class="success-title">
            Your Business Is Ready
        </h1>

        <p class="success-description">
            Your HBOS workspace has been successfully created.
        </p>


        <!-- Workspace Details -->
        <section class="details-box" aria-labelledby="detailsTitle">

            <h2 id="detailsTitle" class="details-title">
                Workspace Details
            </h2>

            <div class="details-divider"></div>


            <div class="detail-row">

                <span class="detail-label">
                    Business Name
                </span>

                <span class="detail-value">
                    Hanif General Store
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Type
                </span>

                <span class="detail-value">
                    General Store
                </span>

            </div>


            <div class="detail-row">

                <span class="detail-label">
                    Owner
                </span>

                <span class="detail-value">
                    Hanif Ullah
                </span>

            </div>

        </section>


        <!-- Dashboard -->
        <a href="#" @click.prevent="$router.push('/')" class="dashboard-button" id="dashboardButton">
            <span>Go to Dashboard</span>
            <i class="bi bi-arrow-right"></i>
        </a>

    
</div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
  console.log('CompleteSetupView mounted');
});
</script>

<style scoped>

        /* =========================================================
           VARIABLES
        ========================================================= */

        :root {
            --primary: #2849b9;
            --primary-hover: #1f3da3;

            --page-bg: #faf8ff;
            --card-bg: #ffffff;
            --details-bg: #f5f3fc;

            --text-primary: #202129;
            --text-secondary: #555666;

            --border: #c8cadb;
            --details-border: #d9d7e8;
        }


        /* =========================================================
           BASE
        ========================================================= */

        *,
        *::before,
        *::after {
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

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 32px 20px;

            background: var(--page-bg);
            color: var(--text-primary);

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Helvetica,
                Arial,
                sans-serif;

            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }


        /* =========================================================
           SUCCESS CARD
        ========================================================= */

        .success-card {
            width: 100%;
            max-width: 512px;

            background: var(--card-bg);

            border: 1px solid var(--border);
            border-radius: 12px;

            padding: 40px 48px 47px;

            box-shadow:
                0 1px 2px rgba(25, 31, 55, 0.03);

            text-align: center;
        }


        /* =========================================================
           SUCCESS ICON
        ========================================================= */

        .success-icon {
            width: 40px;
            height: 40px;

            margin: 0 auto 18px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: var(--primary);
            color: #fff;

            font-size: 23px;
            font-weight: 700;

            line-height: 1;
        }

        .success-icon i {
            position: relative;
            top: 1px;
        }


        /* =========================================================
           HEADING
        ========================================================= */

        .success-title {
            margin: 0;

            color: var(--text-primary);

            font-size: 32px;
            line-height: 40px;
            font-weight: 700;

            letter-spacing: -1px;
        }

        .success-description {
            margin: 4px 0 33px;

            color: var(--text-secondary);

            font-size: 16px;
            line-height: 24px;
            font-weight: 400;
        }


        /* =========================================================
           WORKSPACE DETAILS
        ========================================================= */

        .details-box {
            width: 100%;

            padding: 23px 24px 20px;

            background: var(--details-bg);

            border: 1px solid var(--details-border);
            border-radius: 7px;

            text-align: left;
        }

        .details-title {
            margin: 0;

            color: var(--text-primary);

            font-size: 18px;
            line-height: 23px;
            font-weight: 600;
        }

        .details-divider {
            height: 1px;

            margin: 8px 0 13px;

            background: #dddce7;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;

            column-gap: 20px;

            min-height: 36px;
        }

        .detail-label {
            color: var(--text-secondary);

            font-size: 16px;
            line-height: 23px;
            font-weight: 400;
        }

        .detail-value {
            color: var(--text-primary);

            font-size: 18px;
            line-height: 24px;
            font-weight: 600;

            text-align: right;

            white-space: nowrap;
        }


        /* =========================================================
           DASHBOARD BUTTON
        ========================================================= */

        .dashboard-button {
            width: 100%;
            height: 41px;

            margin-top: 31px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            border: 1px solid var(--primary);
            border-radius: 0;

            background: var(--primary);
            color: #fff;

            font-size: 13px;
            line-height: 18px;
            font-weight: 600;

            letter-spacing: 0.15px;

            text-decoration: none;

            cursor: pointer;

            transition:
                background-color 0.15s ease,
                border-color 0.15s ease,
                transform 0.05s ease;
        }

        .dashboard-button:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
            color: #fff;
        }

        .dashboard-button:active {
            transform: translateY(1px);
        }

        .dashboard-button i {
            font-size: 16px;
            line-height: 1;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 576px) {

            body {
                padding: 20px 14px;
            }

            .success-card {
                max-width: 100%;

                padding: 32px 22px 30px;

                border-radius: 10px;
            }

            .success-icon {
                width: 42px;
                height: 42px;

                margin-bottom: 17px;
            }

            .success-title {
                font-size: 28px;
                line-height: 35px;
                letter-spacing: -0.8px;
            }

            .success-description {
                margin-top: 6px;
                margin-bottom: 28px;

                font-size: 15px;
                line-height: 22px;
            }

            .details-box {
                padding: 20px 17px 17px;
            }

            .details-title {
                font-size: 17px;
            }

            .detail-row {
                grid-template-columns: 1fr;
                gap: 1px;

                padding: 6px 0;
            }

            .detail-label {
                font-size: 14px;
                line-height: 20px;
            }

            .detail-value {
                font-size: 16px;
                line-height: 22px;

                text-align: left;
                white-space: normal;
            }

            .dashboard-button {
                margin-top: 25px;
            }
        }


        @media (max-width: 360px) {

            body {
                padding: 14px 10px;
            }

            .success-card {
                padding-left: 17px;
                padding-right: 17px;
            }

            .success-title {
                font-size: 25px;
                line-height: 32px;
            }

            .success-description {
                font-size: 14px;
            }

            .details-box {
                padding-left: 14px;
                padding-right: 14px;
            }
        }


        /* =========================================================
           REDUCED MOTION
        ========================================================= */

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition: none !important;
                animation: none !important;
            }
        }
    

</style>
