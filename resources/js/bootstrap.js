import axios from 'axios';
import Swal from 'sweetalert2';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

let isAlertShowing = false;

window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            // Check if we are already on login page or home (which shows login if unauth)
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
                        window.location.href = '/';
                    });
                }
            }
        }
        return Promise.reject(error);
    }
);

