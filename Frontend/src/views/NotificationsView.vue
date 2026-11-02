<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useNotificationStore } from '../stores/notifications';
import ActionModalFactory from '../components/ActionModalFactory.vue';

const router = useRouter();
const route = useRoute();
const notificationStore = useNotificationStore();

const selectedActionNotification = ref(null);
const searchQuery = ref('');

const activeTab = ref('All');
const tabs = [
  { id: 'All', label: 'All Notifications' },
  { id: 'Alerts & Attention', label: 'Needs Attention / Alerts', isAlertTab: true },
  { id: 'Important/Soon', label: 'Important / Soon' },
  { id: 'Common / Activity', label: 'Activity & System' }
];

onMounted(() => {
  if (route.query.tab === 'alerts' || route.query.tab === 'needs_attention') {
    activeTab.value = 'Alerts & Attention';
  } else if (route.query.tab === 'activity' || route.query.tab === 'system') {
    activeTab.value = 'Common / Activity';
  } else if (route.query.tab === 'important') {
    activeTab.value = 'Important/Soon';
  }
  notificationStore.fetchNotifications();
});

const alertCount = computed(() => {
  return notificationStore.formattedNotifications.filter(n => {
    return (n.type === 'Alerts & Attention' || n.type === 'Important/Soon' || n.type === 'Emergency/Critical') && !n.isRead;
  }).length;
});

const getTabCount = (tabId) => {
  if (tabId === 'All') return notificationStore.formattedNotifications.length;
  if (tabId === 'Alerts & Attention') {
    return notificationStore.formattedNotifications.filter(n => n.type === 'Alerts & Attention' || n.type === 'Emergency/Critical').length;
  }
  if (tabId === 'Important/Soon') {
    return notificationStore.formattedNotifications.filter(n => n.type === 'Important/Soon').length;
  }
  return notificationStore.formattedNotifications.filter(n => n.type === 'Common / Activity' || n.type === 'Common/General').length;
};

const filteredNotifications = computed(() => {
  let list = notificationStore.formattedNotifications;

  // Filter by Tab
  if (activeTab.value === 'Alerts & Attention') {
    list = list.filter(n => n.type === 'Alerts & Attention' || n.type === 'Emergency/Critical');
  } else if (activeTab.value === 'Important/Soon') {
    list = list.filter(n => n.type === 'Important/Soon');
  } else if (activeTab.value === 'Common / Activity') {
    list = list.filter(n => n.type === 'Common / Activity' || n.type === 'Common/General');
  }

  // Filter by Search Query
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter(n => 
      (n.title && n.title.toLowerCase().includes(q)) || 
      (n.message && n.message.toLowerCase().includes(q))
    );
  }

  return list;
});

const handleNotificationAction = async (notification) => {
  if (!notification.isRead) {
    await notificationStore.markAsRead(notification.id);
  }

  if (notification.action_type === 'navigate' && notification.action_payload) {
    router.push(notification.action_payload);
  } else if (notification.action_payload && typeof notification.action_payload === 'string' && notification.action_payload.startsWith('/')) {
    router.push(notification.action_payload);
  } else if (notification.action_type) {
    selectedActionNotification.value = notification;
  }
};

const markAllAsRead = async () => {
  await notificationStore.markAllAsRead();
};

const dismissNotification = async (id) => {
  await notificationStore.dismissNotification(id);
};

const markAsRead = async (id) => {
  await notificationStore.markAsRead(id);
};
</script>

