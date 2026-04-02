<template>
  <div class="topbar">
    <span class="topbar-title">📋 Data Tagihan</span>
    <nav class="flex gap-2 items-center">
      <RouterLink to="/"        class="nav-tab" :class="{ active: route.path === '/' }">Data Tagihan</RouterLink>
      <RouterLink v-if="auth.isAdmin" to="/bulk"    class="nav-tab" :class="{ active: route.path === '/bulk' }">Input Massal</RouterLink>
      <RouterLink v-if="auth.isAdmin" to="/setting" class="nav-tab" :class="{ active: route.path === '/setting' }">Pengaturan</RouterLink>
    </nav>
    <div class="topbar-user">
      <span class="badge" :class="auth.isAdmin ? 'badge-admin' : 'badge-user'">{{ auth.isAdmin ? 'admin' : 'user' }}</span>
      <span>{{ auth.user }}</span>
      <button class="btn btn-ghost btn-sm" @click="handleLogout">Logout</button>
    </div>
  </div>
</template>

<script setup>
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const route  = useRoute()
const router = useRouter()

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
