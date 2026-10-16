import { createRouter, createWebHistory, createWebHashHistory } from 'vue-router';
import Login from './components/Login.vue';
import Home from './components/Home.vue';

const routes = [
    {
        path: '/',
        name: 'Login',
        component: Login
    },
    {
        path: '/home',
        name: 'Home',
        component: Home
    }
];

// Use Hash history for GitHub Pages static hosting or WebHistory for standard server
const isGitHubPages = typeof window !== 'undefined' && (
    window.location.hostname.endsWith('github.io') || 
    window.location.protocol === 'file:' ||
    window.location.pathname.includes('/Portal-Website')
);

const router = createRouter({
    history: isGitHubPages ? createWebHashHistory() : createWebHistory(),
    routes
});

export default router;
