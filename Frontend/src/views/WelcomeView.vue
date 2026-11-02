<template>
  <div class="welcome-container">
    <!-- Top Bar with Branding, Connectivity, and Native Window Controls -->
    <header class="welcome-header">
      <div class="brand-left">
        <span class="brand-logo" aria-hidden="true">
          <i class="fa-solid fa-shop"></i>
        </span>
        <div class="brand-titles">
          <h1 class="brand-title">SkyraSoft HBOS</h1>
          <p class="brand-subtitle">Hyperlocal Business Operating System</p>
        </div>
      </div>

      <div class="header-right">
        <!-- Connectivity Badge -->
        <div class="status-pill" :class="systemStatusClass">
          <span class="status-dot"></span>
          <span class="status-label">{{ systemStatusText }}</span>
        </div>

        <!-- Exit Application Button -->
        <button type="button" class="btn-exit-app" @click="handleExitApp" title="Exit HBOS Application">
          <i class="fa-solid fa-power-off"></i>
          <span>Exit App</span>
        </button>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="welcome-content">
      <!-- Active Real Session Card (if previously logged in) -->
      <section v-if="sessionStore.hasSavedSession" class="saved-session-banner">
        <div class="session-info">
          <div class="session-avatar">
            <i class="fa-solid fa-user-check"></i>
          </div>
          <div>
            <span class="session-tag">Active Account Found</span>
            <h2 class="session-name">
              {{ sessionStore.savedUser?.name }}
              <span class="business-badge">{{ sessionStore.savedBusinessName }}</span>
            </h2>
            <p class="session-sub">You have an active session saved on this computer.</p>
          </div>
        </div>
        <div class="session-actions">
          <button type="button" class="btn btn-primary" @click="resumeActiveSession">
            <i class="fa-solid fa-arrow-right-to-bracket"></i>
            <span>Resume Work</span>
          </button>
          <button type="button" class="btn btn-ghost" @click="sessionStore.logout()">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Switch Account</span>
          </button>
        </div>
      </section>

      <!-- Welcome Hero Heading -->
      <div class="welcome-hero">
        <h2 class="hero-title">Welcome to Your Retail Operating System</h2>
        <p class="hero-desc">
          Choose an option below to access your real shop, register a brand-new business, or explore our standardized retail demonstration.
        </p>
      </div>

      <!-- Exactly THREE Primary Cards Grid -->
      <div class="entry-cards-grid">
        <!-- CARD 1: LOG IN -->
        <div class="entry-card card-login" @click="navigateTo('/login')">
          <div class="card-icon-wrap">
            <i class="fa-solid fa-key"></i>
          </div>
          <div class="card-body">
            <h3 class="card-title">Log In</h3>
            <p class="card-description">
              Access your existing business account, historical sales, customer Khata, product stock, and cash drawer.
            </p>
            <ul class="card-features">
              <li><i class="fa-solid fa-check"></i> Restore your real store data</li>
              <li><i class="fa-solid fa-check"></i> Owner, Manager, or Cashier sign in</li>
              <li><i class="fa-solid fa-check"></i> Preserves all previous transactions</li>
            </ul>
          </div>
          <div class="card-footer">
            <router-link to="/login" class="btn-card-action btn-login-action" @click.stop>
              <span>Log In</span>
              <i class="fa-solid fa-arrow-right"></i>
            </router-link>
          </div>
        </div>

        <!-- CARD 2: CREATE MY BUSINESS -->
        <div class="entry-card card-register" @click="navigateTo('/register')">
          <div class="card-icon-wrap">
            <i class="fa-solid fa-store"></i>
          </div>
          <div class="card-body">
            <span class="card-pill">New Retailer</span>
            <h3 class="card-title">Create My Business</h3>
            <p class="card-description">
              Register a brand-new retail shop in under two minutes. Completely clean store setup with zero fake data.
            </p>
            <ul class="card-features">
              <li><i class="fa-solid fa-check"></i> 100% clean starting inventory & cash</li>
              <li><i class="fa-solid fa-check"></i> 5-stage guided store setup</li>
              <li><i class="fa-solid fa-check"></i> Pakistani PKR & mobile defaults</li>
            </ul>
          </div>
          <div class="card-footer">
            <router-link to="/register" class="btn-card-action btn-register-action" @click.stop>
              <span>Create My Business</span>
              <i class="fa-solid fa-arrow-right"></i>
            </router-link>
          </div>
        </div>

        <!-- CARD 3: TRY DEMO -->
        <div class="entry-card card-demo" @click="showDemoModal = true">
          <div class="card-icon-wrap">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
          </div>
          <div class="card-body">
            <span class="card-pill pill-demo">Instant Preview</span>
            <h3 class="card-title">Try Demo</h3>
            <p class="card-description">
              Explore a pre-loaded Pakistani retail store immediately without registering or affecting real business data.
            </p>
            <ul class="card-features">
              <li><i class="fa-solid fa-check"></i> Single-Store & Multi-Store options</li>
              <li><i class="fa-solid fa-check"></i> Try POS, loose items, & Khata ledger</li>
              <li><i class="fa-solid fa-check"></i> Safe sandbox with one-click reset</li>
            </ul>
          </div>
          <div class="card-footer">
            <button type="button" class="btn-card-action btn-demo-action" @click.stop="showDemoModal = true">
              <span>Try Demo</span>
              <i class="fa-solid fa-arrow-right"></i>
            </button>
          </div>
        </div>
      </div>
    </main>

    <!-- Footer Information -->
    <footer class="welcome-footer">
      <div class="footer-left">
        <span>Designed for Pakistani retail shopkeepers & expanding multi-branch businesses.</span>
      </div>
      <div class="footer-right">
        <span class="version-badge">HBOS v1.0.0 (Windows Desktop)</span>
      </div>
    </footer>

    <!-- DEMO SELECTION MODAL -->
    <div v-if="showDemoModal" class="demo-modal-overlay" @click.self="showDemoModal = false">
      <div class="demo-modal-card">
        <div class="demo-modal-header">
          <div class="modal-icon">
            <i class="fa-solid fa-layer-group"></i>
          </div>
          <div>
            <h3>Choose Demonstration Experience</h3>
            <p>Select which type of retail business you would like to explore:</p>
          </div>
          <button type="button" class="btn-close-modal" @click="showDemoModal = false" aria-label="Close dialog">&times;</button>
        </div>

        <div class="demo-options-grid">
          <!-- Option A: Single-Store Shop -->
          <div class="demo-option-box" :class="{ 'selected': selectedDemoType === 'single' }" @click="selectedDemoType = 'single'">
            <div class="option-header">
              <span class="option-icon"><i class="fa-solid fa-shop"></i></span>
              <span class="option-tag">Situation 1</span>
            </div>
            <h4>Single-Store Shop</h4>
            <p class="option-sub">Al-Madina General Store (Owner Operated)</p>
            <p class="option-desc">
              Experience HBOS for one person running one store. No dummy employees or complex hierarchy.
            </p>
            <ul class="option-bullets">
              <li>Fast POS checkout & loose/fractional quantities (Kg/g)</li>
              <li>Customer Khata credit ledger & repayment recording</li>
              <li>Supplier purchases, cash payouts, and landed cost</li>
              <li>End-of-day cash drawer closing & P&L report</li>
            </ul>
            <button type="button" class="btn-start-demo" @click.stop="launchDemo('single')">
              Launch Single-Store Demo →
            </button>
          </div>

          <!-- Option B: Multi-Store Supermarket -->
          <div class="demo-option-box" :class="{ 'selected': selectedDemoType === 'multi' }" @click="selectedDemoType = 'multi'">
            <div class="option-header">
              <span class="option-icon"><i class="fa-solid fa-network-wired"></i></span>
              <span class="option-tag">Situation 3</span>
            </div>
            <h4>Multi-Store Supermarket</h4>
            <p class="option-sub">Al-Madina Supermarket (Main Store + 2 Branches)</p>
            <p class="option-desc">
              Experience HBOS for an expanding business. Main store preserved, branch stock transfers, and Owner command.
            </p>
            <ul class="option-bullets">
              <li>Clifton Main Store + Saddar Express & Gulshan Outlet (Karachi)</li>
              <li>Branch Managers & Cashiers with scoped permissions</li>
              <li>Inter-branch stock transfer dispatch & receipt reconciliation</li>
              <li>Owner consolidated All Stores P&L and comparative reporting</li>
            </ul>
            <button type="button" class="btn-start-demo btn-multi-demo" @click.stop="launchDemo('multi')">
              Launch Multi-Store Demo →
            </button>
          </div>
        </div>

        <div class="demo-modal-footer">
          <span class="disclaimer-text">
            <i class="fa-solid fa-shield-halved"></i>
            Demo runs in an isolated sandbox. It never touches real business records or synchronizes fake sales to servers.
          </span>
          <button type="button" class="btn btn-secondary" @click="showDemoModal = false">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionModeStore } from '../stores/sessionMode';

