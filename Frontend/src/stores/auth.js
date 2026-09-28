import { defineStore } from 'pinia';
import api from '../api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('hbos_user')) || null,
        token: localStorage.getItem('hbos_token') || null,
        activeBusinessId: localStorage.getItem('hbos_business_id') || null,
    }),
    
    getters: {
        isAuthenticated: (state) => !!state.token,
    },
    
    actions: {
        async login(credentials) {
            try {
                const response = await api.post('/auth/login', credentials);
                const businessId = response.data.business?.id || response.data.user?.business_id || null;
                this.setAuthData(response.data.user, response.data.access_token || response.data.token, businessId);
                return true;
            } catch (error) {
                console.error('Login failed:', error);
                throw error;
            }
        },
        
        async register(userData) {
            try {
                const response = await api.post('/auth/register', userData);
                const businessId = response.data.business?.id || response.data.user?.business_id || null;
                this.setAuthData(response.data.user, response.data.access_token || response.data.token, businessId);
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
        
        setActiveBusinessId(businessId) {
            this.activeBusinessId = businessId;
            if (businessId) {
                localStorage.setItem('hbos_business_id', String(businessId));
            } else {
                localStorage.removeItem('hbos_business_id');
            }
        },

        setAuthData(user, token, businessId = null) {
            this.user = user;
            this.token = token;
            const resolvedBizId = businessId || user?.business_id || null;
            this.activeBusinessId = resolvedBizId;
            localStorage.setItem('hbos_user', JSON.stringify(user));
            localStorage.setItem('hbos_token', token);
            if (resolvedBizId) {
                localStorage.setItem('hbos_business_id', String(resolvedBizId));
            }
        },
        
        clearAuthData() {
            this.user = null;
            this.token = null;
            this.activeBusinessId = null;
            localStorage.removeItem('hbos_user');
            localStorage.removeItem('hbos_token');
            localStorage.removeItem('hbos_business_id');
        }
    }
});
