<template>
<div class="businesssetup1-page-wrapper">

    <!-- =================================
         PROGRESS STEPPER (5 STAGES)
    ================================== -->
    <div class="progress-wrapper">
        <div class="progress">

            <!-- 1. ACCOUNT -->
            <div class="progress-step active">
                <div class="step-circle">
                    <i class="bi bi-check"></i>
                </div>
                <div class="step-number">
                    Account
                </div>
            </div>

            <div class="progress-line active"></div>

            <!-- 2. BUSINESS -->
            <div class="progress-step active">
                <div class="step-circle">
                    <i class="bi bi-check"></i>
                </div>
                <div class="step-number">
                    Business
                </div>
            </div>

            <div class="progress-line active"></div>

            <!-- 3. MAIN STORE -->
            <div class="progress-step active current">
                <div class="step-circle">
                    <span>3</span>
                </div>
                <div class="step-number">
                    Main Store
                </div>
            </div>

            <div class="progress-line"></div>

            <!-- 4. PREFERENCES -->
            <div class="progress-step">
                <div class="step-circle">
                    <span>4</span>
                </div>
                <div class="step-number">
                    Preferences
                </div>
            </div>

            <div class="progress-line"></div>

            <!-- 5. COMPLETE -->
            <div class="progress-step">
                <div class="step-circle">
                    <span>5</span>
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

        <div class="card-content">
            <h1 class="card-title">
                Set Up Your Primary Store
            </h1>
            <p class="card-subtitle">
                Configure your main store branch location and contact details for sales and inventory.
            </p>

            <form class="setup-form" id="mainStoreSetupForm" @submit.prevent="handleContinue" novalidate="">

                <!-- STORE NAME + CODE -->
                <div class="form-row">
                    <!-- STORE NAME -->
                    <div class="form-group" :class="{ 'has-error': errors.name }">
                        <label for="storeName" class="form-label">
                            Store / Branch Name <span class="text-danger">*</span>
                        </label>
                        <div class="input-wrapper has-icon">
                            <i class="bi bi-shop input-icon"></i>
                            <input 
                                type="text" 
                                id="storeName" 
                                v-model="form.name" 
                                class="form-control-custom" 
                                placeholder="e.g. Main Branch" 
                                required
                            >
                        </div>
                        <div class="error-message" v-if="errors.name" style="display: block; color: #dc2626; font-size: 12px; margin-top: 4px;">
                            {{ errors.name }}
                        </div>
                    </div>

                    <!-- BRANCH CODE -->
                    <div class="form-group" :class="{ 'has-error': errors.code }">
                        <label for="branchCode" class="form-label">
                            Branch Code <span class="text-danger">*</span>
                        </label>
                        <div class="input-wrapper has-icon">
                            <i class="bi bi-upc-scan input-icon"></i>
                            <input 
                                type="text" 
                                id="branchCode" 
                                v-model="form.code" 
                                class="form-control-custom" 
                                placeholder="e.g. MAIN" 
                                required
                            >
                        </div>
                        <div class="error-message" v-if="errors.code" style="display: block; color: #dc2626; font-size: 12px; margin-top: 4px;">
                            {{ errors.code }}
                        </div>
                    </div>
                </div>

                <!-- PHONE -->
                <div class="form-group">
                    <label for="storePhone" class="form-label">
                        Store Phone Number
                    </label>
                    <div class="input-wrapper has-icon">
                        <i class="bi bi-telephone input-icon"></i>
                        <input 
                            type="tel" 
                            id="storePhone" 
                            v-model="form.phone" 
                            class="form-control-custom" 
                            placeholder="+92 300 1234567" 
                            autocomplete="tel"
                        >
                    </div>
                </div>

                <div class="form-divider"></div>

                <!-- ADDRESS -->
                <div class="form-group">
                    <label for="storeAddress" class="form-label">
                        Store Physical Address
                    </label>
                    <div class="input-wrapper has-icon">
                        <i class="bi bi-geo-alt input-icon"></i>
                        <input 
                            type="text" 
                            id="storeAddress" 
                            v-model="form.address" 
                            class="form-control-custom" 
                            placeholder="e.g. Shop #12, Commercial Market" 
                            autocomplete="street-address"
                        >
                    </div>
                </div>

                <!-- CITY + STATUS NOTE -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="storeCity" class="form-label">
                            City
                        </label>
                        <input 
                            type="text" 
                            id="storeCity" 
                            v-model="form.city" 
                            class="form-control-custom" 
                            placeholder="Lahore" 
                            autocomplete="address-level2"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Primary Branch Status
                        </label>
                        <div class="country-wrapper" style="background: #f1f5f9; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; color: #334155;">
                            <i class="bi bi-star-fill text-warning me-1"></i> Default Primary Branch &amp; Main Cash Drawer
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <!-- =================================
             FOOTER ACTIONS
        ================================== -->
        <div class="card-footer-custom">
            <!-- BACK -->
            <button type="button" class="back-button" id="backButton" @click="handleBack">
                <i class="bi bi-arrow-left"></i>
                <span>Back</span>
            </button>

            <!-- CONTINUE -->
            <button type="submit" form="mainStoreSetupForm" class="continue-button" id="continueButton" :disabled="loading">
                <span>{{ loading ? 'Saving...' : 'Continue to Preferences' }}</span>
                <i class="bi bi-arrow-right"></i>
            </button>
        </div>

    </section>

