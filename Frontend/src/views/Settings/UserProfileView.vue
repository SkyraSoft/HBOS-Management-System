<template>
  <div class="user-profile-view">
    <!-- TOP HEADER -->
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
      <div>
        <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
          SETTINGS <i class="bi bi-chevron-right mx-1"></i> <span class="text-primary">{{ activeSection === 'profile' ? 'PROFILE & ACCOUNT' : 'SECURITY & PASSWORD' }}</span>
        </div>
        <h1 class="page-title fw-bolder mb-1" style="font-size: 1.85rem; color: #0f172a;">
          {{ activeSection === 'profile' ? 'Profile & Account Settings' : 'Security & Password Settings' }}
        </h1>
        <p class="text-muted m-0" style="font-size: 0.95rem; color: #64748b;">
          {{ activeSection === 'profile' ? 'Manage your personal profile details, contact information, and business identity.' : 'Manage your login credentials, password security, and active device sessions.' }}
        </p>
      </div>

      <!-- SECTION SWITCHER BUTTONS -->
      <div class="d-flex gap-2 align-items-center bg-white p-1 rounded-3 shadow-sm border flex-wrap">
        <button 
          type="button" 
          class="btn btn-sm px-3 py-2 rounded-2 fw-semibold transition"
          :class="activeSection === 'profile' ? 'btn-primary' : 'btn-light text-secondary border-0'"
          @click="switchSection('profile')"
        >
          <i class="bi bi-person-badge me-1"></i> My Profile
        </button>
        <button 
          type="button" 
          class="btn btn-sm px-3 py-2 rounded-2 fw-semibold transition"
          :class="activeSection === 'security' ? 'btn-primary' : 'btn-light text-secondary border-0'"
          @click="switchSection('security')"
        >
          <i class="bi bi-shield-lock me-1"></i> Security &amp; Password
        </button>
      </div>
    </div>

    <!-- ALERTS -->
    <transition name="fade">
      <div v-if="successMessage" class="alert alert-success d-flex align-items-center justify-content-between rounded-3 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-check-circle-fill fs-5 text-success"></i>
          <div>{{ successMessage }}</div>
        </div>
        <button type="button" class="btn-close" @click="successMessage = ''" aria-label="Close"></button>
      </div>
    </transition>

    <transition name="fade">
      <div v-if="errorMessage" class="alert alert-danger d-flex align-items-center justify-content-between rounded-3 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
          <div>{{ errorMessage }}</div>
        </div>
        <button type="button" class="btn-close" @click="errorMessage = ''" aria-label="Close"></button>
      </div>
    </transition>

    <!-- ========================================================
         SECTION 1: PROFILE & ACCOUNT
    ======================================================== -->
    <div v-if="activeSection === 'profile'" class="profile-section">
      <!-- USER HERO CARD -->
      <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
        <div class="p-4 bg-gradient-brand text-white d-flex align-items-center justify-content-between flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="profile-hero-avatar rounded-circle bg-white text-primary fw-bold shadow">
              {{ userInitial }}
            </div>
            <div>
              <h3 class="fw-bold m-0 text-white">{{ profileForm.name || 'User Profile' }}</h3>
              <p class="m-0 text-white-50 small">{{ profileForm.email }}</p>
              <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                <span class="badge bg-white text-primary fw-semibold px-2.5 py-1 rounded-pill">
                  <i class="bi bi-award me-1"></i> {{ userRole }}
                </span>
                <span class="badge bg-white bg-opacity-25 text-white fw-normal px-2.5 py-1 rounded-pill">
                  <i class="bi bi-building me-1"></i> {{ profileForm.business_name || 'HBOS Retail' }}
                </span>
                <span class="badge bg-success bg-opacity-75 text-white fw-normal px-2.5 py-1 rounded-pill">
                  <span class="online-indicator"></span> Active Session
                </span>
              </div>
            </div>
          </div>

          <div class="text-end">
            <button class="btn btn-light btn-sm fw-semibold rounded-3 shadow-sm" type="button" @click="resetProfileForm">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Changes
            </button>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <!-- EDIT PROFILE FORM -->
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
              <div>
                <h5 class="fw-bold m-0 text-dark">Edit Profile Information</h5>
                <small class="text-muted">Update your personal account credentials and contact details</small>
              </div>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                Editable
              </span>
            </div>

            <form @submit.prevent="saveProfile">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary small">Full Name <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input 
                      type="text" 
                      class="form-control border-start-0 bg-light" 
                      v-model="profileForm.name" 
                      placeholder="e.g. Muhammad Huzaifa"
                      required
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary small">Email Address <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                    <input 
                      type="email" 
                      class="form-control border-start-0 bg-light" 
                      v-model="profileForm.email" 
                      placeholder="e.g. user@hbos.pk"
                      required
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary small">Phone Number</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-telephone"></i></span>
                    <input 
                      type="tel" 
                      class="form-control border-start-0 bg-light" 
                      v-model="profileForm.phone" 
                      placeholder="e.g. +92 300 1234567"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary small">Business / Organization Name</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shop"></i></span>
                    <input 
                      type="text" 
                      class="form-control border-start-0 bg-light" 
                      v-model="profileForm.business_name" 
                      placeholder="e.g. HBOS Retail Solutions"
                    />
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary small">Designated Role</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-check"></i></span>
                    <input 
                      type="text" 
                      class="form-control border-start-0 bg-light text-muted" 
                      :value="userRole" 
                      disabled
                      title="Role assignments are controlled under Settings > Roles"
                    />
                  </div>
                  <small class="text-muted" style="font-size: 11px;">Assigned via Roles &amp; Permissions</small>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary small">Primary Branch Scope</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-geo-alt"></i></span>
                    <input 
                      type="text" 
                      class="form-control border-start-0 bg-light text-muted" 
                      :value="branchName" 
                      disabled
                    />
                  </div>
                  <small class="text-muted" style="font-size: 11px;">Scope managed under Store &amp; Branch Settings</small>
                </div>

                <div class="col-12">
                  <label class="form-label fw-semibold text-secondary small">Profile Bio / Operational Notes</label>
                  <textarea 
                    class="form-control bg-light" 
                    rows="3" 
                    v-model="profileForm.notes" 
                    placeholder="Brief description or notes regarding this administrative profile..."
                  ></textarea>
                </div>
              </div>

              <div class="d-flex align-items-center justify-content-between mt-4 pt-3 border-top flex-wrap gap-2">
                <span class="text-muted small">
                  <i class="bi bi-info-circle me-1"></i> Changes take effect immediately across all windows.
                </span>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-outline-secondary px-4 py-2 rounded-3 fw-semibold" @click="resetProfileForm">
                    Cancel
                  </button>
                  <button 
                    type="submit" 
                    class="btn btn-primary px-4 py-2 rounded-3 fw-semibold shadow-sm d-flex align-items-center gap-2"
                    :disabled="isSavingProfile"
                  >
                    <span v-if="isSavingProfile" class="spinner-border spinner-border-sm" role="status"></span>
                    <i v-else class="bi bi-check2-circle"></i>
                    <span>Save Profile Changes</span>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- ACCOUNT DETAILS & SUMMARY SIDEBAR -->
        <div class="col-lg-4">
          <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h6 class="fw-bold text-dark mb-3">Account Overview</h6>
            
            <div class="info-item mb-3">
              <span class="text-muted small d-block">Account Status</span>
              <span class="fw-semibold text-success d-flex align-items-center gap-2 mt-0.5">
                <i class="bi bi-check-circle-fill"></i> Verified &amp; Active
              </span>
            </div>

            <div class="info-item mb-3">
              <span class="text-muted small d-block">System Mode</span>
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 mt-0.5">
                {{ isDemoMode ? 'Demonstration Sandbox' : 'Production Environment' }}
              </span>
            </div>

            <div class="info-item mb-3">
              <span class="text-muted small d-block">User ID</span>
              <code class="text-dark bg-light px-2 py-1 rounded mt-0.5 d-inline-block">
                #{{ authStore.user?.id || 1 }}
              </code>
            </div>

            <div class="info-item">
              <span class="text-muted small d-block">Security Recommendation</span>
              <p class="small text-muted mt-1 mb-0">
                Regularly update your account password and review device logins under 
                <a href="javascript:void(0)" @click="switchSection('security')" class="text-primary fw-semibold">Security &amp; Password</a>.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================================
         SECTION 2: SECURITY & PASSWORD
    ======================================================== -->
    <div v-else-if="activeSection === 'security'" class="security-section">
      <!-- SECURITY KPI CARDS -->
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted small fw-semibold">Password Status</span>
              <div class="icon-box bg-success-subtle text-success rounded-3 p-2">
                <i class="bi bi-shield-check fs-5"></i>
              </div>
            </div>
            <div class="h5 fw-bold m-0 text-dark">Active &amp; Protected</div>
            <small class="text-muted">Standard encryption enforced</small>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted small fw-semibold">Active Sessions</span>
              <div class="icon-box bg-primary-subtle text-primary rounded-3 p-2">
                <i class="bi bi-laptop fs-5"></i>
              </div>
            </div>
            <div class="h5 fw-bold m-0 text-dark">1 Current Device</div>
            <small class="text-muted">Localhost Windows session</small>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="text-muted small fw-semibold">Two-Factor Authentication</span>
              <div class="icon-box bg-warning-subtle text-warning rounded-3 p-2">
                <i class="bi bi-key fs-5"></i>
              </div>
            </div>
            <div class="h5 fw-bold m-0 text-dark">Optional Security</div>
            <small class="text-muted">Available for enhanced protection</small>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <!-- CHANGE PASSWORD FORM -->
        <div class="col-lg-7">
          <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
              <div>
                <h5 class="fw-bold m-0 text-dark">Change Password</h5>
                <small class="text-muted">Update your password to keep your account safe</small>
              </div>
              <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1">
                Security
              </span>
            </div>

            <form @submit.prevent="updatePassword">
              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Current Password <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                  <input 
                    :type="showCurrentPassword ? 'text' : 'password'" 
                    class="form-control border-start-0 border-end-0 bg-light" 
                    v-model="passwordForm.current_password" 
                    placeholder="Enter your current password"
                    required
                  />
                  <button 
                    type="button" 
                    class="btn btn-light border border-start-0 text-muted" 
                    @click="showCurrentPassword = !showCurrentPassword"
                  >
                    <i :class="showCurrentPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                  </button>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">New Password <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key"></i></span>
                  <input 
                    :type="showNewPassword ? 'text' : 'password'" 
                    class="form-control border-start-0 border-end-0 bg-light" 
                    v-model="passwordForm.new_password" 
                    placeholder="Enter new secure password (min 8 characters)"
                    required
                    minlength="8"
                  />
                  <button 
                    type="button" 
                    class="btn btn-light border border-start-0 text-muted" 
                    @click="showNewPassword = !showNewPassword"
                  >
                    <i :class="showNewPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                  </button>
                </div>
                <div class="password-strength-bars mt-2 d-flex gap-1">
                  <div class="bar flex-grow-1 rounded-pill" :class="passwordStrength >= 1 ? 'bg-danger' : 'bg-light'"></div>
                  <div class="bar flex-grow-1 rounded-pill" :class="passwordStrength >= 2 ? 'bg-warning' : 'bg-light'"></div>
                  <div class="bar flex-grow-1 rounded-pill" :class="passwordStrength >= 3 ? 'bg-success' : 'bg-light'"></div>
                </div>
                <small class="text-muted" style="font-size: 11px;">Password strength: {{ passwordStrengthLabel }}</small>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Confirm New Password <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-check2-circle"></i></span>
                  <input 
                    :type="showConfirmPassword ? 'text' : 'password'" 
                    class="form-control border-start-0 border-end-0 bg-light" 
                    v-model="passwordForm.new_password_confirmation" 
                    placeholder="Re-type new password"
                    required
                  />
                  <button 
                    type="button" 
                    class="btn btn-light border border-start-0 text-muted" 
                    @click="showConfirmPassword = !showConfirmPassword"
                  >
                    <i :class="showConfirmPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                  </button>
                </div>
                <small v-if="passwordMismatch" class="text-danger" style="font-size: 11px;">
                  Passwords do not match!
                </small>
              </div>

              <div class="d-flex align-items-center justify-content-end gap-2 mt-4 pt-3 border-top">
                <button type="button" class="btn btn-outline-secondary px-3 py-2 rounded-3 fw-semibold" @click="resetPasswordForm">
                  Clear
                </button>
                <button 
                  type="submit" 
                  class="btn btn-dark px-4 py-2 rounded-3 fw-semibold shadow-sm d-flex align-items-center gap-2"
                  :disabled="isUpdatingPassword || passwordMismatch || !passwordForm.new_password"
                >
                  <span v-if="isUpdatingPassword" class="spinner-border spinner-border-sm" role="status"></span>
                  <i v-else class="bi bi-shield-check"></i>
                  <span>Update Password</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- SESSIONS & DEVICE GOVERNANCE -->
        <div class="col-lg-5">
          <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h5 class="fw-bold text-dark mb-1">Active Login Sessions</h5>
            <small class="text-muted d-block mb-3">Devices currently authenticated to your account</small>

            <div class="session-item p-3 rounded-3 bg-light border mb-3">
              <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                  <div class="session-icon bg-white text-primary rounded-3 p-2 shadow-sm">
                    <i class="bi bi-display fs-5"></i>
                  </div>
                  <div>
                    <div class="fw-bold text-dark small">Windows PC — Desktop Web</div>
                    <div class="text-muted" style="font-size: 11px;">IP: 127.0.0.1 (Localhost) &bull; Chrome Browser</div>
                  </div>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 10.5px;">
                  Current Device
                </span>
              </div>
            </div>

            <div class="p-3 rounded-3 border border-danger-subtle bg-danger-subtle bg-opacity-25 mt-4">
              <h6 class="fw-bold text-danger mb-1">Revoke Remote Sessions</h6>
              <p class="text-muted small mb-3">
                If you suspect unauthorized access, you can terminate all other active device tokens instantly.
              </p>
              <button 
                type="button" 
                class="btn btn-outline-danger btn-sm w-100 fw-semibold rounded-3 py-2 d-flex align-items-center justify-content-center gap-2"
                :disabled="isLoggingOutDevices"
                @click="logoutAllDevices"
              >
                <span v-if="isLoggingOutDevices" class="spinner-border spinner-border-sm" role="status"></span>
                <i v-else class="bi bi-box-arrow-right"></i>
                <span>Log Out All Other Devices</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useNavigationStore } from '@/stores/navigation';
