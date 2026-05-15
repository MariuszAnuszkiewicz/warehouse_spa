import 'bootstrap/dist/css/bootstrap.min.css';
import 'vue-multiselect/dist/vue-multiselect.css';
import 'vue3-toastify/dist/index.css';
import { createApp } from 'vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faEye, faX, faTrashArrowUp } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Vue3Toastify, { toast } from 'vue3-toastify';
import App from '@/App.vue';
import router from '@/router';

library.add(faEye);
library.add(faX);
library.add(faTrashArrowUp);

const app = createApp(App)

app.component('font-awesome-icon', FontAwesomeIcon)
app.use(router)
app.use(Vue3Toastify, {
    autoClose: 3000,   // global options
    position: "top-right"
})
app.provide('apiDomain', import.meta.env.VITE_API_URL)

app.mount('#app')

export { toast }