</div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';
import { useToast } from 'vue-toastification';

const router = useRouter();
const toast = useToast();
const loading = ref(false);

const form = reactive({
  name: 'Main Branch',
  code: 'MAIN',
  phone: '',
  address: '',
  city: 'Lahore',
});

const errors = reactive({
  name: '',
  code: '',
});

const handleBack = () => {
  router.push('/businesssetup1');
};

const handleContinue = async () => {
  errors.name = '';
  errors.code = '';
  
  if (!form.name || !form.name.trim()) {
    errors.name = 'Store / branch name is required';
    return;
  }
  if (!form.code || !form.code.trim()) {
    errors.code = 'Branch code is required';
    return;
  }

  loading.value = true;
  try {
    await api.post('/onboarding/step', {
      step: 3,
      branch_name: form.name.trim(),
      branch_code: form.code.trim(),
      branch_phone: form.phone,
      branch_address: form.address,
      branch_city: form.city,
    });
    router.push('/preferences');
  } catch (err) {
    console.error('Failed to save store onboarding step', err);
    toast.error(err.response?.data?.message || 'Failed to save store details');
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  try {
    const res = await api.get('/onboarding/status');
    const branch = res.data?.primary_branch;
    const biz = res.data?.business;
    if (branch) {
      if (branch.name) form.name = branch.name;
      if (branch.code) form.code = branch.code;
      if (branch.phone) form.phone = branch.phone;
      if (branch.address) form.address = branch.address;
      if (branch.city) form.city = branch.city;
    } else if (biz) {
      if (biz.name) form.name = `${biz.name} Main`;
      if (biz.phone) form.phone = biz.phone;
      if (biz.address) form.address = biz.address;
      if (biz.city) form.city = biz.city;
    }
  } catch (err) {
    console.warn('Could not fetch onboarding status', err);
  }
});
</script>

<style scoped>
.businesssetup1-page-wrapper {
  min-height: 100vh;
  background-color: #f8fafc;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 20px;
  font-family: inherit;
}

.progress-wrapper {
  width: 100%;
  max-width: 680px;
  margin-bottom: 32px;
}

.progress {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: transparent;
  height: auto;
  overflow: visible;
}

.progress-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  position: relative;
  z-index: 2;
}

.step-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #ffffff;
  border: 2px solid #cbd5e1;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s ease;
}

.progress-step.active .step-circle {
  background: #0f172a;
  border-color: #0f172a;
  color: #ffffff;
}

.progress-step.active.current .step-circle {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
}

.step-number {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: capitalize;
}

.progress-step.active .step-number {
  color: #0f172a;
}

.progress-line {
  flex: 1;
  height: 2px;
  background: #e2e8f0;
  margin: 0 12px;
  transform: translateY(-12px);
  position: relative;
  z-index: 1;
}

.progress-line.active {
  background: #0f172a;
}

.setup-card {
  width: 100%;
  max-width: 680px;
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.card-content {
  padding: 36px;
}

.card-title {
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 8px;
}

.card-subtitle {
  font-size: 14px;
  color: #64748b;
  margin-bottom: 28px;
}

.setup-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-row {
  display: flex;
  gap: 16px;
}

.form-row .form-group {
  flex: 1;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrapper.has-icon .input-icon {
  position: absolute;
  left: 14px;
  color: #94a3b8;
  font-size: 16px;
  pointer-events: none;
}

.form-control-custom {
  width: 100%;
  height: 44px;
  padding: 0 14px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background-color: #ffffff;
  color: #0f172a;
  font-size: 14px;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.input-wrapper.has-icon .form-control-custom {
  padding-left: 42px;
}

.form-control-custom:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
  outline: none;
}

.form-divider {
  height: 1px;
  background-color: #f1f5f9;
  margin: 4px 0;
}

.card-footer-custom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 36px;
  background-color: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

.back-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: transparent;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  color: #475569;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.back-button:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.continue-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 22px;
  background: #0f172a;
  border: none;
  border-radius: 8px;
  color: #ffffff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.continue-button:hover:not(:disabled) {
  background: #1e293b;
}

.continue-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .form-row {
    flex-direction: column;
    gap: 20px;
  }
  .card-content {
    padding: 24px;
  }
  .card-footer-custom {
    padding: 16px 24px;
  }
}
</style>