import api from '@/api';

const props = defineProps({
  initialSection: {
    type: String,
    default: 'profile'
  }
});

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const navigationStore = useNavigationStore();

const activeSection = ref(props.initialSection || 'profile');

watch(() => props.initialSection, (val) => {
  if (val) activeSection.value = val;
});

watch(() => route.query.tab, (tab) => {
  if (tab === 'account-security' || tab === 'security-password') {
    activeSection.value = 'security';
  } else if (tab === 'profile') {
    activeSection.value = 'profile';
  }
});

const switchSection = (section) => {
  activeSection.value = section;
  router.replace({ 
    query: { ...route.query, tab: section === 'security' ? 'account-security' : 'profile' } 
  });
};

const successMessage = ref('');
const errorMessage = ref('');

const isDemoMode = computed(() => {
  const mode = localStorage.getItem('hbos_demo_mode');
  return mode === 'single' || mode === 'multi';
});

const userRole = computed(() => {
  return authStore.user?.role || (isDemoMode.value ? 'Business Owner (Demo)' : 'Business Owner');
});

const branchName = computed(() => {
  return navigationStore.currentBranchName || 'Main Store';
});

const userInitial = computed(() => {
  const name = profileForm.value.name || authStore.user?.name || 'Administrator';
  return name.charAt(0).toUpperCase();
});

