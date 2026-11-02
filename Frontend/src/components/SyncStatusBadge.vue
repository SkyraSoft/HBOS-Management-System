<template>
  <div class="sync-badge-wrapper position-relative" ref="containerRef">
    <!-- Status Pill Button -->
    <button
      @click="togglePopover"
      type="button"
      class="sync-pill-btn d-inline-flex align-items-center gap-1.5 px-2.5 py-1 rounded-pill border shadow-xs"
      :class="badgeClass"
      :title="badgeTooltip"
    >
      <!-- Pulsing Dot or Spinner -->
      <span v-if="state.status === 'syncing'" class="sync-spinner-dot"></span>
      <span v-else class="sync-dot" :class="dotClass"></span>

      <!-- Label -->
      <span class="sync-label fw-semibold">{{ labelText }}</span>

      <!-- Pending Count Pill -->
      <span
        v-if="state.pendingCount > 0"
        class="badge rounded-pill ms-0.5"
        :class="countBadgeClass"
        style="font-size: 10px; padding: 2px 5px;"
      >
        {{ state.pendingCount }}
      </span>
    </button>

    <!-- Details Popover (Floating Absolutely Positioned, NEVER pushes navbar) -->
    <div
      v-if="isPopoverOpen"
      class="sync-popover shadow-lg rounded-3 bg-white p-3"
      @click.stop
    >
      <div class="pb-2 border-bottom">
        <div class="d-flex align-items-center justify-content-between mb-1">
          <span class="fw-bold text-dark" style="font-size: 13px;">Device Sync Status</span>
          <span class="badge bg-light text-muted border font-monospace" style="font-size: 10px;">This Device</span>
        </div>
        <p class="text-muted mb-0" style="font-size: 11.5px; line-height: 1.35;">
          {{ statusDescription }}
        </p>
      </div>

      <div class="py-2.5 border-bottom" style="font-size: 12px;">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span class="text-secondary">Unsynced Transactions:</span>
          <span class="fw-bold text-dark">{{ state.pendingCount }}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span class="text-secondary">Network Reachability:</span>
          <span :class="state.isOnline ? 'text-success fw-semibold' : 'text-warning fw-semibold'">
            <i class="bi me-1" :class="state.isOnline ? 'bi-wifi' : 'bi-wifi-off'"></i>
            {{ state.isOnline ? 'Connected' : 'Offline' }}
          </span>
        </div>
        <div v-if="state.lastSyncTime" class="d-flex justify-content-between align-items-center">
          <span class="text-secondary">Last Synced:</span>
          <span class="text-dark">{{ formatTime(state.lastSyncTime) }}</span>
        </div>
      </div>

      <!-- Actionable Resolution Guidance or Error -->
      <div v-if="state.lastError" class="py-2">
        <div class="p-2 rounded-2 bg-danger-subtle border border-danger-subtle text-danger" style="font-size: 11px;">
          <div class="fw-bold">{{ formatErrorCategory(state.lastError.category) }}</div>
          <div class="mt-0.5 text-danger-emphasis">{{ state.lastError.message }}</div>
        </div>
      </div>

      <!-- Actions -->
      <div class="pt-2">
        <button
          @click="handleManualSync"
          type="button"
          :disabled="state.isSyncing || !state.isOnline || state.status === 'update_required'"
          class="btn btn-primary btn-sm w-100 rounded-2 fw-semibold d-flex align-items-center justify-content-center gap-1.5"
          style="font-size: 12px; height: 32px;"
        >
          <span v-if="state.isSyncing" class="spinner-border spinner-border-sm" role="status"></span>
          <span v-if="state.isSyncing">Syncing...</span>
          <span v-else-if="state.status === 'update_required'">App Update Required</span>
          <span v-else>
            <i class="bi bi-arrow-repeat me-1"></i> Sync Now
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { syncCoordinator } from '../services/syncCoordinator.js';

const containerRef = ref(null);
const isPopoverOpen = ref(false);
const state = ref(syncCoordinator.getState());

let unsubscribe = null;

onMounted(() => {
  unsubscribe = syncCoordinator.subscribe((newState) => {
    state.value = newState;
  });
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  if (unsubscribe) unsubscribe();
  document.removeEventListener('click', handleClickOutside);
});

