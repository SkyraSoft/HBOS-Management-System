<script setup>
import { ref, computed, onMounted } from 'vue';
import { useNotificationStore } from '../stores/notifications';
import ActionModalFactory from '../components/ActionModalFactory.vue';

const notificationStore = useNotificationStore();

const selectedActionNotification = ref(null);

const activeTab = ref('All');
const tabs = ['All', 'Common/General', 'Important/Soon', 'Emergency/Critical'];

onMounted(() => {
  if (notificationStore.notifications.length === 0) {
    notificationStore.fetchNotifications();
  }
});

const filteredNotifications = computed(() => {
  const notifs = notificationStore.formattedNotifications;
  if (activeTab.value === 'All') return notifs;
  return notifs.filter(n => n.type === activeTab.value);
});

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
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="page-title m-0">Notifications</h1>
        <p class="text-muted mb-0 mt-1">Stay updated with system alerts and activities.</p>
      </div>
      <button class="btn btn-primary rounded-pill px-4 fw-semibold" @click="markAllAsRead">
        <i class="bi bi-check2-all me-1"></i> Mark all as read
      </button>
    </div>

    <!-- Filters / Tabs -->
    <div class="nav nav-pills mb-4 gap-2 border-bottom pb-3">
      <button 
        v-for="tab in tabs" 
        :key="tab"
        class="nav-link rounded-pill px-4 fw-medium"
        :class="activeTab === tab ? 'active shadow-sm' : 'text-secondary bg-light'"
        @click="activeTab = tab"
      >
        {{ tab }}
      </button>
    </div>

    <!-- Notifications List -->
    <div class="row g-3">
      <div v-for="notification in filteredNotifications" :key="notification.id" class="col-12">
        <div 
          class="card border-0 shadow-sm rounded-4 overflow-hidden notification-card"
          :class="{ 'unread-card': !notification.isRead }"
        >
          <div class="card-body p-4">
            <div class="d-flex gap-4 align-items-start">
              
              <!-- Icon -->
              <div class="notification-icon-wrapper rounded-circle d-flex align-items-center justify-content-center" :class="notification.bgClass" style="width: 50px; height: 50px; flex-shrink: 0;">
                <i class="bi fs-4" :class="[notification.icon, notification.colorClass]"></i>
              </div>

              <!-- Content -->
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 fw-bold text-dark">{{ notification.title }}</h5>
                    <span 
                      class="badge rounded-pill fw-medium" 
                      :class="notification.type === 'Emergency/Critical' ? 'bg-danger' : (notification.type === 'Important/Soon' ? 'bg-warning text-dark' : 'bg-primary')"
                      style="font-size: 11px;"
                    >
                      {{ notification.type }}
                    </span>
                  </div>
                  <small class="text-muted fw-medium">{{ notification.time }}</small>
                </div>
                <p class="mb-0 text-secondary" style="font-size: 15px;">{{ notification.message }}</p>

                <!-- Actions -->
                <div class="mt-3 d-flex gap-2">
                  <button v-if="notification.action_type" class="btn btn-sm btn-primary rounded-pill px-3 fw-medium" @click="selectedActionNotification = notification">
                    Take Action
                  </button>
                  <button v-if="!notification.isRead" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-medium" @click="markAsRead(notification.id)">
                    Mark as Read
                  </button>
                  <button class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-medium" @click="dismissNotification(notification.id)">
                    Dismiss
                  </button>
                </div>
              </div>

              <!-- Unread Dot -->
              <div v-if="!notification.isRead" class="d-flex align-items-center mt-2">
                <span class="badge bg-primary rounded-circle p-2 shadow-sm" title="Unread"></span>
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
          <p class="text-muted mb-0">You don't have any {{ activeTab !== 'All' ? activeTab : '' }} alerts right now.</p>
        </div>
      </div>
    </div>

    <!-- Action Modal -->
    <ActionModalFactory 
      :notification="selectedActionNotification" 
      @close="selectedActionNotification = null" 
    />
  </div>
</template>

<style scoped>
.page-title {
  color: #1e293b;
  font-size: 32px;
  font-weight: 800;
  letter-spacing: -0.5px;
}
.nav-pills .nav-link {
  cursor: pointer;
  transition: all 0.2s ease;
  border: none !important;
  outline: none !important;
  box-shadow: none !important;
}
.nav-pills .nav-link:not(.active):hover {
  background-color: #e2e8f0 !important;
  color: #334155 !important;
}
.nav-pills .nav-link.active {
  border: none !important;
  outline: none !important;
}
.notification-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 1px solid #f1f5f9 !important;
}
.notification-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05) !important;
}
.unread-card {
  border-left: 4px solid #0d6efd !important;
  background-color: #f8fafc;
}
.notification-icon-wrapper {
  transition: transform 0.2s ease;
}
.notification-card:hover .notification-icon-wrapper {
  transform: scale(1.1);
}
</style>