// Profile Form State
const isSavingProfile = ref(false);
const profileForm = ref({
  name: '',
  email: '',
  phone: '',
  business_name: '',
  notes: ''
});

const loadInitialUserData = () => {
  const user = authStore.user || {};
  profileForm.value = {
    name: user.name || (isDemoMode.value ? 'Demo Administrator' : 'Administrator'),
    email: user.email || (isDemoMode.value ? 'demo@hbos.pk' : 'admin@hbos.pk'),
    phone: user.phone || '+92 300 1234567',
    business_name: user.business_name || localStorage.getItem('hbos_business_name') || 'HBOS Retail Management',
    notes: user.notes || 'Primary administrative user with full governance and access authority.'
  };
};

const resetProfileForm = () => {
  loadInitialUserData();
  successMessage.value = '';
  errorMessage.value = '';
};

const saveProfile = async () => {
  isSavingProfile.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  try {
    const payload = {
      name: profileForm.value.name,
      email: profileForm.value.email,
      phone: profileForm.value.phone,
      business_name: profileForm.value.business_name,
      notes: profileForm.value.notes
    };

    const res = await api.put('/profile', payload);
    
    // Update local store and storage
    if (authStore.user) {
      authStore.user.name = payload.name;
      authStore.user.email = payload.email;
      authStore.user.phone = payload.phone;
      authStore.user.business_name = payload.business_name;
      authStore.user.notes = payload.notes;
      localStorage.setItem('hbos_user', JSON.stringify(authStore.user));
    } else {
      localStorage.setItem('hbos_user', JSON.stringify(payload));
    }
    localStorage.setItem('hbos_business_name', payload.business_name);

    successMessage.value = res.data?.message || 'Profile information updated successfully!';
    setTimeout(() => {
      successMessage.value = '';
    }, 4000);
  } catch (err) {
    console.error('Failed to update profile:', err);
    errorMessage.value = err.response?.data?.message || 'Failed to update profile. Please check your network and input.';
  } finally {
    isSavingProfile.value = false;
  }
};

