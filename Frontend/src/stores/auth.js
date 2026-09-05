import { defineStore } from 'pinia';
import api from '../api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('hbos_user')) || null,
        token: localStorage.getItem('hbos_token') || null,
    }),
    
    getters: {
        isAuthenticated: (state) => !!state.token,
    },
    
    actions: {
        async login(credentials) {
            try {
                const response = await api.post('/auth/login', credentials);
                this.setAuthData(response.data.user, response.data.access_token || response.data.token);
                return true;
            } catch (error) {
                console.error('Login failed:', error);
                throw error;
            }
        },
        
        async register(userData) {
            try {
                const response = await api.post('/auth/register', userData);
                this.setAuthData(response.data.user, response.data.access_token || response.data.token);
                return true;
            } catch (error) {
                console.error('Registration failed:', error);
                throw error;
            }
        },
        
        async logout() {
            try {
                await api.post('/auth/logout');
            } catch (error) {
                console.error('Logout failed:', error);
            } finally {
                this.clearAuthData();
            }
        },
        
        setAuthData(user, token) {
            this.user = user;
            this.token = token;
            localStorage.setItem('hbos_user', JSON.stringify(user));
            localStorage.setItem('hbos_token', token);
        },
        
        clearAuthData() {
            this.user = null;
            this.token = null;
            localStorage.removeItem('hbos_user');
            localStorage.removeItem('hbos_token');
        }
    }
});
