<template>
  <div v-if="isDemo" class="demo-mode-banner" role="banner" aria-label="Demo Mode Indicator">
    <div class="demo-banner-container">
      <div class="demo-info-left">
        <span class="demo-badge">
          <i class="fa-solid fa-flask"></i> DEMO MODE
        </span>
        <span class="demo-type-pill">
          {{ demoType === 'multi' ? 'Multi-Store Demo (3 Branches)' : 'Single-Store Demo (Independent Shop)' }}
        </span>
        <span class="demo-disclaimer d-none d-lg-inline">
          Fictional data sandbox. Real business records are completely isolated.
        </span>
      </div>

      <div class="demo-actions-right">
        <!-- Switch Demo Type -->
        <button 
          type="button" 
          class="btn-demo-action" 
          @click="toggleDemoType"
          :title="demoType === 'multi' ? 'Switch to Single-Store Demo' : 'Switch to Multi-Store Demo'"
        >
          <i class="fa-solid fa-repeat"></i>
          <span>{{ demoType === 'multi' ? 'Single-Store' : 'Multi-Store' }}</span>
        </button>

        <!-- Reset Demo Sandbox -->
        <button 
          type="button" 
          class="btn-demo-action btn-reset" 
          @click="resetSandbox"
          title="Reset sample sandbox to original pristine demo state"
        >
          <i class="fa-solid fa-rotate-left"></i>
          <span>Reset Demo</span>
        </button>

        <!-- Exit Demo to Welcome -->
        <button 
          type="button" 
          class="btn-demo-action btn-exit" 
          @click="exitDemo"
          title="Exit demonstration and return to Welcome screen"
        >
          <i class="fa-solid fa-arrow-right-from-bracket"></i>
          <span>Exit Demo</span>
        </button>

        <!-- Create Real Business -->
        <button 
          type="button" 
          class="btn-demo-action btn-register" 
          @click="createRealBusiness"
          title="Register a clean, real business"
        >
          <i class="fa-solid fa-store"></i>
          <span>Create My Business</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionModeStore } from '../stores/sessionMode';

const router = useRouter();
const sessionStore = useSessionModeStore();

const isDemo = computed(() => {
  const mode = localStorage.getItem('hbos_demo_mode');
  return mode === 'single' || mode === 'multi';
});

const demoType = computed(() => {
  return localStorage.getItem('hbos_demo_mode') || 'single';
});

const toggleDemoType = () => {
  const newType = demoType.value === 'multi' ? 'single' : 'multi';
  sessionStore.startDemo(newType);
  window.location.reload();
};

const resetSandbox = () => {
  if (confirm('Reset demonstration sandbox to pristine sample records? Any actions made during this demo session will be reset.')) {
    localStorage.removeItem(`HBOS_Store_Biz_demo_${demoType.value}`);
    sessionStore.startDemo(demoType.value);
    window.location.reload();
  }
};

const exitDemo = () => {
  sessionStore.exitDemo();
  router.push('/welcome');
};

const createRealBusiness = () => {
  sessionStore.exitDemo();
  router.push('/register');
};
</script>

<style scoped>
.demo-mode-banner {
  background: linear-gradient(90deg, #6d28d9 0%, #7c3aed 50%, #4f46e5 100%);
  color: #ffffff;
  padding: 6px 16px;
  position: sticky;
  top: 0;
  z-index: 1045;
  box-shadow: 0 2px 10px rgba(109, 40, 217, 0.35);
  font-size: 0.825rem;
}

.demo-banner-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 1600px;
  margin: 0 auto;
  gap: 12px;
}

.demo-info-left {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.demo-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.35);
  padding: 2px 8px;
  border-radius: 9999px;
  font-weight: 700;
  letter-spacing: 0.04em;
  font-size: 0.75rem;
  text-transform: uppercase;
}

.demo-type-pill {
  font-weight: 600;
  color: #f3e8ff;
}

.demo-disclaimer {
  color: rgba(255, 255, 255, 0.75);
  font-size: 0.75rem;
  border-left: 1px solid rgba(255, 255, 255, 0.25);
  padding-left: 10px;
}

.demo-actions-right {
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-demo-action {
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.25);
  color: #ffffff;
  padding: 3px 9px;
  border-radius: 6px;
  font-size: 0.775rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  transition: all 0.15s ease;
}

.btn-demo-action:hover {
  background: rgba(255, 255, 255, 0.28);
  border-color: rgba(255, 255, 255, 0.5);
}

.btn-reset:hover {
  background: rgba(245, 158, 11, 0.3);
  border-color: #f59e0b;
}

.btn-exit:hover {
  background: rgba(239, 68, 68, 0.3);
  border-color: #ef4444;
}

.btn-register {
  background: #10b981;
  border-color: #059669;
  font-weight: 600;
}

.btn-register:hover {
  background: #059669;
}

@media (max-width: 768px) {
  .demo-disclaimer {
    display: none;
  }
  .demo-actions-right span {
    display: none;
  }
}
</style>