// Password Form State
const isUpdatingPassword = ref(false);
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
});

const resetPasswordForm = () => {
  passwordForm.value = {
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
  };
};

const passwordMismatch = computed(() => {
  return passwordForm.value.new_password && 
         passwordForm.value.new_password_confirmation && 
         passwordForm.value.new_password !== passwordForm.value.new_password_confirmation;
});

const passwordStrength = computed(() => {
  const p = passwordForm.value.new_password || '';
  if (p.length < 8) return 0;
  let score = 1;
  if (/[0-9]/.test(p) || /[^A-Za-z0-9]/.test(p)) score++;
  if (/[A-Z]/.test(p) && /[a-z]/.test(p) && p.length >= 10) score++;
  return score;
});

const passwordStrengthLabel = computed(() => {
  if (passwordStrength.value === 0) return 'Too short (min 8 characters)';
  if (passwordStrength.value === 1) return 'Fair';
  if (passwordStrength.value === 2) return 'Good';
  return 'Strong & Secure';
});

const updatePassword = async () => {
  if (passwordMismatch.value) {
    errorMessage.value = 'New password and confirmation do not match.';
    return;
  }

  isUpdatingPassword.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  try {
    const payload = {
      current_password: passwordForm.value.current_password,
      new_password: passwordForm.value.new_password,
      new_password_confirmation: passwordForm.value.new_password_confirmation
    };

    const res = await api.put('/profile/password', payload);
    successMessage.value = res.data?.message || 'Password changed successfully!';
    resetPasswordForm();
    setTimeout(() => {
      successMessage.value = '';
    }, 4000);
  } catch (err) {
    console.error('Failed to change password:', err);
    errorMessage.value = err.response?.data?.message || 'Failed to change password. Current password may be incorrect.';
  } finally {
    isUpdatingPassword.value = false;
  }
};

