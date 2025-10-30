import 'bootstrap/dist/css/bootstrap.min.css';
import 'vue-multiselect/dist/vue-multiselect.css';
import { createApp } from 'vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faEye, faX } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { createRouter, createWebHistory } from 'vue-router';
import Vue3Toastify, { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import App from '@/App.vue';
import Login from '@/components/auth/Login';
import Register from '@/components/auth/Register';
import Products from '@/components/products/Products';
import Orders from '@/components/orders/Orders';
import CreateOrder from '@/components/orders/CreateOrder';

library.add(faEye);
library.add(faX);

const routes = [
    { path: '/login', component: Login },
    { path: '/register', component: Register },
    { path: '/products', component: Products },
    { path: '/orders', component: Orders, meta: { requiresAuth: true }},
    { path: '/orders/create', component: CreateOrder },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

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