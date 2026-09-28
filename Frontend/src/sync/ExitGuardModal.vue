<template>
  <div v-if="visible" class="exit-guard-overlay">
    <div class="exit-guard-modal">
      <div class="modal-header">
        <div class="shield-icon">🛡️</div>
        <div>
          <h2>HBOS Mandatory Shift-Close Guard</h2>
          <p class="subtitle">Validating Cloud Sync & Ledger Integrity Before Exit</p>
        </div>
      </div>

      <div class="modal-body">
        <!-- Progress Bar -->
        <div class="progress-container">
          <div class="progress-bar" :style="{ width: progressPercentage + '%' }"></div>
        </div>
        <p class="progress-text">{{ currentStatusText }}</p>

        <!-- Step List -->
        <div class="step-list">
          <div 
            v-for="(item, idx) in logs" 
            :key="idx" 
            class="step-item"
            :class="{ active: idx === logs.length - 1 }"
          >
            <span class="step-bullet">●</span>
            <span class="step-msg">{{ item.msg }}</span>
          </div>
        </div>

        <!-- Offline Checksum Warning Box (If cloud is unreachable) -->
        <div v-if="offlineSeal" class="seal-alert-box">
          <div class="seal-title">⚠️ Cryptographic Offline Seal Created</div>
          <div class="seal-hash">{{ offlineSeal }}</div>
          <div class="seal-desc">
            Internet is currently unreachable. Transactions are locked in encrypted local outbox and will auto-sync on next app boot.
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button 
          v-if="isComplete" 
          class="btn-exit"
          @click="confirmShutdown"
        >
          🔒 Safe Application Exit
        </button>
        <button 
          v-else 
          class="btn-processing" 
          disabled
        >
          ⏳ Executing Sync & Z-Report...
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import syncCoordinator from './SyncCoordinator.js';

export default {
  name: 'ExitGuardModal',
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    shiftSummary: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      logs: [],
      progressPercentage: 10,
      currentStatusText: 'Initializing Shift-Close Guard...',
      isComplete: false,
      offlineSeal: null
    };
  },
  watch: {
    visible(val) {
      if (val) {
        this.runExitGuardProcess();
      }
    }
  },
  methods: {
    async runExitGuardProcess() {
      this.logs = [];
      this.progressPercentage = 15;
      this.isComplete = false;
      this.offlineSeal = null;

      try {
        const result = await syncCoordinator.executeShiftCloseExitGuard(this.shiftSummary);
        this.logs = result.statusLog;
        this.progressPercentage = 100;
        this.currentStatusText = 'Shift-Close Guard complete. All systems secure.';
        this.isComplete = true;
        if (result.offlineChecksum) {
          this.offlineSeal = result.offlineChecksum;
        }
      } catch (err) {
        this.logs.push({ step: 'ERROR', msg: `Sync Error: ${err.message}` });
        this.progressPercentage = 100;
        this.currentStatusText = 'Process completed with offline seal fallback.';
        this.isComplete = true;
      }
    },
    confirmShutdown() {
      this.$emit('close-and-exit');
      if (typeof window !== 'undefined' && window.__TAURI__) {
        window.__TAURI__.process.exit(0);
      }
    }
  }
};
</script>

<style scoped>
.exit-guard-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 99999;
}

.exit-guard-modal {
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 16px;
  width: 540px;
  max-width: 90vw;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  color: #f8fafc;
  overflow: hidden;
}

.modal-header {
  padding: 24px;
  background: #0f172a;
  display: flex;
  align-items: center;
  gap: 16px;
  border-bottom: 1px solid #334155;
}

.shield-icon {
  font-size: 2.2rem;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #38bdf8;
}

.subtitle {
  margin: 4px 0 0 0;
  font-size: 0.85rem;
  color: #94a3b8;
}

.modal-body {
  padding: 24px;
}

.progress-container {
  background: #334155;
  height: 8px;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 12px;
}

.progress-bar {
  background: linear-gradient(90deg, #38bdf8, #818cf8);
  height: 100%;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.9rem;
  font-weight: 600;
  color: #cbd5e1;
  margin-bottom: 16px;
}

.step-list {
  background: #0f172a;
  border: 1px solid #334155;
  border-radius: 8px;
  padding: 12px;
  max-height: 160px;
  overflow-y: auto;
  font-family: monospace;
  font-size: 0.82rem;
}

.step-item {
  display: flex;
  gap: 8px;
  margin-bottom: 6px;
  color: #64748b;
}

.step-item.active {
  color: #38bdf8;
  font-weight: bold;
}

.seal-alert-box {
  margin-top: 16px;
  background: rgba(234, 179, 8, 0.1);
  border: 1px solid #eab308;
  border-radius: 8px;
  padding: 12px;
  color: #fef08a;
}

.seal-title {
  font-weight: bold;
  font-size: 0.85rem;
  margin-bottom: 4px;
}

.seal-hash {
  font-family: monospace;
  font-size: 0.75rem;
  background: rgba(0, 0, 0, 0.3);
  padding: 4px 8px;
  border-radius: 4px;
  word-break: break-all;
  margin-bottom: 6px;
}

.seal-desc {
  font-size: 0.78rem;
  color: #fde047;
}

.modal-footer {
  padding: 16px 24px;
  background: #0f172a;
  border-top: 1px solid #334155;
  display: flex;
  justify-content: flex-end;
}

.btn-exit {
  background: #22c55e;
  color: #052e16;
  font-weight: bold;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.95rem;
  transition: all 0.2s;
}

.btn-exit:hover {
  background: #16a34a;
  color: #ffffff;
}

.btn-processing {
  background: #334155;
  color: #94a3b8;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 0.9rem;
}
</style>