<template>
  <div class="notifications-page p-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
      <div>
        <h1 class="page-title m-0">Alerts &amp; Notifications</h1>
        <p class="text-muted mb-0 mt-1">Centralized operational alerts, attention items, and store activity.</p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button 
          v-if="notificationStore.unreadCount > 0"
          class="btn btn-outline-primary rounded-pill px-4 fw-semibold" 
          @click="markAllAsRead"
        >
          <i class="bi bi-check2-all me-1"></i> Mark All Read
        </button>
      </div>
    </div>

    <!-- Active Attention Banner (Visible when unread alerts exist) -->
    <div 
      v-if="alertCount > 0 && activeTab !== 'Alerts & Attention'" 
      class="alert border-0 shadow-sm rounded-4 bg-warning-subtle text-dark p-3.5 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3"
    >
      <div class="d-flex align-items-center gap-3">
        <div class="p-2.5 rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center">
          <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        </div>
        <div>
          <h6 class="fw-bold mb-0.5">Needs Your Attention ({{ alertCount }})</h6>
          <span class="text-secondary small">Pending supplier payables, customer Khata receivables, or stock thresholds require review.</span>
        </div>
      </div>
      <button 
        type="button" 
        class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3 shadow-xs" 
        @click="activeTab = 'Alerts & Attention'"
      >
        View Attention Items &rarr;
      </button>
    </div>

    <!-- Filter Tabs & Search Bar Row -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 border-bottom pb-3">
      <div class="nav nav-pills gap-2">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          class="nav-link rounded-pill px-3.5 py-1.5 fw-medium d-flex align-items-center gap-2"
          :class="activeTab === tab.id ? 'active shadow-sm' : 'text-secondary bg-light'"
          @click="activeTab = tab.id"
        >
          <i v-if="tab.isAlertTab" class="bi bi-exclamation-triangle-fill text-warning"></i>
          <span>{{ tab.label }}</span>
          <span 
            class="badge rounded-pill"
            :class="activeTab === tab.id ? 'bg-white text-primary' : (tab.isAlertTab && alertCount > 0 ? 'bg-warning text-dark' : 'bg-secondary-subtle text-secondary')"
            style="font-size: 11px;"
          >
            {{ getTabCount(tab.id) }}
          </span>
        </button>
      </div>

      <!-- Quick Search Input -->
      <div class="search-input-wrapper position-relative" style="min-width: 260px;">
        <i class="bi bi-search position-absolute text-muted" style="top: 10px; left: 14px;"></i>
        <input 
          v-model="searchQuery" 
          type="text" 
          class="form-control rounded-pill ps-5 bg-white border-light shadow-xs text-sm" 
          placeholder="Filter notifications..."
        >
        <button 
          v-if="searchQuery" 
          type="button" 
          class="btn btn-sm btn-link position-absolute text-muted p-0" 
          style="top: 8px; right: 12px;"
          @click="searchQuery = ''"
        >
          <i class="bi bi-x-circle-fill"></i>
        </button>
      </div>
    </div>

    <!-- Notifications List -->
    <div class="row g-3">
      <div v-for="notification in filteredNotifications" :key="notification.id" class="col-12">
        <div 
          class="card border-0 shadow-sm rounded-4 overflow-hidden notification-card"
          :class="{ 
            'unread-card': !notification.isRead,
            'alert-card': notification.type === 'Alerts & Attention'
          }"
        >
          <div class="card-body p-4">
            <div class="d-flex gap-4 align-items-start">
              
              <!-- Icon -->
              <div 
                class="notification-icon-wrapper rounded-circle d-flex align-items-center justify-content-center" 
                :class="notification.bgClass" 
                style="width: 52px; height: 52px; flex-shrink: 0;"
              >
                <i class="bi fs-4" :class="[notification.icon, notification.colorClass]"></i>
              </div>

              <!-- Content -->
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h5 class="mb-0 fw-bold text-dark fs-6">{{ notification.title }}</h5>
                    <span 
                      class="badge rounded-pill fw-semibold" 
                      :class="{
                        'bg-warning-subtle text-warning-emphasis border border-warning-subtle': notification.type === 'Alerts & Attention',
                        'bg-danger-subtle text-danger border border-danger-subtle': notification.severity === 'critical',
                        'bg-primary-subtle text-primary border border-primary-subtle': notification.type === 'Common / Activity'
                      }"
                      style="font-size: 11px;"
                    >
                      {{ notification.type }}
                    </span>
                    <span v-if="!notification.isRead" class="badge bg-primary text-white rounded-pill px-2" style="font-size: 10px;">
                      Unread
                    </span>
                  </div>
                  <small class="text-muted fw-medium">{{ notification.time }}</small>
                </div>
                
                <p class="mb-0 text-secondary" style="font-size: 14.5px; line-height: 1.5;">{{ notification.message }}</p>

                <!-- Actions Toolbar -->
                <div class="mt-3 d-flex gap-2 align-items-center flex-wrap">
                  <button 
                    v-if="notification.action_payload || notification.action_type" 
                    class="btn btn-sm btn-primary rounded-pill px-3.5 fw-semibold shadow-xs d-flex align-items-center gap-1.5" 
                    @click="handleNotificationAction(notification)"
                  >
                    <span>{{ notification.action_label || 'Take Action' }}</span>
                    <i class="bi bi-arrow-right"></i>
                  </button>
                  <button 
                    v-if="!notification.isRead" 
                    class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-medium" 
                    @click="markAsRead(notification.id)"
                  >
                    Mark as Read
                  </button>
                  <button 
                    class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-medium" 
                    @click="dismissNotification(notification.id)"
                  >
                    Dismiss
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-if="filteredNotifications.length === 0" class="col-12">
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
          <div class="d-inline-block bg-light rounded-circle p-4 mb-3">
            <i class="bi bi-bell-slash text-muted" style="font-size: 2.5rem;"></i>
          </div>
          <h5 class="fw-bold text-dark">No notifications found</h5>
          <p class="text-muted mb-0">
            {{ searchQuery ? 'No alerts matched your search query.' : "You don't have any items in this category right now." }}
          </p>
        </div>
      </div>
    </div>

    <!-- Action Modal (Fallback for interactive modals) -->
    <ActionModalFactory 
      :notification="selectedActionNotification" 
      @close="selectedActionNotification = null" 
    />
  </div>
</template>

<style scoped>
.page-title {
  color: #1e293b;
  font-size: 28px;
  font-weight: 800;
  letter-spacing: -0.5px;
}
.nav-pills .nav-link {
  cursor: pointer;
  transition: all 0.2s ease;
  border: none !important;
  outline: none !important;
}
.nav-pills .nav-link:not(.active):hover {
  background-color: #e2e8f0 !important;
  color: #334155 !important;
}
.notification-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 1px solid #f1f5f9 !important;
  background-color: #ffffff;
}
.notification-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.06) !important;
}
.unread-card {
  background-color: #f8fafc;
}
.alert-card {
  border-left: 4px solid #f59e0b !important;
}
.unread-card:not(.alert-card) {
  border-left: 4px solid #0d6efd !important;
}
.notification-icon-wrapper {
  transition: transform 0.2s ease;
}
.notification-card:hover .notification-icon-wrapper {
  transform: scale(1.08);
}
.shadow-xs {
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
</style>
