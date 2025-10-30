<template>
  <div class="col-8 mx-auto p-2">
    <div class="col-8 text-center mx-auto">
      <div class="col-md-6 mx-auto">
        <h2>Login</h2>
        <form @submit.prevent="onSubmit">
          <div class="col-6 mb-3 mx-auto">
            <label for="email" class="form-label">Email</label>
            <input type="text" class="form-control" v-model="authService.$forms.loginForm.email" id="email" required />
          </div>
          <div class="col-6 mb-3 mx-auto">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" v-model="authService.$forms.loginForm.password" id="password" required />
          </div>
          <div class="col-6 mb-3 mx-auto">
            <button type="submit" class="btn btn-success">Login</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter, useRoute } from 'vue-router';
import { toast } from 'vue3-toastify';
import authService from '@/services/authService';

const router = useRouter();
const route = useRoute()

function redirectWithRefreshPage(url) {
  router.replace({ path: `/${url}` }).then(() => {
    window.location.reload();
  })
}

async function onSubmit() {
  const res = await authService.handleLogin();

  if (res) {
    toast.success('Login successfully!');

    setTimeout(() => {
      redirectWithRefreshPage('orders')
    }, 4000);
  } else {
    toast.error('Login failed.');

    setTimeout(() => {
      if (authService.detect401()) {
        authService.$forms.loginForm.email = "";
        authService.$forms.loginForm.password = "";
      }
    }, 4000);
  }
}
</script>