import './bootstrap';
import '../css/app.css';

import '@fortawesome/fontawesome-free/css/all.min.css';

import { createApp } from 'vue';
import router from './router';
import App from './App.vue';

createApp(App)
    .use(router)
    .mount('#app');
