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
      return state.notifications.filter(n => n.read_at === null).length;
    },
    
    // Convert Laravel's notification structure to the frontend format
    formattedNotifications: (state) => {
      return state.notifications.map(n => ({
        id: n.id,
        title: n.data.title || 'Notification',
        message: n.data.message || '',
        type: n.data.type || 'Common/General',
        action_type: n.data.action_type || null,
        action_payload: n.data.action_payload || null,
        icon: n.data.icon || 'bi-info-circle-fill',
        colorClass: n.data.colorClass || 'text-primary',
        bgClass: n.data.bgClass || 'bg-primary-subtle',
        time: new Date(n.created_at).toLocaleString(),
        isRead: n.read_at !== null
      }));
    }
  },

  actions: {
    async fetchNotifications() {
      this.isLoading = true;
      try {
        const response = await api.get('/notifications');
        this.notifications = response.data.data;
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
