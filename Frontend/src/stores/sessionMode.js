import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import desktopBridgeService from '../services/desktopBridgeService';
import { useNotificationStore } from './notifications';

export const SESSION_MODES = {
  UNAUTHENTICATED: 'UNAUTHENTICATED',
  AUTHENTICATED_REAL_USER: 'AUTHENTICATED_REAL_USER',
  NEW_BUSINESS_ONBOARDING: 'NEW_BUSINESS_ONBOARDING',
  DEMO_SINGLE_STORE: 'DEMO_SINGLE_STORE',
  DEMO_MULTI_STORE: 'DEMO_MULTI_STORE',
  VALIDATED_OFFLINE_REAL_SESSION: 'VALIDATED_OFFLINE_REAL_SESSION',
};

export const useSessionModeStore = defineStore('sessionMode', () => {
  const currentMode = ref(SESSION_MODES.UNAUTHENTICATED);
  const activeDemoType = ref(null); // 'single' | 'multi' | null
  const systemStatus = ref('checking'); // 'connected' | 'offline' | 'checking'
  const savedUser = ref(null);
  const savedBusinessName = ref('');

  // Inspect storage to determine existing session on startup
  function resolveStartupState() {
    // Demo sessions are strictly transient: never auto-boot into demo on cold startup
    localStorage.removeItem('hbos_demo_mode');
    activeDemoType.value = null;

    const token = localStorage.getItem('hbos_token');
    const rawUser = localStorage.getItem('hbos_user');

    if (token && rawUser) {
      try {
        const userObj = JSON.parse(rawUser);
        savedUser.value = userObj;
        savedBusinessName.value = userObj.business_name || localStorage.getItem('hbos_business_name') || 'My Store';
        currentMode.value = SESSION_MODES.AUTHENTICATED_REAL_USER;
      } catch (e) {
        savedUser.value = null;
        currentMode.value = SESSION_MODES.UNAUTHENTICATED;
      }
    } else {
      savedUser.value = null;
      savedBusinessName.value = '';
      currentMode.value = SESSION_MODES.UNAUTHENTICATED;
    }
  }

  // Check network/backend connectivity
  async function checkSystemConnectivity() {
    systemStatus.value = 'checking';
    try {
      const resp = await fetch('/api/v1/health', { method: 'GET', headers: { 'Accept': 'application/json' } });
      if (resp.ok) {
        systemStatus.value = 'connected';
      } else {
        systemStatus.value = 'offline';
      }
    } catch (e) {
      // In standalone desktop mode without local XAMPP web server running, mark offline
      systemStatus.value = 'offline';
    }
  }

  // Start Demo Mode
  function startDemo(type = 'single') {
    // Clear any real user tokens to prevent token leakage
    localStorage.removeItem('hbos_token');
    localStorage.removeItem('hbos_user');
    localStorage.removeItem('hbos_business_id');

    localStorage.setItem('hbos_demo_mode', type);
    activeDemoType.value = type;
    if (type === 'multi') {
      currentMode.value = SESSION_MODES.DEMO_MULTI_STORE;
    } else {
      currentMode.value = SESSION_MODES.DEMO_SINGLE_STORE;
    }

    try {
      const notifStore = useNotificationStore();
      notifStore.fetchNotifications();
    } catch (e) {
      // ignore
    }
  }

  // Exit Demo Mode and return to unauthenticated Welcome
  function exitDemo() {
    localStorage.removeItem('hbos_demo_mode');
    activeDemoType.value = null;
    currentMode.value = SESSION_MODES.UNAUTHENTICATED;

    try {
      const notifStore = useNotificationStore();
      notifStore.notifications = [];
    } catch (e) {
      // ignore
    }
  }

  // Clear session / Log out
  function logout() {
    localStorage.removeItem('hbos_token');
    localStorage.removeItem('hbos_user');
    localStorage.removeItem('hbos_business_id');
    localStorage.removeItem('hbos_demo_mode');
    savedUser.value = null;
    savedBusinessName.value = '';
    activeDemoType.value = null;
    currentMode.value = SESSION_MODES.UNAUTHENTICATED;
  }

  // Exit Application completely (Native Tauri close or window exit)
  async function exitApplication() {
    return await desktopBridgeService.exitApplication();
  }

  const isDemo = computed(() => {
    return currentMode.value === SESSION_MODES.DEMO_SINGLE_STORE || currentMode.value === SESSION_MODES.DEMO_MULTI_STORE;
  });

  const isAuthenticatedReal = computed(() => {
    return currentMode.value === SESSION_MODES.AUTHENTICATED_REAL_USER;
  });

  const hasSavedSession = computed(() => {
    return !!savedUser.value;
  });

  return {
    currentMode,
    activeDemoType,
    systemStatus,
    savedUser,
    savedBusinessName,
    isDemo,
    isAuthenticatedReal,
    hasSavedSession,
    resolveStartupState,
    checkSystemConnectivity,
    startDemo,
    exitDemo,
    logout,
    exitApplication,
  };
});
