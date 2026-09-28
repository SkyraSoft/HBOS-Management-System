<template>
<div class="full-screen-register">
    <div class="register-overlay">
        <div class="register-card">
            <div class="brand-header">
                <span class="store-icon" aria-hidden="true">
                    <span class="roof"></span>
                    <span class="awning"></span>
                    <span class="body"></span>
                </span>
                <span>HBOS</span>
            </div>
            
            <div class="form-container">
                <h2>Create an Account</h2>
                <p class="form-description">
                    Set up your business profile to get started.
                </p>

                <form @submit.prevent="handleRegister" novalidate >
                    
                    <div class="input-group-custom" :class="{ 'has-error': errors.business_name }">
                        <label class="form-label">Business Name</label>
                        <input type="text" v-model="form.business_name" class="form-control-custom w-100" placeholder="e.g. Acme Retail">
                        <div class="error-message" v-if="errors.business_name">{{ errors.business_name }}</div>
                    </div>

                    <div class="two-fields">
                        <div class="input-group-custom" :class="{ 'has-error': errors.name }">
                            <label class="form-label">Your Name</label>
                            <input type="text" v-model="form.name" class="form-control-custom w-100" placeholder="John Doe">
                            <div class="error-message" v-if="errors.name">{{ errors.name }}</div>
                        </div>
                        
                        <div class="input-group-custom" :class="{ 'has-error': errors.phone }">
                            <label class="form-label">Phone</label>
                            <input type="text" v-model="form.phone" class="form-control-custom w-100" placeholder="1234567890">
                            <div class="error-message" v-if="errors.phone">{{ errors.phone }}</div>
                        </div>
                    </div>

                    <div class="input-group-custom" :class="{ 'has-error': errors.email }">
                        <label class="form-label">Email</label>
                        <input type="email" v-model="form.email" class="form-control-custom w-100" placeholder="john@example.com">
                        <div class="error-message" v-if="errors.email">{{ errors.email }}</div>
                    </div>

                    <div class="two-fields">
                        <div class="input-group-custom" :class="{ 'has-error': errors.password }">
                            <label class="form-label">Password</label>
                            <div class="input-wrapper" style="position: relative;">
                                <input :type="showPassword ? 'text' : 'password'" v-model="form.password" class="form-control-custom w-100 password-input" placeholder="Password">
                                <button type="button" @click="showPassword = !showPassword" class="password-toggle"><i class="fa-solid" :class="showPassword ? 'fa-eye' : 'fa-eye-slash'"></i></button>
                            </div>
                            <div class="error-message" v-if="errors.password">{{ errors.password }}</div>
                        </div>
                        
                        <div class="input-group-custom" :class="{ 'has-error': errors.password_confirmation }">
                            <label class="form-label">Confirm Pass</label>
                            <div class="input-wrapper" style="position: relative;">
                                <input :type="showPassword ? 'text' : 'password'" v-model="form.password_confirmation" class="form-control-custom w-100 password-input" placeholder="Confirm">
                                <button type="button" @click="showPassword = !showPassword" class="password-toggle"><i class="fa-solid" :class="showPassword ? 'fa-eye' : 'fa-eye-slash'"></i></button>
                            </div>
                            <div class="error-message" v-if="errors.password_confirmation">{{ errors.password_confirmation }}</div>
                        </div>
                    </div>

                    <div class="terms-row">
                        <input type="checkbox" id="terms" v-model="form.terms" class="terms-checkbox">
                        <label for="terms" class="terms-text">
                            I agree to the <a href="#">Terms</a> & <a href="#">Privacy</a>.
                        </label>
                    </div>
                    <div class="error-message" v-if="errors.terms" style="margin-top: -10px; margin-bottom: 10px;">{{ errors.terms }}</div>

                    <button type="submit" class="create-btn" :disabled="loading">
                        {{ loading ? 'Creating Account...' : 'Create Account' }}
                    </button>
                    
                    <div v-if="generalError" class="error-message" style="display: block; text-align: center; margin-top: 10px;">
                        {{ generalError }}
                    </div>
                </form>

                <p class="register-text">
                    Already have an account? 
                    <router-link to="/login" class="register-link">Sign In</router-link>
                </p>
            </div>
        </div>
    </div>