function togglePopover() {
  isPopoverOpen.value = !isPopoverOpen.value;
}

function handleClickOutside(event) {
  if (containerRef.value && !containerRef.value.contains(event.target)) {
    isPopoverOpen.value = false;
  }
}

async function handleManualSync() {
  await syncCoordinator.manualRetry();
}

const labelText = computed(() => {
  if (state.value.status === 'update_required') return 'Update Required';
  if (state.value.status === 'syncing') return 'Syncing...';
  if (!state.value.isOnline) return 'Offline';
  if (state.value.lastError && state.value.pendingCount > 0) return 'Needs Attention';
  if (state.value.pendingCount > 0) return 'Pending';
  return 'Online';
});

const badgeClass = computed(() => {
  if (state.value.status === 'update_required') {
    return 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100';
  }
  if (state.value.status === 'syncing') {
    return 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100';
  }
  if (!state.value.isOnline) {
    return 'bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200';
  }
  if (state.value.lastError && state.value.pendingCount > 0) {
    return 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100';
  }
  if (state.value.pendingCount > 0) {
    return 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100';
  }
  return 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100';
});

const dotClass = computed(() => {
  if (state.value.status === 'update_required') return 'bg-purple-500';
  if (!state.value.isOnline) return 'bg-gray-400';
  if (state.value.lastError && state.value.pendingCount > 0) return 'bg-red-500';
  if (state.value.pendingCount > 0) return 'bg-amber-500';
  return 'bg-green-500';
});

const countBadgeClass = computed(() => {
  if (state.value.lastError) return 'bg-red-200 text-red-800';
  if (!state.value.isOnline) return 'bg-gray-200 text-gray-800';
  return 'bg-amber-200 text-amber-800';
});

const badgeTooltip = computed(() => {
  return `${labelText.value} (${state.value.pendingCount} pending on this device)`;
});

const statusDescription = computed(() => {
  if (state.value.status === 'update_required') {
    return 'Server requires client update before synchronizing this device queue.';
  }
  if (state.value.status === 'syncing') {
    return 'Uploading queued transactions for this device...';
  }
  if (!state.value.isOnline) {
    return 'Counter operating offline. Sales safely saved to this device.';
  }
  if (state.value.lastError) {
    return 'Some transactions require review or network reconnection.';
  }
  if (state.value.pendingCount > 0) {
    return `${state.value.pendingCount} transactions waiting to sync on this device.`;
  }
  return 'All transactions on this device are synchronized with the server.';
});

function formatTime(isoString) {
  try {
    const d = new Date(isoString);
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  } catch (e) {
    return isoString;
  }
}

function formatErrorCategory(cat) {
  if (!cat) return 'Sync Error';
  switch (cat) {
    case 'STOCK_CONFLICT':
      return 'Stock Conflict';
    case 'PRICE_CONFLICT':
      return 'Price Conflict';
    case 'AUTH_REQUIRED':
      return 'Authentication Expired';
    case 'UPDATE_REQUIRED':
      return 'Client Upgrade Required';
    case 'NETWORK_RETRYABLE':
      return 'Network Retryable';
    default:
      return 'Action Required';
  }
}
</script>

<style scoped>
.sync-badge-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.sync-pill-btn {
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  outline: none;
  transition: all 0.15s ease-in-out;
  background-color: #ffffff;
}

.sync-pill-btn:hover {
  filter: brightness(0.96);
}

.sync-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.sync-spinner-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #0d6efd;
  display: inline-block;
  animation: syncPulse 1.2s infinite ease-in-out;
}

@keyframes syncPulse {
  0% { transform: scale(0.85); opacity: 0.6; }
  50% { transform: scale(1.25); opacity: 1; }
  100% { transform: scale(0.85); opacity: 0.6; }
}

/* Floating Popover: Absolute positioning ensures ZERO layout disruption to navbar */
.sync-popover {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 295px;
  z-index: 1060;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
  animation: popoverFadeIn 0.15s ease-out;
}

@keyframes popoverFadeIn {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.shadow-xs {
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
</style>