const router = useRouter();
const sessionStore = useSessionModeStore();

const showDemoModal = ref(false);
const selectedDemoType = ref('single');

onMounted(() => {
  sessionStore.resolveStartupState();
  sessionStore.checkSystemConnectivity();
});

const systemStatusClass = computed(() => {
  if (sessionStore.systemStatus === 'connected') return 'status-online';
  if (sessionStore.systemStatus === 'offline') return 'status-offline';
  return 'status-checking';
});

const systemStatusText = computed(() => {
  if (sessionStore.systemStatus === 'connected') return 'System Connected';
  if (sessionStore.systemStatus === 'offline') return 'Local Standalone Mode';
  return 'Checking Connection...';
});

const navigateTo = (path) => {
  router.push(path);
};

const resumeActiveSession = () => {
  const role = sessionStore.savedUser?.role || sessionStore.savedUser?.roles?.[0]?.name;
  if (role === 'Salesperson') {
    router.push('/POS');
  } else {
    router.push('/dashboard');
  }
};

const launchDemo = (type) => {
  sessionStore.startDemo(type);
  showDemoModal.value = false;
  // Route to dashboard or POS in demo mode
  router.push('/dashboard');
};

const handleExitApp = async () => {
  await sessionStore.exitApplication();
};
</script>

