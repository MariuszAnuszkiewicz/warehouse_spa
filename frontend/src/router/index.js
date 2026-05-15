import { createRouter, createWebHistory } from 'vue-router';
import Login from '@/components/auth/Login';
import Register from '@/components/auth/Register';
import Products from '@/components/products/Products';
import Orders from '@/components/orders/Orders';
import CreateOrder from '@/components/orders/CreateOrder';

const routes = [
    { path: '/login', component: Login },
    { path: '/register', component: Register },
    { path: '/products', component: Products },
    { path: '/orders', component: Orders, meta: { requiresAuth: true } },
    { path: '/orders/create', component: CreateOrder },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
