<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-logo">
        <div class="login-logo-icon">📋</div>
        <div class="login-title">Data Tagihan</div>
        <div class="login-sub">Masuk untuk melanjutkan</div>
      </div>

      <div v-if="error" class="alert alert-danger mb-3">
        <span>⚠</span> {{ error }}
      </div>

      <form @submit.prevent="handleLogin">
        <div class="form-group mb-3">
          <label class="form-label">Username</label>
          <input
            v-model="form.username"
            class="form-control"
            type="text"
            placeholder="Masukkan username"
            autocomplete="username"
            required
            autofocus
          />
        </div>
        <div class="form-group mb-4">
          <label class="form-label">Password</label>
          <input
            v-model="form.password"
            class="form-control"
            type="password"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          />
        </div>
        <button class="btn btn-primary w-full" style="height:38px;font-size:.85rem" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          <span v-else>Masuk</span>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

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
    error.value = e.response?.data?.message || 'Login gagal, periksa username dan password'
  } finally {
    loading.value = false
  }
}
</script>
