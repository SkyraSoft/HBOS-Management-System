<template>
  <div v-if="navStore.isViewAsActive" class="view-as-banner">
    <div class="view-as-container">
      <div class="view-as-info">
        <i class="bi bi-eye-fill"></i>
        <span class="view-as-badge">READ-ONLY PREVIEW</span>
        <span class="view-as-text">
          Viewing <strong>{{ navStore.viewAsBranchName || 'Branch #' + navStore.viewAsBranchId }}</strong> 
          as <strong>{{ navStore.viewAsRole }}</strong>.
        </span>
        <span class="view-as-muted">All mutations are blocked server-side.</span>
      </div>
      <button class="view-as-exit-btn" @click="handleExit" :disabled="isExiting">
        <i class="bi bi-box-arrow-right"></i>
        <span>{{ isExiting ? 'Exiting...' : 'Exit Preview' }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useNavigationStore } from '../stores/navigation';
import api from '../api';

const navStore = useNavigationStore();
const isExiting = ref(false);

async function handleExit() {
  isExiting.value = true;
  try {
    await api.post('/view-as/exit');
  } catch (err) {
    console.warn('View-As exit API call completed with notice:', err);
  } finally {
    navStore.exitViewAs();
    isExiting.value = false;
  }
}
</script>

<style scoped>
.view-as-banner {
  background: linear-gradient(90deg, #d97706, #b45309);
  color: #ffffff;
  padding: 8px 16px;
  position: sticky;
  top: 0;
  z-index: 1050;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  font-size: 0.9rem;
}

.view-as-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 1400px;
  margin: 0 auto;
}

.view-as-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.view-as-badge {
  background: rgba(0, 0, 0, 0.3);
  padding: 2px 8px;
  border-radius: 4px;
  font-weight: 700;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
}

.view-as-text {
  font-weight: 500;
}

.view-as-muted {
  opacity: 0.85;
  font-size: 0.8rem;
  margin-left: 8px;
}

.view-as-exit-btn {
  background: #ffffff;
  color: #b45309;
  border: none;
  border-radius: 6px;
  padding: 4px 14px;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s ease;
}

.view-as-exit-btn:hover {
  background: #fef3c7;
  transform: translateY(-1px);
}

.view-as-exit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