<style scoped>
.welcome-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
  color: #f8fafc;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  box-sizing: border-box;
}

/* Header */
.welcome-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 2.5rem;
  background: rgba(15, 23, 42, 0.75);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(12px);
}

.brand-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.brand-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: #ffffff;
  border-radius: 12px;
  font-size: 1.5rem;
  box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
}

.brand-title {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: #ffffff;
}

.brand-subtitle {
  margin: 0;
  font-size: 0.825rem;
  color: #94a3b8;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.status-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.8rem;
  font-weight: 500;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-online .status-dot { background: #10b981; box-shadow: 0 0 8px #10b981; }
.status-online { color: #6ee7b7; border-color: rgba(16, 185, 129, 0.3); }

.status-offline .status-dot { background: #f59e0b; box-shadow: 0 0 8px #f59e0b; }
.status-offline { color: #fcd34d; border-color: rgba(245, 158, 11, 0.3); }

.status-checking .status-dot { background: #94a3b8; }
.status-checking { color: #cbd5e1; }

.btn-exit-app {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 0.95rem;
  background: rgba(239, 68, 68, 0.12);
  color: #fca5a5;
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 8px;
  font-size: 0.825rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-exit-app:hover {
  background: rgba(239, 68, 68, 0.25);
  color: #ffffff;
  border-color: #ef4444;
}

/* Main Content */
.welcome-content {
  flex: 1;
  max-width: 1280px;
  width: 100%;
  margin: 0 auto;
  padding: 2.5rem 2rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  box-sizing: border-box;
}

.saved-session-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(30, 41, 59, 0.9);
  border: 1px solid rgba(16, 185, 129, 0.35);
  border-radius: 16px;
  padding: 1.25rem 1.75rem;
  margin-bottom: 2rem;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.session-info {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.session-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.15);
  color: #10b981;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
}

.session-tag {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #10b981;
  font-weight: 700;
}

.session-name {
  margin: 0.2rem 0;
  font-size: 1.2rem;
  color: #ffffff;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.business-badge {
  font-size: 0.8rem;
  padding: 0.2rem 0.6rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  color: #cbd5e1;
  font-weight: 500;
}

.session-sub {
  margin: 0;
  font-size: 0.825rem;
  color: #94a3b8;
}

.session-actions {
  display: flex;
  gap: 0.75rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.65rem 1.25rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.btn-primary {
  background: #10b981;
  color: #ffffff;
  border: none;
}

.btn-primary:hover {
  background: #059669;
}

.btn-ghost {
  background: transparent;
  color: #cbd5e1;
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.btn-ghost:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #ffffff;
}

.welcome-hero {
  text-align: center;
  margin-bottom: 2.5rem;
}

.hero-title {
  margin: 0 0 0.75rem 0;
  font-size: 2.25rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-desc {
  max-width: 680px;
  margin: 0 auto;
  font-size: 1.05rem;
  color: #94a3b8;
  line-height: 1.5;
}

/* 3 Cards Grid */
.entry-cards-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.75rem;
  margin-bottom: 2rem;
}

.entry-card {
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 2rem 1.75rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  position: relative;
  overflow: hidden;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
}

.entry-card:hover {
  transform: translateY(-4px);
  background: rgba(30, 41, 59, 0.95);
  border-color: rgba(255, 255, 255, 0.2);
  box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.45);
}

.card-login:hover { border-color: rgba(59, 130, 246, 0.5); }
.card-register:hover { border-color: rgba(16, 185, 129, 0.5); }
.card-demo:hover { border-color: rgba(168, 85, 247, 0.5); }

.card-icon-wrap {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  margin-bottom: 1.5rem;
}

.card-login .card-icon-wrap { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
.card-register .card-icon-wrap { background: rgba(16, 185, 129, 0.15); color: #34d399; }
.card-demo .card-icon-wrap { background: rgba(168, 85, 247, 0.15); color: #c084fc; }

.card-pill {
  display: inline-block;
  padding: 0.25rem 0.65rem;
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
  border-radius: 9999px;
  font-size: 0.725rem;
  font-weight: 700;
  text-transform: uppercase;
  margin-bottom: 0.75rem;
}

.pill-demo {
  background: rgba(168, 85, 247, 0.15);
  color: #c084fc;
}

.card-title {
  margin: 0 0 0.75rem 0;
  font-size: 1.45rem;
  font-weight: 700;
  color: #ffffff;
}

.card-description {
  margin: 0 0 1.25rem 0;
  font-size: 0.925rem;
  color: #94a3b8;
  line-height: 1.45;
  min-height: 54px;
}

.card-features {
  list-style: none;
  padding: 0;
  margin: 0 0 1.75rem 0;
}

.card-features li {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.85rem;
  color: #cbd5e1;
  margin-bottom: 0.5rem;
}

.card-features li i {
  color: #10b981;
  font-size: 0.75rem;
}

.card-footer {
  margin-top: auto;
}

.btn-card-action {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.6rem;
  padding: 0.85rem 1.25rem;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.btn-login-action {
  background: #2563eb;
  color: #ffffff;
}
.btn-login-action:hover { background: #1d4ed8; }

.btn-register-action {
  background: #059669;
  color: #ffffff;
}
.btn-register-action:hover { background: #047857; }

.btn-demo-action {
  background: #7c3aed;
  color: #ffffff;
}
.btn-demo-action:hover { background: #6d28d9; }

/* Footer */
.welcome-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 2.5rem;
  background: rgba(15, 23, 42, 0.8);
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  font-size: 0.825rem;
  color: #64748b;
}

.version-badge {
  background: rgba(255, 255, 255, 0.05);
  padding: 0.25rem 0.6rem;
  border-radius: 6px;
  color: #94a3b8;
  font-family: monospace;
}

/* Demo Modal */
.demo-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1.5rem;
}

.demo-modal-card {
  background: #1e293b;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 20px;
  max-width: 860px;
  width: 100%;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
  padding: 2rem;
  box-sizing: border-box;
}

.demo-modal-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  position: relative;
  margin-bottom: 1.75rem;
}

.modal-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(168, 85, 247, 0.15);
  color: #c084fc;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.demo-modal-header h3 {
  margin: 0;
  font-size: 1.35rem;
  color: #ffffff;
}

.demo-modal-header p {
  margin: 0.25rem 0 0 0;
  font-size: 0.875rem;
  color: #94a3b8;
}

.btn-close-modal {
  position: absolute;
  top: 0;
  right: 0;
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 1.5rem;
  cursor: pointer;
  line-height: 1;
}

.btn-close-modal:hover { color: #ffffff; }

.demo-options-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.75rem;
}

.demo-option-box {
  background: rgba(15, 23, 42, 0.6);
  border: 2px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  padding: 1.5rem;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: all 0.2s ease;
}

.demo-option-box:hover, .demo-option-box.selected {
  border-color: #a855f7;
  background: rgba(168, 85, 247, 0.05);
}

.option-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.option-icon {
  font-size: 1.3rem;
  color: #c084fc;
}

.option-tag {
  font-size: 0.725rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.08);
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
}

.demo-option-box h4 {
  margin: 0;
  font-size: 1.15rem;
  color: #ffffff;
}

.option-sub {
  margin: 0.2rem 0 0.75rem 0;
  font-size: 0.8rem;
  color: #a855f7;
  font-weight: 600;
}

.option-desc {
  font-size: 0.85rem;
  color: #94a3b8;
  line-height: 1.4;
  margin-bottom: 1rem;
}

.option-bullets {
  list-style: none;
  padding: 0;
  margin: 0 0 1.25rem 0;
}

.option-bullets li {
  font-size: 0.8rem;
  color: #cbd5e1;
  margin-bottom: 0.4rem;
  padding-left: 1rem;
  position: relative;
}

.option-bullets li::before {
  content: "•";
  color: #a855f7;
  position: absolute;
  left: 0;
  font-weight: bold;
}

.btn-start-demo {
  width: 100%;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  background: #a855f7;
  color: #ffffff;
  font-weight: 600;
  font-size: 0.9rem;
  border: none;
  cursor: pointer;
  transition: background 0.2s;
  margin-top: auto;
}

.btn-start-demo:hover { background: #9333ea; }
.btn-multi-demo { background: #3b82f6; }
.btn-multi-demo:hover { background: #2563eb; }

.demo-modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 1.25rem;
}

.disclaimer-text {
  font-size: 0.775rem;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.disclaimer-text i {
  color: #10b981;
}

.btn-secondary {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #cbd5e1;
}
.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}

/* Responsive adjustments for 1366x768 and smaller screens */
@media (max-width: 1200px) {
  .welcome-content {
    padding: 1.5rem 1rem;
  }
  .entry-cards-grid {
    gap: 1.25rem;
  }
  .entry-card {
    padding: 1.5rem 1.25rem;
  }
  .hero-title {
    font-size: 1.85rem;
  }
}

@media (max-width: 900px) {
  .entry-cards-grid {
    grid-template-columns: 1fr;
  }
  .demo-options-grid {
    grid-template-columns: 1fr;
  }
}
</style>
