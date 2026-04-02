<template>
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-title">📋 Data Tagihan</div>
      <FlashMessage :message="error" type="danger" />
      <form @submit.prevent="handleLogin">
        <div class="form-group mb-3">
          <label class="form-label">Username</label>
          <input v-model="form.username" class="form-control" type="text" autocomplete="username" required />
        </div>
        <div class="form-group mb-3">
          <label class="form-label">Password</label>
          <input v-model="form.password" class="form-control" type="password" autocomplete="current-password" required />
        </div>
        <button class="btn btn-primary w-full" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          <span v-else>Login</span>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import FlashMessage from '@/components/common/FlashMessage.vue'

const auth    = useAuthStore()
const router  = useRouter()
const loading = ref(false)
const error   = ref('')
const form    = ref({ username: '', password: '' })

async function handleLogin() {
  loading.value = true
  error.value   = ''
  try {
    await auth.login(form.value.username, form.value.password)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message || 'Login gagal'
  } finally {
    loading.value = false
  }
}
</script>
