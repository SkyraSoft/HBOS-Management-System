import { defineStore } from 'pinia';
import api from '../api';

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    isLoading: false,
    error: null,
  }),

  getters: {
    unreadCount: (state) => {
      return state.notifications.filter(n => !n.read_at).length;
    },

    alertCount: (state) => {
      return state.notifications.filter(n => {
        const type = n.data?.type || n.type;
        return (type === 'Alerts & Attention' || type === 'Important/Soon' || type === 'Emergency/Critical') && !n.read_at;
      }).length;
    },
    
    // Convert Laravel's notification structure or flat sandbox structure to frontend format
    formattedNotifications: (state) => {
      return (state.notifications || []).map(n => {
        const d = n.data || {};
        return {
          id: n.id,
          title: d.title || n.title || 'Notification',
          message: d.message || n.message || '',
          type: d.type || n.type || 'Common / Activity',
          severity: d.severity || n.severity || 'info',
          action_type: d.action_type || n.action_type || null,
          action_payload: d.action_payload || n.action_payload || null,
          action_label: d.action_label || n.action_label || 'Take Action',
          icon: d.icon || n.icon || 'bi-info-circle-fill',
          colorClass: d.colorClass || n.colorClass || 'text-primary',
          bgClass: d.bgClass || n.bgClass || 'bg-primary-subtle',
          time: n.created_at ? new Date(n.created_at).toLocaleString() : 'Just now',
          isRead: n.read_at !== null
        };
      });
    }
  },

  actions: {
    async fetchNotifications() {
      this.isLoading = true;
      try {
        const response = await api.get('/notifications', { params: { include_alerts: true } });
        const list = Array.isArray(response.data) ? response.data : (response.data?.data || []);
        this.notifications = list;
      } catch (error) {
        console.error('Failed to fetch notifications:', error);
        this.error = 'Failed to load notifications';
      } finally {
        this.isLoading = false;
      }
    },

    async markAsRead(id) {
      try {
        await api.put(`/notifications/${id}/mark-read`);
        // Update local state
        const notification = this.notifications.find(n => n.id === id);
        if (notification) {
          notification.read_at = new Date().toISOString();
        }
      } catch (error) {
        console.error('Failed to mark notification as read:', error);
      }
    },

    async markAllAsRead() {
      try {
        await api.put('/notifications/mark-all-read');
        // Update local state
        this.notifications.forEach(n => {
          if (n.read_at === null) n.read_at = new Date().toISOString();
        });
      } catch (error) {
        console.error('Failed to mark all as read:', error);
      }
    },

    async dismissNotification(id) {
      try {
        await api.delete(`/notifications/${id}`);
        // Remove from local state
        this.notifications = this.notifications.filter(n => n.id !== id);
      } catch (error) {
        console.error('Failed to dismiss notification:', error);
      }
    }
  }
});
