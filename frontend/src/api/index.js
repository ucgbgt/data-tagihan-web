import axios from 'axios'

const api = axios.create({ baseURL: import.meta.env.VITE_API_URL || '/api' })

api.interceptors.request.use(config => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  res => res,
  err => {
    const status  = err.response?.status
    const message = err.response?.data?.message ?? ''
    const url     = err.config?.url ?? ''

    // Hanya logout jika:
    // 1. Status 401 DAN
    // 2. Bukan dari endpoint login (itu error kredensial, bukan token) DAN
    // 3. Pesan dari server memang terkait token / unauthorized
    const isLoginEndpoint = url.includes('/auth/login')
    const isTokenError    = status === 401 && !isLoginEndpoint

    if (isTokenError) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      localStorage.removeItem('role')
      // Gunakan location replace agar tidak bisa back ke halaman sebelumnya
      window.location.replace(import.meta.env.BASE_URL + 'login')
    }

    return Promise.reject(err)
  }
)

export default api
