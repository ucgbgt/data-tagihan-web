import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || '')
  const user  = ref(localStorage.getItem('user')  || '')
  const role  = ref(localStorage.getItem('role')  || '')

  const isLoggedIn = computed(() => !!token.value)
  const isAdmin    = computed(() => role.value === 'admin')

  async function login(username, password) {
    const res = await api.post('/auth/login', { username, password })
    token.value = res.data.data.token
    user.value  = res.data.data.user
    role.value  = res.data.data.role
    localStorage.setItem('token', token.value)
    localStorage.setItem('user',  user.value)
    localStorage.setItem('role',  role.value)
  }

  async function logout() {
    try { await api.post('/auth/logout') } catch {}
    token.value = ''
    user.value  = ''
    role.value  = ''
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('role')
  }

  return { token, user, role, isLoggedIn, isAdmin, login, logout }
})