</div>
</template>

<script setup>

import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
  business_name: '',
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  terms: false
});

const errors = reactive({
  business_name: '',
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  terms: ''
});

const generalError = ref('');
const loading = ref(false);
const showPassword = ref(false);

const clearErrors = () => {
  Object.keys(errors).forEach(key => errors[key] = '');
  generalError.value = '';
};

const handleRegister = async () => {
  clearErrors();
  let isValid = true;

  if (!form.business_name) { errors.business_name = 'Business name is required'; isValid = false; }
  if (!form.name) { errors.name = 'Name is required'; isValid = false; }
  if (!form.email) { errors.email = 'Email is required'; isValid = false; }
  if (!form.password) { errors.password = 'Password is required'; isValid = false; }
  if (form.password !== form.password_confirmation) { errors.password_confirmation = 'Passwords do not match'; isValid = false; }
  if (!form.terms) { errors.terms = 'You must accept the terms'; isValid = false; }

  if (!isValid) return;

  loading.value = true;

  try {
    await authStore.register({
      business_name: form.business_name,
      name: form.name,
      email: form.email,
      phone: form.phone,
      password: form.password,
      password_confirmation: form.password_confirmation
    });
    
    router.push('/');
  } catch (error) {
    if (error.response?.data?.errors) {
      const serverErrors = error.response.data.errors;
      Object.keys(serverErrors).forEach(key => {
        if (errors[key] !== undefined) {
          errors[key] = serverErrors[key][0];
        }
      });
    } else {
      generalError.value = error.response?.data?.message || 'Registration failed. Please try again.';
    }
  } finally {
    loading.value = false;
  }
};

</script>

