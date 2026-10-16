<script setup>
import { ref } from 'vue';

const username = ref('');
const password = ref('');
const errorMessage = ref('');
const isCapsLockOn = ref(false);
const showPassword = ref(false);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const checkCapsLock = (event) => {
    if (event.getModifierState) {
        isCapsLockOn.value = event.getModifierState('CapsLock');
    }
};

import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();

import { onMounted } from 'vue';

const sessionMessage = ref('');
const isCheckingSession = ref(true); // Default to true to hide flicker

const portalBackground = ref('');

onMounted(async () => {
    const logoutReason = localStorage.getItem('logoutReason');
    
    // 1. Check if user is already logged in (Fix for Browser Back Button)
    // ONLY redirect if there is no pending logout reason/message
    if (!logoutReason && localStorage.getItem('selectedRole')) {
        try {
            const check = await axios.get('/api/user');
            if (check.data) {
                if (check.data.last_login) {
                    localStorage.setItem('loginTimestamp', check.data.last_login);
                }
                console.log("Active session detected, redirecting to Home...");
                router.replace('/home');
                return;
            }
        } catch (e) {
            localStorage.clear();
        }
    }

    isCheckingSession.value = false; // Show the login form after check

    // 2. Handle Logout Messages
    if (logoutReason) {
        if (logoutReason === 'concurrent') {
            sessionMessage.value = "Sesi Anda telah berakhir karena login di browser lain";
        } else if (logoutReason === 'timeout') {
            sessionMessage.value = "Session Habis Silahkan Login kembali";
        }
        localStorage.removeItem('logoutReason');
    }
    
    // Cleanup old flag if exists
    localStorage.removeItem('sessionExpired');
    // ... remaining of onMounted ...
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('preview')) {
        router.replace({ name: 'Home', query: { preview: urlParams.get('preview') } });
    }

    // Fetch portal settings for background (with Cache)
    const CACHE_KEY = 'portalSettingsCache';
    const cached = localStorage.getItem(CACHE_KEY);
    if (cached) {
        try {
            const { data } = JSON.parse(cached);
            const bgSetting = data.find(s => s.section === 'background');
            if (bgSetting && bgSetting.image_name) {
                portalBackground.value = `/images/${bgSetting.image_name}`;
            }
        } catch (e) { console.error("Cache parse error", e); }
    }

    try {
        const response = await axios.get('/api/portal-settings');
        const bgSetting = response.data.find(s => s.section === 'background');
        if (bgSetting && bgSetting.image_name) {
            const bgUrl = `/images/${bgSetting.image_name}`;
            portalBackground.value = bgUrl;
        }
        localStorage.setItem(CACHE_KEY, JSON.stringify({
            data: response.data,
            timestamp: Date.now()
        }));
    } catch (e) {
        console.error("Failed to fetch login background", e);
    }
});

const login = async () => {
    errorMessage.value = '';
    sessionMessage.value = ''; // Clear session message on attempt
    try {
        const response = await axios.post('/api/login', {
            username: username.value,
            password: password.value
        });
        
        console.log('Login successful', response.data);
        
        if (response.data.redirect) {
             console.log('Redirecting to:', response.data.redirect);
             
             // Sync the login timestamp from server for session lock logic
             if (response.data.user && response.data.user.last_login) {
                 localStorage.setItem('loginTimestamp', response.data.user.last_login);
             }

             // Clear navigation persistence on fresh login
             localStorage.removeItem('lastMenu');
             localStorage.removeItem('lastSubmenu');
             localStorage.removeItem('selectedRole');
             
             router.push(response.data.redirect);
        }
        
    } catch (error) {
        // Handle 'First Login Wins' - if server returns 423 (Locked) or specific message
        if (error.response && (error.response.status === 423 || error.response.status === 403)) {
            errorMessage.value = error.response.data.message || 'User Anda Telah Login diBrowser Lain, Silahkan Logout Terlebih Dahulu!!';
        } else if (error.response && error.response.data && error.response.data.message) {
            errorMessage.value = error.response.data.message;
        } else {
            errorMessage.value = 'Login failed. Please try again.';
        }
    }
};
</script>

<template>
    <div v-if="!isCheckingSession" class="min-h-screen flex items-center justify-center bg-gray-100" 
         :style="{ 
            backgroundImage: portalBackground ? `url(${portalBackground})` : 'url(/images/background.png)', 
            backgroundSize: 'cover',
            backgroundPosition: 'center'
         }">
        <!-- Login Form Content ... -->
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md bg-opacity-90">
            <div class="text-center mb-6">
                <img :src="'/images/logo.png'" alt="Logo" class="mx-auto h-12 mb-2">
                <h1 class="text-2xl font-bold text-gray-800">PortalCXC Login</h1>
            </div>
            <div v-if="errorMessage" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ errorMessage }}</span>
            </div>
            
            <div v-if="sessionMessage" class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative text-center font-bold" role="alert">
                <i class="fas fa-clock mr-2"></i> <span class="block sm:inline">{{ sessionMessage }}</span>
            </div>
            
            <form @submit.prevent="login">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        Username / NPP / Email
                    </label>
                    <input v-model="username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="username" type="text" placeholder="Username / NPP / Email">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Password
                    </label>
                    <div class="relative">
                        <input v-model="password" @keyup="checkCapsLock" @mousedown="checkCapsLock" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline pr-10" id="password" :type="showPassword ? 'text' : 'password'" placeholder="******************">
                        <button type="button" @click="togglePasswordVisibility" tabindex="-1" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <i :class="['fas', showPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                        </button>
                    </div>
                    <div v-if="isCapsLockOn" class="text-orange-500 text-xs font-bold mt-1">
                        <i class="fas fa-exclamation-triangle mr-1"></i> CapsLock Hidup
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full" type="submit">
                        Sign In
                    </button>
                </div>
            </form>
            <div class="mt-4 text-center">
                <p class="text-xs text-gray-500">
                    &copy; 2026 PortalCXC. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</template>
