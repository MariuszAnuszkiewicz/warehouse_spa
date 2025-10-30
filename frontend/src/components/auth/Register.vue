<template>
  <div class="col-8 mx-auto p-2">
    <div class="col-8 text-center mx-auto">
      <div class="col-md-6 mx-auto">
        <h2>Register</h2>
        <form @submit.prevent="onSubmit">
          <div class="col-6 mb-3 mx-auto">
            <label for="email" class="form-label">Email</label>
            <input type="text" class="form-control" v-model="authService.$forms.registerForm.email" id="email" required />
            <span v-for="error in errors.email" class="text-danger">{{ error }}</span>
          </div>
          <div class="col-6 mb-3 mx-auto">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" v-model="authService.$forms.registerForm.password" id="password" required />
            <span v-for="error in errors.password" class="text-danger">{{ error }}</span>
          </div>
          <div class="col-6 mb-3 mx-auto">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" v-model="authService.$forms.registerForm.name" id="name" required />
            <span v-for="error in errors.name" class="text-danger">{{ error }}</span>
          </div>
          <div class="col-6 mb-3 mx-auto">
            <button type="submit" class="btn btn-success">Register</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import { useRouter } from 'vue-router';
import { toast } from 'vue3-toastify';
import authService from '@/services/authService';

const router = useRouter();

const errors = reactive({
  "email": "",
  "password": "",
  "name": ""
});

async function onSubmit() {
  const res = await authService.handleRegister();
  if (res) {
    toast.success('Registered successfully!');

    setTimeout(() => {
      router.push('/login');
    }, 1000);
  }

  errors.email = authService.errors.value.email;
  errors.password = authService.errors.value.password;
  errors.name = authService.errors.value.name;
}
</script>