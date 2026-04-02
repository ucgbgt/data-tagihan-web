<template>
  <TopBar />
  <div class="main-container">
    <FlashMessage :message="flash.msg" :type="flash.type" />

    <!-- Sub-tabs -->
    <div class="flex gap-2" style="border-bottom:1px solid var(--border);margin-bottom:.75rem">
      <button class="nav-tab" :class="{ active: stab === 'users' }" @click="stab = 'users'">👤 Manajemen User</button>
      <button class="nav-tab" :class="{ active: stab === 'logs'  }" @click="stab = 'logs'; loadLogs()">📋 Log Aktivitas</button>
    </div>

    <!-- ══ MANAJEMEN USER ══ -->
    <template v-if="stab === 'users'">
      <!-- Tambah user -->
      <div class="card">
        <div class="card-header" @click="addOpen = !addOpen">
          ➕ Tambah User Baru
          <span style="margin-left:auto;color:var(--muted)">{{ addOpen ? '▲' : '▼' }}</span>
        </div>
        <div v-if="addOpen" class="card-body">
          <form @submit.prevent="addUser" style="max-width:480px">
            <div class="form-group mb-2">
              <label class="form-label">Username (min. 3 karakter)</label>
              <input v-model="newUser.username" class="form-control" minlength="3" required />
            </div>
            <div class="form-group mb-2">
              <label class="form-label">Password (min. 6 karakter)</label>
              <input v-model="newUser.password" class="form-control" type="password" minlength="6" required />
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Konfirmasi Password</label>
              <input v-model="newUser.password_confirm" class="form-control" type="password" required />
            </div>
            <button class="btn btn-primary" :disabled="addLoading">
              <span v-if="addLoading" class="spinner"></span>
              <span v-else>Tambah User</span>
            </button>
          </form>
        </div>
      </div>

      <!-- Daftar user -->
      <div class="card">
        <div class="card-header">Daftar User</div>
        <div v-if="usersLoading" style="padding:2rem;text-align:center"><span class="spinner"></span></div>
        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Username</th>
                <th>Status</th>
                <th>Dibuat oleh</th>
                <th>Dibuat pada</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!users.length">
                <td colspan="6" style="text-align:center;color:var(--muted);padding:2rem">Belum ada user</td>
              </tr>
              <tr v-for="(u, i) in users" :key="u.id">
                <td class="text-muted">{{ i + 1 }}</td>
                <td>{{ u.username }}</td>
                <td>
                  <span class="badge" :class="u.is_active ? 'badge-ok' : 'badge-off'">
                    {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td class="text-muted">{{ u.created_by }}</td>
                <td class="text-muted text-sm">{{ formatDate(u.created_at) }}</td>
                <td>
                  <div class="td-actions">
                    <button class="btn btn-ghost btn-sm" @click="toggleUser(u)">
                      {{ u.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                    <button class="btn btn-warning btn-sm" @click="openReset(u)">Reset Pass</button>
                    <button class="btn btn-danger btn-sm" @click="askDeleteUser(u)">Hapus</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Reset password modal -->
      <div v-if="resetModal.show" class="modal-overlay" @click.self="resetModal.show = false">
        <div class="modal-box" style="max-width:380px">
          <div class="modal-header">Reset Password — {{ resetModal.user?.username }}</div>
          <div class="modal-body">
            <form @submit.prevent="doReset">
              <div class="form-group mb-2">
                <label class="form-label">Password Baru (min. 6 karakter)</label>
                <input v-model="resetForm.password" class="form-control" type="password" minlength="6" required />
              </div>
              <div class="form-group mb-3">
                <label class="form-label">Konfirmasi Password</label>
                <input v-model="resetForm.password_confirm" class="form-control" type="password" required />
              </div>
              <button class="btn btn-primary w-full" :disabled="resetLoading">
                <span v-if="resetLoading" class="spinner"></span>
                <span v-else>Simpan Password Baru</span>
              </button>
            </form>
          </div>
          <div class="modal-footer">
            <button class="btn btn-ghost" @click="resetModal.show = false">Batal</button>
          </div>
        </div>
      </div>
    </template>

    <!-- ══ LOG AKTIVITAS ══ -->
    <template v-if="stab === 'logs'">
      <div class="card">
        <div class="card-header">Log Aktivitas ({{ logs.total }} entri)</div>
        <div v-if="logsLoading" style="padding:2rem;text-align:center"><span class="spinner"></span></div>
        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Waktu</th>
                <th>Aktor</th>
                <th>Role</th>
                <th>Aksi</th>
                <th>ID Tagihan</th>
                <th>Sebelum</th>
                <th>Sesudah</th>
                <th>IP</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(log, i) in logs.items" :key="log.id">
                <td class="text-muted">{{ i + 1 }}</td>
                <td class="text-sm text-muted">{{ formatDate(log.timestamp) }}</td>
                <td>{{ log.actor }}</td>
                <td><span class="badge" :class="log.role === 'admin' ? 'badge-admin' : 'badge-user'">{{ log.role }}</span></td>
                <td><span class="badge badge-ok">{{ log.action }}</span></td>
                <td class="text-muted">{{ log.tagihan_id || '—' }}</td>
                <td style="max-width:160px"><pre class="log-json">{{ jsonFmt(log.before_data) }}</pre></td>
                <td style="max-width:160px"><pre class="log-json">{{ jsonFmt(log.after_data) }}</pre></td>
                <td class="text-muted text-sm">{{ log.ip }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>

  <ConfirmDialog
    :show="delUserModal.show"
    title="Hapus User"
    :message="`Hapus user '${delUserModal.user?.username}'?`"
    @confirm="doDeleteUser"
    @cancel="delUserModal.show = false"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import TopBar from '@/components/layout/TopBar.vue'
import FlashMessage from '@/components/common/FlashMessage.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import api from '@/api'

const flash = ref({ msg: '', type: 'success' })
const stab  = ref('users')

// ── User management ────────────────────────────────────────────────────────
const users        = ref([])
const usersLoading = ref(false)
const addOpen      = ref(true)
const addLoading   = ref(false)
const newUser      = ref({ username: '', password: '', password_confirm: '' })

async function loadUsers() {
  usersLoading.value = true
  try {
    const res = await api.get('/users')
    users.value = res.data.data
  } catch {}
  usersLoading.value = false
}

async function addUser() {
  addLoading.value = true
  try {
    await api.post('/users', newUser.value)
    flash.value = { msg: 'User berhasil ditambah', type: 'success' }
    newUser.value = { username: '', password: '', password_confirm: '' }
    loadUsers()
  } catch (e) {
    flash.value = { msg: e.response?.data?.message || 'Gagal', type: 'danger' }
  } finally {
    addLoading.value = false
  }
}

async function toggleUser(u) {
  try {
    const res = await api.patch(`/users/${u.id}/toggle`)
    u.is_active = res.data.data.is_active
    flash.value = { msg: res.data.message, type: 'success' }
  } catch (e) {
    flash.value = { msg: e.response?.data?.message || 'Gagal', type: 'danger' }
  }
}

const delUserModal = ref({ show: false, user: null })
function askDeleteUser(u) { delUserModal.value = { show: true, user: u } }
async function doDeleteUser() {
  try {
    await api.delete(`/users/${delUserModal.value.user.id}`)
    flash.value = { msg: 'User berhasil dihapus', type: 'success' }
    delUserModal.value.show = false
    loadUsers()
  } catch (e) {
    flash.value = { msg: e.response?.data?.message || 'Gagal', type: 'danger' }
    delUserModal.value.show = false
  }
}

const resetModal  = ref({ show: false, user: null })
const resetForm   = ref({ password: '', password_confirm: '' })
const resetLoading = ref(false)

function openReset(u) {
  resetModal.value = { show: true, user: u }
  resetForm.value  = { password: '', password_confirm: '' }
}

async function doReset() {
  resetLoading.value = true
  try {
    await api.patch(`/users/${resetModal.value.user.id}/password`, resetForm.value)
    flash.value = { msg: 'Password berhasil direset', type: 'success' }
    resetModal.value.show = false
  } catch (e) {
    flash.value = { msg: e.response?.data?.message || 'Gagal', type: 'danger' }
  } finally {
    resetLoading.value = false
  }
}

// ── Logs ───────────────────────────────────────────────────────────────────
const logs       = ref({ items: [], total: 0 })
const logsLoading = ref(false)

async function loadLogs() {
  logsLoading.value = true
  try {
    const res = await api.get('/logs')
    logs.value = res.data.data
  } catch {}
  logsLoading.value = false
}

// ── Helpers ────────────────────────────────────────────────────────────────
function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
}

function jsonFmt(v) {
  if (!v) return '—'
  return JSON.stringify(v, null, 2)
}

onMounted(() => loadUsers())
</script>

<style scoped>
.log-json {
  font-size: .72rem;
  color: var(--muted);
  white-space: pre-wrap;
  word-break: break-all;
  margin: 0;
  max-height: 80px;
  overflow: hidden;
}
</style>
