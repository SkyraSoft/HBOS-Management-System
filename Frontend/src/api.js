import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '/api/v1',
    headers: {
        'Accept': 'application/json'
    }
});

// Request interceptor to attach bearer token and active business context
api.interceptors.request.use(config => {
    const token = localStorage.getItem('hbos_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    const businessId = localStorage.getItem('hbos_business_id');
    if (businessId) {
        config.headers['X-Business-ID'] = businessId;
    }
    return config;
}, error => {
    return Promise.reject(error);
});

// Response interceptor to handle 401 Unauthorized (session expired)
api.interceptors.response.use(response => {
    return response;
}, error => {
    if (error.response && error.response.status === 401) {
        localStorage.removeItem('hbos_token');
        localStorage.removeItem('hbos_user');
        localStorage.removeItem('hbos_business_id');
        
        // Redirect to login only on actual 401 unauthenticated session expiry
        if (window.location.pathname !== '/login') {
            window.location.href = '/login';
        }
    }
    // HTTP 403 Forbidden is a permission denial, NOT a session expiry.
    // Preserves authentication token and allows UI to display access-denied state.
    return Promise.reject(error);
});

export default api;