<style scoped>


        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
        }

        body {
            font-family: "Inter", Arial, sans-serif;
            background: #ffffff;
            color: #111111;
            overflow: hidden;
        }

        /* ================================
           FULL SCREEN BACKGROUND
        ================================= */

        .full-screen-register {
            width: 100vw;
            height: 100vh;
            background-color: #ffffff;
}

        .register-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-card {
            background: #ffffff;
            width: 100%;
            max-width: 480px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 30px 40px;
            position: relative;
            z-index: 10;
        }

        .brand-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 750;
            color: #111;
        }

        /* subtle blur matching reference */
        .visual-side::before {
            content: "";
            position: absolute;
            inset: 0;
            backdrop-filter: blur(1.5px);
            -webkit-backdrop-filter: blur(1.5px);
            pointer-events: none;
        }

        /* ================================
           HBOS BRAND
        ================================= */

        .brand {
            position: absolute;
            top: 5px;
            left: 32px;
            z-index: 5;

            display: flex;
            align-items: center;
            gap: 6px;

            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.8px;
        }

        .store-icon {
            width: 27px;
            height: 27px;
            position: relative;
            display: inline-block;
        }

        .store-icon .roof {
            position: absolute;
            left: 3px;
            top: 5px;
            width: 21px;
            height: 7px;
            border: 2px solid #111111;
            border-bottom: 0;
            border-radius: 2px 2px 0 0;
        }

        .store-icon .awning {
            position: absolute;
            left: 2px;
            top: 10px;
            width: 23px;
            height: 6px;
            border-bottom: 2px solid #ffffff;
        }

        .store-icon .awning::before {
            content: "";
            position: absolute;
            inset: 0;

            background:
                linear-gradient(90deg,
                    transparent 0 20%,
                    #fff 20% 27%,
                    transparent 27% 48%,
                    #fff 48% 55%,
                    transparent 55% 76%,
                    #fff 76% 83%,
                    transparent 83%);
        }

        .store-icon .body {
            position: absolute;
            left: 5px;
            top: 15px;
            width: 17px;
            height: 10px;
            border: 2px solid #111111;
            border-top: 0;
            border-radius: 0 0 2px 2px;
        }

        /* ================================
           LEFT TEXT
        ================================= */

        .visual-content {
            position: absolute;
            left: 34px;
            right: 30px;
            bottom: 31px;
            z-index: 4;
        }

        .visual-content h1 {
            margin: 0 0 10px;

            max-width: 500px;

            color: #ffffff;
            font-size: clamp(27px, 2.35vw, 36px);
            line-height: 1.13;
            font-weight: 800;
            letter-spacing: -1.15px;
        }

        .visual-content p {
            max-width: 500px;
            margin: 0;

            color: #c8cddd;
            font-size: clamp(16px, 1.35vw, 20px);
            line-height: 1.55;
            font-weight: 400;
        }

        /* ================================
           RIGHT SIDE
        ================================= */

        .form-side {
            width: 55%;
            height: 100%;
            background: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-container {
            width: 100%;
            max-width: 400px;
            margin-top: -1px;
        }

        /* ================================
           FORM HEADER
        ================================= */

        .form-container h2 {
            margin: 0 0 9px;

            color: #050505;
            font-size: 32px;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: -1.1px;
        }

        .form-description {
            margin: 0 0 31px;

            color: #555761;
            font-size: 16px;
            line-height: 1.5;
            font-weight: 400;
        }

        /* ================================
           LABELS
        ================================= */

        .form-label {
            display: block;
            margin-bottom: 6px;

            color: #171717;
            font-size: 14px;
            font-weight: 500;
        }

        /* ================================
           INPUTS
        ================================= */

        .input-group-custom {
            position: relative;
            width: 100%;
            margin-bottom: 24px;
        }

        .form-control-custom {
            width: 100%;
            height: 49px;

            padding: 0 16px;

            border: 1px solid #c8cbd1;
            border-radius: 8px;

            background: #ffffff;

            color: #171717;
            font-family: inherit;
            font-size: 16px;

            outline: none;

            transition:
                border-color 0.18s ease,
                box-shadow 0.18s ease;
        }

        .form-control-custom::placeholder {
            color: #7c7f87;
            opacity: 1;
        }

        .form-control-custom:focus {
            border-color: #0759d9;
            box-shadow: 0 0 0 2px rgba(7, 89, 217, 0.08);
        }

        .password-input {
            padding-right: 55px;
        }

        /* ================================
           PASSWORD EYE
        ================================= */

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            border: 0;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #6b7280; /* for the emoji if used */
            font-size: 18px; /* for the emoji if used */
            padding: 0;
            z-index: 2;
        }

        .password-toggle:hover {
            color: #111827;
        }

        .eye-icon {
            width: 20px;
            height: 14px;
            position: relative;
        }

        .eye-icon::before {
            content: "";
            position: absolute;
            inset: 0;

            border: 2px solid #111111;
            border-radius: 70% 15%;
            transform: rotate(45deg) scale(0.78);
        }

        .eye-icon::after {
            content: "";
            position: absolute;

            width: 5px;
            height: 5px;

            top: 4.5px;
            left: 7.5px;

            background: #ffffff;
            border-radius: 50%;
        }

        /* ================================
           OPTIONS
        ================================= */

        .form-options {
            width: 100%;
            margin-top: -1px;
            margin-bottom: 34px;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .remember-wrapper {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .remember-checkbox {
            appearance: none;
            -webkit-appearance: none;

            width: 16px;
            height: 16px;

            margin: 0;

            border: 1px solid #c9cbd0;
            border-radius: 4px;

            background: #ffffff;

            cursor: pointer;

            position: relative;
        }

        .remember-checkbox:checked {
            background: #075bdc;
            border-color: #075bdc;
        }

        .remember-checkbox:checked::after {
            content: "";

            position: absolute;
            width: 5px;
            height: 9px;

            left: 5px;
            top: 1.5px;

            border: solid white;
            border-width: 0 2px 2px 0;

            transform: rotate(45deg);
        }

        .remember-label {
            color: #35363a;
            font-size: 14px;
            cursor: pointer;
            user-select: none;
        }

        .forgot-link {
            color: #005bea;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* ================================
           SIGN IN BUTTON
        ================================= */

        .signin-button {
            width: 100%;
            height: 49px;

            border: 0;
            border-radius: 7px;

            background: #075bdc;
            color: #ffffff;

            font-family: inherit;
            font-size: 14px;
            font-weight: 500;

            cursor: pointer;

            transition:
                background 0.2s ease,
                transform 0.1s ease,
                box-shadow 0.2s ease;
        }

        .signin-button:hover {
            background: #064fbe;
            box-shadow: 0 4px 12px rgba(7, 91, 220, 0.16);
        }

        .signin-button:active {
            transform: translateY(1px);
        }

        .signin-button.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        /* ================================
           REGISTER
        ================================= */

        .register-text {
            margin: 31px 0 0;

            text-align: center;

            color: #595b62;
            font-size: 14px;
            line-height: 1.5;
        }

        .register-link {
            color: #005bea;
            font-weight: 500;
            text-decoration: none;
            margin-left: 4px;
        }

        .register-link:hover {
            text-decoration: underline;
        }

        /* ================================
           VALIDATION
        ================================= */

        .error-message {
            display: none;

            margin-top: -17px;
            margin-bottom: 17px;

            color: #dc3545;
            font-size: 12px;
        }

        .form-control-custom.invalid {
            border-color: #dc3545;
        }

        .form-control-custom.invalid:focus {
            box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.08);
        }

        .success-message {
            display: none;

            margin-top: 15px;
            padding: 10px 12px;

            border-radius: 7px;

            background: #ecfdf3;
            color: #15803d;

            text-align: center;
            font-size: 13px;
        }

        /* ================================
           RESPONSIVE
        ================================= */

        @media (max-width: 900px) {

            body {
                overflow: auto;
            }

            .login-wrapper {
                min-height: 100vh;
                height: auto;
                padding: 10px;
            }

            .login-card {
                height: auto;
                min-height: calc(100vh - 20px);
                flex-direction: column;
            }

            .visual-side {
                width: 100%;
                min-height: 360px;
                height: 44vh;
            }

            .form-side {
                width: 100%;
                min-height: 560px;
                padding: 55px 25px;
            }

            .form-container {
                max-width: 500px;
            }

            .brand {
                left: 25px;
            }

            .visual-content {
                left: 25px;
                bottom: 28px;
            }
        }

        @media (max-width: 576px) {

            .login-wrapper {
                padding: 0;
            }

            .login-card {
                width: 100%;
                min-height: 100vh;
                border: 0;
                border-radius: 0;
            }

            .visual-side {
                min-height: 300px;
                height: 40vh;
                border-radius: 0;
            }

            .brand {
                top: 10px;
                left: 20px;
                font-size: 22px;
            }

            .visual-content {
                left: 20px;
                right: 20px;
                bottom: 22px;
            }

            .visual-content h1 {
                font-size: 27px;
                letter-spacing: -0.8px;
            }

            .visual-content p {
                font-size: 14px;
                line-height: 1.45;
            }

            .form-side {
                padding: 45px 22px;
            }

            .form-container h2 {
                font-size: 29px;
            }

            .form-description {
                font-size: 15px;
                margin-bottom: 28px;
            }

            .form-options {
                margin-bottom: 30px;
            }
        }

        @media (max-height: 700px) and (min-width: 901px) {

            .form-container {
                transform: scale(0.92);
            }

            .visual-content {
                bottom: 20px;
            }

            .visual-content h1 {
                font-size: 30px;
            }

            .visual-content p {
                font-size: 16px;
            }
        }
    



/* Add any custom rules for register */

.two-fields {
  display: flex;
  gap: 15px;
}
.two-fields .input-group-custom {
  flex: 1;
}

.terms-row {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 5px;
}

.terms-checkbox {
  margin-top: 4px;
}

.terms-text {
  font-size: 13px;
  color: #4b5563;
  line-height: 1.4;
}

.terms-text a {
  color: #1460d9;
  text-decoration: none;
}

.create-btn {
  width: 100%;
  height: 49px;
  padding: 12px 20px;
  margin-top: 14px;
  margin-bottom: 10px;
  background-color: #075bdc;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.2s, transform 0.1s ease, box-shadow 0.2s ease;
}

.create-btn:hover {
  background-color: #064fbe;
  box-shadow: 0 4px 12px rgba(7, 91, 220, 0.2);
}

.create-btn:active {
  transform: translateY(1px);
}

.create-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

</style>