// Sessions & Remote Logout
const isLoggingOutDevices = ref(false);
const logoutAllDevices = async () => {
  if (!confirm('Are you sure you want to log out all other active sessions and devices?')) return;

  isLoggingOutDevices.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  try {
    await authStore.logoutAllDevices();
    successMessage.value = 'All other remote sessions were terminated successfully.';
    setTimeout(() => {
      successMessage.value = '';
    }, 4000);
  } catch (err) {
    console.error('Remote logout error:', err);
    errorMessage.value = 'Failed to revoke remote devices.';
  } finally {
    isLoggingOutDevices.value = false;
  }
};

onMounted(() => {
  if (props.initialSection) {
    activeSection.value = props.initialSection;
  }
  loadInitialUserData();
});
</script>

<style scoped>
.user-profile-view {
  width: 100%;
}

.bg-gradient-brand {
  background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%);
}

.profile-hero-avatar {
  width: 72px;
  height: 72px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  border: 3px solid rgba(255, 255, 255, 0.4);
}

.online-indicator {
  display: inline-block;
  width: 7px;
  height: 7px;
  background-color: #4ade80;
  border-radius: 50%;
  margin-right: 5px;
}

.password-strength-bars .bar {
  height: 4px;
  transition: all 0.2s ease;
}

.transition {
  transition: all 0.15s ease-in-out;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

