<template>
  <header class="topbar">
    <div class="topbar-brand">
      <div class="topbar-brand-icon">📋</div>
      Data Tagihan
    </div>

    <nav class="topbar-nav">
      <RouterLink to="/" class="topbar-link" :class="{ active: route.path === '/' }">
        Tagihan
      </RouterLink>
      <RouterLink v-if="auth.isAdmin" to="/bulk" class="topbar-link" :class="{ active: route.path === '/bulk' }">
        Input Massal
      </RouterLink>
      <RouterLink v-if="auth.isAdmin" to="/setting" class="topbar-link" :class="{ active: route.path === '/setting' }">
        Pengaturan
      </RouterLink>
    </nav>

    <div class="topbar-right">
      <div class="user-pill">
        <span class="badge" :class="auth.isAdmin ? 'badge-admin' : 'badge-user'">
          {{ auth.isAdmin ? 'Admin' : 'User' }}
        </span>
        <span style="color:var(--text2);font-weight:500">{{ auth.user }}</span>
      </div>
      <button class="btn btn-ghost btn-sm" @click="handleLogout">Keluar</button>
    </div>
  </header>
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
