import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    component: () => import('@/views/LoginView.vue'),
    meta: { guest: true }
  },
  {
    path: '/',
    component: () => import('@/views/TagihanView.vue'),
    meta: { auth: true }
  },
  {
    path: '/bulk',
    component: () => import('@/views/BulkInputView.vue'),
    meta: { auth: true, admin: true }
  },
  {
    path: '/setting',
    component: () => import('@/views/SettingView.vue'),
    meta: { auth: true, admin: true }
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.auth  && !auth.isLoggedIn) return '/login'
  if (to.meta.admin && !auth.isAdmin)    return '/'
  if (to.meta.guest && auth.isLoggedIn)  return '/'
})

export default router
