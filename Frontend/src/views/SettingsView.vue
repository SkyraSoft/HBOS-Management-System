<template>
  <div class="settings-page-wrapper">
    <div class="settings-main-container">
      <!-- DEDICATED PAGE CONTENT (DRIVEN BY SIDEBAR NAVIGATION) -->
      <div class="settings-active-panel">
        <transition name="fade-fast" mode="out-in">
          <div :key="activeTab">
            <!-- 0. BRANCHES (MULTI-STORE ONLY) -->
            <div v-if="activeTab === 'branches' && navigationStore.isMultiBranch" class="settings-view-card">
              <BranchesView />
            </div>

            <!-- 1. USERS ONLY -->
            <div v-else-if="activeTab === 'users'" class="settings-view-card">
              <UsersView />
            </div>

            <!-- 2. ROLES ONLY -->
            <div v-else-if="activeTab === 'roles'" class="settings-view-card">
              <RolesView />
            </div>

            <!-- 3. RECEIPTS ONLY -->
            <div v-else-if="activeTab === 'receipts'" class="settings-view-card">
              <ReceiptsView />
            </div>

            <!-- 4. SECURITY ONLY -->
            <div v-else-if="activeTab === 'security'" class="settings-view-card">
              <SecurityView />
            </div>

            <!-- 5. OFFLINE SYNC ONLY -->
            <div v-else-if="activeTab === 'sync' || activeTab === 'offline-sync'" class="settings-view-card">
              <SyncView />
            </div>

            <!-- 6. USER PROFILE & ACCOUNT -->
            <div v-else-if="activeTab === 'profile'" class="settings-view-card">
              <UserProfileView initial-section="profile" />
            </div>

            <!-- 7. ACCOUNT SECURITY & PASSWORD -->
            <div v-else-if="activeTab === 'account-security' || activeTab === 'security-password'" class="settings-view-card">
              <UserProfileView initial-section="security" />
            </div>
          </div>
        </transition>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useNavigationStore } from '../stores/navigation';
import UsersView from './Settings/UsersView.vue';
import BranchesView from './Settings/BranchesView.vue';
import RolesView from './Settings/RolesView.vue';
import ReceiptsView from './Settings/ReceiptsView.vue';
import SecurityView from './Settings/SecurityView.vue';
import SyncView from './Settings/SyncView.vue';
import UserProfileView from './Settings/UserProfileView.vue';

const route = useRoute();
const navigationStore = useNavigationStore();

const activeTab = ref(route.query.tab || 'users');

watch(() => route.query.tab, (newTab) => {
  if (newTab === 'branches' && !navigationStore.isMultiBranch) {
    activeTab.value = 'users';
  } else if (newTab) {
    activeTab.value = newTab;
  } else {
    activeTab.value = 'users';
  }
}, { immediate: true });

onMounted(() => {
  if (route.query.tab === 'branches' && !navigationStore.isMultiBranch) {
    activeTab.value = 'users';
  } else if (route.query.tab) {
    activeTab.value = route.query.tab;
  }
});
</script>

<style scoped>
.settings-page-wrapper {
  width: 100%;
  min-height: 100%;
  background: #f8fafc;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: #0f172a;
}

.settings-main-container {
  padding: 24px 32px;
}

.settings-active-panel {
  width: 100%;
}

.settings-view-card {
  width: 100%;
}

/* TRANSITIONS */
.fade-fast-enter-active,
.fade-fast-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.fade-fast-enter-from {
  opacity: 0;
  transform: translateY(4px);
}

.fade-fast-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
