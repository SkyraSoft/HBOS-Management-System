import { defineStore } from 'pinia';
import api from '../api';
import { openTenantDatabase, closeTenantDatabase } from '../services/localDb';
import { useCustomersStore } from './customers';
import { useExpensesStore } from './expenses';
import { useKhataStore } from './khata';
import { useNotificationStore } from './notifications';
import { usePurchasesStore } from './purchases';
import { useRetailStore } from './retail';
import { useSuppliersStore } from './suppliers';

async function resetSensitiveStores() {
    // 1. customers store
    try {
        const store = useCustomersStore();
        if (typeof store.$reset === 'function') {
            store.$reset();
        } else {
            store.customers = [];
        }
    } catch (e) {
        console.warn('[HBOS Session] Failed resetting customers store:', e);
    }

    // 2. expenses store
    try {
        const store = useExpensesStore();
        if (typeof store.$reset === 'function') {
            store.$reset();
        } else {
            store.expenses = [];
            store.recurringExpenses = [];
            store.categories = [];
        }
    } catch (e) {
        console.warn('[HBOS Session] Failed resetting expenses store:', e);
    }

    // 3. khata store
    try {
        const store = useKhataStore();
        if (typeof store.$reset === 'function') {
            store.$reset();
        } else {
            store.transactions = [];
        }
    } catch (e) {
        console.warn('[HBOS Session] Failed resetting khata store:', e);
    }

    // 4. notifications store (options store with native $reset)
    try {
        const store = useNotificationStore();
        if (typeof store.$reset === 'function') {
            store.$reset();
        } else {
            store.notifications = [];
            store.error = null;
            store.isLoading = false;
        }
    } catch (e) {
        console.warn('[HBOS Session] Failed resetting notifications store:', e);
    }

    // 5. purchases store
    try {
        const store = usePurchasesStore();
        if (typeof store.$reset === 'function') {
            store.$reset();
        } else {
            store.purchases = [];
        }
    } catch (e) {
        console.warn('[HBOS Session] Failed resetting purchases store:', e);
    }

    // 6. retail store
    try {
        const store = useRetailStore();
        if (typeof store.$reset === 'function') {
            store.$reset();
        } else {
            store.products = [];
            store.categories = [];
            store.subcategories = [];
            store.brands = [];
            store.cart = [];
            store.sales = [];
            store.cartDiscount = 0;
        }
    } catch (e) {
        console.warn('[HBOS Session] Failed resetting retail store:', e);
    }

    // 7. suppliers store
    try {
        const store = useSuppliersStore();
        if (typeof store.$reset === 'function') {
            store.$reset();
        } else {
            store.suppliers = [];
        }
    } catch (e) {
        console.warn('[HBOS Session] Failed resetting suppliers store:', e);
    }

    // 8. counter store: non-sensitive demonstration data, no action required

    // 9. navigation store: session reset awaited to guarantee atomic teardown without static circular import
    try {
        const { useNavigationStore } = await import('./navigation');
        const store = useNavigationStore();
        if (typeof store.reset === 'function') {
            store.reset();
        }
    } catch (e) {
        console.warn('[HBOS Session] Failed resetting navigation store:', e);
    }
}

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
                await this.clearAuthData();
            }
        },

        async logoutAllDevices() {
            try {
                await api.post('/auth/logout-all-devices');
            } catch (error) {
                console.error('Logout all devices failed:', error);
            } finally {
                await this.clearAuthData();
            }
        },
        
        setActiveBusinessId(businessId) {
            this.activeBusinessId = businessId;
            if (businessId) {
                localStorage.setItem('hbos_business_id', String(businessId));
                openTenantDatabase(businessId);
            } else {
                localStorage.removeItem('hbos_business_id');
                closeTenantDatabase();
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
                openTenantDatabase(resolvedBizId);
            }
        },
        
        setActiveBranch(branchId) {
            if (this.user) {
                this.user.branch_id = branchId;
                localStorage.setItem('hbos_user', JSON.stringify(this.user));
            }
            if (branchId && branchId !== 'all') {
                api.defaults.headers.common['X-Branch-ID'] = String(branchId);
            } else {
                delete api.defaults.headers.common['X-Branch-ID'];
            }
            // Reset branch-specific retail state to prevent cross-branch cart/stock leaks
            try {
                const retailStore = useRetailStore();
                retailStore.cart = [];
                retailStore.cartDiscount = 0;
            } catch (e) {
                // ignore
            }
        },

        setViewAs(active, role = null, branchId = null) {
            if (active && role) {
                api.defaults.headers.common['X-View-As-Role'] = role;
                api.defaults.headers.common['X-View-As-Active'] = 'true';
                if (branchId) {
                    api.defaults.headers.common['X-Branch-ID'] = String(branchId);
                }
            } else {
                delete api.defaults.headers.common['X-View-As-Role'];
                delete api.defaults.headers.common['X-View-As-Active'];
            }
        },
        
        async clearAuthData(broadcast = true) {
            closeTenantDatabase();
            await resetSensitiveStores();
            this.user = null;
            this.token = null;
            this.activeBusinessId = null;
            localStorage.removeItem('hbos_user');
            localStorage.removeItem('hbos_token');
            localStorage.removeItem('hbos_business_id');
            if (api.defaults && api.defaults.headers) {
                delete api.defaults.headers.common['Authorization'];
                delete api.defaults.headers.common['X-Business-ID'];
                delete api.defaults.headers.common['X-Business-Id'];
                delete api.defaults.headers.common['X-Branch-ID'];
                delete api.defaults.headers.common['X-View-As-Role'];
                delete api.defaults.headers.common['X-View-As-Active'];
            }
            if (broadcast && authChannel) {
                try {
                    authChannel.postMessage({ type: 'LOGOUT' });
                } catch (e) {
                    // ignore broadcast error
                }
            }
        }
    }
});

// Cross-tab authentication & session revocation channel (REQ-0007)
let authChannel = null;
if (typeof window !== 'undefined' && typeof BroadcastChannel !== 'undefined') {
    try {
        authChannel = new BroadcastChannel('hbos_auth_channel');
        authChannel.onmessage = async (event) => {
            if (event.data?.type === 'LOGOUT') {
                const store = useAuthStore();
                if (store.token || store.user) {
                    await store.clearAuthData(false);
                    if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
                        window.location.href = '/login';
                    }
                }
            }
        };
    } catch (e) {
        console.warn('[HBOS Auth] BroadcastChannel initialization failed:', e);
    }
}

// Storage event listener fallback for cross-tab session invalidation
if (typeof window !== 'undefined') {
    window.addEventListener('storage', async (event) => {
        if (event.key === 'hbos_token' || event.key === null) {
            if (!localStorage.getItem('hbos_token')) {
                const store = useAuthStore();
                if (store.token || store.user) {
                    await store.clearAuthData(false);
                    if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
                        window.location.href = '/login';
                    }
                }
            }
        }
    });
}

