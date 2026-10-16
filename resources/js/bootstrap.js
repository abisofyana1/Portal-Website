import axios from 'axios';
import Swal from 'sweetalert2';
import { MOCK_USERS_DATA, MOCK_UNITS, MOCK_NEWS, MOCK_SETTINGS, MOCK_DASHBOARD_STATS } from './mockData';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Helper to check if running in standalone mock mode (GitHub Pages or no backend)
export const isMockSession = () => {
    return !!localStorage.getItem('mockSessionUser');
};

export const getMockUser = () => {
    try {
        const data = localStorage.getItem('mockSessionUser');
        return data ? JSON.parse(data) : null;
    } catch {
        return null;
    }
};

export const setMockUser = (user) => {
    localStorage.setItem('mockSessionUser', JSON.stringify(user));
};

export const clearMockSession = () => {
    localStorage.removeItem('mockSessionUser');
};

// Intercept requests in Demo / Standalone Mode or Fallback
window.axios.interceptors.request.use(async (config) => {
    const url = config.url || '';
    const method = (config.method || 'get').toLowerCase();

    // 1. Standalone Login Interception
    if (url.includes('/api/login') && method === 'post') {
        const body = typeof config.data === 'string' ? JSON.parse(config.data) : (config.data || {});
        const found = MOCK_USERS_DATA.find(u => 
            (u.username === body.username || u.employee.email === body.username || u.employee.npp === body.username) &&
            u.password === body.password
        );

        if (found) {
            setMockUser(found);
            config.adapter = async () => ({
                data: {
                    message: 'Login successful (Demo Mode)',
                    redirect: '/home',
                    user: found
                },
                status: 200,
                statusText: 'OK',
                headers: {},
                config
            });
            return config;
        }
    }

    // 2. Mock Session active -> Mock other API endpoints
    const activeMock = getMockUser();
    if (activeMock) {
        if (url.includes('/api/user') && !url.includes('/api/users')) {
            config.adapter = async () => ({
                data: activeMock,
                status: 200,
                statusText: 'OK',
                headers: {},
                config
            });
            return config;
        }

        if (url.includes('/api/logout')) {
            clearMockSession();
            config.adapter = async () => ({
                data: { message: 'Logged out' },
                status: 200,
                statusText: 'OK',
                headers: {},
                config
            });
            return config;
        }

        if (url.includes('/api/units')) {
            config.adapter = async () => ({
                data: MOCK_UNITS,
                status: 200,
                statusText: 'OK',
                headers: {},
                config
            });
            return config;
        }

        if (url.includes('/api/portal-settings')) {
            config.adapter = async () => ({
                data: MOCK_SETTINGS,
                status: 200,
                statusText: 'OK',
                headers: {},
                config
            });
            return config;
        }

        if (url.includes('/api/users')) {
            config.adapter = async () => ({
                data: {
                    data: MOCK_USERS_DATA,
                    current_page: 1,
                    last_page: 1,
                    total: MOCK_USERS_DATA.length,
                    per_page: 20
                },
                status: 200,
                statusText: 'OK',
                headers: {},
                config
            });
            return config;
        }

        if (url.includes('/api/news')) {
            config.adapter = async () => ({
                data: {
                    data: MOCK_NEWS,
                    current_page: 1,
                    last_page: 1,
                    total: MOCK_NEWS.length,
                    per_page: 20
                },
                status: 200,
                statusText: 'OK',
                headers: {},
                config
            });
            return config;
        }

        if (url.includes('/api/dashboard/stats')) {
            config.adapter = async () => ({
                data: MOCK_DASHBOARD_STATS,
                status: 200,
                statusText: 'OK',
                headers: {},
                config
            });
            return config;
        }

        if (url.includes('/api/dashboard/track-login') || url.includes('/api/dashboard/track-click')) {
            config.adapter = async () => ({
                data: { success: true },
                status: 200,
                statusText: 'OK',
                headers: {},
                config
            });
            return config;
        }
    }

    // 3. Fallback for Portal Settings on Login screen when not logged in
    if (url.includes('/api/portal-settings')) {
        config.adapter = async () => ({
            data: MOCK_SETTINGS,
            status: 200,
            statusText: 'OK',
            headers: {},
            config
        });
        return config;
    }

    return config;
});

let isAlertShowing = false;

window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            if (isMockSession()) {
                return Promise.resolve({ data: getMockUser() });
            }
            if (window.location.pathname !== '/' && window.location.pathname !== '/login') {
                if (!isAlertShowing) {
                    isAlertShowing = true;
                    Swal.fire({
                        title: 'Sesi Berakhir!',
                        text: 'Sesi Anda telah berakhir atau akun Anda telah login di browser lain.',
                        icon: 'warning',
                        confirmButtonText: 'Login Kembali',
                        confirmButtonColor: '#3085d6',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        customClass: {
                            popup: 'swal2-popup-small',
                            icon: 'swal2-icon-small',
                            confirmButton: 'swal2-btn-small'
                        }
                    }).then(() => {
                        window.location.href = './';
                    });
                }
            }
        }
        return Promise.reject(error);
    }
);
