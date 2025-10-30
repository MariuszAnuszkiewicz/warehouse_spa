<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-5">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">MyApp</a>
      <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
          aria-controls="navbarNav"
          aria-expanded="true"
          aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
        <ul class="navbar-nav">
          <RouterLink to="/products" class="nav-item nav-link">Products</RouterLink>
          <RouterLink to="/orders" class="nav-item nav-link">Orders</RouterLink>
          <RouterLink to="/orders/create" class="nav-item nav-link">Create Orders</RouterLink>
          <li v-if="!authService.loginStatus.value" class="nav-item">
            <RouterLink to="/login" v-if="!authService.switchLink.value" class="nav-item nav-link">Login</RouterLink>
            <RouterLink to="/register" v-else-if="authService.switchLink.value" class="nav-item nav-link">Register</RouterLink>
          </li>
        </ul>
        <div v-if="authService.loginStatus.value">
          <form @submit.prevent="authService.logout" class="d-flex">
            <div>
              <button type="submit" class="btn btn-outline-light">Logout</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { onMounted } from 'vue';
import authService from '@/services/authService';

onMounted(() => {
  authService.checkLoginStatus();
  authService.checkIsLoginUrl();
});
</script>